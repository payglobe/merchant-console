package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.ImportProgress;
import com.payglobe.merchant.entity.BinTable;
import com.payglobe.merchant.repository.BinTableRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.InputStream;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.concurrent.ConcurrentHashMap;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

@Service
@Slf4j
@RequiredArgsConstructor
public class BinTableService {

    private final BinTableRepository binTableRepository;
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.ofPattern("yyyyMMdd");

    // Mappa per tracciare import in corso
    private final Map<String, ImportProgress> importProgressMap = new ConcurrentHashMap<>();

    /**
     * Trova informazioni banca da BIN (prime 6-9 cifre del PAN)
     *
     * @param pan PAN completo o parziale (prende le prime 6-16 cifre)
     * @return BinTable con info banca, oppure empty
     */
    public Optional<BinTable> findBankByPan(String pan) {
        if (pan == null || pan.length() < 6) {
            return Optional.empty();
        }

        // Sostituisci caratteri mascherati ('x', 'X', '*') con '0' per il matching
        String cleanPan = pan.toLowerCase().replace('x', '0').replace('*', '0');

        // Prova con diverse lunghezze BIN
        // Prima prova con il PAN completo (padding con zeri fino a 16-19 cifre)
        // Poi prova con lunghezze decrescenti (16, 15, 14, ..., 6 cifre)
        for (int len = Math.min(cleanPan.length(), 19); len >= 6; len--) {
            try {
                String binStr = cleanPan.substring(0, Math.min(len, cleanPan.length()));

                // Aggiungi padding di zeri se necessario per raggiungere lunghezza desiderata
                while (binStr.length() < len && binStr.length() < 19) {
                    binStr += "0";
                }

                Long bin = Long.parseLong(binStr);

                Optional<BinTable> result = binTableRepository.findByBin(bin);
                if (result.isPresent()) {
                    log.debug("Found BIN match for pan={} with bin={}", pan, bin);
                    return result;
                }
            } catch (NumberFormatException e) {
                log.debug("NumberFormatException for len={}, continuing...", len);
                // Continua con lunghezza inferiore
            }
        }

        log.warn("No BIN match found for pan={}", pan);
        return Optional.empty();
    }

    /**
     * Ottieni nome banca da PAN (con fallback)
     */
    public String getBankName(String pan) {
        return findBankByPan(pan)
            .map(BinTable::getBankName)
            .orElse("Banca sconosciuta");
    }

    /**
     * Ottieni paese da PAN (con fallback)
     */
    public String getCountryName(String pan) {
        return findBankByPan(pan)
            .map(BinTable::getCountryName)
            .orElse("Sconosciuto");
    }

    /**
     * Importa dati da ZIP contenente CSV
     *
     * @param inputStream InputStream del file ZIP
     * @return Numero di record importati
     */
    @Transactional
    public int importFromZip(InputStream inputStream) {
        log.info("Starting BIN table import from ZIP");

        try (ZipInputStream zipInputStream = new ZipInputStream(inputStream)) {
            ZipEntry entry;
            while ((entry = zipInputStream.getNextEntry()) != null) {
                String fileName = entry.getName().toLowerCase();

                // Cerca file CSV dentro lo ZIP
                if (fileName.endsWith(".csv") && !entry.isDirectory()) {
                    log.info("Found CSV file in ZIP: {}", entry.getName());

                    // Importa direttamente dallo stream (NON chiudere zipInputStream)
                    int imported = importFromCsvStream(zipInputStream);

                    // Non chiamare closeEntry() qui - lo stream è già chiuso da importFromCsvStream
                    log.info("Import from ZIP completed successfully");
                    return imported;
                }
                zipInputStream.closeEntry();
            }

            throw new RuntimeException("No CSV file found in ZIP archive");

        } catch (Exception e) {
            log.error("Error importing BIN table from ZIP: {}", e.getMessage(), e);
            throw new RuntimeException("Failed to import BIN table from ZIP: " + e.getMessage(), e);
        }
    }

    /**
     * Importa dati da CSV usando InputStream (per file upload diretto o ZIP)
     *
     * Formato CSV (separatore: ;):
     * Run Date;Start BIN Value;End BIN Value;BIN Length;BIN Country;BIN Country Description;
     * Country Code;Card Brand Description;Service Type Description;Card Organisation Description;
     * Card Product;Issuer Name;Tipo Carta;Paese;Transcodifica
     *
     * @param inputStream InputStream del file CSV
     * @return Numero di record importati
     */
    @Transactional
    public int importFromCsv(InputStream inputStream) {
        return importFromCsvStream(inputStream);
    }

