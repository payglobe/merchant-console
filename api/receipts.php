<?php
declare(strict_types=1);

require_once __DIR__ . '/src/TokenProvider.php';
require_once __DIR__ . '/src/AcubeClient.php';

$config = require __DIR__ . '/config.php';

// === CORS (se non lo gestisci da Apache) ===
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type,X-Idempotency-Key,X-App-Signature');
header('Access-Control-Allow-Methods: GET,POST,DELETE,OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204); exit;
}

// === VARIABILI DA CONFIG ===
$APP_SECRET = (string)($config['APP_SHARED_SECRET'] ?? '');
$ALLOWLIST  = $config['ALLOWED_FISCAL_IDS'] ?? null;

if ($APP_SECRET === '') {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'APP_SHARED_SECRET missing in config.php']);
    exit;
}

// === HELPER ===
function jsonOut(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

function verify_signature(string $rawBody, string $headerSig, string $secret): bool {
    $calc = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
    return hash_equals($calc, $headerSig);
}

// === ROUTING BASE ===
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$base   = '/merchant/api/a-cube';
$rel    = substr($path, strlen($base));

// === CLIENT A-CUBE ===
$tokenProvider = new TokenProvider($config);
$client = new AcubeClient($config['ACUBE_BASE_URL'], $tokenProvider);

// ------- routing -------
if ($method === 'POST' && $rel === '/receipts') {
    $raw = file_get_contents('php://input') ?: '';
    $sig = $_SERVER['HTTP_X_APP_SIGNATURE'] ?? '';
    $idk = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? '';
    $bodySha = hash('sha256', $raw);

    // 🔎 LOG: cosa arriva dall’app
    error_log("[APP→WRAP] POST $rel Idk=$idk Sig=" . substr($sig,0,12) . "… BodySHA256=$bodySha");
    error_log("[APP→WRAP] Body=" . $raw);
    if (!$sig || !verify_signature($raw, $sig, $APP_SECRET)) {
        jsonOut(401, ['ok'=>false,'error'=>'Invalid signature']);
    }

    $in = json_decode($raw, true);
    if (!is_array($in)) jsonOut(400, ['ok'=>false,'error'=>'Invalid JSON']);

    // fiscal_id OBBLIGATORIO dal body
    $fiscalId = trim((string)($in['fiscal_id'] ?? ''));
    if ($fiscalId === '') jsonOut(400, ['ok'=>false,'error'=>'fiscal_id is required']);
    if ($ALLOWLIST && !in_array($fiscalId, $ALLOWLIST, true)) {
        jsonOut(403, ['ok'=>false,'error'=>'fiscal_id not allowed']);
    }

    // items
    $items = $in['items'] ?? [];
    if (!$items || !is_array($items)) jsonOut(400, ['ok'=>false,'error'=>'items[] required']);

    // payments
    $payments = $in['payments'] ?? ['cash'=>0,'electronic'=>0];
    $discount = (float)($in['discount'] ?? 0.0);

    // map to A-Cube payload
    $payload = [
        'fiscal_id' => $fiscalId,
        'items' => array_map(function($r){
            return [
                'quantity'      => (float)$r['quantity'],
                'description'   => (string)$r['description'],
                'unit_price'    => (float)$r['unit_price'],
                'vat_rate_code' => (string)$r['vat_rate_code'],
                'discount'      => isset($r['discount']) ? (float)$r['discount'] : 0.0,
            ];
        }, $items),
        'cash_payment_amount'        => (float)($payments['cash'] ?? 0.0),
        'electronic_payment_amount'  => (float)($payments['electronic'] ?? 0.0),
        'discount'                   => $discount,
    ];

    // controlli basilari
    $paid = $payload['cash_payment_amount'] + $payload['electronic_payment_amount'];
    if (round($paid, 2) <= 0) jsonOut(400, ['ok'=>false,'error'=>'payments must be > 0']);

    $idk = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;

    try {
        $created = $client->sendReceipt($payload, $idk);
        jsonOut(201, ['ok'=>true,'receipt'=>$created]);
    } catch (RuntimeException $e) {
        $code = $e->getCode() ?: 502;
        jsonOut($code, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

if ($method === 'GET' && preg_match('#^/receipts/([a-f0-9-]{8,})$#i', $rel, $m)) {
    try {
        $data = $client->getReceiptDetails($m[1]);
        jsonOut(200, ['ok'=>true,'receipt'=>$data]);
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

if ($method === 'GET' && preg_match('#^/receipts/([a-f0-9-]{8,})/pdf$#i', $rel, $m)) {
    try {
        $pdf = $client->getReceiptPdf($m[1]);
        header('Content-Type: application/pdf');
        echo $pdf; exit;
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

if ($method === 'POST' && preg_match('#^/receipts/([a-f0-9-]{8,})/return$#i', $rel, $m)) {
    $raw = file_get_contents('php://input') ?: '';
    $sig = $_SERVER['HTTP_X_APP_SIGNATURE'] ?? '';
    if (!$sig || !verify_signature($raw, $sig, $APP_SECRET)) {
        jsonOut(401, ['ok'=>false,'error'=>'Invalid signature']);
    }
    $in = json_decode($raw, true);
    $items = $in['items'] ?? null;
    if (!$items || !is_array($items)) jsonOut(400, ['ok'=>false,'error'=>'items[] required']);
    try {
        $res = $client->returnItems($m[1], $items);
        jsonOut(201, ['ok'=>true,'receipt'=>$res]);
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

if ($method === 'DELETE' && preg_match('#^/receipts/([a-f0-9-]{8,})$#i', $rel, $m)) {
    try {
        $client->voidReceipt($m[1]);
        jsonOut(204, []);
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

jsonOut(404, ['ok'=>false,'error'=>'Not found']);

