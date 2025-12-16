package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.math.BigDecimal;
import java.util.List;

/**
 * Response per distribuzione geografica delle transazioni
 * Basata sul country_code della bin_table
 */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class GeoDistributionResponse {

    // Statistiche per area geografica
    private GeoArea domestic;      // Italia (IT)
    private GeoArea eu;            // Unione Europea (esclusa Italia)
    private GeoArea extraEu;       // Extra UE
    private GeoArea unknown;       // BIN non trovato nel database

    // Totali
    private long totalTransactions;
    private BigDecimal totalAmount;

    // Top 5 negozi per area geografica (solo admin)
    private List<StoreGeoStats> topDomesticStores;
    private List<StoreGeoStats> topEuStores;
    private List<StoreGeoStats> topExtraEuStores;

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class StoreGeoStats {
        private String ragioneSociale;
        private String insegna;
        private long count;
        private BigDecimal amount;
        private double percentage;
    }

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class GeoArea {
        private String name;
        private long count;
        private BigDecimal amount;
        private double percentageCount;
        private double percentageAmount;

        // Dettaglio per circuito (Visa, Mastercard, etc)
        private List<CircuitDetail> byCircuit;
    }

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class CircuitDetail {
        private String circuit;
        private long count;
        private BigDecimal amount;
    }
}
