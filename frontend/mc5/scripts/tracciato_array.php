<?php
// Tracciato endpoint with ARRAY format (for backward compatibility)
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

    // Query tracciato table with LEFT JOIN stores (like tracciato_pos VIEW)
    $sql = "SELECT t.codificaStab, t.terminalID, COALESCE(s.Modello_pos, '') as Modello_pos,
                   t.domestico, t.pan, t.tag4f,
                   t.dataOperazione, t.oraOperazione, t.importo, t.codiceAutorizzativo,
                   t.acquirer, t.flagLog, t.actinCode, t.tipoOperazione,
                   COALESCE(s.Insegna, '') as insegna,
                   COALESCE(s.Ragione_Sociale, '') as Ragione_Sociale,
                   COALESCE(s.indirizzo, '') as indirizzo,
                   COALESCE(s.citta, '') as localita,
                   COALESCE(s.prov, '') as prov,
                   COALESCE(s.cap, '') as cap
            FROM tracciato t
            LEFT JOIN stores s ON s.TerminalID = t.terminalID
            WHERE ($where) AND t.tipoOperazione <> 'e-Commerce'
            ORDER BY t.dataOperazione DESC, t.oraOperazione DESC
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
            cleanUTF8($row['tipoOperazione']),    // 13 - tipoOperazione from tracciato
            cleanUTF8($row['insegna']),           // 14 - insegna from stores
            cleanUTF8($row['Ragione_Sociale']),   // 15 - ragione sociale from stores
            cleanUTF8($row['indirizzo']),         // 16 - indirizzo from stores
            cleanUTF8($row['localita']),          // 17 - città from stores
            cleanUTF8($row['prov']),              // 18 - provenienza (PV/SDT) from stores
            cleanUTF8($row['cap'])                // 19 - CAP from stores
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
