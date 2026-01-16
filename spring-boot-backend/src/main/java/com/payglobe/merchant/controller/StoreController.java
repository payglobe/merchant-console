package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.ImportProgress;
import com.payglobe.merchant.dto.response.StoreGroupResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import com.payglobe.merchant.service.StoreService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

/**
 * Controller per stores
 *
 * Base path: /api/v2/stores
 */
@RestController
@RequestMapping("/api/v2/stores")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")
public class StoreController {

    private final JdbcTemplate jdbcTemplate;
    private final UserRepository userRepository;
    private final StoreService storeService;

    /**
     * Lista stores raggruppati per punto vendita
     *
     * GET /api/v2/stores/groups
     */
    @GetMapping("/groups")
    public ResponseEntity<List<StoreGroupResponse>> getStoreGroups() {

        User currentUser = getCurrentUser();

        log.info("GET /api/v2/stores/groups - user: {}, bu: {}",
                 currentUser.getEmail(), currentUser.getBu());

        String sql;
        List<StoreGroupResponse> groups;

        if (currentUser.isAdmin()) {
            // Admin: tutti i negozi (aumentato LIMIT per vedere tutti gli store)
            sql = """
                SELECT
                    GROUP_CONCAT(DISTINCT s.TerminalID ORDER BY s.TerminalID SEPARATOR ',') as terminalIds,
                    s.Insegna as insegna,
                    s.Ragione_Sociale as ragioneSociale,
                    s.indirizzo,
                    s.citta,
                    COUNT(DISTINCT s.TerminalID) as terminalCount
                FROM stores s
                INNER JOIN (SELECT DISTINCT posid FROM transactions LIMIT 500000) t ON s.TerminalID = t.posid
                WHERE s.TerminalID IS NOT NULL
                GROUP BY s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta
                ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo
                LIMIT 50000
                """;

            groups = jdbcTemplate.query(sql, (rs, rowNum) ->
                StoreGroupResponse.builder()
                    .terminalIds(rs.getString("terminalIds"))
                    .insegna(rs.getString("insegna"))
                    .ragioneSociale(rs.getString("ragioneSociale"))
                    .indirizzo(rs.getString("indirizzo"))
                    .citta(rs.getString("citta"))
                    .terminalCount(rs.getInt("terminalCount"))
                    .build()
            );
        } else {
            // Utente normale: filtra per bu, bu1 o bu2
            sql = """
                SELECT
                    GROUP_CONCAT(DISTINCT s.TerminalID ORDER BY s.TerminalID SEPARATOR ',') as terminalIds,
                    s.Insegna as insegna,
                    s.Ragione_Sociale as ragioneSociale,
                    s.indirizzo,
                    s.citta,
                    COUNT(DISTINCT s.TerminalID) as terminalCount
                FROM stores s
                INNER JOIN transactions t ON s.TerminalID = t.posid
                WHERE s.TerminalID IS NOT NULL
                  AND (s.bu = ? OR s.bu1 = ? OR s.bu2 = ?)
                GROUP BY s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta
                ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo
                """;

            String userBu = currentUser.getBu();
            groups = jdbcTemplate.query(sql, (rs, rowNum) ->
                StoreGroupResponse.builder()
                    .terminalIds(rs.getString("terminalIds"))
                    .insegna(rs.getString("insegna"))
                    .ragioneSociale(rs.getString("ragioneSociale"))
                    .indirizzo(rs.getString("indirizzo"))
                    .citta(rs.getString("citta"))
                    .terminalCount(rs.getInt("terminalCount"))
                    .build(),
                userBu, userBu, userBu
            );
        }

        log.info("Found {} store groups for user {}", groups.size(), currentUser.getEmail());

        return ResponseEntity.ok(groups);
    }

    // ==================== ADMIN: IMPORT STORES ====================

    /**
     * Upload anagrafica stores (ASINCRONO - solo admin)
     *
     * POST /api/v2/stores/admin/upload-async
     *
     * @param file CSV o ZIP contenente CSV
     * @return importId per polling stato
     */
    @PostMapping("/admin/upload-async")
    public ResponseEntity<?> uploadStoresAsync(@RequestParam("file") MultipartFile file) {
        User currentUser = getCurrentUser();

        // Solo admin
        if (!currentUser.isAdmin()) {
            log.warn("Non-admin user {} tried to upload stores", currentUser.getEmail());
            return ResponseEntity.status(403)
                .body(Map.of("error", "Accesso negato. Solo admin."));
        }

        if (file.isEmpty()) {
            return ResponseEntity.badRequest()
                .body(Map.of("error", "File vuoto"));
        }

        String filename = file.getOriginalFilename();
        boolean isZip = filename != null && filename.toLowerCase().endsWith(".zip");
        boolean isCsv = filename != null && filename.toLowerCase().endsWith(".csv");

        if (!isZip && !isCsv) {
            return ResponseEntity.badRequest()
                .body(Map.of("error", "Il file deve essere CSV o ZIP"));
        }

        try {
            log.info("Admin {} uploading stores file: {} ({} bytes)",
                     currentUser.getEmail(), filename, file.getSize());

            // Leggi file in byte array per passarlo all'async
            byte[] fileContent = file.getBytes();

            // Avvia import asincrono
            String importId = storeService.startAsyncImport(fileContent, filename, isZip);

            return ResponseEntity.ok(Map.of(
                "message", "Import avviato in background",
                "importId", importId,
                "filename", filename,
                "fileSize", file.getSize(),
                "fileType", isZip ? "ZIP" : "CSV"
            ));

        } catch (Exception e) {
            log.error("Error starting store import", e);
            return ResponseEntity.internalServerError()
                .body(Map.of("error", "Errore avvio import: " + e.getMessage()));
        }
    }

    /**
     * Stato import stores (solo admin)
     *
     * GET /api/v2/stores/admin/import-status/{importId}
     */
    @GetMapping("/admin/import-status/{importId}")
    public ResponseEntity<?> getImportStatus(@PathVariable String importId) {
        User currentUser = getCurrentUser();

        if (!currentUser.isAdmin()) {
            return ResponseEntity.status(403)
                .body(Map.of("error", "Accesso negato. Solo admin."));
        }

        return storeService.getImportProgress(importId)
            .map(progress -> ResponseEntity.ok((Object) progress))
            .orElse(ResponseEntity.notFound().build());
    }

    /**
     * Conta totale stores (solo admin)
     *
     * GET /api/v2/stores/admin/count
     */
    @GetMapping("/admin/count")
    public ResponseEntity<?> getStoreCount() {
        User currentUser = getCurrentUser();

        if (!currentUser.isAdmin()) {
            return ResponseEntity.status(403)
                .body(Map.of("error", "Accesso negato. Solo admin."));
        }

        long count = storeService.countStores();
        return ResponseEntity.ok(Map.of("count", count));
    }

    /**
     * Ottiene user corrente dall'autenticazione JWT
     */
    private User getCurrentUser() {
        Authentication authentication = SecurityContextHolder.getContext().getAuthentication();

        if (authentication == null || !authentication.isAuthenticated() ||
            "anonymousUser".equals(authentication.getName())) {
            throw new RuntimeException("Utente non autenticato");
        }

        String email = authentication.getName();
        return userRepository.findByEmail(email)
            .orElseThrow(() -> new RuntimeException("Utente non trovato: " + email));
    }

}
