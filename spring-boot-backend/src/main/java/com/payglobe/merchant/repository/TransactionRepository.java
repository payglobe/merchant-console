package com.payglobe.merchant.repository;

import com.payglobe.merchant.entity.Transaction;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;

/**
 * Repository per gestione Transaction
 */
@Repository
public interface TransactionRepository extends JpaRepository<Transaction, Long> {

    /**
     * Trova transazioni per POSID con paginazione
     */
    Page<Transaction> findByPosid(String posid, Pageable pageable);

    /**
     * Trova transazioni per POSID in un range di date
     */
    @Query(value = """
        SELECT t.* FROM transactions t
        WHERE FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        ORDER BY t.transaction_date DESC
        """,
        countQuery = """
        SELECT COUNT(*) FROM transactions t
        WHERE FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        """,
        nativeQuery = true)
    Page<Transaction> findByPosidAndDateRange(
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        Pageable pageable
    );

    /**
     * Trova transazioni per range di date (TUTTE - solo admin)
     */
    @Query("SELECT t FROM Transaction t WHERE " +
           "t.transactionDate BETWEEN :startDate AND :endDate " +
           "ORDER BY t.transactionDate DESC")
    Page<Transaction> findByDateRange(
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        Pageable pageable
    );

    /**
     * Trova transazioni per Business Unit e range date
     * Join con store per filtrare per BU (bu, bu1 o bu2) - QUERY NATIVA per evitare problemi con @NotFound
     */
    @Query(value = """
        SELECT t.* FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND t.transaction_date BETWEEN :startDate AND :endDate
        ORDER BY t.transaction_date DESC
        """,
        countQuery = """
        SELECT COUNT(*) FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND t.transaction_date BETWEEN :startDate AND :endDate
        """,
        nativeQuery = true)
    Page<Transaction> findByBuAndDateRange(
        @Param("bu") String bu,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        Pageable pageable
    );

    /**
     * Trova transazioni per BU (bu, bu1 o bu2) e POSID in un range di date
     */
    @Query(value = """
        SELECT t.* FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        ORDER BY t.transaction_date DESC
        """,
        countQuery = """
        SELECT COUNT(*) FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        """,
        nativeQuery = true)
    Page<Transaction> findByBuAndPosidAndDateRange(
        @Param("bu") String bu,
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        Pageable pageable
    );

