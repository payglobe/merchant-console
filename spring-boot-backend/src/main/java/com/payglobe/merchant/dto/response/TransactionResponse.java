package com.payglobe.merchant.dto.response;

import com.fasterxml.jackson.annotation.JsonFormat;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.math.BigDecimal;
import java.time.LocalDateTime;

/**
 * DTO per transazione (risposta API)
 */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class TransactionResponse {

    private Long id;
    private String posid;

    @JsonFormat(pattern = "yyyy-MM-dd'T'HH:mm:ss")
    private LocalDateTime transactionDate;

    private String transactionType;
    private BigDecimal amount;
    private String pan;
    private String cardBrand;
    private String settlementFlag;
    private String responseCode;
    private String authorizationCode;
    private String rrn;

    // Store info (nested)
    private String storeInsegna;
    private String storeRagioneSociale;
    private String storeCitta;

    // Computed fields
    private Boolean isSettled;
    private Boolean isRefund;
    private BigDecimal signedAmount;

}
