package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.response.CircuitDistributionResponse;
import com.payglobe.merchant.dto.response.DashboardStatsResponse;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.dto.response.TransactionResponse;
import com.payglobe.merchant.entity.Transaction;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.TransactionRepository;
import com.payglobe.merchant.util.CircuitCodeMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Service per gestione transazioni
 */
@Service
@Slf4j
@RequiredArgsConstructor
public class TransactionService {

    private final TransactionRepository transactionRepository;

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
                    filterStore, startDate, endDate, pageable);
            } else {
                transactionPage = transactionRepository.findByDateRange(
                    startDate, endDate, pageable);
            }
        } else {
            // Utente normale: filtra per BU (e opzionalmente per store)
            if (filterStore != null && !filterStore.isBlank()) {
                transactionPage = transactionRepository.findByBuAndPosidAndDateRange(
                    currentUser.getBu(), filterStore, startDate, endDate, pageable);
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
                stats = transactionRepository.calculateDashboardStatsByPosid(filterStore, startDate, endDate);
            } else {
                stats = transactionRepository.calculateDashboardStats(startDate, endDate);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                stats = transactionRepository.calculateDashboardStatsByBuAndPosid(
                    currentUser.getBu(), filterStore, startDate, endDate);
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
                results = transactionRepository.getCircuitDistributionByPosid(filterStore, startDate, endDate);
            } else {
                results = transactionRepository.getCircuitDistribution(startDate, endDate);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                results = transactionRepository.getCircuitDistributionByBuAndPosid(
                    currentUser.getBu(), filterStore, startDate, endDate);
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
                results = transactionRepository.getDailyTrendByPosid(filterStore, startDate, endDate);
            } else {
                results = transactionRepository.getDailyTrend(startDate, endDate);
            }
        } else {
            if (filterStore != null && !filterStore.isBlank()) {
                results = transactionRepository.getDailyTrendByBuAndPosid(
                    currentUser.getBu(), filterStore, startDate, endDate);
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

}
