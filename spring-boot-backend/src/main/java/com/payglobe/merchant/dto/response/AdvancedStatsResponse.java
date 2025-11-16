package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.math.BigDecimal;
import java.util.List;
import java.util.Map;

/**
 * Response per statistiche avanzate (statistics.php equivalent)
 */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class AdvancedStatsResponse {

    // KPI Generali
    private Long totalTransactions;
    private BigDecimal netVolume;
    private BigDecimal avgTicket;
    private Integer periodDays;
    private BigDecimal dailyAvgVolume;
    private BigDecimal dailyAvgTransactions;

    // Analisi per giorno della settimana
    private List<DayAnalysis> dayAnalysis;

    // Analisi oraria
    private List<HourlyAnalysis> hourlyAnalysis;

    // Analisi BIN (Top 15 banche)
    private List<BinAnalysis> binAnalysis;

    // Circuiti (già esistente in CircuitDistributionResponse)
    // Trend temporale (già esistente in /trend endpoint)

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class DayAnalysis {
        private String dayName;
        private Integer dayNum;
        private Long transactionCount;
        private BigDecimal dailyVolume;
        private BigDecimal avgTicket;
    }

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class HourlyAnalysis {
        private Integer hourOfDay;
        private Long transactionCount;
        private BigDecimal hourlyVolume;
    }

    @Data
    @Builder
    @NoArgsConstructor
    @AllArgsConstructor
    public static class BinAnalysis {
        private String bin;
        private String bankName;
        private String country;
        private Long transactionCount;
        private BigDecimal binVolume;
        private BigDecimal avgTicket;
        private BigDecimal volumePercentage;
    }
}
