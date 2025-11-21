<?php
// Tracciato endpoint for SPAIN (ES) with ARRAY format
error_reporting(0);
ini_set('display_errors', 0);

// Increase limits BEFORE any processing - for VERY large datasets (2+ months)
ini_set('memory_limit', '2G');
ini_set('max_execution_time', '300');
set_time_limit(300);

// DB connection
$servername = "10.10.10.12";
$username = "PGDBUSER";
$password = "PNeNkar{K1.%D~V";
$dbname = "payglobe";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Get WHERE clause from URL
    $where = isset($_GET['where']) ? $_GET['where'] : "1=1";

// Helper function to clean UTF-8
function cleanUTF8($str) {
    if ($str === null) return '';
    $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
    return $str;
}

    // Query tracciato_pos_es VIEW (already has JOIN with stores) - return as ARRAY format
    $sql = "SELECT codificaStab, terminalID, Modello_pos, domestico, pan, tag4f,
                   dataOperazione, oraOperazione, importo, codiceAutorizzativo,
                   acquirer, flagLog, actinCode,
                   insegna, Ragione_Sociale, indirizzo, localita, prov, cap
            FROM tracciato_pos_es
            WHERE $where
            ORDER BY dataOperazione DESC, oraOperazione DESC
            LIMIT 200000";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        // Return as ARRAY (not object) for backward compatibility
        $data[] = array(
            cleanUTF8($row['codificaStab']),      // 0
            $row['terminalID'],                    // 1
            cleanUTF8($row['Modello_pos']),       // 2
            cleanUTF8($row['domestico']),         // 3
            $row['pan'],                           // 4
            $row['tag4f'],                         // 5
            $row['dataOperazione'],                // 6
            $row['oraOperazione'],                 // 7
            $row['importo'],                       // 8
            $row['codiceAutorizzativo'],          // 9
            cleanUTF8($row['acquirer']),          // 10
            $row['flagLog'],                       // 11
            $row['actinCode'],                     // 12
            '',                                    // 13 - tipoOperazione (not in VIEW)
            cleanUTF8($row['insegna']),           // 14 - insegna from VIEW
            cleanUTF8($row['Ragione_Sociale']),   // 15 - ragione sociale from VIEW
            cleanUTF8($row['indirizzo']),         // 16 - indirizzo from VIEW
            cleanUTF8($row['localita']),          // 17 - città from VIEW (stores.citta)
            cleanUTF8($row['prov']),              // 18 - provenienza (PV/SDT) from VIEW
            cleanUTF8($row['cap'])                // 19 - CAP from VIEW
        );
    }

    header('Content-Type: application/json');
    echo json_encode([
        'data' => $data
    ], JSON_INVALID_UTF8_IGNORE);

    $conn->close();

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'records' => isset($data) ? count($data) : 0
    ]);
}
?>
