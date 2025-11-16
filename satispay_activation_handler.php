<?php
/**
 * Satispay Activation Handler
 * Gestisce l'attivazione di Satispay per un terminale specifico
 */

// Imposta il Content-Type SUBITO per evitare problemi con output HTML
header('Content-Type: application/json; charset=utf-8');

// Log degli errori
error_log("[satispay_activation] Request started");

session_start();
if (!isset($_SESSION['username'])) {
    error_log("[satispay_activation] Session not found or username missing");
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Non autenticato']);
    exit();
}

error_log("[satispay_activation] User authenticated: " . $_SESSION['username']);

try {
    include 'config.php';
} catch (Exception $e) {
    error_log("[satispay_activation] Config include error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Errore configurazione']);
    exit();
}

error_log("[satispay_activation] Request method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("[satispay_activation] Invalid method");
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo non consentito']);
    exit();
}

$rawInput = file_get_contents('php://input');
error_log("[satispay_activation] Raw input: " . substr($rawInput, 0, 200));

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("[satispay_activation] JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON non valido']);
    exit();
}

error_log("[satispay_activation] Input decoded successfully");

$terminalId = trim($input['terminalId'] ?? '');
$token = trim($input['token'] ?? '');
$environment = strtoupper(trim($input['environment'] ?? 'SANDBOX'));

error_log("[satispay_activation] Params - terminalId: $terminalId, env: $environment, tokenLen: " . strlen($token));

// Validazione input
if (empty($terminalId)) {
    error_log("[satispay_activation] Terminal ID missing");
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Terminal ID mancante']);
    exit();
}

if (empty($token)) {
    error_log("[satispay_activation] Token missing");
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Token di attivazione mancante']);
    exit();
}

if (!in_array($environment, ['SANDBOX', 'PROD'], true)) {
    error_log("[satispay_activation] Invalid environment: $environment");
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ambiente non valido']);
    exit();
}

error_log("[satispay_activation] Validation passed, checking terminal ID in DB");

// Verifica che il terminal ID esista nella configurazione
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM activation_codes
        WHERE store_terminal_id = ?
    ");
    $stmt->bind_param("s", $terminalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    error_log("[satispay_activation] Terminal ID check result: " . $row['count']);
} catch (Exception $e) {
    error_log("[satispay_activation] DB error checking terminal: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
    exit();
}

if ($row['count'] == 0) {
    error_log("[satispay_activation] Terminal ID not found in activation_codes");
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Terminal ID non trovato']);
    exit();
}

error_log("[satispay_activation] Terminal ID found, calling remote activate_store.php");

// Chiama activate_store.php sul server locale
$activateUrl = 'http://localhost/satispay/activate_store.php';
error_log("[satispay_activation] Target URL: $activateUrl");

$postData = json_encode([
    'store' => $terminalId,
    'token' => $token,
    'env' => $environment
]);

error_log("[satispay_activation] POST data prepared: " . substr($postData, 0, 100));

$ch = curl_init($activateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

error_log("[satispay_activation] Executing cURL request...");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

error_log("[satispay_activation] cURL completed - HTTP code: $httpCode, error: " . ($curlError ?: 'none'));
error_log("[satispay_activation] Response: " . substr($response, 0, 200));

if ($curlError) {
    error_log("[satispay_activation] cURL error occurred");
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Errore connessione: ' . $curlError]);
    exit();
}

$responseData = json_decode($response, true);
error_log("[satispay_activation] Response decoded, ok=" . ($responseData['ok'] ?? 'null'));

if ($httpCode !== 200 || !isset($responseData['ok']) || !$responseData['ok']) {
    http_response_code($httpCode);
    echo json_encode([
        'ok' => false,
        'error' => $responseData['error'] ?? 'Attivazione fallita',
        'details' => $responseData
    ]);
    exit();
}

// Salva lo stato di attivazione nel database
try {
    $conn->begin_transaction();

    // Salva lo stato di attivazione
    $stmt = $conn->prepare("
        INSERT INTO terminal_config (config_key, config_value, terminal_id, updated_by, updated_at)
        VALUES ('satispay_activated', '1', ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            config_value = '1',
            updated_by = ?,
            updated_at = NOW()
    ");
    $username = $_SESSION['username'];
    $stmt->bind_param("sss", $terminalId, $username, $username);
    $stmt->execute();
    $stmt->close();

    // Salva l'ambiente
    $stmt = $conn->prepare("
        INSERT INTO terminal_config (config_key, config_value, terminal_id, updated_by, updated_at)
        VALUES ('satispay_environment', ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            config_value = ?,
            updated_by = ?,
            updated_at = NOW()
    ");
    $stmt->bind_param("sssss", $environment, $terminalId, $username, $environment, $username);
    $stmt->execute();
    $stmt->close();

    // Log dell'operazione
    $logStmt = $conn->prepare("
        INSERT INTO config_audit_log (action, user_agent, ip_address, performed_by, details, created_at)
        VALUES ('SATISPAY_ACTIVATION', ?, ?, ?, ?, NOW())
    ");
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $details = "Satispay activated for Terminal ID: $terminalId, Environment: $environment";
    $logStmt->bind_param("ssss", $userAgent, $ipAddress, $username, $details);
    $logStmt->execute();
    $logStmt->close();

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Satispay attivato con successo',
        'terminalId' => $terminalId,
        'environment' => $environment
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Errore nel salvataggio: ' . $e->getMessage()
    ]);
}
?>
