<?php
// terminal_config_handler.php
// Gestisce la configurazione dei parametri PAX

function getTerminalConfig($conn, $terminalId = null) {
    // Recupera la configurazione salvata nel database per un Terminal ID specifico
    if ($terminalId) {
        $stmt = $conn->prepare("
            SELECT config_key, config_value
            FROM terminal_config
            WHERE terminal_id = ?
            ORDER BY config_key
        ");
        $stmt->bind_param("s", $terminalId);
    } else {
        // Configurazione globale di default
        $stmt = $conn->prepare("
            SELECT config_key, config_value
            FROM terminal_config
            WHERE terminal_id IS NULL OR terminal_id = ''
            ORDER BY config_key
        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $config = [];
    while ($row = $result->fetch_assoc()) {
        $config[$row['config_key']] = $row['config_value'];
    }
    $stmt->close();

    // Valori di default se non esistono nel database
    $defaults = [
        'terminalId' => '00000000',
        'merchantName' => 'PayGlobe',
        'connectionIpAddress' => '192.168.1.1',
        'portPrimary' => '1234',
        'timeoutMs' => '30000',
        'sslEnabled' => '0',
        'backupIp' => '',
        'backupPort' => '0',
        'gtId' => '',
        'connectionDevice' => 'ETH',
        'connectionProtocol' => 'IP_HEADER',
        'apnName' => '',
        'apnUser' => '',
        'apnPassword' => '',
        'connectionTimeout' => '30',
        'responseTimeout' => '30',
        'tlsCertificateId' => '',
        'personalizationID' => '',
        'alias' => '',
        'slotName' => '',
        'backupConnectionDevice' => '',
        'backupConnectionProtocol' => '',
        'backupConnectionIpAddress' => '',
        // PayByLink Configuration
        'payByLinkApiKey' => '',
        // Payment Features
        'satispay' => '0',
        'bpe' => '0',
        'demat' => '1',
        'applePay' => '1',
        'googlePay' => '1',
        'payByLink' => '0',
        'contactless' => '1',
        // Payment Config
        'maxContactlessAmount' => '5000',
        'allowTip' => '1',
        'maxTipPercent' => '20',
        'forcePIN' => '0',
        'allowRefund' => '1',
        'allowPartialRefund' => '1',
        // Transaction Limits
        'maxTransactionAmount' => '99999999',
        'dailyLimit' => '500000000',
        'maxRefundDays' => '365',
        // Receipt Configuration
        'receiptHeader1' => 'PayGlobe Store',
        'receiptHeader2' => '',
        'receiptHeader3' => '',
        'receiptFooter1' => 'Grazie per PayGlobe',
        'receiptFooter2' => 'www.payglobe.com',
        'receiptFooter3' => '',
        // Fiscal Config
        'fiscalPrinter' => '1',
        'fiscalMode' => 'IT_STANDARD',
        'vatRateA' => '22.0',
        'vatRateB' => '10.0',
        'vatRateC' => '4.0',
        'vatRateD' => '0.0',
        // System Config
        'pollingIntervalSeconds' => '5',
        'enableLogging' => '0',
        'satispayStore' => '',
        'environment' => 'PRODUCTION',
        // Additional Features
        'tip' => '1',
        'receipt' => '1',
        'printing' => '1',
        // Satispay Activation
        'satispay_activated' => '0',
        'satispay_environment' => ''
    ];

    // Merge con i defaults
    return array_merge($defaults, $config);
}

function saveTerminalConfig($conn, $configData, $username, $terminalId = null) {
    try {
        $conn->begin_transaction();

        // Prima elimina tutte le configurazioni esistenti per questo Terminal ID
        if ($terminalId) {
            $stmt = $conn->prepare("DELETE FROM terminal_config WHERE terminal_id = ?");
            $stmt->bind_param("s", $terminalId);
            $stmt->execute();
            $stmt->close();
        } else {
            // Configurazione globale
            $conn->query("DELETE FROM terminal_config WHERE terminal_id IS NULL OR terminal_id = ''");
        }

        // Poi inserisce i nuovi valori
        $stmt = $conn->prepare("
            INSERT INTO terminal_config (config_key, config_value, terminal_id, updated_by, updated_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        foreach ($configData as $key => $value) {
            $stmt->bind_param("ssss", $key, $value, $terminalId, $username);
            $stmt->execute();
        }

        $stmt->close();
        $conn->commit();

        // Log dell'operazione
        $logStmt = $conn->prepare("
            INSERT INTO config_audit_log (action, user_agent, ip_address, performed_by, details, created_at)
            VALUES ('CONFIG_UPDATE', ?, ?, ?, ?, NOW())
        ");
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $details = 'Terminal configuration updated with ' . count($configData) . ' parameters' . ($terminalId ? " for Terminal ID: $terminalId" : " (global)");
        $logStmt->bind_param("ssss", $userAgent, $ipAddress, $username, $details);
        $logStmt->execute();
        $logStmt->close();

        return true;

    } catch (Exception $e) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackError) {
            // Ignora errori rollback
        }
        throw $e;
    }
}

function validateConfigData($data) {
    $errors = [];

    // Validazioni obbligatorie
    if (empty($data['gtId'])) {
        $errors[] = "Company ID (gtId) è obbligatorio";
    } elseif (!preg_match('/^[0-9]{5}$/', $data['gtId'])) {
        $errors[] = "Company ID deve essere di 5 cifre";
    }

    if (empty($data['connectionIpAddress'])) {
        $errors[] = "Connection IP Address è obbligatorio";
    }

    if (empty($data['portPrimary']) || !is_numeric($data['portPrimary']) || $data['portPrimary'] < 1 || $data['portPrimary'] > 65535) {
        $errors[] = "Port Primary deve essere tra 1 e 65535";
    }

    if (empty($data['connectionDevice']) || !in_array($data['connectionDevice'], ['ETH', 'WIFI', 'GPRS'])) {
        $errors[] = "Connection Device deve essere ETH, WIFI o GPRS";
    }

    if (empty($data['connectionProtocol']) || !in_array($data['connectionProtocol'], ['IP_HEADER', 'BT'])) {
        $errors[] = "Connection Protocol deve essere IP_HEADER o BT";
    }

    // Validazioni timeout
    if (!empty($data['connectionTimeout'])) {
        $timeout = intval($data['connectionTimeout']);
        if ($timeout < 5 || $timeout > 50) {
            $errors[] = "Connection Timeout deve essere tra 5 e 50 secondi";
        }
    }

    if (!empty($data['responseTimeout'])) {
        $timeout = intval($data['responseTimeout']);
        if ($timeout < 10 || $timeout > 50) {
            $errors[] = "Response Timeout deve essere tra 10 e 50 secondi";
        }
    }

    // Validazioni opzionali
    if (!empty($data['personalizationID']) && !preg_match('/^[0-9]{3}$/', $data['personalizationID'])) {
        $errors[] = "Personalization ID deve essere di 3 cifre";
    }

    if (!empty($data['terminalId']) && !preg_match('/^[0-9]{8}$/', $data['terminalId'])) {
        $errors[] = "Terminal ID deve essere di 8 cifre";
    }

    if (!empty($data['backupPort'])) {
        $port = intval($data['backupPort']);
        if ($port < 0 || $port > 65535) {
            $errors[] = "Backup Port deve essere tra 0 e 65535";
        }
    }

    return $errors;
}

function createConfigTables($conn) {
    // Crea la tabella per le configurazioni se non esiste
    $conn->query("
        CREATE TABLE IF NOT EXISTS terminal_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(50) NOT NULL,
            config_value TEXT,
            terminal_id VARCHAR(15),
            updated_by VARCHAR(50),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_config (config_key, terminal_id),
            INDEX idx_config_key (config_key),
            INDEX idx_terminal_id (terminal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Crea la tabella per l'audit log delle configurazioni
    $conn->query("
        CREATE TABLE IF NOT EXISTS config_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            user_agent TEXT,
            ip_address VARCHAR(45),
            performed_by VARCHAR(50),
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_performed_by (performed_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function getAvailableTerminalIds($conn, $userBU = null, $isAdmin = false) {
    // Recupera tutti i Terminal ID dai codici di attivazione
    if ($isAdmin) {
        $stmt = $conn->prepare("
            SELECT DISTINCT store_terminal_id, bu, notes, status
            FROM activation_codes
            WHERE store_terminal_id IS NOT NULL AND store_terminal_id != ''
            ORDER BY store_terminal_id
        ");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT store_terminal_id, bu, notes, status
            FROM activation_codes
            WHERE store_terminal_id IS NOT NULL AND store_terminal_id != '' AND bu = ?
            ORDER BY store_terminal_id
        ");
        $stmt->bind_param("s", $userBU);
        $stmt->execute();
    }

    $result = $stmt->get_result();
    $terminals = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $terminals;
}
?>