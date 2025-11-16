package com.payglobe.merchant.repository;

import com.payglobe.merchant.entity.ActivationCode;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

@Repository
public interface ActivationCodeRepository extends JpaRepository<ActivationCode, Long> {

    /**
     * Trova codice per valore code
     */
    Optional<ActivationCode> findByCode(String code);

    /**
     * Trova tutti i codici per una BU
     */
    Page<ActivationCode> findByBu(String bu, Pageable pageable);

    /**
     * Trova tutti i codici per una BU con filtro status
     */
    Page<ActivationCode> findByBuAndStatus(String bu, String status, Pageable pageable);

    /**
     * Trova tutti i codici (per admin)
     */
    Page<ActivationCode> findAll(Pageable pageable);

    /**
     * Conta codici per status
     */
    long countByStatus(String status);

    /**
     * Conta codici per BU e status
     */
    long countByBuAndStatus(String bu, String status);

    /**
     * Conta codici scaduti
     */
    @Query("SELECT COUNT(ac) FROM ActivationCode ac WHERE ac.expiresAt < :now AND ac.status = 'PENDING'")
    long countExpired(@Param("now") LocalDateTime now);

    /**
     * Conta codici scaduti per BU
     */
    @Query("SELECT COUNT(ac) FROM ActivationCode ac WHERE ac.bu = :bu AND ac.expiresAt < :now AND ac.status = 'PENDING'")
    long countExpiredByBu(@Param("bu") String bu, @Param("now") LocalDateTime now);

    /**
     * Elimina codici scaduti non usati
     */
    @Query("DELETE FROM ActivationCode ac WHERE ac.status = 'EXPIRED' AND ac.usedAt IS NULL")
    int deleteExpired();

    /**
     * Ricerca con filtri multipli (per admin)
     */
    @Query("SELECT ac FROM ActivationCode ac WHERE " +
           "(:status IS NULL OR ac.status = :status) AND " +
           "(:bu IS NULL OR ac.bu = :bu) AND " +
           "(:search IS NULL OR ac.code LIKE %:search% OR ac.storeTerminalId LIKE %:search% OR ac.notes LIKE %:search%)")
    Page<ActivationCode> searchActivationCodes(
        @Param("status") String status,
        @Param("bu") String bu,
        @Param("search") String search,
        Pageable pageable
    );

    /**
     * Ricerca con filtri per utente non-admin
     */
    @Query("SELECT ac FROM ActivationCode ac WHERE ac.bu = :userBu AND " +
           "(:status IS NULL OR ac.status = :status) AND " +
           "(:search IS NULL OR ac.code LIKE %:search% OR ac.storeTerminalId LIKE %:search% OR ac.notes LIKE %:search%)")
    Page<ActivationCode> searchActivationCodesForUser(
        @Param("userBu") String userBu,
        @Param("status") String status,
        @Param("search") String search,
        Pageable pageable
    );

    /**
     * Ottieni lista BU distinte (per admin)
     */
    @Query("SELECT DISTINCT ac.bu FROM ActivationCode ac ORDER BY ac.bu")
    List<String> findDistinctBu();
}
