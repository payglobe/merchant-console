package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.response.CircuitDistributionResponse;
import com.payglobe.merchant.dto.response.DashboardStatsResponse;
import com.payglobe.merchant.dto.response.GeoDistributionResponse;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.dto.response.TransactionResponse;
import com.payglobe.merchant.entity.BinTable;
import com.payglobe.merchant.entity.Transaction;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.BinTableRepository;
import com.payglobe.merchant.repository.TransactionRepository;
import com.payglobe.merchant.util.CircuitCodeMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.time.LocalDateTime;
import java.util.*;
import java.util.concurrent.ConcurrentHashMap;

/**
 * Service per gestione transazioni
 */
@Service
@Slf4j
@RequiredArgsConstructor
public class TransactionService {

    private final TransactionRepository transactionRepository;
    private final BinTableRepository binTableRepository;
    private final JdbcTemplate jdbcTemplate;

    // Cache per lookup BIN -> country (evita query ripetute)
    private final Map<String, String> binCountryCache = new ConcurrentHashMap<>();

    // Cache per lookup POSID -> [ragioneSociale, insegna] (evita N+1 query)
    private final Map<String, String[]> storeCache = new ConcurrentHashMap<>();

    // Flag per warm-up cache eseguito
    private volatile boolean binCacheWarmedUp = false;
    private volatile boolean storeCacheWarmedUp = false;

    // Paesi UE (codici ISO 2 lettere)
    private static final Set<String> EU_COUNTRIES = Set.of(
        "AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR",
        "DE", "GR", "HU", "IE", "LV", "LT", "LU", "MT", "NL", "PL",
        "PT", "RO", "SK", "SI", "ES", "SE"
        // IT esclusa perché è Domestic
    );

    /**
     * Trova transazioni con filtri e autorizzazione BU
     */
    @Transactional(readOnly = true)
    public PagedResponse<TransactionResponse> findTransactions(
            LocalDateTime startDate,
            LocalDateTime endDate,
            String filterStore,
            User currentUser,
            Pageable pageable) {

        log.debug("Finding transactions: startDate={}, endDate={}, filterStore={}, bu={}",
                  startDate, endDate, filterStore, currentUser.getBu());

        Page<Transaction> transactionPage;

        if (currentUser.isAdmin()) {
            // Admin vede tutto
            if (filterStore != null && !filterStore.isBlank()) {
                transactionPage = transactionRepository.findByPosidAndDateRange(
                    parseFilterStore(filterStore), startDate, endDate, pageable);
            } else {
                transactionPage = transactionRepository.findByDateRange(
                    startDate, endDate, pageable);
            }
        } else {
            // Utente normale: filtra per BU (e opzionalmente per store)
            if (filterStore != null && !filterStore.isBlank()) {
                transactionPage = transactionRepository.findByBuAndPosidAndDateRange(
                    currentUser.getBu(), parseFilterStore(filterStore), startDate, endDate, pageable);
            } else {
                transactionPage = transactionRepository.findByBuAndDateRange(
                    currentUser.getBu(), startDate, endDate, pageable);
            }
        }

        List<TransactionResponse> content = transactionPage.getContent().stream()
            .map(this::mapToResponse)
            .toList();

        return PagedResponse.<TransactionResponse>builder()
            .content(content)
            .page(transactionPage.getNumber())
            .size(transactionPage.getSize())
            .totalElements(transactionPage.getTotalElements())
            .totalPages(transactionPage.getTotalPages())
            .first(transactionPage.isFirst())
            .last(transactionPage.isLast())
            .build();
    }

    /**
     * Calcola statistiche dashboard
     */
    @Transactional(readOnly = true)
    public DashboardStatsResponse getDashboardStats(
            LocalDateTime startDate,
            LocalDateTime endDate,
            String filterStore,
            User currentUser) {

        log.debug("Calculating dashboard stats: startDate={}, endDate={}, filterStore={}, bu={}",
                  startDate, endDate, filterStore, currentUser.getBu());

        Map<String, Object> stats;

        if (currentUser.isAdmin()) {
            if (filterStore != null && !filterStore.isBlank()) {
                stats = transactionRepository.calculateDashboardStatsByPosid(parseFilterStore(filterStore), startDate, endDate);
            } else {
                stats = transactionRepository.calculateDashboardStats(startDate, endDate);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                stats = transactionRepository.calculateDashboardStatsByBuAndPosid(
                    currentUser.getBu(), parseFilterStore(filterStore), startDate, endDate);
            } else {
                stats = transactionRepository.calculateDashboardStatsByBu(
                    currentUser.getBu(), startDate, endDate);
            }
        }

        return DashboardStatsResponse.builder()
            .total(getLong(stats, "total"))
            .volume(getBigDecimal(stats, "volume"))
            .settledCount(getLong(stats, "settled_count"))
            .notSettledCount(getLong(stats, "not_settled_count"))
            .build();
    }

