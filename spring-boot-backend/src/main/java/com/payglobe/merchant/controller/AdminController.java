package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.request.GeneratePasswordHashRequest;
import com.payglobe.merchant.dto.response.PasswordHashResponse;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.ResponseEntity;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.web.bind.annotation.*;

/**
 * Controller per operazioni admin
 *
 * Base path: /api/v2/admin
 */
@RestController
@RequestMapping("/api/v2/admin")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")
public class AdminController {

    private final PasswordEncoder passwordEncoder;

    /**
     * Genera hash BCrypt per una password
     * Utile per creare password con caratteri speciali
     *
     * POST /api/v2/admin/generate-password-hash
     *
     * Body: { "password": "Test!123@#" }
     * Response: { "password": "Test!123@#", "hash": "$2a$10...", "sqlUpdate": "UPDATE users..." }
     */
    @PostMapping("/generate-password-hash")
    public ResponseEntity<PasswordHashResponse> generatePasswordHash(
            @Valid @RequestBody GeneratePasswordHashRequest request) {

        log.info("POST /api/v2/admin/generate-password-hash - Generating hash for password with {} chars",
                 request.getPassword().length());

        String hash = passwordEncoder.encode(request.getPassword());

        String sqlUpdate = String.format(
            "UPDATE users SET password = '%s' WHERE email = 'user@example.com';",
            hash
        );

        PasswordHashResponse response = PasswordHashResponse.builder()
            .password(request.getPassword())
            .hash(hash)
            .sqlUpdate(sqlUpdate)
            .build();

        log.info("Hash generated successfully");

        return ResponseEntity.ok(response);
    }

    /**
     * Testa se una password corrisponde a un hash BCrypt
     *
     * POST /api/v2/admin/test-password-match
     *
     * Body: { "password": "Test!123@#", "hash": "$2a$10..." }
     * Response: { "matches": true }
     */
    @PostMapping("/test-password-match")
    public ResponseEntity<?> testPasswordMatch(
            @RequestBody TestPasswordMatchRequest request) {

        log.info("POST /api/v2/admin/test-password-match");

        boolean matches = passwordEncoder.matches(request.getPassword(), request.getHash());

        log.info("Password match result: {}", matches);

        return ResponseEntity.ok(new TestPasswordMatchResponse(matches));
    }

    // Inner classes for test endpoint
    @lombok.Data
    public static class TestPasswordMatchRequest {
        private String password;
        private String hash;
    }

    @lombok.Data
    @lombok.AllArgsConstructor
    public static class TestPasswordMatchResponse {
        private boolean matches;
    }

    /**
     * Info sistema (memoria, uptime, etc.)
     *
     * GET /api/v2/admin/system-info
     */
    @GetMapping("/system-info")
    public ResponseEntity<?> getSystemInfo() {
        Runtime runtime = Runtime.getRuntime();

        long maxMemory = runtime.maxMemory();       // -Xmx
        long totalMemory = runtime.totalMemory();   // Heap allocato
        long freeMemory = runtime.freeMemory();     // Heap libero
        long usedMemory = totalMemory - freeMemory; // Heap usato

        // Uptime JVM
        long uptimeMs = java.lang.management.ManagementFactory.getRuntimeMXBean().getUptime();
        long uptimeSeconds = uptimeMs / 1000;
        long hours = uptimeSeconds / 3600;
        long minutes = (uptimeSeconds % 3600) / 60;
        long seconds = uptimeSeconds % 60;
        String uptime = String.format("%dh %dm %ds", hours, minutes, seconds);

        // Processors
        int processors = runtime.availableProcessors();

        return ResponseEntity.ok(new SystemInfoResponse(
            formatBytes(usedMemory),
            formatBytes(totalMemory),
            formatBytes(maxMemory),
            Math.round((double) usedMemory / maxMemory * 100),
            uptime,
            processors
        ));
    }

    private String formatBytes(long bytes) {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return String.format("%.1f KB", bytes / 1024.0);
        if (bytes < 1024 * 1024 * 1024) return String.format("%.1f MB", bytes / (1024.0 * 1024));
        return String.format("%.2f GB", bytes / (1024.0 * 1024 * 1024));
    }

    @lombok.Data
    @lombok.AllArgsConstructor
    public static class SystemInfoResponse {
        private String usedMemory;      // Heap usato
        private String allocatedMemory; // Heap allocato (totalMemory)
        private String maxMemory;       // Heap max (-Xmx)
        private long usedPercent;       // Percentuale usata
        private String uptime;          // Tempo di esecuzione
        private int processors;         // CPU cores
    }
}
