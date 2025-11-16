package com.payglobe.merchant.repository;

import com.payglobe.merchant.entity.User;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

/**
 * Repository per gestione User
 */
@Repository
public interface UserRepository extends JpaRepository<User, Long> {

    /**
     * Trova user per email (login)
     */
    Optional<User> findByEmail(String email);

    /**
     * Trova user per email e attivo
     */
    Optional<User> findByEmailAndActiveTrue(String email);

    /**
     * Trova tutti gli utenti di una Business Unit
     */
    List<User> findByBu(String bu);

    /**
     * Verifica se email esiste già
     */
    boolean existsByEmail(String email);

    // ========== Metodi per gestione admin utenti ==========

    /**
     * Conta utenti attivi
     */
    long countByActive(Boolean active);

    /**
     * Conta utenti con forza cambio password
     */
    long countByForcePasswordChange(Boolean forcePasswordChange);

    /**
     * Conta utenti con password scadute
     */
    long countByPasswordLastChangedBefore(LocalDateTime threshold);

    /**
     * Ricerca utenti per email, BU o ragione sociale
     */
    Page<User> findByEmailContainingIgnoreCaseOrBuContainingIgnoreCaseOrRagioneSocialeContainingIgnoreCase(
        String email, String bu, String ragioneSociale, Pageable pageable
    );

}
