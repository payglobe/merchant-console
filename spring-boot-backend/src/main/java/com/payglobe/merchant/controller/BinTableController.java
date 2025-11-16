package com.payglobe.merchant.controller;

import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import com.payglobe.merchant.service.BinTableService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.util.Map;

/**
 * Controller per gestione BIN Table (solo admin)
 *
 * Base path: /api/v2/admin/bin-table
 */
@RestController
@RequestMapping("/api/v2/admin/bin-table")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")
public class BinTableController {

    private final BinTableService binTableService;
    private final UserRepository userRepository;

    /**
     * Upload e importa file CSV BIN table in modo ASINCRONO (RACCOMANDATO)
     *
     * POST /api/v2/admin/bin-table/upload-async
     *
     * Multipart form-data con campo "file"
     * Content-Type: multipart/form-data
     *
     * Restituisce subito importId per tracking progresso
     */
    @PostMapping("/upload-async")
    public ResponseEntity<Map<String, Object>> uploadCsvAsync(
            @RequestParam("file") MultipartFile file) {

        log.info("POST /api/v2/admin/bin-table/upload-async - filename={}, size={} bytes",
                 file.getOriginalFilename(), file.getSize());

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono caricare BIN table");
        }

        // Validazioni
        if (file.isEmpty()) {
            throw new IllegalArgumentException("File vuoto");
        }

        String filename = file.getOriginalFilename();
        if (filename == null) {
            throw new IllegalArgumentException("Nome file non valido");
        }

        String filenameLower = filename.toLowerCase();
        boolean isZip = filenameLower.endsWith(".zip");
        boolean isCsv = filenameLower.endsWith(".csv");

        if (!isZip && !isCsv) {
            throw new IllegalArgumentException("Il file deve essere in formato CSV o ZIP contenente CSV");
        }

