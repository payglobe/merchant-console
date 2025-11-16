package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.request.CreateUserRequest;
import com.payglobe.merchant.dto.request.ResetPasswordRequest;
import com.payglobe.merchant.dto.request.UpdateUserRequest;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.dto.response.UserResponse;
import com.payglobe.merchant.dto.response.UserStatsResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import com.payglobe.merchant.service.UserManagementService;
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

import java.util.Map;

/**
 * Controller per gestione utenti (solo admin)
 *
 * Base path: /api/v2/admin/users
 */
@RestController
@RequestMapping("/api/v2/admin/users")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")
public class UserManagementController {

    private final UserManagementService userManagementService;
    private final UserRepository userRepository;

    /**
     * Crea nuovo utente
     *
     * POST /api/v2/admin/users
     */
    @PostMapping
    public ResponseEntity<UserResponse> createUser(
            @Valid @RequestBody CreateUserRequest request) {

        log.info("POST /api/v2/admin/users - email={}, bu={}", request.getEmail(), request.getBu());

        User currentUser = getCurrentUser();

        UserResponse response = userManagementService.createUser(request, currentUser);

        return ResponseEntity.ok(response);
    }

    /**
     * Lista utenti con filtri
     *
     * GET /api/v2/admin/users
     */
    @GetMapping
    public ResponseEntity<PagedResponse<UserResponse>> listUsers(
            @RequestParam(required = false) String search,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "25") int size) {

        log.info("GET /api/v2/admin/users - search={}, page={}, size={}", search, page, size);

        User currentUser = getCurrentUser();

        Pageable pageable = PageRequest.of(page, size, Sort.by("createdAt").descending());

        PagedResponse<UserResponse> response = userManagementService.listUsers(search, pageable, currentUser);

        return ResponseEntity.ok(response);
    }

    /**
     * Ottieni dettagli utente
     *
     * GET /api/v2/admin/users/{userId}
     */
    @GetMapping("/{userId}")
    public ResponseEntity<UserResponse> getUserById(@PathVariable Long userId) {

        log.info("GET /api/v2/admin/users/{}", userId);

        User currentUser = getCurrentUser();

        UserResponse response = userManagementService.getUserById(userId, currentUser);

        return ResponseEntity.ok(response);
    }

    /**
     * Aggiorna utente
     *
     * PUT /api/v2/admin/users/{userId}
     */
    @PutMapping("/{userId}")
    public ResponseEntity<UserResponse> updateUser(
            @PathVariable Long userId,
            @Valid @RequestBody UpdateUserRequest request) {

        log.info("PUT /api/v2/admin/users/{} - email={}, bu={}", userId, request.getEmail(), request.getBu());

        User currentUser = getCurrentUser();

        UserResponse response = userManagementService.updateUser(userId, request, currentUser);

        return ResponseEntity.ok(response);
    }

    /**
     * Reset password utente
     *
     * POST /api/v2/admin/users/{userId}/reset-password
     */
    @PostMapping("/{userId}/reset-password")
    public ResponseEntity<Map<String, String>> resetPassword(
            @PathVariable Long userId,
            @Valid @RequestBody ResetPasswordRequest request) {

        log.info("POST /api/v2/admin/users/{}/reset-password", userId);

        User currentUser = getCurrentUser();

        userManagementService.resetPassword(userId, request, currentUser);

        return ResponseEntity.ok(Map.of(
            "message", "Password resetata con successo. L'utente dovrà cambiarla al prossimo login"
        ));
    }

    /**
     * Elimina utente
     *
     * DELETE /api/v2/admin/users/{userId}
     */
    @DeleteMapping("/{userId}")
    public ResponseEntity<Map<String, String>> deleteUser(@PathVariable Long userId) {

        log.info("DELETE /api/v2/admin/users/{}", userId);

        User currentUser = getCurrentUser();

        userManagementService.deleteUser(userId, currentUser);

        return ResponseEntity.ok(Map.of("message", "Utente eliminato con successo"));
    }

    /**
     * Statistiche utenti
     *
     * GET /api/v2/admin/users/stats
     */
    @GetMapping("/stats")
    public ResponseEntity<UserStatsResponse> getStats() {

        log.info("GET /api/v2/admin/users/stats");

        User currentUser = getCurrentUser();

        UserStatsResponse stats = userManagementService.getStats(currentUser);

        return ResponseEntity.ok(stats);
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
