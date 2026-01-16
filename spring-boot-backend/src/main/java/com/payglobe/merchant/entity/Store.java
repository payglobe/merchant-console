package com.payglobe.merchant.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

/**
 * Entity Store - Mappa tabella "stores" esistente
 *
 * Campi CSV: TerminalID, Ragione_Sociale, Insegna, indirizzo, Citta, Cap, Prov,
 *            sia_pagobancomat, six, amex, Modello_pos, country, bu, bu1, bu2
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

    @Column(name = "Ragione_Sociale", length = 255)
    private String ragioneSociale;

    @Column(name = "Insegna", length = 255)
    private String insegna;

    @Column(name = "indirizzo", length = 255)
    private String indirizzo;

    @Column(name = "citta", length = 100)
    private String citta;

    @Column(name = "cap", length = 10)
    private String cap;

    @Column(name = "prov", length = 10)
    private String prov;

    @Column(name = "sia_pagobancomat", length = 45)
    private String siaPagobancomat;

    @Column(name = "six", length = 45)
    private String six;

    @Column(name = "amex", length = 45)
    private String amex;

    @Column(name = "Modello_pos", length = 100)
    private String modelloPos;

    @Column(name = "country", length = 5)
    private String country;

    @Column(name = "bu", length = 50)
    private String bu;

    @Column(name = "bu1", length = 50)
    private String bu1;

    @Column(name = "bu2", length = 50)
    private String bu2;

    // Helper method
    public String getDisplayName() {
        return insegna != null && !insegna.isBlank() ? insegna : ragioneSociale;
    }

}
