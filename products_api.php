<?php
// products_api.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__.'/config.php'; // espone $conn

$tid = $_GET['tid'] ?? '';
if (!preg_match('/^\d{6,12}$/', $tid)) {
    http_response_code(400);
    echo json_encode(['error'=>'tid mancante o non valido (6–12 cifre)']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, terminal_id AS terminalId, name, price_cents AS priceCents, vat_percent AS vatPercent
     FROM products
     WHERE terminal_id = ?
     ORDER BY name"
);
$stmt->bind_param('s', $tid);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) { $items[] = $row; }
$stmt->close();

echo json_encode(['terminalId'=>$tid, 'count'=>count($items), 'items'=>$items], JSON_UNESCAPED_UNICODE);
