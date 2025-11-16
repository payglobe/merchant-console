package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.response.CircuitDistributionResponse;
import com.payglobe.merchant.dto.response.DashboardStatsResponse;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.dto.response.TransactionResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import com.payglobe.merchant.service.TransactionService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.List;
import java.util.Map;

/**
 * Controller per transazioni e dashboard
 *
 * Base path: /api/v2/transactions
 */
@RestController
@RequestMapping("/api/v2/transactions")
@Slf4j
@RequiredArgsConstructor
@CrossOrigin(origins = "*")  // Temporaneo per sviluppo
public class TransactionController {

    private final TransactionService transactionService;
    private final UserRepository userRepository;

    /**
     * Lista transazioni con filtri
     *
     * GET /api/v2/transactions
     */
    @GetMapping
    public ResponseEntity<PagedResponse<TransactionResponse>> getTransactions(
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate startDate,
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate endDate,
            @RequestParam(required = false) String filterStore,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "25") int size) {

        log.info("GET /api/v2/transactions - startDate={}, endDate={}, filterStore={}, page={}, size={}",
                 startDate, endDate, filterStore, page, size);

        User currentUser = getCurrentUser();

        // Default date range: ultimi 30 giorni (7 per admin per performance)
        if (startDate == null) {
            startDate = currentUser.isAdmin() ? LocalDate.now().minusDays(7) : LocalDate.now().minusDays(30);
        }
        if (endDate == null) {
            endDate = LocalDate.now();
        }

        // VALIDAZIONE ADMIN: max 7 giorni di differenza (troppi dati!)
        if (currentUser.isAdmin()) {
            long daysBetween = java.time.temporal.ChronoUnit.DAYS.between(startDate, endDate);
            if (daysBetween > 7) {
                throw new IllegalArgumentException(
                    "Per gli admin, la differenza tra data inizio e data fine non può superare 7 giorni. " +
                    "Attualmente: " + daysBetween + " giorni. Troppi dati!"
                );
            }
        }

        LocalDateTime startDateTime = startDate.atStartOfDay();
        LocalDateTime endDateTime = endDate.atTime(LocalTime.MAX);

        // Clean filterStore from spaces
        String cleanFilterStore = cleanFilterStore(filterStore);

        // Repository queries already have ORDER BY hardcoded, so don't add Sort to Pageable
        Pageable pageable = PageRequest.of(page, size);

        PagedResponse<TransactionResponse> response = transactionService.findTransactions(
            startDateTime, endDateTime, cleanFilterStore, currentUser, pageable);

        return ResponseEntity.ok(response);
    }

    /**
     * Statistiche dashboard (KPI)
     *
     * GET /api/v2/transactions/stats
     */
    @GetMapping("/stats")
    public ResponseEntity<DashboardStatsResponse> getDashboardStats(
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate startDate,
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate endDate,
            @RequestParam(required = false) String filterStore) {

        log.info("GET /api/v2/transactions/stats - startDate={}, endDate={}, filterStore={}",
                 startDate, endDate, filterStore);

        User currentUser = getCurrentUser();

        // Default: ultimo mese (7 giorni per admin)
        if (startDate == null) {
            startDate = currentUser.isAdmin() ? LocalDate.now().minusDays(7) : LocalDate.now().minusMonths(1);
        }
        if (endDate == null) {
            endDate = LocalDate.now();
        }

        // VALIDAZIONE ADMIN: max 7 giorni di differenza (troppi dati!)
        if (currentUser.isAdmin()) {
            long daysBetween = java.time.temporal.ChronoUnit.DAYS.between(startDate, endDate);
            if (daysBetween > 7) {
                throw new IllegalArgumentException(
                    "Per gli admin, la differenza tra data inizio e data fine non può superare 7 giorni. " +
                    "Attualmente: " + daysBetween + " giorni. Troppi dati!"
                );
            }
        }

        LocalDateTime startDateTime = startDate.atStartOfDay();
        LocalDateTime endDateTime = endDate.atTime(LocalTime.MAX);

        String cleanFilterStore = cleanFilterStore(filterStore);

        DashboardStatsResponse stats = transactionService.getDashboardStats(
            startDateTime, endDateTime, cleanFilterStore, currentUser);

        return ResponseEntity.ok(stats);
    }

    /**
     * Distribuzione circuiti per grafico
     *
     * GET /api/v2/transactions/circuits
     */
    @GetMapping("/circuits")
    public ResponseEntity<CircuitDistributionResponse> getCircuitDistribution(
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate startDate,
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate endDate,
            @RequestParam(required = false) String filterStore) {

        log.info("GET /api/v2/transactions/circuits - startDate={}, endDate={}, filterStore={}",
                 startDate, endDate, filterStore);

        User currentUser = getCurrentUser();

        if (startDate == null) {
            startDate = currentUser.isAdmin() ? LocalDate.now().minusDays(7) : LocalDate.now().minusMonths(1);
        }
        if (endDate == null) {
            endDate = LocalDate.now();
        }

        // VALIDAZIONE ADMIN: max 7 giorni di differenza (troppi dati!)
        if (currentUser.isAdmin()) {
            long daysBetween = java.time.temporal.ChronoUnit.DAYS.between(startDate, endDate);
            if (daysBetween > 7) {
                throw new IllegalArgumentException(
                    "Per gli admin, la differenza tra data inizio e data fine non può superare 7 giorni. " +
                    "Attualmente: " + daysBetween + " giorni. Troppi dati!"
                );
            }
        }

        LocalDateTime startDateTime = startDate.atStartOfDay();
        LocalDateTime endDateTime = endDate.atTime(LocalTime.MAX);

        String cleanFilterStore = cleanFilterStore(filterStore);

        CircuitDistributionResponse response = transactionService.getCircuitDistribution(
            startDateTime, endDateTime, cleanFilterStore, currentUser);

        return ResponseEntity.ok(response);
    }

    /**
     * Trend giornaliero per grafico
     *
     * GET /api/v2/transactions/trend
     */
    @GetMapping("/trend")
    public ResponseEntity<List<Map<String, Object>>> getDailyTrend(
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate startDate,
            @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate endDate,
            @RequestParam(required = false) String filterStore) {

        log.info("GET /api/v2/transactions/trend - startDate={}, endDate={}, filterStore={}",
                 startDate, endDate, filterStore);

        User currentUser = getCurrentUser();

        if (startDate == null) {
            startDate = currentUser.isAdmin() ? LocalDate.now().minusDays(7) : LocalDate.now().withDayOfMonth(1);
        }
        if (endDate == null) {
            endDate = LocalDate.now();
        }

        // VALIDAZIONE ADMIN: max 7 giorni di differenza (troppi dati!)
        if (currentUser.isAdmin()) {
            long daysBetween = java.time.temporal.ChronoUnit.DAYS.between(startDate, endDate);
            if (daysBetween > 7) {
                throw new IllegalArgumentException(
                    "Per gli admin, la differenza tra data inizio e data fine non può superare 7 giorni. " +
                    "Attualmente: " + daysBetween + " giorni. Troppi dati!"
                );
            }
        }

        LocalDateTime startDateTime = startDate.atStartOfDay();
        LocalDateTime endDateTime = endDate.atTime(LocalTime.MAX);

        String cleanFilterStore = cleanFilterStore(filterStore);

        List<Map<String, Object>> trend = transactionService.getDailyTrend(
            startDateTime, endDateTime, cleanFilterStore, currentUser);

        return ResponseEntity.ok(trend);
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

    /**
     * Pulisce la stringa filterStore rimuovendo gli spazi dai terminal IDs
     * Input: "95025018 ,95025020" -> Output: "95025018,95025020"
     */
    private String cleanFilterStore(String filterStore) {
        if (filterStore == null || filterStore.isBlank()) {
            return filterStore;
        }

        // Split per virgola, trim ogni elemento, rejoin
        return String.join(",",
            java.util.Arrays.stream(filterStore.split(","))
                .map(String::trim)
                .filter(s -> !s.isEmpty())
                .toArray(String[]::new)
        );
    }

}
