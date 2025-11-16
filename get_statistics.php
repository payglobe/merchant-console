<?php
include 'config.php';

// Funzione per ottenere le statistiche
function getStatistics($conn, $startDate, $endDate) {
    $sql = "SELECT COUNT(*) AS total_transactions, SUM(importo) AS total_amount FROM tracciato_pos_iva WHERE dataOperazione BETWEEN ? AND ? WHERE piva = ?";

    $piva = [$_SESSION['bu']]; // Add piva to parameters
   

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $startDate, $endDate,$piva);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return [
        'totalTransactions' => $row['total_transactions'],
        'totalAmount' => number_format($row['total_amount'], 2, ',', '.') . " €"
    ];
}

// Recupera i parametri
$year = isset($_GET['year']) ? $_GET['year'] : date("Y");
$month = isset($_GET['month']) ? $_GET['month'] : date("m");
$day = isset($_GET['day']) ? $_GET['day'] : date("d");

// Calcola le date di inizio e fine
$startDate = "$year-$month-$day";
$endDate = "$year-$month-$day";

// Ottieni le statistiche
$statistics = getStatistics($conn, $startDate, $endDate);

// Invia la risposta JSON
header('Content-Type: application/json');
echo json_encode($statistics);

$conn->close();
?>