    /**
     * Distribuzione circuiti per grafico
     */
    @Transactional(readOnly = true)
    public CircuitDistributionResponse getCircuitDistribution(
            LocalDateTime startDate,
            LocalDateTime endDate,
            String filterStore,
            User currentUser) {

        log.debug("Getting circuit distribution: startDate={}, endDate={}, filterStore={}, bu={}",
                  startDate, endDate, filterStore, currentUser.getBu());

        List<Object[]> results;

        if (currentUser.isAdmin()) {
            if (filterStore != null && !filterStore.isBlank()) {
                results = transactionRepository.getCircuitDistributionByPosid(parseFilterStore(filterStore), startDate, endDate);
            } else {
                results = transactionRepository.getCircuitDistribution(startDate, endDate);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                results = transactionRepository.getCircuitDistributionByBuAndPosid(
                    currentUser.getBu(), parseFilterStore(filterStore), startDate, endDate);
            } else {
                results = transactionRepository.getCircuitDistributionByBu(
                    currentUser.getBu(), startDate, endDate);
            }
        }

        // Raggruppa per circuit group
        Map<String, Long> circuitGroups = new HashMap<>();

        for (Object[] row : results) {
            String cardBrand = (String) row[0];
            Long count = ((Number) row[1]).longValue();

            String group = CircuitCodeMapper.getCircuitGroup(cardBrand);
            circuitGroups.merge(group, count, Long::sum);
        }

        return CircuitDistributionResponse.builder()
            .circuits(circuitGroups)
            .build();
    }

    /**
     * Trend giornaliero (per grafico)
     */
    @Transactional(readOnly = true)
    public List<Map<String, Object>> getDailyTrend(
            LocalDateTime startDate,
            LocalDateTime endDate,
            String filterStore,
            User currentUser) {

        log.debug("Getting daily trend: startDate={}, endDate={}, filterStore={}, bu={}",
                  startDate, endDate, filterStore, currentUser.getBu());

        List<Object[]> results;

        if (currentUser.isAdmin()) {
            if (filterStore != null && !filterStore.isBlank()) {
                results = transactionRepository.getDailyTrendByPosid(parseFilterStore(filterStore), startDate, endDate);
            } else {
                results = transactionRepository.getDailyTrend(startDate, endDate);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                results = transactionRepository.getDailyTrendByBuAndPosid(
                    currentUser.getBu(), parseFilterStore(filterStore), startDate, endDate);
            } else {
                results = transactionRepository.getDailyTrendByBu(
                    currentUser.getBu(), startDate, endDate);
            }
        }

        return results.stream()
            .map(row -> {
                Map<String, Object> dayData = new HashMap<>();
                dayData.put("date", row[0].toString());     // Cambiato da "day"
                dayData.put("count", ((Number) row[1]).longValue());    // Cambiato da "daily_count"
                dayData.put("amount", ((Number) row[2]).doubleValue()); // Cambiato da "daily_volume"
                return dayData;
            })
            .toList();
    }

