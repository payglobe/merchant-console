<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/vendor/autoload.php';

use SatispayGBusiness\Api;

header('Content-Type: application/json; charset=utf-8');

function jerr(int $code, string $where, string $msg, array $ctx = []) {
  http_response_code($code);
  $extra = $ctx ? ' ' . json_encode($ctx) : '';
  error_log("[activate_store] {$where}: {$msg}{$extra}");
  echo json_encode(['ok'=>false,'where'=>$where,'error'=>$msg], JSON_PRETTY_PRINT);
  exit;
}

try {
  // Input grezzo (per debug)
  $rawInput = file_get_contents('php://input');
  error_log("[activate_store] raw POST body: " . substr($rawInput, 0, 500));

  $input = $_POST ?: json_decode($rawInput, true);

  $store = trim((string)($input['store'] ?? ''));
  $token = trim((string)($input['token'] ?? ''));
  $env   = strtoupper(trim((string)($input['env'] ?? 'SANDBOX')));

  // Log parametri sanitizzati
  error_log("[activate_store] params => store={$store}, env={$env}, tokenLen=" . strlen($token));

  if ($store === '' || $token === '' || !in_array($env, ['SANDBOX','PROD'], true)) {
    jerr(400, 'input', 'Parametri invalidi (store, token, env)', [
      'store' => $store,
      'env'   => $env,
      'tokenLen' => strlen($token)
    ]);
  }

  // Ambiente prima dell’autenticazione
  Api::setSandbox($env === 'SANDBOX');
  error_log("[activate_store] setSandbox=" . ($env === 'SANDBOX' ? 'true' : 'false'));

  // Test Satispay
  try {
    $auth = Api::authenticateWithToken($token);
    error_log("[activate_store] Autenticazione OK, keyId={$auth->keyId}");
  } catch (Throwable $e) {
    jerr(500, 'satispay', $e->getMessage(), [
      'store' => $store,
      'env'   => $env
    ]);
  }

  // Persistenza
  try {
    KeyStore::upsertKeys($store, $env, $auth->publicKey, $auth->privateKey, $auth->keyId);
    error_log("[activate_store] Chiavi salvate per store={$store}, env={$env}");
  } catch (Throwable $e) {
    jerr(500, 'database', $e->getMessage(), [
      'store' => $store,
      'env'   => $env
    ]);
  }

  echo json_encode([
    'ok'    => true,
    'store' => $store,
    'env'   => $env
  ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  jerr(500, 'fatal', $e->getMessage());
}
