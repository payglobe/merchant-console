<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

// Imposta gli header per l'export Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transazioni_' . date('Y-m-d_His') . '.csv');

// Output UTF-8 BOM per Excel
echo "\xEF\xBB\xBF";

// Controllo BU
$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');

// Prendi i filtri dalla sessione o GET
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-1 day'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');
$filterStore = $_GET['filterStore'] ?? '';

// Costruisci query
$whereClause = "WHERE DATE(transaction_date) BETWEEN ? AND ?";
$params = [$startDate, $endDate];
$types = "ss";

if ($filterStore && !$isAdmin) {
    $whereClause .= " AND s.TerminalID = ?";
    $params[] = $filterStore;
    $types .= "s";
} elseif (!$isAdmin) {
    $whereClause .= " AND s.bu = ?";
    $params[] = $userBU;
    $types .= "s";
}

// Query completa
if ($isAdmin) {
    $query = "SELECT t.*, s.Insegna, s.Ragione_Sociale
              FROM transactions t
              LEFT JOIN stores s ON t.posid = s.TerminalID
              $whereClause
              ORDER BY t.transaction_date DESC";
} else {
    $query = "SELECT t.*, s.Insegna, s.Ragione_Sociale
              FROM transactions t
              INNER JOIN stores s ON t.posid = s.TerminalID
              $whereClause
              ORDER BY t.transaction_date DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Header CSV
$output = fopen('php://output', 'w');
fputcsv($output, [
    'Data/Ora',
    'POSID',
    'Store',
    'Tipo Transazione',
    'Importo (€)',
    'PAN',
    'Circuito',
    'Settlement',
    'Codice Esito',
    'Descrizione Esito',
    'Authorization Code',
    'RRN',
    'Stan'
]);

// Funzione per tradurre response code (versione semplificata)
function getResponseDescription($code) {
    $codes = [
        '000' => 'Approvato',
        '100' => 'Non autorizzato',
        '101' => 'Carta scaduta',
        '102' => 'Sospetta frode',
        '116' => 'Fondi insufficienti',
        '117' => 'PIN errato',
        '119' => 'Transazione non permessa al titolare',
        '120' => 'Transazione non permessa al terminale',
        '121' => 'Superato limite prelievo',
    ];
    return $codes[$code] ?? '';
}

// Dati
while ($row = $result->fetch_assoc()) {
    $amount = $row['amount'];

    // Gestione storni (importo negativo)
    if (in_array($row['transaction_type'], ['DSESTO', 'DSISTO', 'CSESTO', 'CSISTO'])) {
        $amount = -$amount;
    }

    $responseCode = $row['response_code'] ?: $row['ib_response_code'] ?: '';

    fputcsv($output, [
        date('d/m/Y H:i:s', strtotime($row['transaction_date'])),
        $row['posid'],
        $row['Insegna'] ?: $row['Ragione_Sociale'] ?: 'N/A',
        $row['transaction_type'],
        number_format($amount, 2, ',', '.'),
        $row['pan'],
        $row['card_brand'],
        $row['settlement_flag'] == '1' ? 'OK' : 'NO',
        $responseCode,
        getResponseDescription($responseCode),
        $row['authorization_code'] ?? '',
        $row['rrn'] ?? '',
        $row['stan'] ?? ''
    ]);
}

fclose($output);
$stmt->close();
$conn->close();
