package com.payglobe.merchant.controller;

import com.payglobe.merchant.dto.response.StoreGroupResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.*;

import java.util.List;
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
            // Admin: tutti i negozi (con LIMIT per evitare timeout)
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
                LIMIT 1000
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
            // Utente normale: solo il proprio BU
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
                WHERE s.TerminalID IS NOT NULL AND s.bu = ?
                GROUP BY s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta
                ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo
                """;

            groups = jdbcTemplate.query(sql, (rs, rowNum) ->
                StoreGroupResponse.builder()
                    .terminalIds(rs.getString("terminalIds"))
                    .insegna(rs.getString("insegna"))
                    .ragioneSociale(rs.getString("ragioneSociale"))
                    .indirizzo(rs.getString("indirizzo"))
                    .citta(rs.getString("citta"))
                    .terminalCount(rs.getInt("terminalCount"))
                    .build(),
                currentUser.getBu()
            );
        }

        log.info("Found {} store groups for user {}", groups.size(), currentUser.getEmail());

        return ResponseEntity.ok(groups);
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
