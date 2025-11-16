package com.payglobe.merchant.service;

import com.payglobe.merchant.dto.request.ChangePasswordRequest;
import com.payglobe.merchant.dto.request.LoginRequest;
import com.payglobe.merchant.dto.response.LoginResponse;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.UserRepository;
import com.payglobe.merchant.security.JwtTokenProvider;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;

/**
 * Service per autenticazione
 */
@Service
@Slf4j
@RequiredArgsConstructor
public class AuthService {

    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;
    private final JwtTokenProvider jwtTokenProvider;

    /**
     * Login con email e password
     */
    @Transactional
    public LoginResponse login(LoginRequest request) {
        log.info("Login attempt for email: {}", request.getEmail());

        // Trova user per email
        User user = userRepository.findByEmailAndActiveTrue(request.getEmail())
            .orElseThrow(() -> {
                log.warn("Login failed: user not found or inactive - {}", request.getEmail());
                return new RuntimeException("Credenziali non valide");
            });

        // Verifica password (BCrypt - compatibile con PHP)
        if (!passwordEncoder.matches(request.getPassword(), user.getPassword())) {
            log.warn("Login failed: invalid password - {}", request.getEmail());
            throw new RuntimeException("Credenziali non valide");
        }

        // Verifica password scaduta (ma NON bloccare se solo forcePasswordChange)
        if (user.isPasswordExpired()) {
            log.warn("Login failed: password expired - {}", request.getEmail());
            throw new RuntimeException("Password scaduta. Contattare l'amministratore.");
        }

        // Aggiorna last login
        user.setLastLogin(LocalDateTime.now());
        userRepository.save(user);

        // Genera JWT tokens
        String accessToken = jwtTokenProvider.generateAccessToken(
            user.getId(), user.getEmail(), user.getBu());
        String refreshToken = jwtTokenProvider.generateRefreshToken(user.getId());

        log.info("Login successful for user: {}, BU: {}", user.getEmail(), user.getBu());

        return LoginResponse.builder()
            .accessToken(accessToken)
            .refreshToken(refreshToken)
            .userId(user.getId())
            .email(user.getEmail())
            .bu(user.getBu())
            .ragioneSociale(user.getRagioneSociale())
            .isAdmin(user.isAdmin())
            .forcePasswordChange(user.getForcePasswordChange())
            .build();
    }

    /**
     * Refresh access token
     */
    public String refreshAccessToken(String refreshToken) {
        if (!jwtTokenProvider.validateToken(refreshToken)) {
            throw new RuntimeException("Refresh token non valido");
        }

        Long userId = jwtTokenProvider.getUserIdFromToken(refreshToken);

        User user = userRepository.findById(userId)
            .orElseThrow(() -> new RuntimeException("User non trovato"));

        if (!user.getActive()) {
            throw new RuntimeException("User disabilitato");
        }

        return jwtTokenProvider.generateAccessToken(
            user.getId(), user.getEmail(), user.getBu());
    }

    /**
     * Cambio password per utente corrente (usando email)
     */
    @Transactional
    public void changePassword(String email, ChangePasswordRequest request) {
        log.info("Password change request for user email: {}", email);

        // Trova user by email
        User user = userRepository.findByEmail(email)
            .orElseThrow(() -> {
                log.error("User not found: {}", email);
                return new RuntimeException("Utente non trovato");
            });

        changePasswordInternal(user, request);
    }

    /**
     * Cambio password per utente corrente (usando userId)
     */
    @Transactional
    public void changePassword(Long userId, ChangePasswordRequest request) {
        log.info("Password change request for user ID: {}", userId);

        // Trova user
        User user = userRepository.findById(userId)
            .orElseThrow(() -> {
                log.error("User not found: {}", userId);
                return new RuntimeException("Utente non trovato");
            });

        changePasswordInternal(user, request);
    }

    /**
     * Logica comune per cambio password
     */
    private void changePasswordInternal(User user, ChangePasswordRequest request) {

        // Verifica vecchia password
        if (!passwordEncoder.matches(request.getOldPassword(), user.getPassword())) {
            log.warn("Password change failed: incorrect old password for user ID: {}", user.getId());
            throw new RuntimeException("Password attuale non corretta");
        }

        // Verifica che la nuova password sia diversa
        if (request.getOldPassword().equals(request.getNewPassword())) {
            log.warn("Password change failed: new password same as old for user ID: {}", user.getId());
            throw new RuntimeException("La nuova password deve essere diversa da quella attuale");
        }

        // Aggiorna password
        user.setPassword(passwordEncoder.encode(request.getNewPassword()));
        user.setForcePasswordChange(false);
        user.setPasswordLastChanged(LocalDateTime.now());
        userRepository.save(user);

        log.info("Password changed successfully for user ID: {}", user.getId());
    }

}
