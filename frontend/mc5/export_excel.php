<?php
require_once 'authentication.php';
require_once("../conf.php");

if (!($role=="Admin")) {
  echo "Non Hai i permessi per accedere";
  die();
}

// Get query parameter (WHERE clause from tutte-5.php)
$where = $_GET['q'] ?? '';

// Database connection
$conn = new mysqli('10.10.10.12', 'PGDBUSER', 'PNeNkar{K1.%D~V', 'payglobe');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set headers for Excel export
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename=transazioni_negozio_' . date('Y-m-d') . '.xls');

// Build SQL query using VIEW tracciato_pos (already has JOIN with stores)
$sql = "SELECT
    codificaStab,
    terminalID,
    Modello_pos,
    domestico,
    pan,
    tag4f,
    dataOperazione,
    oraOperazione,
    importo,
    codiceAutorizzativo,
    acquirer,
    flagLog,
    actinCode,
    tipoOperazione,
    insegna,
    Ragione_Sociale,
    indirizzo,
    localita,
    prov,
    cap
FROM tracciato_pos
WHERE $where
ORDER BY dataOperazione DESC, oraOperazione DESC";

$result = $conn->query($sql);

// Print Excel header (tab-separated)
echo implode("\t", [
    'Codifica Stab',
    'Terminal ID',
    'Modello POS',
    'Domestico',
    'PAN',
    'Circuito',
    'Data',
    'Ora',
    'Importo',
    'Cod. Autorizz.',
    'Acquirer',
    'Flag Log',
    'Actin Code',
    'Tipo Operazione',
    'Insegna',
    'Ragione Sociale',
    'Indirizzo',
    'Città',
    'Provenienza',
    'CAP'
]) . "\n";

// Print rows
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo implode("\t", [
            $row['codificaStab'] ?? '',
            $row['terminalID'] ?? '',
            $row['Modello_pos'] ?? '',
            $row['domestico'] ?? '',
            $row['pan'] ?? '',
            $row['tag4f'] ?? '',
            $row['dataOperazione'] ?? '',
            $row['oraOperazione'] ?? '',
            number_format($row['importo'] ?? 0, 2, ',', '.'),
            $row['codiceAutorizzativo'] ?? '',
            $row['acquirer'] ?? '',
            $row['flagLog'] ?? '',
            $row['actinCode'] ?? '',
            $row['tipoOperazione'] ?? '',
            $row['insegna'] ?? '',
            $row['Ragione_Sociale'] ?? '',
            $row['indirizzo'] ?? '',
            $row['localita'] ?? '',
            $row['prov'] ?? '',
            $row['cap'] ?? ''
        ]) . "\n";
    }
} else {
    echo "Nessun dato trovato\n";
}

$conn->close();
?>