    /**
     * Statistiche dashboard - Admin (tutte le transazioni)
     */
    @Query(value = """
        SELECT
            COUNT(*) as total,
            SUM(CASE
                WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -amount
                WHEN settlement_flag = '1'
                THEN amount
                ELSE 0
            END) as volume,
            SUM(CASE WHEN settlement_flag = '1' THEN 1 ELSE 0 END) as settled_count,
            SUM(CASE WHEN settlement_flag != '1' THEN 1 ELSE 0 END) as not_settled_count
        FROM transactions
        WHERE transaction_date BETWEEN :startDate AND :endDate
        """, nativeQuery = true)
    Map<String, Object> calculateDashboardStats(
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Statistiche dashboard - Per Business Unit (bu, bu1 o bu2)
     */
    @Query(value = """
        SELECT
            COUNT(*) as total,
            SUM(CASE
                WHEN t.settlement_flag = '1' AND t.transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -t.amount
                WHEN t.settlement_flag = '1'
                THEN t.amount
                ELSE 0
            END) as volume,
            SUM(CASE WHEN t.settlement_flag = '1' THEN 1 ELSE 0 END) as settled_count,
            SUM(CASE WHEN t.settlement_flag != '1' THEN 1 ELSE 0 END) as not_settled_count
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND t.transaction_date BETWEEN :startDate AND :endDate
        """, nativeQuery = true)
    Map<String, Object> calculateDashboardStatsByBu(
        @Param("bu") String bu,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Statistiche dashboard - Per POSID specifici (filtro negozio)
     */
    @Query(value = """
        SELECT
            COUNT(*) as total,
            SUM(CASE
                WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -amount
                WHEN settlement_flag = '1'
                THEN amount
                ELSE 0
            END) as volume,
            SUM(CASE WHEN settlement_flag = '1' THEN 1 ELSE 0 END) as settled_count,
            SUM(CASE WHEN settlement_flag != '1' THEN 1 ELSE 0 END) as not_settled_count
        FROM transactions
        WHERE FIND_IN_SET(posid, :posids) > 0
        AND transaction_date BETWEEN :startDate AND :endDate
        """, nativeQuery = true)
    Map<String, Object> calculateDashboardStatsByPosid(
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Statistiche dashboard - Per BU (bu, bu1 o bu2) e POSID specifici (utente normale + filtro negozio)
     */
    @Query(value = """
        SELECT
            COUNT(*) as total,
            SUM(CASE
                WHEN t.settlement_flag = '1' AND t.transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -t.amount
                WHEN t.settlement_flag = '1'
                THEN t.amount
                ELSE 0
            END) as volume,
            SUM(CASE WHEN t.settlement_flag = '1' THEN 1 ELSE 0 END) as settled_count,
            SUM(CASE WHEN t.settlement_flag != '1' THEN 1 ELSE 0 END) as not_settled_count
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        """, nativeQuery = true)
    Map<String, Object> calculateDashboardStatsByBuAndPosid(
        @Param("bu") String bu,
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Distribuzione circuiti (solo settled) - Admin
     */
    @Query(value = """
        SELECT card_brand, COUNT(*) as count
        FROM transactions
        WHERE transaction_date BETWEEN :startDate AND :endDate
        AND settlement_flag = '1'
        GROUP BY card_brand
        ORDER BY count DESC
        """, nativeQuery = true)
    List<Object[]> getCircuitDistribution(
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Distribuzione circuiti (solo settled) - Per BU (bu, bu1 o bu2)
     */
    @Query(value = """
        SELECT t.card_brand, COUNT(*) as count
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND t.transaction_date BETWEEN :startDate AND :endDate
        AND t.settlement_flag = '1'
        GROUP BY t.card_brand
        ORDER BY count DESC
        """, nativeQuery = true)
    List<Object[]> getCircuitDistributionByBu(
        @Param("bu") String bu,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Distribuzione circuiti (solo settled) - Per POSID specifici (filtro negozio)
     */
    @Query(value = """
        SELECT card_brand, COUNT(*) as count
        FROM transactions
        WHERE FIND_IN_SET(posid, :posids) > 0
        AND transaction_date BETWEEN :startDate AND :endDate
        AND settlement_flag = '1'
        GROUP BY card_brand
        ORDER BY count DESC
        """, nativeQuery = true)
    List<Object[]> getCircuitDistributionByPosid(
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Distribuzione circuiti (solo settled) - Per BU (bu, bu1 o bu2) e POSID specifici (utente normale + filtro negozio)
     */
    @Query(value = """
        SELECT t.card_brand, COUNT(*) as count
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        AND t.settlement_flag = '1'
        GROUP BY t.card_brand
        ORDER BY count DESC
        """, nativeQuery = true)
    List<Object[]> getCircuitDistributionByBuAndPosid(
        @Param("bu") String bu,
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Trend giornaliero (transazioni + volume) - Admin
     */
    @Query(value = """
        SELECT
            DATE(transaction_date) as day,
            COUNT(*) as daily_count,
            SUM(CASE
                WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -amount
                WHEN settlement_flag = '1'
                THEN amount
                ELSE 0
            END) as daily_volume
        FROM transactions
        WHERE transaction_date BETWEEN :startDate AND :endDate
        GROUP BY DATE(transaction_date)
        ORDER BY DATE(transaction_date)
        """, nativeQuery = true)
    List<Object[]> getDailyTrend(
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Trend giornaliero (transazioni + volume) - Per BU (bu, bu1 o bu2)
     */
    @Query(value = """
        SELECT
            DATE(t.transaction_date) as day,
            COUNT(*) as daily_count,
            SUM(CASE
                WHEN t.settlement_flag = '1' AND t.transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -t.amount
                WHEN t.settlement_flag = '1'
                THEN t.amount
                ELSE 0
            END) as daily_volume
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND t.transaction_date BETWEEN :startDate AND :endDate
        GROUP BY DATE(t.transaction_date)
        ORDER BY DATE(t.transaction_date)
        """, nativeQuery = true)
    List<Object[]> getDailyTrendByBu(
        @Param("bu") String bu,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Trend giornaliero (transazioni + volume) - Per POSID specifici (filtro negozio)
     */
    @Query(value = """
        SELECT
            DATE(transaction_date) as day,
            COUNT(*) as daily_count,
            SUM(CASE
                WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -amount
                WHEN settlement_flag = '1'
                THEN amount
                ELSE 0
            END) as daily_volume
        FROM transactions
        WHERE FIND_IN_SET(posid, :posids) > 0
        AND transaction_date BETWEEN :startDate AND :endDate
        GROUP BY DATE(transaction_date)
        ORDER BY DATE(transaction_date)
        """, nativeQuery = true)
    List<Object[]> getDailyTrendByPosid(
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    /**
     * Trend giornaliero (transazioni + volume) - Per BU (bu, bu1 o bu2) e POSID specifici (utente normale + filtro negozio)
     */
    @Query(value = """
        SELECT
            DATE(t.transaction_date) as day,
            COUNT(*) as daily_count,
            SUM(CASE
                WHEN t.settlement_flag = '1' AND t.transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -t.amount
                WHEN t.settlement_flag = '1'
                THEN t.amount
                ELSE 0
            END) as daily_volume
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        GROUP BY DATE(t.transaction_date)
        ORDER BY DATE(t.transaction_date)
        """, nativeQuery = true)
    List<Object[]> getDailyTrendByBuAndPosid(
        @Param("bu") String bu,
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate
    );

    // ========== Metodi per Geo Distribution (liste non paginate) ==========

    /**
     * Lista transazioni per date range - Admin (limitata)
     */
    @Query(value = """
        SELECT * FROM transactions
        WHERE transaction_date BETWEEN :startDate AND :endDate
        AND settlement_flag = '1'
        ORDER BY transaction_date DESC
        LIMIT :maxResults
        """, nativeQuery = true)
    List<Transaction> findByDateRangeList(
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        @Param("maxResults") int maxResults
    );

    /**
     * Lista transazioni per POSID e date range (limitata)
     */
    @Query(value = """
        SELECT * FROM transactions
        WHERE FIND_IN_SET(posid, :posids) > 0
        AND transaction_date BETWEEN :startDate AND :endDate
        AND settlement_flag = '1'
        ORDER BY transaction_date DESC
        LIMIT :maxResults
        """, nativeQuery = true)
    List<Transaction> findByPosidAndDateRangeList(
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        @Param("maxResults") int maxResults
    );

    /**
     * Lista transazioni per BU (bu, bu1 o bu2) e date range (limitata)
     */
    @Query(value = """
        SELECT t.* FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND t.transaction_date BETWEEN :startDate AND :endDate
        AND t.settlement_flag = '1'
        ORDER BY t.transaction_date DESC
        LIMIT :maxResults
        """, nativeQuery = true)
    List<Transaction> findByBuAndDateRangeList(
        @Param("bu") String bu,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        @Param("maxResults") int maxResults
    );

    /**
     * Lista transazioni per BU (bu, bu1 o bu2), POSID e date range (limitata)
     */
    @Query(value = """
        SELECT t.* FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        WHERE (s.bu = :bu OR s.bu1 = :bu OR s.bu2 = :bu)
        AND FIND_IN_SET(t.posid, :posids) > 0
        AND t.transaction_date BETWEEN :startDate AND :endDate
        AND t.settlement_flag = '1'
        ORDER BY t.transaction_date DESC
        LIMIT :maxResults
        """, nativeQuery = true)
    List<Transaction> findByBuAndPosidAndDateRangeList(
        @Param("bu") String bu,
        @Param("posids") String posids,
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        @Param("maxResults") int maxResults
    );

}
