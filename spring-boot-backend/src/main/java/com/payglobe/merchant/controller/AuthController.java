package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.request.ChangePasswordRequest;
import com.payglobe.merchant.dto.request.LoginRequest;
import com.payglobe.merchant.dto.response.LoginResponse;
import com.payglobe.merchant.service.AuthService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;

/**
 * Controller per autenticazione
 *
 * Base path: /api/v2/auth
 */
@RestController
@RequestMapping("/api/v2/auth")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")  // Temporaneo per sviluppo
public class AuthController {

    private final AuthService authService;

    /**
     * Login con email e password
     *
     * POST /api/v2/auth/login
     */
    @PostMapping("/login")
    public ResponseEntity<LoginResponse> login(@Valid @RequestBody LoginRequest request) {
        log.info("POST /api/v2/auth/login - email: {}", request.getEmail());

        try {
            LoginResponse response = authService.login(request);
            return ResponseEntity.ok(response);
        } catch (RuntimeException e) {
            log.error("Login failed: {}", e.getMessage());
            return ResponseEntity.status(401).build();
        }
    }

    /**
     * Refresh access token
     *
     * POST /api/v2/auth/refresh
     */
    @PostMapping("/refresh")
    public ResponseEntity<String> refresh(@RequestBody String refreshToken) {
        log.info("POST /api/v2/auth/refresh");

        try {
            String newAccessToken = authService.refreshAccessToken(refreshToken);
            return ResponseEntity.ok(newAccessToken);
        } catch (RuntimeException e) {
            log.error("Refresh failed: {}", e.getMessage());
            return ResponseEntity.status(401).build();
        }
    }

    /**
     * Cambio password per utente corrente (auth required)
     *
     * POST /api/v2/auth/change-password
     */
    @PostMapping("/change-password")
    public ResponseEntity<?> changePassword(@Valid @RequestBody ChangePasswordRequest request) {
        log.info("POST /api/v2/auth/change-password");

        try {
            // Get authenticated user email from JWT token
            Authentication authentication = SecurityContextHolder.getContext().getAuthentication();
            String email = authentication.getName();

            log.info("Change password for user: {}", email);
            authService.changePassword(email, request);
            return ResponseEntity.ok().build();
        } catch (RuntimeException e) {
            log.error("Change password failed: {}", e.getMessage());
            return ResponseEntity.status(400).body(e.getMessage());
        }
    }

    /**
     * Health check (NO auth required)
     *
     * GET /api/v2/auth/health
     */
    @GetMapping("/health")
    public ResponseEntity<String> health() {
        return ResponseEntity.ok("OK");
    }

}
