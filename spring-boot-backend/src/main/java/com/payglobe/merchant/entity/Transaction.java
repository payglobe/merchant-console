package com.payglobe.merchant.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import org.hibernate.annotations.CreationTimestamp;

import java.math.BigDecimal;
import java.time.LocalDateTime;

/**
 * Entity Transaction - Mappa tabella "transactions" esistente (PHP)
 */
@Entity
@Table(name = "transactions", indexes = {
    @Index(name = "idx_transaction_date", columnList = "transaction_date"),
    @Index(name = "idx_posid", columnList = "posid"),
    @Index(name = "idx_settlement_flag", columnList = "settlement_flag"),
    @Index(name = "idx_card_brand", columnList = "card_brand")
})
@Data
@NoArgsConstructor
@AllArgsConstructor
public class Transaction {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "posid", nullable = false, length = 50)
    private String posid;

    @Column(name = "transaction_date", nullable = false)
    private LocalDateTime transactionDate;

    @Column(name = "transaction_type", nullable = false, length = 20)
    private String transactionType;  // DAACQU, CAACQU, DSESTO, etc.

    @Column(name = "amount", nullable = false, precision = 10, scale = 2)
    private BigDecimal amount;

    @Column(name = "pan", length = 50)
    private String pan;

    @Column(name = "card_brand", length = 20)
    private String cardBrand;  // PA, VC, MC, MBK, etc.

    @Column(name = "settlement_flag", length = 1)
    private String settlementFlag;  // '1' = OK, altro = NO

    @Column(name = "response_code", length = 10)
    private String responseCode;

    @Column(name = "ib_response_code", length = 10)
    private String ibResponseCode;

    @Column(name = "approval_code", length = 6)
    private String approvalCode;

    @Column(name = "rrn", length = 12)
    private String rrn;

    @Column(name = "transaction_number", length = 6)
    private String transactionNumber;

    @Column(name = "processed_at", updatable = false)
    private LocalDateTime processedAt;

    @Column(name = "updated_at")
    private LocalDateTime updatedAt;

    // Relazione con Store (lazy loading) - OPTIONAL perché alcuni posid non esistono in stores
    @ManyToOne(fetch = FetchType.LAZY, optional = true)
    @JoinColumn(name = "posid", referencedColumnName = "TerminalID",
                insertable = false, updatable = false)
    @org.hibernate.annotations.NotFound(action = org.hibernate.annotations.NotFoundAction.IGNORE)
    private Store store;

    // Helper methods

    public boolean isSettled() {
        return "1".equals(settlementFlag);
    }

    public boolean isRefund() {
        return transactionType != null && (
            transactionType.equals("DSESTO") ||
            transactionType.equals("DSISTO") ||
            transactionType.equals("CSESTO") ||
            transactionType.equals("CSISTO")
        );
    }

    /**
     * Calcola l'importo con segno corretto per volume netto
     * Storni hanno segno negativo
     */
    public BigDecimal getSignedAmount() {
        if (!isSettled()) {
            return BigDecimal.ZERO;
        }
        return isRefund() ? amount.negate() : amount;
    }

}