    /**
     * Helper method per importare da CSV stream (condiviso tra importFromCsv e importFromZip)
     */
    private int importFromCsvStream(InputStream inputStream) {
        log.info("Starting BIN table import from InputStream");

        List<BinTable> records = new ArrayList<>();
        int lineNumber = 0;
        int imported = 0;

        try (BufferedReader reader = new BufferedReader(new java.io.InputStreamReader(inputStream, java.nio.charset.StandardCharsets.UTF_8))) {
            String line;

            // Salta header
            reader.readLine();
            lineNumber++;

            while ((line = reader.readLine()) != null) {
                lineNumber++;

                try {
                    String[] fields = line.split(";", -1);  // -1 per mantenere campi vuoti

                    if (fields.length < 15) {
                        log.warn("Line {}: Invalid number of fields ({}), skipping", lineNumber, fields.length);
                        continue;
                    }

                    BinTable binTable = new BinTable();

                    // Parse campi
                    binTable.setRunDate(parseDate(fields[0]));
                    binTable.setStartBin(parseLong(fields[1]));
                    binTable.setEndBin(parseLong(fields[2]));
                    binTable.setBinLength(parseInt(fields[3]));
                    binTable.setBinCountry(parseInt(fields[4]));
                    binTable.setBinCountryDescription(emptyToNull(fields[5]));
                    binTable.setCountryCode(emptyToNull(fields[6]));
                    binTable.setCardBrandDescription(emptyToNull(fields[7]));
                    binTable.setServiceTypeDescription(emptyToNull(fields[8]));
                    binTable.setCardOrganisationDescription(emptyToNull(fields[9]));
                    binTable.setCardProduct(emptyToNull(fields[10]));
                    binTable.setIssuerName(emptyToNull(fields[11]));
                    binTable.setTipoCarta(emptyToNull(fields[12]));
                    binTable.setPaese(emptyToNull(fields[13]));
                    binTable.setTranscodifica(emptyToNull(fields[14]));

                    records.add(binTable);

                    // Batch insert ogni 1000 record
                    if (records.size() >= 1000) {
                        binTableRepository.saveAll(records);
                        imported += records.size();
                        records.clear();
                        log.info("Imported {} records so far...", imported);
                    }

                } catch (Exception e) {
                    log.error("Error parsing line {}: {}", lineNumber, e.getMessage());
                }
            }

            // Salva ultimi record
            if (!records.isEmpty()) {
                binTableRepository.saveAll(records);
                imported += records.size();
            }

            log.info("BIN table import completed: {} records imported", imported);
            return imported;

        } catch (Exception e) {
            log.error("Error importing BIN table from CSV: {}", e.getMessage(), e);
            throw new RuntimeException("Failed to import BIN table: " + e.getMessage(), e);
        }
    }

    /**
     * Importa dati da CSV (formato BT_YYYYMMDD_2.csv) - da file path
     *
     * @param csvFilePath Path al file CSV
     * @return Numero di record importati
     */
    @Transactional
    public int importFromCsvFile(String csvFilePath) {
        log.info("Starting BIN table import from file: {}", csvFilePath);

        try (java.io.FileInputStream fis = new java.io.FileInputStream(csvFilePath)) {
            return importFromCsv(fis);
        } catch (Exception e) {
            log.error("Error reading CSV file: {}", e.getMessage(), e);
            throw new RuntimeException("Failed to read CSV file: " + e.getMessage(), e);
        }
    }

