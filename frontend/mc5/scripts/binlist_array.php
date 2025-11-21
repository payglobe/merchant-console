<?php
// BIN list endpoint with ARRAY format
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$servername = "10.10.10.12";
$username = "PGDBUSER";
$password = "PNeNkar{K1.%D~V";
$dbname = "payglobe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Increase limits
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '60');

// Helper function to clean UTF-8
function cleanUTF8($str) {
    if ($str === null) return '';
    $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
    return $str;
}

// Query binlist table
$sql = "SELECT PAN, Circuito, BancaEmettitrice, LivelloCarta, TipoCarta, Nazione
        FROM binlist
        ORDER BY PAN
        LIMIT 100000";

$result = $conn->query($sql);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$data = array();
while ($row = $result->fetch_assoc()) {
    // Return as ARRAY
    $data[] = array(
        $row['PAN'],                              // 0
        cleanUTF8($row['Circuito']),             // 1
        cleanUTF8($row['BancaEmettitrice']),     // 2
        cleanUTF8($row['LivelloCarta']),         // 3
        cleanUTF8($row['TipoCarta']),            // 4
        '',                                       // 5 (empty - no phone)
        '',                                       // 6 (empty - no website)
        '',                                       // 7 (empty - no country code)
        cleanUTF8($row['Nazione'])               // 8
    );
}

header('Content-Type: application/json');
echo json_encode([
    'data' => $data
], JSON_INVALID_UTF8_IGNORE);

$conn->close();
?>
