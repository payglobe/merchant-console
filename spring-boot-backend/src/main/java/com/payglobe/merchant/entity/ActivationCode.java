package com.payglobe.merchant.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import org.hibernate.annotations.CreationTimestamp;

import java.time.LocalDateTime;

/**
 * Entity ActivationCode - Codici di attivazione PAX
 *
 * Tabella: activation_codes
 */
@Entity
@Table(name = "activation_codes")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class ActivationCode {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @Column(name = "code", unique = true, nullable = false, length = 50)
    private String code;  // ACT-XXXXXXXXX format

    @Column(name = "store_terminal_id", nullable = false, length = 50)
    private String storeTerminalId;

    @Column(name = "bu", length = 50)
    private String bu;

    @Column(name = "status", columnDefinition = "ENUM('PENDING','USED','EXPIRED')")
    private String status = "PENDING";  // PENDING, USED, EXPIRED

    @Column(name = "language", length = 5)
    private String language = "it";  // it, en, de, fr, es

    @Column(name = "notes", length = 255)
    private String notes;

    @Column(name = "created_by", length = 100)
    private String createdBy;

    @Column(name = "created_at", updatable = false)
    @CreationTimestamp
    private LocalDateTime createdAt;

    @Column(name = "expires_at")
    private LocalDateTime expiresAt;

    @Column(name = "used_at")
    private LocalDateTime usedAt;

    @Column(name = "used_by", length = 100)
    private String usedBy;

    // Helper methods

    public boolean isExpired() {
        return expiresAt != null && expiresAt.isBefore(LocalDateTime.now());
    }

    public boolean isPending() {
        return "PENDING".equals(status) && !isExpired();
    }

    public boolean isUsed() {
        return "USED".equals(status);
    }

    public long getDaysLeft() {
        if (expiresAt == null) {
            return 0;
        }
        return java.time.temporal.ChronoUnit.DAYS.between(LocalDateTime.now(), expiresAt);
    }
}