    /**
     * DEPRECATED - usa importFromCsvFile() invece
     */
    @Deprecated
    @Transactional
    public int importFromCsvOld(String csvFilePath) {
        log.info("Starting BIN table import from: {}", csvFilePath);

        List<BinTable> records = new ArrayList<>();
        int lineNumber = 0;
        int imported = 0;

        try (BufferedReader reader = new BufferedReader(new FileReader(csvFilePath))) {
            String line;

            // Salta header
            reader.readLine();
            lineNumber++;

            while ((line = reader.readLine()) != null) {
                lineNumber++;

                try {
                    String[] fields = line.split(";", -1);  // -1 per mantenere campi vuoti

                    if (fields.length < 15) {
                        log.warn("Line {}: Invalid number of fields ({}), skipping", lineNumber, fields.length);
                        continue;
                    }

                    BinTable binTable = new BinTable();

                    // Parse campi
                    binTable.setRunDate(parseDate(fields[0]));
                    binTable.setStartBin(parseLong(fields[1]));
                    binTable.setEndBin(parseLong(fields[2]));
                    binTable.setBinLength(parseInt(fields[3]));
                    binTable.setBinCountry(parseInt(fields[4]));
                    binTable.setBinCountryDescription(emptyToNull(fields[5]));
                    binTable.setCountryCode(emptyToNull(fields[6]));
                    binTable.setCardBrandDescription(emptyToNull(fields[7]));
                    binTable.setServiceTypeDescription(emptyToNull(fields[8]));
                    binTable.setCardOrganisationDescription(emptyToNull(fields[9]));
                    binTable.setCardProduct(emptyToNull(fields[10]));
                    binTable.setIssuerName(emptyToNull(fields[11]));
                    binTable.setTipoCarta(emptyToNull(fields[12]));
                    binTable.setPaese(emptyToNull(fields[13]));
                    binTable.setTranscodifica(emptyToNull(fields[14]));

                    records.add(binTable);

                    // Batch insert ogni 1000 record
                    if (records.size() >= 1000) {
                        binTableRepository.saveAll(records);
                        imported += records.size();
                        records.clear();
                        log.info("Imported {} records so far...", imported);
                    }

                } catch (Exception e) {
                    log.error("Error parsing line {}: {}", lineNumber, e.getMessage());
                }
            }

            // Salva ultimi record
            if (!records.isEmpty()) {
                binTableRepository.saveAll(records);
                imported += records.size();
            }

            log.info("BIN table import completed: {} records imported", imported);
            return imported;

        } catch (Exception e) {
            log.error("Error importing BIN table from CSV: {}", e.getMessage(), e);
            throw new RuntimeException("Failed to import BIN table: " + e.getMessage(), e);
        }
    }

    /**
     * Elimina tutti i dati BIN (per re-import)
     */
    @Transactional
    public void clearAll() {
        long count = binTableRepository.count();
        binTableRepository.deleteAll();
        log.info("Deleted {} BIN records", count);
    }

    /**
     * Verifica se la tabella ha dati
     */
    public boolean hasData() {
        return binTableRepository.hasData();
    }

    // ========== Import Asincrono con Progress ==========

    /**
     * Avvia import asincrono da ZIP/CSV
     *
     * @param inputStream InputStream del file
     * @param fileName Nome del file
     * @param isZip Se è un file ZIP
     * @return Import ID per tracking
     */
    public String startAsyncImport(InputStream inputStream, String fileName, boolean isZip) {
        String importId = UUID.randomUUID().toString();
        ImportProgress progress = new ImportProgress(importId, fileName);
        importProgressMap.put(importId, progress);

        log.info("Starting async import with ID: {}", importId);

        // Avvia import in thread separato
        processImportAsync(importId, inputStream, isZip);

        return importId;
    }

    /**
     * Ottieni stato import
     */
    public Optional<ImportProgress> getImportProgress(String importId) {
        return Optional.ofNullable(importProgressMap.get(importId));
    }

    /**
     * Processa import in modo asincrono
     */
    @Async
    @Transactional
    public void processImportAsync(String importId, InputStream inputStream, boolean isZip) {
        ImportProgress progress = importProgressMap.get(importId);

        if (progress == null) {
            log.error("Import progress not found for ID: {}", importId);
            return;
        }

        try {
            int imported;

            if (isZip) {
                imported = importFromZipWithProgress(inputStream, progress);
            } else {
                imported = importFromCsvWithProgress(inputStream, progress);
            }

            progress.markCompleted(imported);
            log.info("Async import {} completed: {} records", importId, imported);

        } catch (Exception e) {
            log.error("Async import {} failed: {}", importId, e.getMessage(), e);
            progress.markFailed(e.getMessage());
        }
    }