    /**
     * Distribuzione geografica transazioni (Domestic/UE/Extra UE)
     */
    @Transactional(readOnly = true)
    public GeoDistributionResponse getGeoDistribution(
            LocalDateTime startDate,
            LocalDateTime endDate,
            String filterStore,
            List<String> excludeStores,
            User currentUser) {

        log.info("Getting geo distribution: startDate={}, endDate={}, filterStore={}, excludeStores={}, bu={}",
                  startDate, endDate, filterStore, excludeStores != null ? excludeStores.size() : 0, currentUser.getBu());

        // Recupera transazioni (limitato a 50000 per performance)
        List<Transaction> transactions;

        if (currentUser.isAdmin()) {
            if (filterStore != null && !filterStore.isBlank()) {
                transactions = transactionRepository.findByPosidAndDateRangeList(
                    parseFilterStore(filterStore), startDate, endDate, 50000);
            } else {
                transactions = transactionRepository.findByDateRangeList(
                    startDate, endDate, 50000);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                transactions = transactionRepository.findByBuAndPosidAndDateRangeList(
                    currentUser.getBu(), parseFilterStore(filterStore), startDate, endDate, 50000);
            } else {
                transactions = transactionRepository.findByBuAndDateRangeList(
                    currentUser.getBu(), startDate, endDate, 50000);
            }
        }

        log.info("Loaded {} transactions for geo distribution", transactions.size());

        // APPLICA ESCLUSIONI (se presenti)
        if (excludeStores != null && !excludeStores.isEmpty()) {
            log.info("Applying exclusions for {} stores: {}", excludeStores.size(), excludeStores);

            // Trova i POSID da escludere basandosi sulle ragioni sociali
            Set<String> excludedPosids = new HashSet<>();
            for (String ragioneSociale : excludeStores) {
                // Query per trovare i POSID associati a questa ragione sociale
                String sql = "SELECT DISTINCT TerminalID FROM stores WHERE Ragione_Sociale = ? OR Insegna = ?";
                List<String> posids = jdbcTemplate.queryForList(sql, String.class, ragioneSociale, ragioneSociale);
                log.info("  Store '{}' -> {} POSIDs found", ragioneSociale, posids.size());
                excludedPosids.addAll(posids);
            }

            if (!excludedPosids.isEmpty()) {
                int beforeCount = transactions.size();
                transactions = transactions.stream()
                    .filter(tx -> !excludedPosids.contains(tx.getPosid()))
                    .collect(java.util.stream.Collectors.toList());
                log.info("Excluded {} transactions ({} stores, {} POSIDs excluded). Remaining: {}",
                         beforeCount - transactions.size(), excludeStores.size(), excludedPosids.size(), transactions.size());
            } else {
                log.warn("No POSIDs found for excluded stores - no transactions filtered!");
            }
        }

        log.info("Processing {} transactions for geo distribution", transactions.size());

        // WARM-UP: Carica cache in memoria con UNA SOLA QUERY
        // Prima chiamata: ~2-3 secondi, dopo: istantaneo (cache in memoria)
        warmupBinCache();
        warmupStoreCache();

        // Contatori per area geografica
        long domesticCount = 0, euCount = 0, extraEuCount = 0, unknownCount = 0;
        BigDecimal domesticAmount = BigDecimal.ZERO;
        BigDecimal euAmount = BigDecimal.ZERO;
        BigDecimal extraEuAmount = BigDecimal.ZERO;
        BigDecimal unknownAmount = BigDecimal.ZERO;

        // Dettaglio per circuito
        Map<String, long[]> domesticByCircuit = new HashMap<>();
        Map<String, long[]> euByCircuit = new HashMap<>();
        Map<String, long[]> extraEuByCircuit = new HashMap<>();
        Map<String, long[]> unknownByCircuit = new HashMap<>();

        Map<String, BigDecimal[]> domesticAmountByCircuit = new HashMap<>();
        Map<String, BigDecimal[]> euAmountByCircuit = new HashMap<>();
        Map<String, BigDecimal[]> extraEuAmountByCircuit = new HashMap<>();
        Map<String, BigDecimal[]> unknownAmountByCircuit = new HashMap<>();

        // TOP 5 NEGOZI PER AREA GEOGRAFICA (solo admin)
        // Key: ragioneSociale, Value: [count, amount, insegna]
        Map<String, Object[]> domesticByStore = new HashMap<>();
        Map<String, Object[]> euByStore = new HashMap<>();
        Map<String, Object[]> extraEuByStore = new HashMap<>();

        for (Transaction tx : transactions) {
            String pan = tx.getPan();
            BigDecimal amount = tx.getAmount() != null ? tx.getAmount() : BigDecimal.ZERO;
            String circuit = CircuitCodeMapper.getCircuitGroup(tx.getCardBrand());
            // Usa cache invece di lazy loading (evita N+1 query!)
            String[] storeInfo = storeCache.get(tx.getPosid());
            String ragioneSociale = storeInfo != null ? storeInfo[0] : "Sconosciuto";
            String insegna = storeInfo != null ? storeInfo[1] : "";

            // PagoBancomat è sempre italiano (non ha BIN internazionale)
            String geoArea;
            if ("PagoBancomat".equalsIgnoreCase(circuit) || "PAGOBANCOMAT".equals(tx.getCardBrand())) {
                geoArea = "DOMESTIC";
            } else {
                // Lookup country code dal BIN per altri circuiti
                String countryCode = getCountryCodeFromPan(pan);
                geoArea = classifyGeoArea(countryCode);
            }

            switch (geoArea) {
                case "DOMESTIC":
                    domesticCount++;
                    domesticAmount = domesticAmount.add(amount);
                    incrementCircuitStats(domesticByCircuit, domesticAmountByCircuit, circuit, amount);
                    incrementStoreStats(domesticByStore, ragioneSociale, insegna, amount);
                    break;
                case "EU":
                    euCount++;
                    euAmount = euAmount.add(amount);
                    incrementCircuitStats(euByCircuit, euAmountByCircuit, circuit, amount);
                    incrementStoreStats(euByStore, ragioneSociale, insegna, amount);
                    break;
                case "EXTRA_EU":
                    extraEuCount++;
                    extraEuAmount = extraEuAmount.add(amount);
                    incrementCircuitStats(extraEuByCircuit, extraEuAmountByCircuit, circuit, amount);
                    incrementStoreStats(extraEuByStore, ragioneSociale, insegna, amount);
                    break;
                default:
                    unknownCount++;
                    unknownAmount = unknownAmount.add(amount);
                    incrementCircuitStats(unknownByCircuit, unknownAmountByCircuit, circuit, amount);
            }
        }

        long totalCount = domesticCount + euCount + extraEuCount + unknownCount;
        BigDecimal totalAmount = domesticAmount.add(euAmount).add(extraEuAmount).add(unknownAmount);

        log.info("Geo distribution results: domestic={}, eu={}, extraEu={}, unknown={}",
                 domesticCount, euCount, extraEuCount, unknownCount);

        // Build top 5 stores per geo area (solo se admin)
        List<GeoDistributionResponse.StoreGeoStats> topDomestic = currentUser.isAdmin()
            ? buildTopStores(domesticByStore, domesticCount, 5) : null;
        List<GeoDistributionResponse.StoreGeoStats> topEu = currentUser.isAdmin()
            ? buildTopStores(euByStore, euCount, 5) : null;
        List<GeoDistributionResponse.StoreGeoStats> topExtraEu = currentUser.isAdmin()
            ? buildTopStores(extraEuByStore, extraEuCount, 5) : null;

        return GeoDistributionResponse.builder()
            .domestic(buildGeoArea("Italia (Domestic)", domesticCount, domesticAmount, totalCount, totalAmount,
                                   domesticByCircuit, domesticAmountByCircuit))
            .eu(buildGeoArea("Unione Europea", euCount, euAmount, totalCount, totalAmount,
                             euByCircuit, euAmountByCircuit))
            .extraEu(buildGeoArea("Extra UE", extraEuCount, extraEuAmount, totalCount, totalAmount,
                                  extraEuByCircuit, extraEuAmountByCircuit))
            .unknown(buildGeoArea("Non identificato", unknownCount, unknownAmount, totalCount, totalAmount,
                                  unknownByCircuit, unknownAmountByCircuit))
            .totalTransactions(totalCount)
            .totalAmount(totalAmount)
            .topDomesticStores(topDomestic)
            .topEuStores(topEu)
            .topExtraEuStores(topExtraEu)
            .build();
    }

