<?php
/**
 * merchant/api/terminal/config.php
 * Endpoint semplificato - Solo parametro activationCode
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include configurazione database (adatta il path)
require_once '../../config.php';

function sendError($code, $error, $message) {
    http_response_code($code);
    echo json_encode(array(
        'success' => false,
        'error' => $error,
        'message' => $message,
        'timestamp' => date('c')
    ), JSON_UNESCAPED_UNICODE);
    exit();
}

// Solo GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError(405, 'METHOD_NOT_ALLOWED', 'Only GET method allowed');
}

// Validazione parametro obbligatorio
$activationCode = trim(isset($_GET['activationCode']) ? $_GET['activationCode'] : '');
$deviceId = trim(isset($_GET['deviceId']) ? $_GET['deviceId'] : 'DEVICE_' . time()); // Default se non fornito

if (empty($activationCode)) {
    sendError(400, 'MISSING_ACTIVATION_CODE', 'Parameter activationCode is required');
}

if (!preg_match('/^ACT-[A-Z0-9]{9}$/', $activationCode)) {
    sendError(400, 'INVALID_CODE_FORMAT', 'Invalid activation code format');
}

try {
    // 1. Verifica codice di attivazione
    $stmt = $conn->prepare("
        SELECT ac.*
        FROM activation_codes ac
        WHERE ac.code = ?
    ");
    $stmt->bind_param("s", $activationCode);
    $stmt->execute();
    $activation = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$activation) {
        sendError(404, 'CODE_NOT_FOUND', 'Activation code not found');
    }
    
    // 2. Verifica se già utilizzato
    if ($activation['status'] === 'USED') {
        // Codice già utilizzato - restituisci la configurazione senza creare nuovi record
        $alreadyUsed = true;
    } else {
        $alreadyUsed = false;
    }

    // 3. Verifica scadenza
    if (strtotime($activation['expires_at']) < time() || $activation['status'] === 'EXPIRED') {
        $conn->query("UPDATE activation_codes SET status = 'EXPIRED' WHERE code = '$activationCode'");
        sendError(410, 'CODE_EXPIRED', 'Activation code has expired');
    }
    
    // 4. Gestione Terminal ID e attivazione
    if (!$alreadyUsed) {
        // Genera Terminal ID univoco solo per codici nuovi
        do {
            $newTerminalId = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            // Verifica che non sia già usato come store_terminal_id in activation_codes
            $check = $conn->query("SELECT COUNT(*) as c FROM activation_codes WHERE store_terminal_id = '$newTerminalId'");
            $exists = $check->fetch_assoc()['c'];
        } while ($exists > 0);

        // 5. Aggiorna codice come utilizzato e crea terminale attivo
        $conn->begin_transaction();

        try {
            // Marca codice come utilizzato
            $stmt = $conn->prepare("UPDATE activation_codes SET status = 'USED', used_at = NOW(), device_id = ? WHERE code = ?");
            $stmt->bind_param("ss", $deviceId, $activationCode);
            $stmt->execute();
            $stmt->close();

            // Crea terminale attivo
            $stmt = $conn->prepare("
                INSERT INTO activated_terminals
                (store_terminal_id, activation_code, device_id, created_by, last_ping_at)
                VALUES (?, ?, ?, 'API', NOW())
            ");
            $stmt->bind_param("sss", $activation['store_terminal_id'], $activationCode, $deviceId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } else {
        // Codice già utilizzato - usa il store_terminal_id dell'activation_code
        $newTerminalId = $activation['store_terminal_id'];
    }
    
    // 6. Recupera configurazioni PAX personalizzate per questo Terminal ID
    include_once '../../terminal_config_handler.php';
    $terminalConfig = getTerminalConfig($conn, $activation['store_terminal_id']);

    // 7. Prepara configurazione JSON
    $config = array(
        'success' => true,
        'activated' => true,
        'activationCode' => $activationCode,
        'activationDate' => date('c'),

        // Campi PAX obbligatori (con valori personalizzabili)
        'merchantId' => 'PAYGLOBE_' . $activation['bu'] . '_' . $activation['store_terminal_id'],
        'terminalId' => $terminalConfig['terminalId'] ?: $newTerminalId,
        'acquirerId' => 'PGL-MNT',
        'currency' => 'EUR',
        'country' => 'IT',
        'deviceId' => $deviceId,
        'configVersion' => '1.0',

        // Configurazione terminale
        'terminalConfig' => array(
            'merchantName' => $terminalConfig['merchantName'] ?: 'PayGlobe Store',
            'merchantVAT' => $activation['bu'],
            'businessUnit' => $activation['bu'],
            'storeTerminalId' => $activation['store_terminal_id'],
            'receiptHeader' => array_filter([
                $terminalConfig['receiptHeader1'] ?: ($terminalConfig['merchantName'] ?: 'PayGlobe Store'),
                $terminalConfig['receiptHeader2'] ?: ('Terminal: ' . $activation['store_terminal_id']),
                $terminalConfig['receiptHeader3'] ?: ('BU: ' . $activation['bu']),
                '--------------------------------'
            ]),
            'receiptFooter' => array_filter([
                '--------------------------------',
                $terminalConfig['receiptFooter1'] ?: 'Grazie per PayGlobe',
                $terminalConfig['receiptFooter2'] ?: 'www.payglobe.com',
                $terminalConfig['receiptFooter3'] ?: ('Terminal ID: ' . ($terminalConfig['terminalId'] ?: $newTerminalId))
            ])
        ),

        // Configurazione rete (con valori personalizzabili)
        'networkConfig' => array(
            'hostPrimary' => $terminalConfig['connectionIpAddress'] ?: '195.188.150.133',
            'portPrimary' => (int)($terminalConfig['portPrimary'] ?: 46409),
            'hostSecondary' => $terminalConfig['backupIp'] ?: '195.188.150.133',
            'portSecondary' => (int)($terminalConfig['backupPort'] ?: 46409),
            'ssl' => (bool)($terminalConfig['sslEnabled'] ?: true),
            'timeout' => (int)($terminalConfig['timeoutMs'] ?: 30000)
        ),

        // Configurazioni PAX specifiche
        'paxConfig' => array(
            'gtId' => $terminalConfig['gtId'],
            'connectionDevice' => $terminalConfig['connectionDevice'] ?: 'ETH',
            'connectionProtocol' => $terminalConfig['connectionProtocol'] ?: 'IP_HEADER',
            'connectionIpAddress' => $terminalConfig['connectionIpAddress'] ?: '192.168.1.1',
            'connectionPort' => $terminalConfig['portPrimary'] ?: '46409',
            'apnName' => $terminalConfig['apnName'],
            'apnUser' => $terminalConfig['apnUser'],
            'apnPassword' => $terminalConfig['apnPassword'],
            'connectionTimeout' => $terminalConfig['connectionTimeout'] ?: '30',
            'responseTimeout' => $terminalConfig['responseTimeout'] ?: '30',
            'tlsCertificateId' => $terminalConfig['tlsCertificateId'],
            'personalizationID' => $terminalConfig['personalizationID'],
            'alias' => $terminalConfig['alias'],
            'slotName' => $terminalConfig['slotName'],
            'backupConnectionDevice' => $terminalConfig['backupConnectionDevice'],
            'backupConnectionProtocol' => $terminalConfig['backupConnectionProtocol'],
            'backupConnectionIpAddress' => $terminalConfig['backupConnectionIpAddress']
        ),
        
        // Configurazione pagamenti
        'paymentConfig' => array(
            'supportedCards' => array('VISA', 'MASTERCARD', 'MAESTRO', 'BANCOMAT'),
            'contactless' => (bool)($terminalConfig['contactless'] ?: true),
            'maxContactlessAmount' => (int)($terminalConfig['maxContactlessAmount'] ?: 5000),
            'allowTip' => (bool)($terminalConfig['allowTip'] ?: true),
            'maxTipPercent' => (int)($terminalConfig['maxTipPercent'] ?: 20),
            'forcePIN' => (bool)($terminalConfig['forcePIN'] ?: false),
            'allowRefund' => (bool)($terminalConfig['allowRefund'] ?: true),
            'allowPartialRefund' => (bool)($terminalConfig['allowPartialRefund'] ?: true)
        ),
        
        // Configurazione sicurezza
        'securityConfig' => array(
            'encryptionKey' => base64_encode('PLACEHOLDER_AES256_' . $newTerminalId),
            'macKey' => base64_encode('PLACEHOLDER_MAC_' . $newTerminalId),
            'certificateThumbprint' => strtoupper(md5($newTerminalId)),
            'keyIndex' => '01'
        ),
        
        // Configurazione fiscale italiana
        'fiscalConfig' => array(
            'fiscalPrinter' => (bool)($terminalConfig['fiscalPrinter'] ?: true),
            'fiscalMode' => $terminalConfig['fiscalMode'] ?: 'IT_STANDARD',
            'vatRates' => array(
                array('code' => 'A', 'rate' => (float)($terminalConfig['vatRateA'] ?: 22.0), 'description' => 'Aliquota ordinaria'),
                array('code' => 'B', 'rate' => (float)($terminalConfig['vatRateB'] ?: 10.0), 'description' => 'Aliquota ridotta'),
                array('code' => 'C', 'rate' => (float)($terminalConfig['vatRateC'] ?: 4.0), 'description' => 'Aliquota minima'),
                array('code' => 'D', 'rate' => (float)($terminalConfig['vatRateD'] ?: 0.0), 'description' => 'Esente IVA')
            )
        ),
        
        // System Configuration
        'systemConfig' => array(
            'pollingIntervalSeconds' => (int)($terminalConfig['pollingIntervalSeconds'] ?: 15),
            'enableLogging' => (bool)($terminalConfig['enableLogging'] ?: false),
            'satispayConfig' => array(
                'store' => $terminalConfig['satispayStore'] ?: ('STORE' . $activation['store_terminal_id']),
                'environment' => strtoupper($terminalConfig['environment'] ?: 'PRODUCTION')
            )
        ),

        // Features
        'features' => array(
            'satispay' => !empty($terminalConfig['satispay']) && $terminalConfig['satispay'] === '1',
            'bpe' => !empty($terminalConfig['bpe']) && $terminalConfig['bpe'] === '1',
            'demat' => !empty($terminalConfig['demat']) && $terminalConfig['demat'] === '1',
            'tip' => !empty($terminalConfig['tip']) && $terminalConfig['tip'] === '1',
            'receipt' => !empty($terminalConfig['receipt']) && $terminalConfig['receipt'] === '1',
            'printing' => !empty($terminalConfig['printing']) && $terminalConfig['printing'] === '1',
            'payByLink' => !empty($terminalConfig['payByLink']) && $terminalConfig['payByLink'] === '1'
        ),

        // Limiti
        'limits' => array(
            'maxTransactionAmount' => (int)($terminalConfig['maxTransactionAmount'] ?: 99999999),
            'dailyLimit' => (int)($terminalConfig['dailyLimit'] ?: 500000000),
            'maxRefundDays' => (int)($terminalConfig['maxRefundDays'] ?: 365)
        ),
        
        // Timestamp
        'timestamps' => array(
            'created' => date('c'),
            'validUntil' => date('c', strtotime('+1 year')),
            'activatedAt' => date('c')
        ),
        
        // Metadata
        'metadata' => array(
            'apiVersion' => '1.0',
            'environment' => 'production',
            'locale' => 'it_IT',
            'language' => $activation['language'] ?: 'it',
            'languageFolder' => $activation['language'] ? ($activation['language'] === 'it' ? 'values' : 'values-' . $activation['language']) : 'values'
        ),

        // PayByLink Configuration
        'paybylink' => !empty($terminalConfig['payByLinkApiKey']) ? array(
            'apiKey' => $terminalConfig['payByLinkApiKey']
        ) : null
    );
    
    // Response finale
    http_response_code(200);
    echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
    // Rollback della transazione se attiva
    try {
        $conn->rollback();
    } catch (Exception $rollbackError) {
        // Ignora errori se non c'è transazione attiva
    }

    error_log("Activation error: " . $e->getMessage());
    sendError(500, 'INTERNAL_ERROR', 'Server error occurred');
}
    
?>
