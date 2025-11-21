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
    // Export transactions
    $sql = "SELECT codificaStab, terminalID, Modello_pos, domestico, pan, tag4f,
                   dataOperazione, oraOperazione, importo, codiceAutorizzativo,
                   acquirer, flagLog, actinCode, insegna, localita
            FROM tracciato_pos
            WHERE $where
            ORDER BY dataOperazione DESC, oraOperazione DESC
            LIMIT 50000";

    // Headers
    fputcsv($output, ['Codifica Stab', 'Terminal ID', 'Modello POS', 'Domestico', 'PAN', 'Tag 4F',
                      'Data Operazione', 'Ora Operazione', 'Importo', 'Codice Autorizzativo',
                      'Acquirer', 'Flag Log', 'Actin Code', 'Insegna', 'Località']);

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