    /**
     * Warm-up: carica TUTTI i BIN->country in memoria con UNA SOLA QUERY
     * Usa ~50-100MB di RAM ma elimina migliaia di query
     */
    private synchronized void warmupBinCache() {
        if (binCacheWarmedUp) {
            return;
        }

        try {
            log.info("BIN WARM-UP: Loading entire BIN->country mapping into memory...");
            long start = System.currentTimeMillis();

            List<Object[]> pairs = binTableRepository.findAllBinCountryPairs();

            for (Object[] pair : pairs) {
                String bin6 = (String) pair[0];
                String country = (String) pair[1];
                if (bin6 != null && country != null) {
                    binCountryCache.put(bin6, country);
                }
            }

            long elapsed = System.currentTimeMillis() - start;
            binCacheWarmedUp = true;
            log.info("BIN WARM-UP COMPLETE: Loaded {} BIN->country mappings in {}ms",
                     binCountryCache.size(), elapsed);
        } catch (Exception e) {
            log.error("Failed to warm up BIN cache: {}", e.getMessage());
        }
    }

    /**
     * Warm-up: carica TUTTI gli stores in memoria con UNA SOLA QUERY
     * Evita N+1 query quando accedo a ragioneSociale/insegna
     */
    private synchronized void warmupStoreCache() {
        if (storeCacheWarmedUp) {
            return;
        }

        try {
            log.info("STORE WARM-UP: Loading all stores into memory...");
            long start = System.currentTimeMillis();

            String sql = "SELECT TerminalID, Ragione_Sociale, Insegna FROM stores";
            List<Map<String, Object>> rows = jdbcTemplate.queryForList(sql);

            for (Map<String, Object> row : rows) {
                String posid = (String) row.get("TerminalID");
                String ragione = (String) row.get("Ragione_Sociale");
                String insegna = (String) row.get("Insegna");
                if (posid != null) {
                    storeCache.put(posid, new String[]{
                        ragione != null ? ragione : "Sconosciuto",
                        insegna != null ? insegna : ""
                    });
                }
            }

            long elapsed = System.currentTimeMillis() - start;
            storeCacheWarmedUp = true;
            log.info("STORE WARM-UP COMPLETE: Loaded {} stores in {}ms",
                     storeCache.size(), elapsed);
        } catch (Exception e) {
            log.error("Failed to warm up store cache: {}", e.getMessage());
        }
    }

