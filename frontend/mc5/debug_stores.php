<?php
require_once 'authentication.php';

$conn = new mysqli('10.10.10.12', 'PGDBUSER', 'PNeNkar{K1.%D~V', 'payglobe');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get table structure
echo "<h2>Struttura tabella 'stores':</h2>";
$result = $conn->query("DESCRIBE stores");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

// Get sample data
echo "<br><h2>Sample data (primi 3 record):</h2>";
$result = $conn->query("SELECT * FROM stores LIMIT 3");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

$conn->close();
?>
