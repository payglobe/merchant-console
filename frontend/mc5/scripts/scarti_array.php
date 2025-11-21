<?php
// Scarti endpoint with ARRAY format
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

// Increase limits
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '60');

// Helper function to clean UTF-8
function cleanUTF8($str) {
    if ($str === null) return '';
    $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
    return $str;
}

// Query scarti table
$sql = "SELECT codificaStab, terminalID, tipoRiep, domestico, pan,
               dataOperazione, oraOperazione, importo, codiceAutorizzativo,
               flagLog, actinCode, insegna, localita
        FROM scarti
        WHERE $where
        ORDER BY dataOperazione DESC, oraOperazione DESC
        LIMIT 10000";

$result = $conn->query($sql);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$data = array();
while ($row = $result->fetch_assoc()) {
    // Return as ARRAY matching expected format
    $data[] = array(
        cleanUTF8($row['codificaStab']),  // 0
        $row['terminalID'],                // 1
        cleanUTF8($row['tipoRiep']),      // 2 (as modelloPos)
        cleanUTF8($row['domestico']),     // 3
        $row['pan'],                       // 4
        '',                                // 5 (tag4f - empty)
        $row['dataOperazione'],            // 6
        $row['oraOperazione'],             // 7
        $row['importo'],                   // 8
        $row['codiceAutorizzativo'],      // 9
        '',                                // 10 (acquirer - empty)
        $row['flagLog'],                   // 11
        $row['actinCode'],                 // 12
        cleanUTF8($row['insegna']),       // 13
        cleanUTF8($row['localita'])       // 14
    );
}

header('Content-Type: application/json');
echo json_encode([
    'data' => $data
], JSON_INVALID_UTF8_IGNORE);

$conn->close();
?>
