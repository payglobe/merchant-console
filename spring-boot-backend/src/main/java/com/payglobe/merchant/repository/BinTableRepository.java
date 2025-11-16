package com.payglobe.merchant.repository;

import com.payglobe.merchant.entity.BinTable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface BinTableRepository extends JpaRepository<BinTable, Long> {

    /**
     * Trova il record BIN corrispondente a un numero BIN
     *
     * Il BIN (prime 6-9 cifre del PAN) viene cercato nei range start_bin - end_bin
     * Ordina per bin_length DESC per dare precedenza ai match più specifici
     *
     * @param bin BIN da cercare (es. 400115 oppure 445795437 se è il full BIN)
     * @return BinTable corrispondente, se trovato
     */
    @Query("SELECT bt FROM BinTable bt WHERE :bin BETWEEN bt.startBin AND bt.endBin ORDER BY bt.binLength DESC")
    Optional<BinTable> findByBin(@Param("bin") Long bin);

    /**
     * Conta record per paese
     */
    long countByCountryCode(String countryCode);

    /**
     * Verifica esistenza dati
     */
    @Query("SELECT COUNT(bt) > 0 FROM BinTable bt")
    boolean hasData();
}