        // Avvia import asincrono
        try {
            String importId = binTableService.startAsyncImport(
                file.getInputStream(),
                filename,
                isZip
            );

            return ResponseEntity.ok(Map.of(
                "message", "Import avviato in background",
                "importId", importId,
                "filename", filename,
                "fileType", isZip ? "ZIP" : "CSV"
            ));

        } catch (Exception e) {
            log.error("Error starting async import: {}", e.getMessage(), e);
            throw new RuntimeException("Errore durante l'avvio import: " + e.getMessage());
        }
    }

    /**
     * Upload e importa file CSV BIN table (SINCRONO - può dare timeout)
     *
     * POST /api/v2/admin/bin-table/upload
     *
     * Multipart form-data con campo "file"
     * Content-Type: multipart/form-data
     */
    @PostMapping("/upload")
    public ResponseEntity<Map<String, Object>> uploadCsv(
            @RequestParam("file") MultipartFile file) {

        log.info("POST /api/v2/admin/bin-table/upload - filename={}, size={} bytes",
                 file.getOriginalFilename(), file.getSize());

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono caricare BIN table");
        }

        // Validazioni
        if (file.isEmpty()) {
            throw new IllegalArgumentException("File vuoto");
        }

        String filename = file.getOriginalFilename();
        if (filename == null) {
            throw new IllegalArgumentException("Nome file non valido");
        }

        String filenameLower = filename.toLowerCase();
        boolean isZip = filenameLower.endsWith(".zip");
        boolean isCsv = filenameLower.endsWith(".csv");

        if (!isZip && !isCsv) {
            throw new IllegalArgumentException("Il file deve essere in formato CSV o ZIP contenente CSV");
        }

        // Import da InputStream
        try {
            int imported;

            if (isZip) {
                log.info("Processing ZIP file: {}", filename);
                imported = binTableService.importFromZip(file.getInputStream());
            } else {
                log.info("Processing CSV file: {}", filename);
                imported = binTableService.importFromCsv(file.getInputStream());
            }

            return ResponseEntity.ok(Map.of(
                "message", "Import completato con successo",
                "filename", filename,
                "fileType", isZip ? "ZIP" : "CSV",
                "recordsImported", imported
            ));

        } catch (Exception e) {
            log.error("Error processing uploaded file: {}", e.getMessage(), e);
            throw new RuntimeException("Errore durante l'import: " + e.getMessage());
        }
    }

    /**
     * Importa dati BIN da CSV (da file path sul server)
     *
     * POST /api/v2/admin/bin-table/import
     *
     * Body: { "csvFilePath": "C:\\Users\\...\\BT_20251013_2.csv" }
     */
    @PostMapping("/import")
    public ResponseEntity<Map<String, Object>> importCsv(
            @RequestBody Map<String, String> request) {

        log.info("POST /api/v2/admin/bin-table/import");

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono importare BIN table");
        }

        String csvFilePath = request.get("csvFilePath");
        if (csvFilePath == null || csvFilePath.isBlank()) {
            throw new IllegalArgumentException("csvFilePath è obbligatorio");
        }

        int imported = binTableService.importFromCsvFile(csvFilePath);

        return ResponseEntity.ok(Map.of(
            "message", "Import completato",
            "recordsImported", imported
        ));
    }

    /**
     * Elimina tutti i dati BIN (per re-import)
     *
     * DELETE /api/v2/admin/bin-table
     */
    @DeleteMapping
    public ResponseEntity<Map<String, String>> clearAll() {

        log.info("DELETE /api/v2/admin/bin-table");

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono eliminare BIN table");
        }

        binTableService.clearAll();

        return ResponseEntity.ok(Map.of("message", "Tutti i dati BIN eliminati"));
    }

    /**
     * Verifica se la tabella ha dati
     *
     * GET /api/v2/admin/bin-table/status
     */
    @GetMapping("/status")
    public ResponseEntity<Map<String, Object>> getStatus() {

        log.info("GET /api/v2/admin/bin-table/status");

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono verificare BIN table");
        }

        boolean hasData = binTableService.hasData();

        return ResponseEntity.ok(Map.of(
            "hasData", hasData,
            "message", hasData ? "BIN table contiene dati" : "BIN table vuota"
        ));
    }

    /**
     * Test lookup BIN
     *
     * GET /api/v2/admin/bin-table/lookup?pan=400115123456789
     */
    @GetMapping("/lookup")
    public ResponseEntity<Map<String, String>> lookupBin(@RequestParam String pan) {

        log.info("GET /api/v2/admin/bin-table/lookup - pan={}", pan);

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono fare lookup BIN");
        }

        String bankName = binTableService.getBankName(pan);
        String country = binTableService.getCountryName(pan);

        return ResponseEntity.ok(Map.of(
            "pan", pan,
            "bankName", bankName,
            "country", country
        ));
    }

    /**
     * Controlla stato import asincrono
     *
     * GET /api/v2/admin/bin-table/import-status/{importId}
     *
     * Ritorna:
     * - status: "processing", "completed", "failed"
     * - progressPercentage: 0.0 - 100.0
     * - processedRecords: numero record processati
     * - importedRecords: numero record importati (disponibile solo a completamento)
     */
    @GetMapping("/import-status/{importId}")
    public ResponseEntity<?> getImportStatus(@PathVariable String importId) {

        log.info("GET /api/v2/admin/bin-table/import-status/{}", importId);

        User currentUser = getCurrentUser();
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono controllare import");
        }

        return binTableService.getImportProgress(importId)
            .map(progress -> ResponseEntity.ok(progress))
            .orElse(ResponseEntity.notFound().build());
    }

    // ========== Helper methods ==========

    private User getCurrentUser() {
        Authentication authentication = SecurityContextHolder.getContext().getAuthentication();

        if (authentication == null || !authentication.isAuthenticated() ||
            "anonymousUser".equals(authentication.getName())) {
            throw new RuntimeException("Utente non autenticato");
        }

        // authentication.getName() ritorna l'email dal JWT token subject
        String email = authentication.getName();
        return userRepository.findByEmail(email)
            .orElseThrow(() -> new RuntimeException("Utente non trovato: " + email));
    }
}
