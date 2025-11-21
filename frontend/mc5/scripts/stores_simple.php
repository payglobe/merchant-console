<?php
// Simple stores endpoint - NO DataTables dependency
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

// Get WHERE clause from URL
$where = isset($_GET['where']) ? $_GET['where'] : "1=1";

// Get pagination parameters
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10000; // Default 10000 records max

// Debug: log the WHERE clause
error_log("WHERE clause: " . $where);

// Increase limits for large dataset
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '60');

// Count total records first
$countSql = "SELECT COUNT(*) as total FROM stores WHERE $where";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];

// Build query with pagination
$sql = "SELECT TerminalID, Ragione_Sociale, Insegna, indirizzo, citta, cap, prov, country
        FROM stores
        WHERE $where
        ORDER BY TerminalID
        LIMIT $start, $length";

$result = $conn->query($sql);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed: ' . $conn->error, 'sql' => $sql]);
    exit;
}

// Helper function to clean UTF-8
function cleanUTF8($str) {
    if ($str === null) return '';
    // Remove invalid UTF-8 characters
    $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
    return $str;
}

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = array(
        'terminalID' => $row['TerminalID'],
        'ragioneSociale' => cleanUTF8($row['Ragione_Sociale']),
        'insegna' => cleanUTF8($row['Insegna']),
        'indirizzo' => cleanUTF8($row['indirizzo']),
        'citta' => cleanUTF8($row['citta']),
        'cap' => $row['cap'],
        'prov' => $row['prov'],
        'country' => $row['country']
    );
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'total' => $totalRecords,
    'loaded' => count($data),
    'start' => $start,
    'length' => $length,
    'data' => $data
], JSON_INVALID_UTF8_IGNORE);

$conn->close();
?>
