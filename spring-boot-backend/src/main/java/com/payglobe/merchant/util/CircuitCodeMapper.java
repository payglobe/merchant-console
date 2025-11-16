package com.payglobe.merchant.util;

import java.util.HashMap;
import java.util.Map;

/**
 * Mapper per codici circuiti - stesso logic del PHP
 */
public class CircuitCodeMapper {

    private static final Map<String, String> CIRCUIT_NAMES = new HashMap<>();
    private static final Map<String, String> CIRCUIT_COLORS = new HashMap<>();

    static {
        // Codici esistenti del dashboard
        CIRCUIT_NAMES.put("ED", "MasterCard Debit Extra-EEA");
        CIRCUIT_NAMES.put("EC", "MasterCard Debit EEA");
        CIRCUIT_NAMES.put("MBK", "MyBank");
        CIRCUIT_NAMES.put("MC", "MasterCard Credit Extra-EEA");
        CIRCUIT_NAMES.put("MCE", "MasterCard Credit EEA");
        CIRCUIT_NAMES.put("MD", "MasterCard Debit Extra-EEA");
        CIRCUIT_NAMES.put("ME", "MasterCard Debit EEA");
        CIRCUIT_NAMES.put("ML", "MasterCard Commercial Extra-EEA");
        CIRCUIT_NAMES.put("MLE", "MasterCard Commercial EEA");
        CIRCUIT_NAMES.put("MN", "MasterCard Commercial Extra-EEA");
        CIRCUIT_NAMES.put("MNE", "MasterCard Commercial EEA");
        CIRCUIT_NAMES.put("MP", "MasterCard PrePaid EEA");
        CIRCUIT_NAMES.put("PA", "Bancomat");
        CIRCUIT_NAMES.put("PB", "Bancomat");
        CIRCUIT_NAMES.put("PP", "Bancomat");
        CIRCUIT_NAMES.put("VC", "Visa Credit Extra-EEA");
        CIRCUIT_NAMES.put("VCE", "Visa Credit EEA");
        CIRCUIT_NAMES.put("VD", "Visa Debit Extra-EEA");
        CIRCUIT_NAMES.put("VDE", "Visa Debit EEA");
        CIRCUIT_NAMES.put("VL", "Visa Commercial Extra-EEA");
        CIRCUIT_NAMES.put("VLE", "Visa Commercial EEA");
        CIRCUIT_NAMES.put("VN", "Visa Commercial Extra-EEA");
        CIRCUIT_NAMES.put("VNE", "Visa Commercial EEA");
        CIRCUIT_NAMES.put("VP", "Visa PrePaid Extra-EEA");
        CIRCUIT_NAMES.put("VPE", "Visa Debit EEA");
        CIRCUIT_NAMES.put("VR", "Visa PrePaid Extra-EEA");
        CIRCUIT_NAMES.put("VRE", "Visa PrePaid EEA");

        // CODICI FTFS UFFICIALI - Carte domestiche (Italia)
        CIRCUIT_NAMES.put("DAACQU", "Vendita (Bancomat)");
        CIRCUIT_NAMES.put("DSESTO", "Storno Operatore (Bancomat)");
        CIRCUIT_NAMES.put("DSISTO", "Storno Tecnico (Bancomat)");
        CIRCUIT_NAMES.put("DPACQU", "Preautorizzazione (Bancomat)");
        CIRCUIT_NAMES.put("DVACQU", "Verifica Carta (Bancomat)");
        CIRCUIT_NAMES.put("DNACQU", "Avviso Post-Autorizzazione (Bancomat)");

        // CODICI FTFS UFFICIALI - Altre carte
        CIRCUIT_NAMES.put("CAACQU", "Vendita (Sale)");
        CIRCUIT_NAMES.put("CPACQU", "Preautorizzazione");
        CIRCUIT_NAMES.put("CNACQU", "Avviso Post-Autorizzazione");
        CIRCUIT_NAMES.put("CSESTO", "Storno Operatore");
        CIRCUIT_NAMES.put("CSISTO", "Storno Tecnico");
        CIRCUIT_NAMES.put("CXACQU", "Credito GT-PO");
        CIRCUIT_NAMES.put("CXECRE", "Credito E-Commerce");

        // Colori per grafico (RGB)
        CIRCUIT_COLORS.put("PagoBancomat", "rgb(255, 99, 132)");   // Rosso
        CIRCUIT_COLORS.put("Visa", "rgb(54, 162, 235)");            // Blu
        CIRCUIT_COLORS.put("MasterCard", "rgb(255, 205, 86)");      // Giallo
        CIRCUIT_COLORS.put("MyBank", "rgb(75, 192, 192)");          // Verde
        CIRCUIT_COLORS.put("Altre Carte", "rgb(255, 159, 64)");     // Arancione
        CIRCUIT_COLORS.put("Altri", "rgb(153, 102, 255)");          // Viola
    }

    /**
     * Traduce codice circuito in nome leggibile
     */
    public static String translateCircuitCode(String code) {
        if (code == null || code.isBlank()) {
            return code;
        }
        return CIRCUIT_NAMES.getOrDefault(code, code);
    }

    /**
     * Raggruppa codici in categorie per grafico
     */
    public static String getCircuitGroup(String code) {
        if (code == null || code.isBlank()) {
            return "Altri";
        }

        // Se è già un nome di gruppo, ritornalo
        if ("PagoBancomat".equals(code) || "Bancomat".equals(code) || "PAGOBANCOMAT".equals(code)) {
            return "PagoBancomat";
        }

        // Carte domestiche italiane (PagoBancomat)
        String[] pagobancomat = {"PA", "PB", "PP", "DAACQU", "DSESTO", "DSISTO", "DPACQU", "DVACQU", "DNACQU"};
        for (String pb : pagobancomat) {
            if (code.equals(pb)) {
                return "PagoBancomat";
            }
        }

        // Altre carte (codici C- FTFS)
        String[] altreCarteC = {"CAACQU", "CPACQU", "CNACQU", "CSESTO", "CSISTO", "CXACQU", "CXECRE"};
        for (String ac : altreCarteC) {
            if (code.equals(ac)) {
                return "Altre Carte";
            }
        }

        // MasterCard
        if (code.startsWith("M") || code.equals("ED") || code.equals("EC")) {
            return "MasterCard";
        }

        // Visa
        if (code.startsWith("V")) {
            return "Visa";
        }

        // MyBank
        if (code.equals("MBK")) {
            return "MyBank";
        }

        return "Altri (" + code + ")";
    }

    /**
     * Ottiene colore per gruppo circuito (per grafico)
     */
    public static String getCircuitColor(String group) {
        if (group != null && group.startsWith("Altri (")) {
            return CIRCUIT_COLORS.get("Altri");
        }
        return CIRCUIT_COLORS.getOrDefault(group, "rgb(108, 117, 125)");
    }

}
