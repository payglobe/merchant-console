package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.ImportProgress;
import com.payglobe.merchant.entity.Store;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.StoreRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.util.*;
import java.util.concurrent.ConcurrentHashMap;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

/**
 * Service per gestione stores
 */
@Service
@Slf4j
@RequiredArgsConstructor
public class StoreService {

    private final StoreRepository storeRepository;

    // Map per tracciare import in corso
    private final ConcurrentHashMap<String, ImportProgress> importProgressMap = new ConcurrentHashMap<>();

    /**
     * Trova store per Terminal ID con autorizzazione BU
     */
    @Transactional(readOnly = true)
    public Optional<Store> findByTerminalId(String terminalId, User currentUser) {
        Optional<Store> store = storeRepository.findByTerminalId(terminalId);

        // Se non admin, verifica BU
        if (store.isPresent() && !currentUser.isAdmin()) {
            if (!store.get().getBu().equals(currentUser.getBu())) {
                log.warn("User {} tried to access store {} from different BU",
                         currentUser.getEmail(), terminalId);
                return Optional.empty();
            }
        }

        return store;
    }

    /**
     * Trova stores con filtri e autorizzazione BU
     */
    @Transactional(readOnly = true)
    public Page<Store> findStores(
            String citta,
            String prov,
            String country,
            String keyword,
            User currentUser,
            Pageable pageable) {

        log.debug("Finding stores: citta={}, prov={}, country={}, keyword={}, bu={}",
                  citta, prov, country, keyword, currentUser.getBu());

        if (currentUser.isAdmin()) {
            // Admin: ricerca globale
            if (keyword != null && !keyword.isBlank()) {
                return storeRepository.searchStores(keyword, pageable);
            }
            // TODO: implementare filtri multipli per admin
            return storeRepository.findAll(pageable);
        } else {
            // Utente normale: solo la propria BU
            if (citta != null || prov != null || country != null) {
                return storeRepository.findByBuWithFilters(
                    currentUser.getBu(), citta, prov, country, pageable);
            }
            return storeRepository.findByBu(currentUser.getBu(), pageable);
        }
    }

    /**
     * Trova stores per BU (per dropdown filtri)
     */
    @Transactional(readOnly = true)
    public List<Store> findByBu(String bu) {
        return storeRepository.findByBu(bu);
    }

    // ==================== IMPORT STORES ====================

    /**
     * Avvia import asincrono stores da file
     */
    public String startAsyncImport(byte[] fileContent, String fileName, boolean isZip) {
        String importId = UUID.randomUUID().toString();
        ImportProgress progress = new ImportProgress(importId, fileName);
        importProgressMap.put(importId, progress);

        log.info("Starting async store import: importId={}, file={}, isZip={}", importId, fileName, isZip);

        // Avvia elaborazione asincrona
        processImportAsync(importId, fileContent, isZip);

        return importId;
    }

    /**
     * Ottiene stato import
     */
    public Optional<ImportProgress> getImportProgress(String importId) {
        return Optional.ofNullable(importProgressMap.get(importId));
    }

    /**
     * Elaborazione asincrona import
     */
    @Async
    public void processImportAsync(String importId, byte[] fileContent, boolean isZip) {
        ImportProgress progress = importProgressMap.get(importId);
        if (progress == null) {
            log.error("Import progress not found for importId: {}", importId);
            return;
        }

        try {
            int imported;
            if (isZip) {
                imported = importFromZipWithProgress(fileContent, progress);
            } else {
                imported = importFromCsvWithProgress(new ByteArrayInputStream(fileContent), progress);
            }
            progress.markCompleted(imported);
            log.info("Store import completed: importId={}, imported={}", importId, imported);
        } catch (Exception e) {
            log.error("Store import failed: importId={}", importId, e);
            progress.markFailed(e.getMessage());
        }
    }

    /**
     * Import da ZIP contenente CSV
     */
    private int importFromZipWithProgress(byte[] zipContent, ImportProgress progress) throws IOException {
        try (ZipInputStream zipInputStream = new ZipInputStream(new ByteArrayInputStream(zipContent))) {
            ZipEntry entry;
            while ((entry = zipInputStream.getNextEntry()) != null) {
                if (entry.getName().toLowerCase().endsWith(".csv")) {
                    log.info("Found CSV in ZIP: {}", entry.getName());
                    // Leggi tutto il contenuto del CSV in memoria per evitare "Stream closed"
                    ByteArrayOutputStream baos = new ByteArrayOutputStream();
                    byte[] buffer = new byte[8192];
                    int len;
                    while ((len = zipInputStream.read(buffer)) > 0) {
                        baos.write(buffer, 0, len);
                    }
                    return importFromCsvWithProgress(new ByteArrayInputStream(baos.toByteArray()), progress);
                }
            }
        }
        throw new IllegalArgumentException("Nessun file CSV trovato nello ZIP");
    }