    /**
     * Ottiene country code dal PAN usando la cache (già warm-up)
     */
    private String getCountryCodeFromPan(String pan) {
        if (pan == null || pan.length() < 6) {
            return null;
        }

        // Extract first 6 digits (visible part of masked PAN like "535574xxxx")
        String bin6 = pan.substring(0, 6);

        // Check cache - use special marker for "not found" since ConcurrentHashMap doesn't allow null values
        if (binCountryCache.containsKey(bin6)) {
            String cached = binCountryCache.get(bin6);
            return "NOT_FOUND".equals(cached) ? null : cached;
        }

        // Fallback: query singola se non in cache (raro dopo warm-up)
        try {
            Optional<BinTable> binRecord = binTableRepository.findByBinPrefix(bin6);
            String countryCode = binRecord.map(BinTable::getCountryCode).orElse(null);
            binCountryCache.put(bin6, countryCode != null ? countryCode : "NOT_FOUND");
            return countryCode;
        } catch (Exception e) {
            log.debug("Error looking up BIN {}: {}", bin6, e.getMessage());
            binCountryCache.put(bin6, "NOT_FOUND");
            return null;
        }
    }

    /**
     * Classifica area geografica dal country code
     */
    private String classifyGeoArea(String countryCode) {
        if (countryCode == null || countryCode.isBlank()) {
            return "UNKNOWN";
        }

        String cc = countryCode.toUpperCase().trim();

        if ("IT".equals(cc) || "ITA".equals(cc)) {
            return "DOMESTIC";
        }

        if (EU_COUNTRIES.contains(cc)) {
            return "EU";
        }

        return "EXTRA_EU";
    }

    private void incrementCircuitStats(Map<String, long[]> countMap, Map<String, BigDecimal[]> amountMap,
                                       String circuit, BigDecimal amount) {
        countMap.computeIfAbsent(circuit, k -> new long[]{0})[0]++;
        amountMap.computeIfAbsent(circuit, k -> new BigDecimal[]{BigDecimal.ZERO})[0] =
            amountMap.get(circuit)[0].add(amount);
    }

    /**
     * Incrementa statistiche per negozio (per top stores)
     * Value: Object[] = [Long count, BigDecimal amount, String insegna]
     */
    private void incrementStoreStats(Map<String, Object[]> storeMap, String ragioneSociale, String insegna, BigDecimal amount) {
        storeMap.compute(ragioneSociale, (k, v) -> {
            if (v == null) {
                return new Object[]{1L, amount, insegna};
            }
            v[0] = (Long) v[0] + 1;
            v[1] = ((BigDecimal) v[1]).add(amount);
            return v;
        });
    }