    /**
     * Import ZIP con progress tracking
     */
    private int importFromZipWithProgress(InputStream inputStream, ImportProgress progress) {
        try (ZipInputStream zipInputStream = new ZipInputStream(inputStream)) {
            ZipEntry entry;
            while ((entry = zipInputStream.getNextEntry()) != null) {
                String fileName = entry.getName().toLowerCase();

                if (fileName.endsWith(".csv") && !entry.isDirectory()) {
                    log.info("Found CSV file in ZIP: {}", entry.getName());
                    int imported = importFromCsvStreamWithProgress(zipInputStream, progress);
                    log.info("Import from ZIP completed successfully");
                    return imported;
                }
                zipInputStream.closeEntry();
            }

            throw new RuntimeException("No CSV file found in ZIP archive");

        } catch (Exception e) {
            log.error("Error importing from ZIP: {}", e.getMessage(), e);
            throw new RuntimeException("Failed to import from ZIP: " + e.getMessage(), e);
        }
    }

    /**
     * Import CSV con progress tracking
     */
    private int importFromCsvWithProgress(InputStream inputStream, ImportProgress progress) {
        return importFromCsvStreamWithProgress(inputStream, progress);
    }

    /**
     * Import CSV stream con progress tracking
     */
    private int importFromCsvStreamWithProgress(InputStream inputStream, ImportProgress progress) {
        log.info("Starting CSV import with progress tracking");

        List<BinTable> records = new ArrayList<>();
        int lineNumber = 0;
        int imported = 0;

        try (BufferedReader reader = new BufferedReader(new java.io.InputStreamReader(inputStream, java.nio.charset.StandardCharsets.UTF_8))) {
            String line;

            // Salta header
            reader.readLine();
            lineNumber++;

            while ((line = reader.readLine()) != null) {
                lineNumber++;

                try {
                    String[] fields = line.split(";", -1);

                    if (fields.length < 15) {
                        continue;
                    }

                    BinTable binTable = new BinTable();
                    binTable.setRunDate(parseDate(fields[0]));
                    binTable.setStartBin(parseLong(fields[1]));
                    binTable.setEndBin(parseLong(fields[2]));
                    binTable.setBinLength(parseInt(fields[3]));
                    binTable.setBinCountry(parseInt(fields[4]));
                    binTable.setBinCountryDescription(emptyToNull(fields[5]));
                    binTable.setCountryCode(emptyToNull(fields[6]));
                    binTable.setCardBrandDescription(emptyToNull(fields[7]));
                    binTable.setServiceTypeDescription(emptyToNull(fields[8]));
                    binTable.setCardOrganisationDescription(emptyToNull(fields[9]));
                    binTable.setCardProduct(emptyToNull(fields[10]));
                    binTable.setIssuerName(emptyToNull(fields[11]));
                    binTable.setTipoCarta(emptyToNull(fields[12]));
                    binTable.setPaese(emptyToNull(fields[13]));
                    binTable.setTranscodifica(emptyToNull(fields[14]));

                    records.add(binTable);

                    // Batch insert ogni 1000 record
                    if (records.size() >= 1000) {
                        binTableRepository.saveAll(records);
                        imported += records.size();
                        records.clear();

                        // Aggiorna progresso (stima basata su lineNumber)
                        progress.updateProgress(imported, lineNumber * 2); // Stima pessimistica
                        log.info("Imported {} records so far... ({}%)", imported, progress.getProgressPercentage());
                    }

                } catch (Exception e) {
                    log.error("Error parsing line {}: {}", lineNumber, e.getMessage());
                }
            }

            // Salva ultimi record
            if (!records.isEmpty()) {
                binTableRepository.saveAll(records);
                imported += records.size();
            }

            log.info("CSV import completed: {} records imported", imported);
            return imported;

        } catch (Exception e) {
            log.error("Error importing CSV: {}", e.getMessage(), e);
            throw new RuntimeException("Failed to import CSV: " + e.getMessage(), e);
        }
    }

    // ========== Helper methods ==========

    private LocalDate parseDate(String value) {
        if (value == null || value.isBlank()) {
            return null;
        }
        try {
            return LocalDate.parse(value, DATE_FORMATTER);
        } catch (Exception e) {
            return null;
        }
    }

    private Long parseLong(String value) {
        if (value == null || value.isBlank()) {
            return null;
        }
        try {
            return Long.parseLong(value.trim());
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private Integer parseInt(String value) {
        if (value == null || value.isBlank()) {
            return null;
        }
        try {
            return Integer.parseInt(value.trim());
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private String emptyToNull(String value) {
        if (value == null || value.isBlank()) {
            return null;
        }
        return value.trim();
    }
}