    /**
     * Import da CSV con tracking progresso
     *
     * Formato CSV (separatore ;):
     * TerminalID;Ragione_Sociale;Insegna;indirizzo;Citta;Cap;Prov;sia_pagobancomat;six;amex;Modello_pos;country;;bu;bu1;bu1
     */
    @Transactional
    public int importFromCsvWithProgress(InputStream inputStream, ImportProgress progress) throws IOException {
        List<Store> records = new ArrayList<>();
        int lineNumber = 0;
        int imported = 0;
        int skipped = 0;

        try (BufferedReader reader = new BufferedReader(
                new InputStreamReader(inputStream, StandardCharsets.UTF_8))) {

            String line;
            // Skip header
            String header = reader.readLine();
            lineNumber++;
            log.info("CSV header: {}", header);

            while ((line = reader.readLine()) != null) {
                lineNumber++;

                if (line.trim().isEmpty()) {
                    skipped++;
                    continue;
                }

                try {
                    Store store = parseCsvLine(line);
                    if (store != null && store.getTerminalId() != null && !store.getTerminalId().isBlank()) {
                        records.add(store);
                    } else {
                        skipped++;
                    }
                } catch (Exception e) {
                    log.warn("Error parsing line {}: {}", lineNumber, e.getMessage());
                    skipped++;
                }

                // Batch save ogni 1000 record
                if (records.size() >= 1000) {
                    storeRepository.saveAll(records);
                    imported += records.size();
                    records.clear();

                    // Aggiorna progresso (stima totale basata su linee lette)
                    progress.updateProgress(imported, lineNumber * 2);
                    log.debug("Store import progress: {} imported, {} skipped", imported, skipped);
                }
            }

            // Salva ultimi record
            if (!records.isEmpty()) {
                storeRepository.saveAll(records);
                imported += records.size();
            }

            progress.updateProgress(imported, imported);
            log.info("Store import finished: {} imported, {} skipped, {} total lines", imported, skipped, lineNumber);

            return imported;
        }
    }

    /**
     * Parse singola riga CSV
     *
     * Campi (indice):
     * 0: TerminalID
     * 1: Ragione_Sociale
     * 2: Insegna
     * 3: indirizzo
     * 4: Citta
     * 5: Cap
     * 6: Prov
     * 7: sia_pagobancomat
     * 8: six
     * 9: amex
     * 10: Modello_pos
     * 11: country
     * 12: (vuoto)
     * 13: bu
     * 14: bu1
     * 15: bu1 (duplicato, ignoriamo)
     */
    private Store parseCsvLine(String line) {
        String[] fields = line.split(";", -1);  // -1 per mantenere campi vuoti

        if (fields.length < 14) {
            log.warn("Line has {} fields, expected at least 14: {}", fields.length, line.substring(0, Math.min(50, line.length())));
            return null;
        }

        Store store = new Store();
        store.setTerminalId(cleanField(fields[0]));
        store.setRagioneSociale(cleanField(fields[1]));
        store.setInsegna(cleanField(fields[2]));
        store.setIndirizzo(cleanField(fields[3]));
        store.setCitta(cleanField(fields[4]));
        store.setCap(cleanField(fields[5]));
        store.setProv(cleanField(fields[6]));
        store.setSiaPagobancomat(cleanField(fields[7]));
        store.setSix(cleanField(fields[8]));
        store.setAmex(cleanField(fields[9]));
        store.setModelloPos(cleanField(fields[10]));
        store.setCountry(cleanField(fields[11]));
        // fields[12] è vuoto, lo saltiamo
        store.setBu(cleanField(fields[13]));

        if (fields.length > 14) {
            store.setBu1(cleanField(fields[14]));
        }
        if (fields.length > 15) {
            store.setBu2(cleanField(fields[15]));  // bu2 o secondo bu1
        }

        return store;
    }

    /**
     * Pulisce campo CSV (trim, null se vuoto)
     */
    private String cleanField(String field) {
        if (field == null) return null;
        String cleaned = field.trim();
        return cleaned.isEmpty() ? null : cleaned;
    }

    /**
     * Conta totale stores
     */
    public long countStores() {
        return storeRepository.count();
    }

}
