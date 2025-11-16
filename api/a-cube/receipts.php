<?php
declare(strict_types=1);

require_once __DIR__ . '/src/TokenProvider.php';
require_once __DIR__ . '/src/AcubeClient.php';
require_once __DIR__ . '/src/Db.php';
require_once __DIR__ . '/src/ReceiptStore.php';

$config = require __DIR__ . '/config.php';

// === CORS (se non lo gestisci da Apache) ===
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type,X-Idempotency-Key,X-App-Signature,X-Terminal-Id');
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
$client        = new AcubeClient($config['ACUBE_BASE_URL'], $tokenProvider);

// === STORE (opzionale) ===
$store = null;
if (!empty($config['SAVE_RECEIPTS'])) {
    try {
        $pdo   = Db::fromConfig($config);
        $table = (string)($config['RECEIPTS_TABLE'] ?? 'serverpos.receipts');
        $store = new ReceiptStore($pdo, $table);
    } catch (\Throwable $e) {
        error_log('[RECEIPTS] DB init failed: ' . $e->getMessage());
    }
}

// ------- routing -------

// ========== CREATE RECEIPT ==========
if ($method === 'POST' && $rel === '/receipts') {
    $raw = file_get_contents('php://input') ?: '';
    $sig = $_SERVER['HTTP_X_APP_SIGNATURE'] ?? '';
    $idk = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;
    //$terminalId = $_SERVER['HTTP_X_TERMINAL_ID'] ?? null;
    $terminalId = $_SERVER['HTTP_X_TERMINAL_ID'] ?? (($in['meta']['terminalId'] ?? $in['payment']['terminalId'] ?? $in['terminalId'] ?? null));
    $bodySha = hash('sha256', $raw);

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
        'order_id'  => $in['order_id'] ?? null,
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

    // eventuale blocco "payment" passato dall'app (mascherato)
    $paymentInfo = isset($in['payment']) && is_array($in['payment']) ? $in['payment'] : null;

    // controlli basilari
    $paid = $payload['cash_payment_amount'] + $payload['electronic_payment_amount'];
    if (round($paid, 2) <= 0) jsonOut(400, ['ok'=>false,'error'=>'payments must be > 0']);

    try {
        $created = $client->sendReceipt($payload, $idk);

        // SALVA (SALE OK)
        if ($store) {
            try {
                $store->saveSaleOk($terminalId, $idk, $payload, $created, $paymentInfo, $bodySha);
            } catch (\Throwable $e) {
                error_log('[RECEIPTS] saveSaleOk failed: ' . $e->getMessage());
            }
        }

        jsonOut(201, ['ok'=>true,'receipt'=>$created]);
    } catch (RuntimeException $e) {
        // SALVA (SALE KO)
        if ($store) {
            try {
                $err = [
                    'http' => $e->getCode() ?: null,
                    'message' => $e->getMessage(),
                    // se in futuro AcubeClient espone title/detail/violations separati, aggiungili qui
                ];
                $store->saveSaleKo($terminalId, $idk, $payload, $err, $paymentInfo, $bodySha);
            } catch (\Throwable $e2) {
                error_log('[RECEIPTS] saveSaleKo failed: ' . $e2->getMessage());
            }
        }
        $code = $e->getCode() ?: 502;
        jsonOut($code, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

// ========== DETAILS ==========
if ($method === 'GET' && preg_match('#^/receipts/([a-f0-9-]{8,})$#i', $rel, $m)) {
    try {
        $data = $client->getReceiptDetails($m[1]);
        jsonOut(200, ['ok'=>true,'receipt'=>$data]);
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

// ========== PDF ==========
if ($method === 'GET' && preg_match('#^/receipts/([a-f0-9-]{8,})/pdf$#i', $rel, $m)) {
    try {
        $pdf = $client->getReceiptPdf($m[1]);
        header('Content-Type: application/pdf');
        echo $pdf; exit;
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

// ========== RETURN ==========
if ($method === 'POST' && preg_match('#^/receipts/([a-f0-9-]{8,})/return$#i', $rel, $m)) {
    $raw = file_get_contents('php://input') ?: '';
    $sig = $_SERVER['HTTP_X_APP_SIGNATURE'] ?? '';
    $terminalId = $_SERVER['HTTP_X_TERMINAL_ID'] ?? null;

    if (!$sig || !verify_signature($raw, $sig, $APP_SECRET)) {
        jsonOut(401, ['ok'=>false,'error'=>'Invalid signature']);
    }
    $in = json_decode($raw, true);
    $items = $in['items'] ?? null;
    if (!$items || !is_array($items)) jsonOut(400, ['ok'=>false,'error'=>'items[] required']);

    try {
        $res = $client->returnItems($m[1], $items);

        // SALVA (RETURN OK)
        if ($store) {
            try {
                $store->saveReturnOk($terminalId, $m[1], $items, $res);
            } catch (\Throwable $e) {
                error_log('[RECEIPTS] saveReturnOk failed: ' . $e->getMessage());
            }
        }

        jsonOut(201, ['ok'=>true,'receipt'=>$res]);
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

// ========== VOID ==========
if ($method === 'DELETE' && preg_match('#^/receipts/([a-f0-9-]{8,})$#i', $rel, $m)) {
    $terminalId = $_SERVER['HTTP_X_TERMINAL_ID'] ?? null;

    try {
        $client->voidReceipt($m[1]);

        // SALVA (VOID OK)
        if ($store) {
            try {
                $store->saveVoidOk($terminalId, $m[1], /*resp*/null);
            } catch (\Throwable $e) {
                error_log('[RECEIPTS] saveVoidOk failed: ' . $e->getMessage());
            }
        }

        jsonOut(204, []);
    } catch (RuntimeException $e) {
        jsonOut($e->getCode() ?: 502, ['ok'=>false,'error'=>$e->getMessage()]);
    }
}

jsonOut(404, ['ok'=>false,'error'=>'Not found']);
