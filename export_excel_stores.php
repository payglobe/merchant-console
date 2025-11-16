<?php
include 'config.php';

// Set headers for Excel export
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=export_stores.xls');

// Function to fetch data for export
function getStoresDataForExport($conn) {
    $sql = "SELECT `stores`.`TerminalID`,
                `stores`.`Ragione_Sociale`,
                `stores`.`Insegna`,
                `stores`.`indirizzo`,
                `stores`.`citta`,
                `stores`.`cap`,
                `stores`.`prov`,
                `stores`.`Modello_pos`,
                `stores`.`country`
            FROM `payglobe`.`stores`";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

// Fetch data using the function
$result = getStoresDataForExport($conn);

// Output headers for the Excel file
echo "Terminal ID\tRagione Sociale\tInsegna\tIndirizzo\tCittà\tCAP\tProvincia\tModello POS\tPaese\n";

// Output data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row["TerminalID"] . "\t";
        echo $row["Ragione_Sociale"] . "\t";
        echo $row["Insegna"] . "\t";
        echo $row["indirizzo"] . "\t";
        echo $row["citta"] . "\t";
        echo $row["cap"] . "\t";
        echo $row["prov"] . "\t";
        echo $row["Modello_pos"] . "\t";
        echo $row["country"] . "\n";
    }
} else {
    echo "Nessun dato trovato\n";
}

$conn->close();
?>

