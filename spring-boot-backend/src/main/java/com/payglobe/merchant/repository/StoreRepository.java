package com.payglobe.merchant.repository;

import com.payglobe.merchant.entity.Store;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

/**
 * Repository per gestione Store
 */
@Repository
public interface StoreRepository extends JpaRepository<Store, Long> {

    /**
     * Trova store per Terminal ID
     */
    Optional<Store> findByTerminalId(String terminalId);

    /**
     * Trova tutti gli store di una Business Unit
     */
    List<Store> findByBu(String bu);

    /**
     * Trova tutti gli store di una Business Unit con paginazione
     */
    Page<Store> findByBu(String bu, Pageable pageable);

    /**
     * Ricerca store per città
     */
    List<Store> findByCitta(String citta);

    /**
     * Ricerca store per provincia
     */
    List<Store> findByProv(String prov);

    /**
     * Ricerca store per paese
     */
    List<Store> findByCountry(String country);

    /**
     * Ricerca fulltext (insegna o ragione sociale contiene keyword)
     */
    @Query("SELECT s FROM Store s WHERE " +
           "LOWER(s.insegna) LIKE LOWER(CONCAT('%', :keyword, '%')) OR " +
           "LOWER(s.ragioneSociale) LIKE LOWER(CONCAT('%', :keyword, '%')) OR " +
           "LOWER(s.citta) LIKE LOWER(CONCAT('%', :keyword, '%'))")
    Page<Store> searchStores(@Param("keyword") String keyword, Pageable pageable);

    /**
     * Ricerca store per BU con filtri multipli
     */
    @Query("SELECT s FROM Store s WHERE s.bu = :bu AND " +
           "(:citta IS NULL OR s.citta = :citta) AND " +
           "(:prov IS NULL OR s.prov = :prov) AND " +
           "(:country IS NULL OR s.country = :country)")
    Page<Store> findByBuWithFilters(
        @Param("bu") String bu,
        @Param("citta") String citta,
        @Param("prov") String prov,
        @Param("country") String country,
        Pageable pageable
    );

}
