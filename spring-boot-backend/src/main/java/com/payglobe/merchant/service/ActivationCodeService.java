package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.request.CreateActivationCodeRequest;
import com.payglobe.merchant.dto.response.ActivationCodeResponse;
import com.payglobe.merchant.dto.response.ActivationCodeStatsResponse;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.entity.ActivationCode;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.ActivationCodeRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.util.List;
import java.util.stream.Collectors;

@Service
@Slf4j
@RequiredArgsConstructor
public class ActivationCodeService {

    private final ActivationCodeRepository activationCodeRepository;
    private static final SecureRandom RANDOM = new SecureRandom();

    /**
     * Genera un codice di attivazione univoco
     * Formato: ACT-XXXXXXXXX (9 caratteri random)
     */
    private String generateUniqueCode() {
        String code;
        do {
            // Genera 9 caratteri random alfanumerici uppercase
            byte[] randomBytes = new byte[5];
            RANDOM.nextBytes(randomBytes);
            StringBuilder sb = new StringBuilder("ACT-");
            for (byte b : randomBytes) {
                sb.append(String.format("%02X", b));
            }
            code = sb.substring(0, 13);  // ACT- + 9 chars
        } while (activationCodeRepository.findByCode(code).isPresent());

        return code;
    }

    /**
     * Crea un nuovo codice di attivazione
     */
    @Transactional
    public ActivationCodeResponse createActivationCode(CreateActivationCodeRequest request, User currentUser) {

        // Validazione autorizzazione BU
        if (!currentUser.isAdmin() &&
            request.getBu() != null &&
            !request.getBu().equals(currentUser.getBu()) &&
            !currentUser.getBu().equals("---")) {
            throw new IllegalArgumentException(
                "Non hai autorizzazione per creare codici per la BU: " + request.getBu()
            );
        }

        String code = generateUniqueCode();
        LocalDateTime expiresAt = LocalDateTime.now().plusDays(21);  // Scadenza 21 giorni

        ActivationCode activationCode = new ActivationCode();
        activationCode.setCode(code);
        activationCode.setStoreTerminalId(request.getTerminalId());
        activationCode.setBu(request.getBu() != null ? request.getBu() : currentUser.getBu());
        activationCode.setLanguage(request.getLanguage() != null ? request.getLanguage() : "it");
        activationCode.setNotes(request.getNotes());
        activationCode.setCreatedBy(currentUser.getEmail());
        activationCode.setExpiresAt(expiresAt);
        activationCode.setStatus("PENDING");

        ActivationCode saved = activationCodeRepository.save(activationCode);

        log.info("Created activation code: {} for terminal: {} by user: {}",
                 code, request.getTerminalId(), currentUser.getEmail());

        return ActivationCodeResponse.fromEntity(saved);
    }

    /**
     * Lista codici con filtri
     */
    public PagedResponse<ActivationCodeResponse> listActivationCodes(
            String status, String bu, String search, User currentUser, Pageable pageable) {

        Page<ActivationCode> page;

        if (currentUser.isAdmin()) {
            // Admin vede tutti i codici
            page = activationCodeRepository.searchActivationCodes(status, bu, search, pageable);
        } else {
            // Utenti normali vedono solo i propri codici
            page = activationCodeRepository.searchActivationCodesForUser(
                currentUser.getBu(), status, search, pageable
            );
        }

        List<ActivationCodeResponse> content = page.getContent().stream()
            .map(ActivationCodeResponse::fromEntity)
            .collect(Collectors.toList());

        return new PagedResponse<>(
            content,
            page.getNumber(),
            page.getSize(),
            page.getTotalElements(),
            page.getTotalPages(),
            page.isFirst(),
            page.isLast()
        );
    }

    /**
     * Ottieni statistiche codici
     */
    public ActivationCodeStatsResponse getStats(User currentUser) {
        LocalDateTime now = LocalDateTime.now();

        if (currentUser.isAdmin()) {
            // Statistiche globali per admin
            long total = activationCodeRepository.count();
            long active = activationCodeRepository.countByStatus("PENDING") -
                         activationCodeRepository.countExpired(now);
            long used = activationCodeRepository.countByStatus("USED");
            long expired = activationCodeRepository.countByStatus("EXPIRED") +
                          activationCodeRepository.countExpired(now);

            return ActivationCodeStatsResponse.builder()
                .total(total)
                .active(active)
                .used(used)
                .expired(expired)
                .build();
        } else {
            // Statistiche per BU specifica
            String userBu = currentUser.getBu();
            long total = activationCodeRepository.findByBu(userBu, Pageable.unpaged()).getTotalElements();
            long active = activationCodeRepository.countByBuAndStatus(userBu, "PENDING") -
                         activationCodeRepository.countExpiredByBu(userBu, now);
            long used = activationCodeRepository.countByBuAndStatus(userBu, "USED");
            long expired = activationCodeRepository.countByBuAndStatus(userBu, "EXPIRED") +
                          activationCodeRepository.countExpiredByBu(userBu, now);

            return ActivationCodeStatsResponse.builder()
                .total(total)
                .active(active)
                .used(used)
                .expired(expired)
                .build();
        }
    }

    /**
     * Disattiva un codice (solo admin)
     */
    @Transactional
    public void deactivateCode(Long codeId, User currentUser) {
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono disattivare codici");
        }

        ActivationCode code = activationCodeRepository.findById(codeId)
            .orElseThrow(() -> new IllegalArgumentException("Codice non trovato"));

        if ("PENDING".equals(code.getStatus())) {
            code.setStatus("EXPIRED");
            activationCodeRepository.save(code);
            log.info("Deactivated activation code: {} by admin: {}", code.getCode(), currentUser.getEmail());
        } else {
            throw new IllegalStateException("Il codice non è in stato PENDING");
        }
    }

    /**
     * Elimina un codice (solo admin)
     */
    @Transactional
    public void deleteCode(Long codeId, User currentUser) {
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono eliminare codici");
        }

        ActivationCode code = activationCodeRepository.findById(codeId)
            .orElseThrow(() -> new IllegalArgumentException("Codice non trovato"));

        activationCodeRepository.delete(code);
        log.info("Deleted activation code: {} by admin: {}", code.getCode(), currentUser.getEmail());
    }

    /**
     * Pulizia bulk codici scaduti (solo admin)
     */
    @Transactional
    public int bulkCleanupExpired(User currentUser) {
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono eseguire pulizia bulk");
        }

        int deleted = activationCodeRepository.deleteExpired();
        log.info("Bulk cleanup: deleted {} expired codes by admin: {}", deleted, currentUser.getEmail());
        return deleted;
    }

    /**
     * Ottieni lista BU distinte (solo admin)
     */
    public List<String> getDistinctBu(User currentUser) {
        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono visualizzare tutte le BU");
        }

        return activationCodeRepository.findDistinctBu();
    }
}
