<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

// Imposta gli header per l’export Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=export_transazioni.xls');

// Prendi tutti i filtri via GET
$startDate     = $_GET['startDate']     ?? null;
$endDate       = $_GET['endDate']       ?? null;
$minAmount     = $_GET['minAmount']     !== '' ? $_GET['minAmount'] : null;
$maxAmount     = $_GET['maxAmount']     !== '' ? $_GET['maxAmount'] : null;
$terminalID    = $_GET['terminalID']    ?? null;
$filterModello = $_GET['filterModello'] ?? null;
$filterInsegna = $_GET['filterInsegna'] ?? null;

// Funzione per ottenere i dati con tutti i filtri
function getTableDataForExport($conn, $startDate, $endDate, $minAmount, $maxAmount, $terminalID, $filterModello, $filterInsegna) {
    $where = " WHERE piva = ? ";
    $params = [ $_SESSION['bu'] ];
    $types  = "s";

    if ($startDate && $endDate) {
        $where    .= " AND dataOperazione BETWEEN ? AND ? ";
        $params[]  = $startDate;
        $params[]  = $endDate;
        $types    .= "ss";
    }
    if ($minAmount !== null) {
        $where    .= " AND importo >= ? ";
        $params[]  = $minAmount;
        $types    .= "d";
    }
    if ($maxAmount !== null) {
        $where    .= " AND importo <= ? ";
        $params[]  = $maxAmount;
        $types    .= "d";
    }
    if ($terminalID) {
        $where    .= " AND terminalID = ? ";
        $params[]  = $terminalID;
        $types    .= "s";
    }
    if ($filterModello) {
        $where    .= " AND Modello_pos = ? ";
        $params[]  = $filterModello;
        $types    .= "s";
    }
    if ($filterInsegna) {
        $where    .= " AND insegna = ? ";
        $params[]  = $filterInsegna;
        $types    .= "s";
    }

    $sql = "SELECT 
                dataOperazione, 
                oraOperazione, 
                terminalID, 
                Modello_pos, 
                pan, 
                tag4f, 
                importo, 
                codiceAutorizzativo, 
                acquirer, 
                insegna, 
                indirizzo
            FROM tracciato_pos_iva"
         . $where;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// Recupera i dati
$result = getTableDataForExport(
    $conn, 
    $startDate, 
    $endDate, 
    $minAmount, 
    $maxAmount, 
    $terminalID, 
    $filterModello, 
    $filterInsegna
);

// Stampa l’header del file Excel (tab-separated)
echo implode("\t", [
    'Data','Ora','Terminale','Modello POS','PAN','Tag4F',
    'Importo','Codice','Acquirer','Insegna','Indirizzo'
]) . "\n";

// Stampa le righe
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo implode("\t", [
            $row['dataOperazione'],
            $row['oraOperazione'],
            $row['terminalID'],
            $row['Modello_pos'],
            $row['pan'],
            $row['tag4f'],
            number_format($row['importo'], 2, ',', '.'),
            $row['codiceAutorizzativo'],
            $row['acquirer'],
            $row['insegna'],
            $row['indirizzo']
        ]) . "\n";
    }
} else {
    echo "Nessun dato trovato\n";
}

$conn->close();
?>
