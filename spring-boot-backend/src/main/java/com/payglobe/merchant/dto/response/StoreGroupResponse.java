package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

/**
 * DTO per stores raggruppati per punto vendita
 */
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class StoreGroupResponse {

    private String terminalIds;      // Lista di TerminalID separati da virgola
    private String insegna;
    private String ragioneSociale;
    private String indirizzo;
    private String citta;
    private Integer terminalCount;   // Numero di terminali nel gruppo

}
