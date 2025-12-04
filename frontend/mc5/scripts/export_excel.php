<?php
// Export data to Excel (CSV format)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export_' . date('Y-m-d_His') . '.csv"');

// DB connection
$servername = "10.10.10.12";
$username = "PGDBUSER";
$password = "PNeNkar{K1.%D~V";
$dbname = "payglobe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get parameters
$table = isset($_GET['table']) ? $_GET['table'] : 'stores';
$where = isset($_GET['where']) ? $_GET['where'] : "1=1";

// Output stream
$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Different queries based on table
if ($table === 'stores') {
    // Export stores
    $sql = "SELECT TerminalID, Ragione_Sociale, Insegna, indirizzo, citta, cap, prov, country
            FROM stores
            WHERE $where
            ORDER BY TerminalID";

    // Headers
    fputcsv($output, ['Terminal ID', 'Ragione Sociale', 'Insegna', 'Indirizzo', 'Città', 'CAP', 'Provincia', 'Paese']);

} elseif ($table === 'tracciato_pos') {
    // Export transactions - EXCLUDE e-Commerce (PV and SDT are NOT e-Commerce!)
    $sql = "SELECT codificaStab, terminalID, Modello_pos, domestico, pan, tag4f,
                   dataOperazione, oraOperazione, importo, codiceAutorizzativo,
                   acquirer, flagLog, actinCode, insegna, localita
            FROM tracciato_pos
            WHERE $where AND (tipoOperazione IS NULL OR tipoOperazione <> 'e-Commerce')
            ORDER BY dataOperazione DESC, oraOperazione DESC
            LIMIT 50000";

    // Headers
    fputcsv($output, ['Codifica Stab', 'Terminal ID', 'Modello POS', 'Domestico', 'PAN', 'Tag 4F',
                      'Data Operazione', 'Ora Operazione', 'Importo', 'Codice Autorizzativo',
                      'Acquirer', 'Flag Log', 'Actin Code', 'Insegna', 'Località']);

} elseif ($table === 'tracciato_ecommerce') {
    // Export E-COMMERCE transactions - SAME FORMAT AS MC4 tracciato.html
    // Include ALL fields from MC4: Mid, Pan, Data, Ora, Importo, Codice AZ, Canale,
    // Insegna, Cap, Localita, Provincia, Negozio (RagioneSociale), Indirizzo, Acquirer, Brand, OrderID, Nome (email)
    // Plus: PayByLink indicator for transactions
    $sql = "SELECT
                   t.codificaStab,
                   t.tipoRiep as tipoOperazioneDettaglio,
                   t.pan,
                   DATE_FORMAT(t.dataOperazione, '%d/%m/%Y') as dataOperazione,
                   TIME_FORMAT(t.oraOperazione, '%H:%i:%s') as oraOperazione,
                   t.importo,
                   t.codiceAutorizzativo,
                   t.tipoOperazione,
                   t.flagLog,
                   t.actinCode,
                   COALESCE(t.insegna, s.Insegna, '') as insegna,
                   COALESCE(t.cap, s.cap, '') as cap,
                   COALESCE(t.localita, s.citta, '') as localita,
                   COALESCE(t.provincia, s.prov, '') as provincia,
                   COALESCE(t.ragioneSociale, s.Ragione_Sociale, '') as ragioneSociale,
                   COALESCE(t.indirizzo, s.indirizzo, '') as indirizzo,
                   t.acquirer,
                   t.tag4f as brand,
                   COALESCE(t.orderID, '') as orderID,
                   UPPER(COALESCE(t.cardholdername, '')) as cardholdername,
                   CASE
                       WHEN t.codificaStab = 'MC_IRIS_A2P' THEN 'Pay By Link'
                       WHEN t.codificaStab LIKE '%PBL%' THEN 'Pay By Link'
                       WHEN t.codificaStab LIKE '%PAYBYLINK%' THEN 'Pay By Link'
                       ELSE 'e-Commerce'
                   END as tipoTransazione
            FROM tracciato t
            LEFT JOIN stores s ON s.TerminalID = t.terminalID
            WHERE $where
            ORDER BY t.dataOperazione DESC, t.oraOperazione DESC
            LIMIT 100000";

    // Headers - same as MC4 + PayByLink indicator
    fputcsv($output, ['MID', 'Operazione', 'PAN', 'Data', 'Ora', 'Importo', 'Codice Aut.',
                      'Canale', 'Flag', 'Esito', 'Insegna', 'CAP', 'Località', 'Provincia',
                      'Negozio', 'Indirizzo', 'Acquirer', 'Circuito', 'Order ID', 'Nome Cliente', 'Tipo']);

} elseif ($table === 'tracciato_pos_es') {
    // Export transactions SPAIN (from tracciato_pos_es VIEW with stores JOIN)
    $sql = "SELECT codificaStab, terminalID, Modello_pos, domestico, pan, tag4f,
                   dataOperazione, oraOperazione, importo, codiceAutorizzativo,
                   acquirer, flagLog, actinCode, insegna, Ragione_Sociale,
                   indirizzo, localita, prov, cap
            FROM tracciato_pos_es
            WHERE $where
            ORDER BY dataOperazione DESC, oraOperazione DESC
            LIMIT 50000";

    // Headers
    fputcsv($output, ['Codifica Stab', 'Terminal ID', 'Modello POS', 'Domestico', 'PAN', 'Tag 4F',
                      'Data Operazione', 'Ora Operazione', 'Importo', 'Codice Autorizzativo',
                      'Acquirer', 'Flag Log', 'Actin Code', 'Insegna', 'Ragione Sociale',
                      'Indirizzo', 'Città', 'Provincia', 'CAP']);

} elseif ($table === 'scarti') {
    // Export rejected transactions
    $sql = "SELECT codificaStab, terminalID, tipoRiep, domestico, pan,
                   dataOperazione, oraOperazione, importo, codiceAutorizzativo,
                   flagLog, actinCode, insegna, localita
            FROM scarti
            WHERE $where
            ORDER BY dataOperazione DESC, oraOperazione DESC
            LIMIT 50000";

    // Headers
    fputcsv($output, ['Codifica Stab', 'Terminal ID', 'Tipo Riep', 'Domestico', 'PAN',
                      'Data Operazione', 'Ora Operazione', 'Importo', 'Codice Autorizzativo',
                      'Flag Log', 'Actin Code', 'Insegna', 'Località']);

} elseif ($table === 'binlist') {
    // Export BIN list
    $sql = "SELECT PAN, Circuito, BancaEmettitrice, LivelloCarta, TipoCarta, Nazione
            FROM binlist
            ORDER BY PAN
            LIMIT 100000";

    // Headers
    fputcsv($output, ['PAN/BIN', 'Circuito', 'Banca Emettitrice', 'Livello Carta', 'Tipo Carta', 'Nazione']);
}

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_row()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$conn->close();
?>
