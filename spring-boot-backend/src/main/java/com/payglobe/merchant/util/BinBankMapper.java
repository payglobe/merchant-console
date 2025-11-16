package com.payglobe.merchant.util;

import java.util.HashMap;
import java.util.Map;

/**
 * Mapper BIN (prime 6 cifre PAN) -> Banca
 *
 * Database BIN delle principali banche italiane ed europee
 */
public class BinBankMapper {

    private static final Map<String, String> BIN_TO_BANK = new HashMap<>();
    private static final Map<String, String> BIN_TO_COUNTRY = new HashMap<>();

    static {
        // BANCHE ITALIANE
        BIN_TO_BANK.put("400115", "Poste Italiane");
        BIN_TO_BANK.put("400124", "Poste Italiane");
        BIN_TO_BANK.put("542927", "Poste Italiane");
        BIN_TO_BANK.put("513226", "Poste Italiane");

        BIN_TO_BANK.put("400593", "Banco BPM");
        BIN_TO_BANK.put("454393", "Banco BPM");
        BIN_TO_BANK.put("543471", "Banco BPM");

        BIN_TO_BANK.put("400594", "Intesa Sanpaolo");
        BIN_TO_BANK.put("492901", "Intesa Sanpaolo");
        BIN_TO_BANK.put("454347", "Intesa Sanpaolo");
        BIN_TO_BANK.put("454617", "Intesa Sanpaolo");
        BIN_TO_BANK.put("542116", "Intesa Sanpaolo");

        BIN_TO_BANK.put("400595", "UniCredit");
        BIN_TO_BANK.put("549530", "UniCredit");
        BIN_TO_BANK.put("454360", "UniCredit");
        BIN_TO_BANK.put("454625", "UniCredit");

        BIN_TO_BANK.put("400596", "Monte dei Paschi");
        BIN_TO_BANK.put("454379", "Monte dei Paschi");
        BIN_TO_BANK.put("549538", "Monte dei Paschi");

        BIN_TO_BANK.put("486493", "Mediolanum");
        BIN_TO_BANK.put("520308", "Mediolanum");

        BIN_TO_BANK.put("543357", "Fineco");
        BIN_TO_BANK.put("454382", "Fineco");

        BIN_TO_BANK.put("400592", "BNL");
        BIN_TO_BANK.put("454824", "BNL");

        BIN_TO_BANK.put("549927", "ING Bank");

        BIN_TO_BANK.put("465922", "CartaSi");
        BIN_TO_BANK.put("465923", "CartaSi");
        BIN_TO_BANK.put("465924", "CartaSi");

        // BANCHE EUROPEE PRINCIPALI
        BIN_TO_BANK.put("417500", "Visa France");
        BIN_TO_BANK.put("434307", "Société Générale");
        BIN_TO_BANK.put("533844", "BNP Paribas");
        BIN_TO_BANK.put("522189", "Credit Agricole");

        BIN_TO_BANK.put("454736", "Commerzbank");
        BIN_TO_BANK.put("549167", "ING Deutschland");

        BIN_TO_BANK.put("516378", "Santander ES");
        BIN_TO_BANK.put("454313", "BBVA");
        BIN_TO_BANK.put("454630", "CaixaBank");

        BIN_TO_BANK.put("533317", "N26");

        // Mapping paese (approssimativo basato su range BIN)
        addCountryMappings();
    }

    private static void addCountryMappings() {
        // Italia
        for (String bin : new String[]{"400115", "400124", "542927", "513226", "400593", "454393",
                                        "400594", "492901", "454347", "454617", "542116", "400595",
                                        "549530", "454360", "454625", "400596", "454379", "549538",
                                        "486493", "520308", "543357", "454382", "400592", "454824",
                                        "549927", "465922", "465923", "465924"}) {
            BIN_TO_COUNTRY.put(bin, "Italia");
        }

        // Francia
        for (String bin : new String[]{"417500", "434307", "533844", "522189"}) {
            BIN_TO_COUNTRY.put(bin, "Francia");
        }

        // Germania
        for (String bin : new String[]{"454736", "549167"}) {
            BIN_TO_COUNTRY.put(bin, "Germania");
        }

        // Spagna
        for (String bin : new String[]{"516378", "454313", "454630"}) {
            BIN_TO_COUNTRY.put(bin, "Spagna");
        }

        // Europa generica
        BIN_TO_COUNTRY.put("533317", "Europa");
    }

    /**
     * Ottieni nome banca da BIN
     */
    public static String getBankName(String bin) {
        if (bin == null || bin.length() < 4) {
            return "Sconosciuta";
        }

        return BIN_TO_BANK.getOrDefault(bin, "Banca " + bin.substring(0, 4) + "**");
    }

    /**
     * Ottieni paese da BIN (approssimativo)
     */
    public static String getCountry(String bin) {
        if (bin == null || bin.length() < 4) {
            return "Altro";
        }

        // Prova lookup esatto
        if (BIN_TO_COUNTRY.containsKey(bin)) {
            return BIN_TO_COUNTRY.get(bin);
        }

        // Fallback su range approssimativo
        String first4 = bin.substring(0, 4);
        int range = Integer.parseInt(first4);

        if (range >= 4001 && range <= 4009) {
            return "Italia";
        } else if (range >= 4970 && range <= 4999) {
            return "Francia";
        } else if (range >= 5132 && range <= 5139) {
            return "Italia";
        } else if (range >= 4000 && range <= 4999) {
            return "Europa/USA";
        } else if (range >= 5000 && range <= 5999) {
            return "Europa";
        }

        return "Altro";
    }
}
