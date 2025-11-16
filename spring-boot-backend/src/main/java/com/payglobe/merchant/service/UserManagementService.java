package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.request.CreateUserRequest;
import com.payglobe.merchant.dto.request.ResetPasswordRequest;
import com.payglobe.merchant.dto.request.UpdateUserRequest;
import com.payglobe.merchant.dto.response.PagedResponse;
import com.payglobe.merchant.dto.response.UserResponse;
import com.payglobe.merchant.dto.response.UserStatsResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.stream.Collectors;

@Service
@Slf4j
@RequiredArgsConstructor
public class UserManagementService {

    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;

    /**
     * Crea un nuovo utente (solo admin)
     */
    @Transactional
    public UserResponse createUser(CreateUserRequest request, User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono creare utenti");
        }

        // Verifica se email già esiste
        if (userRepository.findByEmail(request.getEmail()).isPresent()) {
            throw new IllegalArgumentException("Email già esistente");
        }

        User user = new User();
        user.setEmail(request.getEmail());
        user.setPassword(passwordEncoder.encode(request.getPassword()));
        user.setBu(request.getBu());
        user.setRagioneSociale(request.getRagioneSociale());
        user.setActive(request.getActive() != null ? request.getActive() : true);
        user.setForcePasswordChange(request.getForcePasswordChange() != null ? request.getForcePasswordChange() : false);
        user.setPasswordLastChanged(LocalDateTime.now());
        user.setCreatedAt(LocalDateTime.now());

        User saved = userRepository.save(user);

        log.info("Created user: {} by admin: {}", saved.getEmail(), currentUser.getEmail());

        return UserResponse.fromEntity(saved);
    }

    /**
     * Aggiorna un utente (solo admin)
     */
    @Transactional
    public UserResponse updateUser(Long userId, UpdateUserRequest request, User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono modificare utenti");
        }

        User user = userRepository.findById(userId)
            .orElseThrow(() -> new IllegalArgumentException("Utente non trovato"));

        user.setEmail(request.getEmail());
        user.setBu(request.getBu());
        user.setRagioneSociale(request.getRagioneSociale());

        if (request.getActive() != null) {
            user.setActive(request.getActive());
        }

        if (request.getForcePasswordChange() != null) {
            user.setForcePasswordChange(request.getForcePasswordChange());
        }

        User saved = userRepository.save(user);

        log.info("Updated user: {} by admin: {}", saved.getEmail(), currentUser.getEmail());

        return UserResponse.fromEntity(saved);
    }

    /**
     * Reset password di un utente (solo admin)
     */
    @Transactional
    public void resetPassword(Long userId, ResetPasswordRequest request, User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono resettare password");
        }

        User user = userRepository.findById(userId)
            .orElseThrow(() -> new IllegalArgumentException("Utente non trovato"));

        user.setPassword(passwordEncoder.encode(request.getNewPassword()));
        user.setPasswordLastChanged(LocalDateTime.now());
        user.setForcePasswordChange(true);  // Forza cambio al prossimo login

        userRepository.save(user);

        log.info("Reset password for user: {} by admin: {}", user.getEmail(), currentUser.getEmail());
    }

    /**
     * Elimina un utente (solo admin)
     */
    @Transactional
    public void deleteUser(Long userId, User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono eliminare utenti");
        }

        // Verifica che non si stia eliminando se stesso
        if (currentUser.getId().equals(userId)) {
            throw new IllegalArgumentException("Non puoi eliminare il tuo stesso account");
        }

        User user = userRepository.findById(userId)
            .orElseThrow(() -> new IllegalArgumentException("Utente non trovato"));

        userRepository.delete(user);

        log.info("Deleted user: {} by admin: {}", user.getEmail(), currentUser.getEmail());
    }

    /**
     * Lista tutti gli utenti (solo admin)
     */
    public PagedResponse<UserResponse> listUsers(String search, Pageable pageable, User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono visualizzare tutti gli utenti");
        }

        Page<User> page;

        if (search != null && !search.isBlank()) {
            // Ricerca per email, BU o ragione sociale
            page = userRepository.findByEmailContainingIgnoreCaseOrBuContainingIgnoreCaseOrRagioneSocialeContainingIgnoreCase(
                search, search, search, pageable
            );
        } else {
            page = userRepository.findAll(pageable);
        }

        List<UserResponse> content = page.getContent().stream()
            .map(UserResponse::fromEntity)
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
     * Ottieni statistiche utenti (solo admin)
     */
    public UserStatsResponse getStats(User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono visualizzare statistiche utenti");
        }

        LocalDateTime now = LocalDateTime.now();
        LocalDateTime passwordExpiredThreshold = now.minusDays(45);

        long totalUsers = userRepository.count();
        long activeUsers = userRepository.countByActive(true);
        long pendingPasswordChange = userRepository.countByForcePasswordChange(true);
        long expiredPasswords = userRepository.countByPasswordLastChangedBefore(passwordExpiredThreshold);

        return UserStatsResponse.builder()
            .totalUsers(totalUsers)
            .activeUsers(activeUsers)
            .pendingPasswordChange(pendingPasswordChange)
            .expiredPasswords(expiredPasswords)
            .build();
    }

    /**
     * Ottieni dettagli utente per id (solo admin)
     */
    public UserResponse getUserById(Long userId, User currentUser) {

        if (!currentUser.isAdmin()) {
            throw new SecurityException("Solo gli amministratori possono visualizzare dettagli utenti");
        }

        User user = userRepository.findById(userId)
            .orElseThrow(() -> new IllegalArgumentException("Utente non trovato"));

        return UserResponse.fromEntity(user);
    }
}
