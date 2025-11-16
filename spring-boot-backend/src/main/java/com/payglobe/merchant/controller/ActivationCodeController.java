package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.request.CreateActivationCodeRequest;
import com.payglobe.merchant.dto.response.ActivationCodeResponse;
import com.payglobe.merchant.dto.response.ActivationCodeStatsResponse;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import com.payglobe.merchant.service.ActivationCodeService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.data.domain.Sort;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

/**
 * Controller per gestione codici di attivazione PAX
 *
 * Base path: /api/v2/activation-codes
 */
@RestController
@RequestMapping("/api/v2/activation-codes")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")
public class ActivationCodeController {

    private final ActivationCodeService activationCodeService;
    private final UserRepository userRepository;

    /**
     * Crea nuovo codice di attivazione
     *
     * POST /api/v2/activation-codes
     */
    @PostMapping
    public ResponseEntity<ActivationCodeResponse> createActivationCode(
            @Valid @RequestBody CreateActivationCodeRequest request) {

        log.info("POST /api/v2/activation-codes - terminalId={}, bu={}, language={}",
                 request.getTerminalId(), request.getBu(), request.getLanguage());

        User currentUser = getCurrentUser();

        ActivationCodeResponse response = activationCodeService.createActivationCode(request, currentUser);

        return ResponseEntity.ok(response);
    }

    /**
     * Lista codici di attivazione con filtri
     *
     * GET /api/v2/activation-codes
     */
    @GetMapping
    public ResponseEntity<PagedResponse<ActivationCodeResponse>> listActivationCodes(
            @RequestParam(required = false) String status,
            @RequestParam(required = false) String bu,
            @RequestParam(required = false) String search,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "25") int size) {

        log.info("GET /api/v2/activation-codes - status={}, bu={}, search={}, page={}, size={}",
                 status, bu, search, page, size);

        User currentUser = getCurrentUser();

        Pageable pageable = PageRequest.of(page, size, Sort.by("createdAt").descending());

        PagedResponse<ActivationCodeResponse> response = activationCodeService.listActivationCodes(
            status, bu, search, currentUser, pageable);

        return ResponseEntity.ok(response);
    }

    /**
     * Statistiche codici di attivazione
     *
     * GET /api/v2/activation-codes/stats
     */
    @GetMapping("/stats")
    public ResponseEntity<ActivationCodeStatsResponse> getStats() {

        log.info("GET /api/v2/activation-codes/stats");

        User currentUser = getCurrentUser();

        ActivationCodeStatsResponse stats = activationCodeService.getStats(currentUser);

        return ResponseEntity.ok(stats);
    }

    /**
     * Disattiva un codice (solo admin)
     *
     * POST /api/v2/activation-codes/{codeId}/deactivate
     */
    @PostMapping("/{codeId}/deactivate")
    public ResponseEntity<Map<String, String>> deactivateCode(@PathVariable Long codeId) {

        log.info("POST /api/v2/activation-codes/{}/deactivate", codeId);

        User currentUser = getCurrentUser();

        activationCodeService.deactivateCode(codeId, currentUser);

        return ResponseEntity.ok(Map.of("message", "Codice disattivato con successo"));
    }

    /**
     * Elimina un codice (solo admin)
     *
     * DELETE /api/v2/activation-codes/{codeId}
     */
    @DeleteMapping("/{codeId}")
    public ResponseEntity<Map<String, String>> deleteCode(@PathVariable Long codeId) {

        log.info("DELETE /api/v2/activation-codes/{}", codeId);

        User currentUser = getCurrentUser();

        activationCodeService.deleteCode(codeId, currentUser);

        return ResponseEntity.ok(Map.of("message", "Codice eliminato con successo"));
    }

    /**
     * Pulizia bulk codici scaduti (solo admin)
     *
     * POST /api/v2/activation-codes/cleanup
     */
    @PostMapping("/cleanup")
    public ResponseEntity<Map<String, Object>> bulkCleanup() {

        log.info("POST /api/v2/activation-codes/cleanup");

        User currentUser = getCurrentUser();

        int deleted = activationCodeService.bulkCleanupExpired(currentUser);

        return ResponseEntity.ok(Map.of(
            "message", "Pulizia completata",
            "deletedCount", deleted
        ));
    }

    /**
     * Ottieni lista BU distinte (solo admin)
     *
     * GET /api/v2/activation-codes/bu-list
     */
    @GetMapping("/bu-list")
    public ResponseEntity<List<String>> getDistinctBu() {

        log.info("GET /api/v2/activation-codes/bu-list");

        User currentUser = getCurrentUser();

        List<String> buList = activationCodeService.getDistinctBu(currentUser);

        return ResponseEntity.ok(buList);
    }

    // ========== Helper methods ==========

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
