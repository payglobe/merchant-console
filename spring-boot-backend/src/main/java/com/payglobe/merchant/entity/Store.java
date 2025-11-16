package com.payglobe.merchant.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

/**
 * Entity Store - Mappa tabella "stores" esistente (PHP)
 */
@Entity
@Table(name = "stores")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class Store {

    @Id
    @Column(name = "TerminalID", nullable = false, length = 45)
    private String terminalId;

    @Column(name = "bu", nullable = false, length = 50)
    private String bu;

    @Column(name = "Insegna", length = 255)
    private String insegna;

    @Column(name = "Ragione_Sociale", length = 255)
    private String ragioneSociale;

    @Column(name = "indirizzo", length = 255)
    private String indirizzo;

    @Column(name = "citta", length = 100)
    private String citta;

    @Column(name = "cap", length = 10)
    private String cap;

    @Column(name = "prov", length = 5)
    private String prov;

    @Column(name = "country", length = 5)
    private String country;

    @Column(name = "Modello_pos", length = 100)
    private String modelloPos;

    // Helper method
    public String getDisplayName() {
        return insegna != null && !insegna.isBlank() ? insegna : ragioneSociale;
    }

}