    /**
     * Costruisce lista top N negozi ordinati per count decrescente
     */
    private List<GeoDistributionResponse.StoreGeoStats> buildTopStores(Map<String, Object[]> storeMap, long totalCount, int limit) {
        return storeMap.entrySet().stream()
            .sorted((a, b) -> Long.compare((Long) b.getValue()[0], (Long) a.getValue()[0])) // DESC by count
            .limit(limit)
            .map(e -> {
                long count = (Long) e.getValue()[0];
                BigDecimal amount = (BigDecimal) e.getValue()[1];
                String insegna = (String) e.getValue()[2];
                double pct = totalCount > 0 ? (count * 100.0 / totalCount) : 0;

                return GeoDistributionResponse.StoreGeoStats.builder()
                    .ragioneSociale(e.getKey())
                    .insegna(insegna)
                    .count(count)
                    .amount(amount)
                    .percentage(Math.round(pct * 100.0) / 100.0) // 2 decimali
                    .build();
            })
            .collect(java.util.stream.Collectors.toList());
    }

    private GeoDistributionResponse.GeoArea buildGeoArea(String name, long count, BigDecimal amount,
                                                          long totalCount, BigDecimal totalAmount,
                                                          Map<String, long[]> byCircuitCount,
                                                          Map<String, BigDecimal[]> byCircuitAmount) {
        double pctCount = totalCount > 0 ? (count * 100.0 / totalCount) : 0;
        double pctAmount = totalAmount.compareTo(BigDecimal.ZERO) > 0
            ? amount.multiply(BigDecimal.valueOf(100)).divide(totalAmount, 2, RoundingMode.HALF_UP).doubleValue()
            : 0;

        List<GeoDistributionResponse.CircuitDetail> circuits = new ArrayList<>();
        for (String circuit : byCircuitCount.keySet()) {
            circuits.add(GeoDistributionResponse.CircuitDetail.builder()
                .circuit(circuit)
                .count(byCircuitCount.get(circuit)[0])
                .amount(byCircuitAmount.getOrDefault(circuit, new BigDecimal[]{BigDecimal.ZERO})[0])
                .build());
        }

        // Ordina per count desc
        circuits.sort((a, b) -> Long.compare(b.getCount(), a.getCount()));

        return GeoDistributionResponse.GeoArea.builder()
            .name(name)
            .count(count)
            .amount(amount)
            .percentageCount(Math.round(pctCount * 100.0) / 100.0)
            .percentageAmount(Math.round(pctAmount * 100.0) / 100.0)
            .byCircuit(circuits)
            .build();
    }

    // ========== Helper methods ==========

    private TransactionResponse mapToResponse(Transaction t) {
        return TransactionResponse.builder()
            .id(t.getId())
            .posid(t.getPosid())
            .transactionDate(t.getTransactionDate())
            .transactionType(t.getTransactionType())
            .amount(t.getAmount())
            .pan(t.getPan())
            .cardBrand(t.getCardBrand())
            .settlementFlag(t.getSettlementFlag())
            .responseCode(t.getResponseCode())
            .authorizationCode(t.getApprovalCode())
            .rrn(t.getRrn())
            .storeInsegna(t.getStore() != null ? t.getStore().getInsegna() : null)
            .storeRagioneSociale(t.getStore() != null ? t.getStore().getRagioneSociale() : null)
            .storeCitta(t.getStore() != null ? t.getStore().getCitta() : null)
            .isSettled(t.isSettled())
            .isRefund(t.isRefund())
            .signedAmount(t.getSignedAmount())
            .build();
    }

    private Long getLong(Map<String, Object> map, String key) {
        Object value = map.get(key);
        if (value == null) return 0L;
        if (value instanceof Number) {
            return ((Number) value).longValue();
        }
        return 0L;
    }

    private BigDecimal getBigDecimal(Map<String, Object> map, String key) {
        Object value = map.get(key);
        if (value == null) return BigDecimal.ZERO;
        if (value instanceof BigDecimal) {
            return (BigDecimal) value;
        }
        if (value instanceof Number) {
            return BigDecimal.valueOf(((Number) value).doubleValue());
        }
        return BigDecimal.ZERO;
    }

    /**
     * Converte filterStore da String "pos1,pos2,pos3" a List<String>
     * Per usare IN clause invece di FIND_IN_SET (performance)
     */
    private List<String> parseFilterStore(String filterStore) {
        if (filterStore == null || filterStore.isBlank()) {
            return List.of();
        }
        return Arrays.stream(filterStore.split(","))
            .map(String::trim)
            .filter(s -> !s.isEmpty())
            .collect(java.util.stream.Collectors.toList());
    }

}
