package com.payglobe.merchant.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import org.hibernate.annotations.CreationTimestamp;

import java.time.LocalDate;
import java.time.LocalDateTime;

/**
 * Entity BinTable - Tabella BIN (Bank Identification Number)
 *
 * Contiene i range BIN per identificare le banche emittenti delle carte
 */
@Entity
@Table(name = "bin_table", indexes = {
    @Index(name = "idx_bin_range", columnList = "start_bin,end_bin"),
    @Index(name = "idx_country_code", columnList = "country_code"),
    @Index(name = "idx_issuer_name", columnList = "issuer_name")
})
@Data
@NoArgsConstructor
@AllArgsConstructor
public class BinTable {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    // Range BIN
    @Column(name = "start_bin", nullable = true)
    private Long startBin;

    @Column(name = "end_bin", nullable = true)
    private Long endBin;

    @Column(name = "bin_length", nullable = true)
    private Integer binLength;

    // Paese
    @Column(name = "bin_country")
    private Integer binCountry;

    @Column(name = "bin_country_description", length = 100)
    private String binCountryDescription;

    @Column(name = "country_code", length = 3)
    private String countryCode;

    // Dettagli carta
    @Column(name = "card_brand_description", length = 100)
    private String cardBrandDescription;

    @Column(name = "service_type_description", length = 50)
    private String serviceTypeDescription;

    @Column(name = "card_organisation_description", length = 50)
    private String cardOrganisationDescription;

    @Column(name = "card_product", length = 50)
    private String cardProduct;

    // BANCA EMITTENTE (campo più importante!)
    @Column(name = "issuer_name", length = 255)
    private String issuerName;

    // Campi extra
    @Column(name = "tipo_carta", length = 100)
    private String tipoCarta;

    @Column(name = "paese", length = 100)
    private String paese;

    @Column(name = "transcodifica", length = 255)
    private String transcodifica;

    // Metadata
    @Column(name = "run_date")
    private LocalDate runDate;

    @Column(name = "created_at", updatable = false)
    @CreationTimestamp
    private LocalDateTime createdAt;

    // Helper methods

    /**
     * Verifica se un BIN ricade in questo range
     */
    public boolean containsBin(Long bin) {
        return bin != null && bin >= startBin && bin <= endBin;
    }

    /**
     * Ottiene nome banca (con fallback)
     */
    public String getBankName() {
        if (issuerName != null && !issuerName.isBlank()) {
            return issuerName;
        }
        // Fallback su transcodifica o country
        if (transcodifica != null && !transcodifica.isBlank()) {
            return transcodifica;
        }
        if (binCountryDescription != null) {
            return "Banca " + binCountryDescription;
        }
        return "Banca sconosciuta";
    }

    /**
     * Ottiene nome paese
     */
    public String getCountryName() {
        if (binCountryDescription != null && !binCountryDescription.isBlank()) {
            return binCountryDescription;
        }
        if (countryCode != null) {
            return countryCode;
        }
        return "Sconosciuto";
    }
}
