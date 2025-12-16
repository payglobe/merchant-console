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
     * Trova BIN usando range numerico sul prefisso (fallback quando non c'è match esatto)
     * Cerca record dove start_bin è nel range del prefisso BIN a 6 cifre
     * Usa l'indice numerico su start_bin per performance ottimali
     *
     * @param binMin Valore minimo del range (es. 5355740000000000000)
     * @param binMax Valore massimo del range (es. 5355749999999999999)
     * @return BinTable corrispondente al prefisso
     */
    @Query(value = "SELECT * FROM bin_table WHERE start_bin >= :binMin AND start_bin <= :binMax LIMIT 1", nativeQuery = true)
    Optional<BinTable> findByBinRange(@Param("binMin") Long binMin, @Param("binMax") Long binMax);

    /**
     * Conta record per paese
     */
    long countByCountryCode(String countryCode);

    /**
     * Verifica esistenza dati
     */
    @Query("SELECT COUNT(bt) > 0 FROM BinTable bt")
    boolean hasData();

    /**
     * Trova BIN usando LIKE sul prefisso (6 cifre) - VELOCE!
     * Cerca record dove start_bin inizia con il BIN6
     *
     * @param bin6 Prime 6 cifre del PAN (es. "535574")
     * @return BinTable corrispondente al prefisso
     */
    @Query(value = "SELECT * FROM bin_table WHERE CAST(start_bin AS CHAR) LIKE CONCAT(:bin6, '%') LIMIT 1", nativeQuery = true)
    Optional<BinTable> findByBinPrefix(@Param("bin6") String bin6);

    /**
     * Carica TUTTI i BIN con country_code per warm-up cache
     * Restituisce solo le prime 6 cifre di start_bin e il country_code
     *
     * @return Lista di Object[] dove [0]=bin6 (String), [1]=country_code (String)
     */
    @Query(value = "SELECT DISTINCT LEFT(CAST(start_bin AS CHAR), 6) as bin6, country_code FROM bin_table WHERE country_code IS NOT NULL", nativeQuery = true)
    java.util.List<Object[]> findAllBinCountryPairs();
}
