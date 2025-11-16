<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';
include 'config.php';
include 'terminal_config_handler.php';

$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');
$username = $_SESSION['username'];

$success = '';
$error = '';

// Crea le tabelle se non esistono
createConfigTables($conn);

// Recupera Terminal ID disponibili
$availableTerminals = getAvailableTerminalIds($conn, $userBU, $isAdmin);

// Terminal ID selezionato (da GET o POST)
$selectedTerminalId = isset($_GET['terminal_id']) ? $_GET['terminal_id'] : (isset($_POST['selected_terminal_id']) ? $_POST['selected_terminal_id'] : '');

// Recupera i valori attuali dal database per il Terminal ID selezionato
$currentConfig = getTerminalConfig($conn, $selectedTerminalId);

// Verifica stato attivazione Satispay
$satispayActivated = false;
$satispayEnvironment = '';
if ($selectedTerminalId) {
    $stmt = $conn->prepare("
        SELECT config_value
        FROM terminal_config
        WHERE terminal_id = ? AND config_key = 'satispay_activated'
    ");
    $stmt->bind_param("s", $selectedTerminalId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $satispayActivated = ($row['config_value'] === '1');
    }
    $stmt->close();

    if ($satispayActivated) {
        $stmt = $conn->prepare("
            SELECT config_value
            FROM terminal_config
            WHERE terminal_id = ? AND config_key = 'satispay_environment'
        ");
        $stmt->bind_param("s", $selectedTerminalId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $satispayEnvironment = $row['config_value'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'update_config') {
        // Prepara i dati per il salvataggio
        $configData = [
            'terminalId' => trim($_POST['terminalId'] ?? ''),
            'merchantName' => trim($_POST['merchantName'] ?? ''),
            'connectionIpAddress' => trim($_POST['connectionIpAddress'] ?? ''),
            'portPrimary' => trim($_POST['portPrimary'] ?? ''),
            'timeoutMs' => trim($_POST['timeoutMs'] ?? ''),
            'sslEnabled' => trim($_POST['sslEnabled'] ?? ''),
            'backupIp' => trim($_POST['backupIp'] ?? ''),
            'backupPort' => trim($_POST['backupPort'] ?? ''),
            'gtId' => trim($_POST['gtId'] ?? ''),
            'connectionDevice' => trim($_POST['connectionDevice'] ?? ''),
            'connectionProtocol' => trim($_POST['connectionProtocol'] ?? ''),
            'apnName' => trim($_POST['apnName'] ?? ''),
            'apnUser' => trim($_POST['apnUser'] ?? ''),
            'apnPassword' => trim($_POST['apnPassword'] ?? ''),
            'connectionTimeout' => trim($_POST['connectionTimeout'] ?? ''),
            'responseTimeout' => trim($_POST['responseTimeout'] ?? ''),
            'tlsCertificateId' => trim($_POST['tlsCertificateId'] ?? ''),
            'personalizationID' => trim($_POST['personalizationID'] ?? ''),
            'alias' => trim($_POST['alias'] ?? ''),
            'slotName' => trim($_POST['slotName'] ?? ''),
            'backupConnectionDevice' => trim($_POST['backupConnectionDevice'] ?? ''),
            'backupConnectionProtocol' => trim($_POST['backupConnectionProtocol'] ?? ''),
            'backupConnectionIpAddress' => trim($_POST['backupConnectionIpAddress'] ?? ''),
            // PayByLink Configuration
            'payByLinkApiKey' => trim($_POST['payByLinkApiKey'] ?? ''),
            // Payment Features
            'satispay' => trim($_POST['satispay'] ?? '0'),
            'bpe' => trim($_POST['bpe'] ?? '0'),
            'demat' => trim($_POST['demat'] ?? '0'),
            'applePay' => trim($_POST['applePay'] ?? '0'),
            'googlePay' => trim($_POST['googlePay'] ?? '0'),
            'payByLink' => trim($_POST['payByLink'] ?? '0'),
            'contactless' => trim($_POST['contactless'] ?? '0'),
            'tip' => trim($_POST['tip'] ?? '0'),
            'receipt' => trim($_POST['receipt'] ?? '0'),
            'printing' => trim($_POST['printing'] ?? '0'),
            // System Config
            'pollingIntervalSeconds' => trim($_POST['pollingIntervalSeconds'] ?? '15'),
            'enableLogging' => trim($_POST['enableLogging'] ?? '0'),
            'satispayStore' => trim($_POST['satispayStore'] ?? ''),
            'environment' => trim($_POST['environment'] ?? 'PRODUCTION'),
            // Payment Config
            'maxContactlessAmount' => trim($_POST['maxContactlessAmount'] ?? '5000'),
            'allowTip' => trim($_POST['allowTip'] ?? '0'),
            'maxTipPercent' => trim($_POST['maxTipPercent'] ?? '20'),
            'forcePIN' => trim($_POST['forcePIN'] ?? '0'),
            'allowRefund' => trim($_POST['allowRefund'] ?? '0'),
            'allowPartialRefund' => trim($_POST['allowPartialRefund'] ?? '0'),
            // Transaction Limits
            'maxTransactionAmount' => trim($_POST['maxTransactionAmount'] ?? '99999999'),
            'dailyLimit' => trim($_POST['dailyLimit'] ?? '500000000'),
            'maxRefundDays' => trim($_POST['maxRefundDays'] ?? '365'),
            // Receipt Configuration
            'receiptHeader1' => trim($_POST['receiptHeader1'] ?? ''),
            'receiptHeader2' => trim($_POST['receiptHeader2'] ?? ''),
            'receiptHeader3' => trim($_POST['receiptHeader3'] ?? ''),
            'receiptFooter1' => trim($_POST['receiptFooter1'] ?? ''),
            'receiptFooter2' => trim($_POST['receiptFooter2'] ?? ''),
            'receiptFooter3' => trim($_POST['receiptFooter3'] ?? ''),
            // Fiscal Config
            'fiscalPrinter' => trim($_POST['fiscalPrinter'] ?? '0'),
            'fiscalMode' => trim($_POST['fiscalMode'] ?? 'IT_STANDARD'),
            'vatRateA' => trim($_POST['vatRateA'] ?? '22.0'),
            'vatRateB' => trim($_POST['vatRateB'] ?? '10.0'),
            'vatRateC' => trim($_POST['vatRateC'] ?? '4.0'),
            'vatRateD' => trim($_POST['vatRateD'] ?? '0.0')
        ];

        // Valida i dati
        $validationErrors = validateConfigData($configData);

        if (empty($selectedTerminalId)) {
            $error = "Seleziona un Terminal ID da configurare";
        } elseif (empty($validationErrors)) {
            try {
                saveTerminalConfig($conn, $configData, $username, $selectedTerminalId);
                $success = "Configurazione aggiornata con successo per Terminal ID: " . $selectedTerminalId;

                // Aggiorna i valori correnti
                $currentConfig = $configData;

            } catch (Exception $e) {
                $error = "Errore nel salvataggio della configurazione: " . $e->getMessage();
            }
        } else {
            $error = "Errori di validazione:<br>" . implode("<br>", $validationErrors);
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2><i class="fas fa-cogs"></i> Editor Configurazione Terminale PAX</h2>
            <small class="text-muted">Configurazione parametri per l'integrazione con app Android PAX</small>
            <?php if ($selectedTerminalId): ?>
                <br><small class="text-info">
                    <i class="fas fa-microchip"></i> Configurando: <strong><?= htmlspecialchars($selectedTerminalId) ?></strong>
                </small>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($isAdmin): ?>
                <span class="badge badge-warning">
                    <i class="fas fa-crown"></i> Admin
                </span>
            <?php else: ?>
                <span class="badge badge-info">
                    <i class="fas fa-building"></i> BU: <?= htmlspecialchars($userBU) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Selezione Terminal ID -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5><i class="fas fa-search"></i> Seleziona Terminal ID da Configurare</h5>
            <small>Scegli quale terminale configurare dalle attivazioni disponibili</small>
        </div>
        <div class="card-body">
            <?php if (empty($availableTerminals)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Nessun Terminal ID disponibile.</strong><br>
                    Crea prima un codice di attivazione nella sezione "Gestione Codici di Attivazione".
                </div>
            <?php else: ?>
                <form method="get" class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="terminal_id">Terminal ID</label>
                            <select name="terminal_id" id="terminal_id" class="form-control">
                                <option value="">-- Seleziona Terminal ID --</option>
                                <?php foreach ($availableTerminals as $terminal): ?>
                                    <option value="<?= htmlspecialchars($terminal['store_terminal_id']) ?>"
                                            <?= $selectedTerminalId === $terminal['store_terminal_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($terminal['store_terminal_id']) ?>
                                        <?php if ($terminal['notes']): ?>
                                            - <?= htmlspecialchars($terminal['notes']) ?>
                                        <?php endif; ?>
                                        (BU: <?= htmlspecialchars($terminal['bu']) ?>)
                                        [<?= $terminal['status'] ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-cog"></i> Configura
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <a href="activation_codes.php" class="btn btn-success btn-block">
                                <i class="fas fa-plus"></i> Crea Codice Attivazione
                            </a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $success ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($selectedTerminalId): ?>
    <form method="post" class="needs-validation" novalidate>
        <input type="hidden" name="action" value="update_config">
        <input type="hidden" name="selected_terminal_id" value="<?= htmlspecialchars($selectedTerminalId) ?>">
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Seleziona prima un Terminal ID</strong> per configurare i suoi parametri PAX.
        </div>
    <?php endif; ?>

        <!-- Configurazioni Base Android -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-mobile-alt"></i> Configurazioni Base Android</h5>
                <small>Parametri mappati direttamente dall'app Android</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="terminalId">Terminal ID</label>
                        <input type="text" name="terminalId" id="terminalId" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['terminalId']) ?>"
                               placeholder="00000000" maxlength="8" pattern="[0-9]{8}">
                        <small class="text-muted">8 cifre numeriche</small>
                    </div>
                    <div class="col-md-8">
                        <label for="merchantName">Merchant Name</label>
                        <input type="text" name="merchantName" id="merchantName" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['merchantName']) ?>"
                               placeholder="PayGlobe" maxlength="50">
                        <small class="text-muted">Nome del merchant</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurazioni di Connessione -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-network-wired"></i> Configurazioni di Connessione</h5>
                <small>Parametri di rete e connessione</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="connectionIpAddress">Connection IP Address *</label>
                        <input type="text" name="connectionIpAddress" id="connectionIpAddress" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['connectionIpAddress']) ?>"
                               placeholder="192.168.1.1" required>
                        <small class="text-muted">IP address per la connessione</small>
                    </div>
                    <div class="col-md-2">
                        <label for="portPrimary">Port Primary *</label>
                        <input type="number" name="portPrimary" id="portPrimary" class="form-control"
                               value="<?= $currentConfig['portPrimary'] ?>"
                               placeholder="1234" min="1" max="65535" required>
                        <small class="text-muted">Porta IP</small>
                    </div>
                    <div class="col-md-2">
                        <label for="timeoutMs">Timeout (ms)</label>
                        <input type="number" name="timeoutMs" id="timeoutMs" class="form-control"
                               value="<?= $currentConfig['timeoutMs'] ?>"
                               placeholder="30000" min="1000" max="300000">
                        <small class="text-muted">Timeout in millisecondi</small>
                    </div>
                    <div class="col-md-2">
                        <label for="sslEnabled">SSL Enabled</label>
                        <select name="sslEnabled" id="sslEnabled" class="form-control">
                            <option value="0" <?= !$currentConfig['sslEnabled'] ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $currentConfig['sslEnabled'] ? 'selected' : '' ?>>Sì</option>
                        </select>
                        <small class="text-muted">Abilita SSL</small>
                    </div>
                    <div class="col-md-2">
                        <label for="backupPort">Backup Port</label>
                        <input type="number" name="backupPort" id="backupPort" class="form-control"
                               value="<?= $currentConfig['backupPort'] ?>"
                               placeholder="0" min="0" max="65535">
                        <small class="text-muted">Porta backup</small>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="backupIp">Backup IP</label>
                        <input type="text" name="backupIp" id="backupIp" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['backupIp']) ?>"
                               placeholder="">
                        <small class="text-muted">IP di backup (opzionale)</small>
                    </div>
                    <div class="col-md-6">
                        <label for="slotName">Slot Name (Ingenico)</label>
                        <input type="text" name="slotName" id="slotName" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['slotName']) ?>"
                               placeholder="" maxlength="50">
                        <small class="text-muted">Nome dello slot per TID Ingenico (opzionale)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- CB2 Specifiche -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-credit-card"></i> CB2 Specifiche</h5>
                <small>Parametri specifici per terminali CB2</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="gtId">Company ID (gtId) *</label>
                        <input type="text" name="gtId" id="gtId" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['gtId']) ?>"
                               placeholder="12345" pattern="[0-9]{5}" maxlength="5" required>
                        <small class="text-muted">5 cifre - ID azienda per CREATE_TID</small>
                    </div>
                    <div class="col-md-3">
                        <label for="connectionDevice">Connection Device *</label>
                        <select name="connectionDevice" id="connectionDevice" class="form-control" required>
                            <option value="ETH" <?= $currentConfig['connectionDevice'] === 'ETH' ? 'selected' : '' ?>>ETH</option>
                            <option value="WIFI" <?= $currentConfig['connectionDevice'] === 'WIFI' ? 'selected' : '' ?>>WIFI</option>
                            <option value="GPRS" <?= $currentConfig['connectionDevice'] === 'GPRS' ? 'selected' : '' ?>>GPRS</option>
                        </select>
                        <small class="text-muted">Tipo di dispositivo per connessione</small>
                    </div>
                    <div class="col-md-3">
                        <label for="connectionProtocol">Connection Protocol *</label>
                        <select name="connectionProtocol" id="connectionProtocol" class="form-control" required>
                            <option value="IP_HEADER" <?= $currentConfig['connectionProtocol'] === 'IP_HEADER' ? 'selected' : '' ?>>IP_HEADER</option>
                            <option value="BT" <?= $currentConfig['connectionProtocol'] === 'BT' ? 'selected' : '' ?>>BT</option>
                        </select>
                        <small class="text-muted">Protocollo di comunicazione</small>
                    </div>
                    <div class="col-md-3">
                        <label for="alias">Alias</label>
                        <input type="text" name="alias" id="alias" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['alias']) ?>"
                               placeholder="" maxlength="50">
                        <small class="text-muted">Alias Terminal ID</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeout e Certificati -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-clock"></i> Timeout e Certificati</h5>
                <small>Configurazioni avanzate</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="connectionTimeout">Connection Timeout *</label>
                        <input type="number" name="connectionTimeout" id="connectionTimeout" class="form-control"
                               value="<?= $currentConfig['connectionTimeout'] ?>"
                               placeholder="30" min="5" max="50" required>
                        <small class="text-muted">Timeout connessione (5-50 sec)</small>
                    </div>
                    <div class="col-md-3">
                        <label for="responseTimeout">Response Timeout *</label>
                        <input type="number" name="responseTimeout" id="responseTimeout" class="form-control"
                               value="<?= $currentConfig['responseTimeout'] ?>"
                               placeholder="30" min="10" max="50" required>
                        <small class="text-muted">Timeout risposta (10-50 sec)</small>
                    </div>
                    <div class="col-md-3">
                        <label for="tlsCertificateId">TLS Certificate ID</label>
                        <input type="text" name="tlsCertificateId" id="tlsCertificateId" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['tlsCertificateId']) ?>"
                               placeholder="0" maxlength="10">
                        <small class="text-muted">ID certificato TLS (0=nessuno)</small>
                    </div>
                    <div class="col-md-3">
                        <label for="personalizationID">Personalization ID</label>
                        <input type="text" name="personalizationID" id="personalizationID" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['personalizationID']) ?>"
                               placeholder="" pattern="[0-9]{3}" maxlength="3">
                        <small class="text-muted">ID personalizzazione (3 cifre)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurazioni Backup -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5><i class="fas fa-shield-alt"></i> Configurazioni Backup</h5>
                <small>Parametri per il terzo tentativo di connessione</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="backupConnectionDevice">Backup Connection Device</label>
                        <select name="backupConnectionDevice" id="backupConnectionDevice" class="form-control">
                            <option value="">-- Seleziona --</option>
                            <option value="ETH" <?= $currentConfig['backupConnectionDevice'] === 'ETH' ? 'selected' : '' ?>>ETH</option>
                            <option value="WIFI" <?= $currentConfig['backupConnectionDevice'] === 'WIFI' ? 'selected' : '' ?>>WIFI</option>
                            <option value="GPRS" <?= $currentConfig['backupConnectionDevice'] === 'GPRS' ? 'selected' : '' ?>>GPRS</option>
                        </select>
                        <small class="text-muted">Dispositivo per terzo tentativo</small>
                    </div>
                    <div class="col-md-4">
                        <label for="backupConnectionProtocol">Backup Connection Protocol</label>
                        <select name="backupConnectionProtocol" id="backupConnectionProtocol" class="form-control">
                            <option value="">-- Seleziona --</option>
                            <option value="IP_HEADER" <?= $currentConfig['backupConnectionProtocol'] === 'IP_HEADER' ? 'selected' : '' ?>>IP_HEADER</option>
                            <option value="BT" <?= $currentConfig['backupConnectionProtocol'] === 'BT' ? 'selected' : '' ?>>BT</option>
                        </select>
                        <small class="text-muted">Protocollo per terzo tentativo</small>
                    </div>
                    <div class="col-md-4">
                        <label for="backupConnectionIpAddress">Backup Connection IP</label>
                        <input type="text" name="backupConnectionIpAddress" id="backupConnectionIpAddress" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['backupConnectionIpAddress']) ?>"
                               placeholder="">
                        <small class="text-muted">IP per terzo tentativo</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurazioni Features -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-star"></i> Features di Pagamento</h5>
                <small>Abilita/Disabilita funzionalità di pagamento</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="satispay" value="1" id="satispay" class="form-check-input"
                                   <?= $currentConfig['satispay'] ? 'checked' : '' ?>>
                            <label for="satispay" class="form-check-label">
                                <strong>Satispay</strong><br>
                                <small class="text-muted">Pagamenti Satispay</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="bpe" value="1" id="bpe" class="form-check-input"
                                   <?= $currentConfig['bpe'] ? 'checked' : '' ?>>
                            <label for="bpe" class="form-check-label">
                                <strong>BPE</strong><br>
                                <small class="text-muted">Buono Pasto Elettronico</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="demat" value="1" id="demat" class="form-check-input"
                                   <?= $currentConfig['demat'] ? 'checked' : '' ?>>
                            <label for="demat" class="form-check-label">
                                <strong>Demat</strong><br>
                                <small class="text-muted">Buono Pasto Cartaceo</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="applePay" value="1" id="applePay" class="form-check-input"
                                   <?= $currentConfig['applePay'] ? 'checked' : '' ?>>
                            <label for="applePay" class="form-check-label">
                                <strong>Apple Pay</strong><br>
                                <small class="text-muted">Pagamenti Apple</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="googlePay" value="1" id="googlePay" class="form-check-input"
                                   <?= $currentConfig['googlePay'] ? 'checked' : '' ?>>
                            <label for="googlePay" class="form-check-label">
                                <strong>Google Pay</strong><br>
                                <small class="text-muted">Pagamenti Google</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="payByLink" value="1" id="payByLink" class="form-check-input"
                                   <?= $currentConfig['payByLink'] ? 'checked' : '' ?>>
                            <label for="payByLink" class="form-check-label">
                                <strong>Pay by Link</strong><br>
                                <small class="text-muted">Pagamento via Link</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="payByLinkApiKey">PayByLink API Key</label>
                        <input type="text" name="payByLinkApiKey" id="payByLinkApiKey" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['payByLinkApiKey']) ?>"
                               placeholder="460626a4cbe72c47cc6f54be898530a8716dccae2003bef6a593625fbb64ef52">
                        <small class="text-muted">API Key per il servizio PayByLink (richiesta se PayByLink è abilitato)</small>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="contactless" value="1" id="contactless" class="form-check-input"
                                   <?= $currentConfig['contactless'] ? 'checked' : '' ?>>
                            <label for="contactless" class="form-check-label">
                                <strong>Contactless</strong><br>
                                <small class="text-muted">Pagamenti NFC</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="tip" value="1" id="tip" class="form-check-input"
                                   <?= $currentConfig['tip'] ? 'checked' : '' ?>>
                            <label for="tip" class="form-check-label">
                                <strong>Tip</strong><br>
                                <small class="text-muted">Gestione Mancia</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="receipt" value="1" id="receipt" class="form-check-input"
                                   <?= $currentConfig['receipt'] ? 'checked' : '' ?>>
                            <label for="receipt" class="form-check-label">
                                <strong>Receipt</strong><br>
                                <small class="text-muted">Stampa Ricevute</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" name="printing" value="1" id="printing" class="form-check-input"
                                   <?= $currentConfig['printing'] ? 'checked' : '' ?>>
                            <label for="printing" class="form-check-label">
                                <strong>Printing</strong><br>
                                <small class="text-muted">Funzioni Stampa</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parametri di sistema -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-cogs"></i> Parametri di sistema</h5>
                <small>Configurazioni di sistema</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="pollingIntervalSeconds">Polling Interval (secondi)</label>
                        <input type="number" name="pollingIntervalSeconds" id="pollingIntervalSeconds" class="form-control"
                               value="5" readonly disabled>
                        <input type="hidden" name="pollingIntervalSeconds" value="5">
                        <small class="text-muted">Intervallo di polling fisso: 5 secondi</small>
                    </div>
                    <div class="col-md-6">
                        <label for="enableLogging">Logging</label>
                        <select name="enableLogging" id="enableLogging" class="form-control">
                            <option value="0" <?= !$currentConfig['enableLogging'] || $currentConfig['enableLogging'] === '0' ? 'selected' : '' ?>>Disabilitato</option>
                            <option value="1" <?= $currentConfig['enableLogging'] === '1' ? 'selected' : '' ?>>Abilitato</option>
                        </select>
                        <small class="text-muted">Controllo logging sistema</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurazioni Satispay -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-mobile-alt"></i> Configurazioni Satispay</h5>
                <small>Parametri e attivazione Satispay</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="environment">Ambiente</label>
                        <select name="environment" id="environment" class="form-control">
                            <option value="PRODUCTION" <?= $currentConfig['environment'] === 'PRODUCTION' ? 'selected' : '' ?>>PRODUCTION</option>
                            <option value="SANDBOX" <?= $currentConfig['environment'] === 'SANDBOX' ? 'selected' : '' ?>>SANDBOX</option>
                        </select>
                        <small class="text-muted">Ambiente di esecuzione Satispay</small>
                    </div>
                    <div class="col-md-6">
                        <label for="satispayStore">Satispay Store ID</label>
                        <input type="text" name="satispayStore" id="satispayStore" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['satispayStore']) ?>"
                               placeholder="STORE12345">
                        <small class="text-muted">ID store per Satispay (opzionale)</small>
                    </div>
                </div>

                <!-- Attivazione Satispay -->
                <?php if ($selectedTerminalId): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card border-<?= $satispayActivated ? 'success' : 'warning' ?>">
                            <div class="card-header bg-<?= $satispayActivated ? 'success' : 'warning' ?> text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-<?= $satispayActivated ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                                    Stato Attivazione Satispay
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <?php if ($satispayActivated): ?>
                                            <div class="alert alert-success mb-0">
                                                <strong><i class="fas fa-check"></i> Satispay Attivato</strong><br>
                                                <small>Ambiente: <strong><?= htmlspecialchars($satispayEnvironment) ?></strong></small><br>
                                                <small>Terminal ID: <strong><?= htmlspecialchars($selectedTerminalId) ?></strong></small>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning mb-0">
                                                <strong><i class="fas fa-exclamation-triangle"></i> Satispay Non Attivato</strong><br>
                                                <small>Per attivare Satispay, inserisci il Codice di Attivazione dalla Dashboard Satispay</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <button type="button" class="btn btn-<?= $satispayActivated ? 'info' : 'primary' ?> btn-lg"
                                                data-toggle="modal" data-target="#satispayActivationModal">
                                            <i class="fas fa-<?= $satispayActivated ? 'sync' : 'play-circle' ?>"></i>
                                            <?= $satispayActivated ? 'Ri-attiva' : 'Attiva Satispay' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configurazioni Pagamento -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-credit-card"></i> Configurazioni Pagamento</h5>
                <small>Limiti e comportamenti dei pagamenti</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="maxContactlessAmount">Limite Contactless (centesimi)</label>
                        <input type="number" name="maxContactlessAmount" id="maxContactlessAmount" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['maxContactlessAmount']) ?>"
                               placeholder="5000" min="0" max="10000">
                        <small class="text-muted">Limite per pagamenti contactless</small>
                    </div>
                    <div class="col-md-2">
                        <label for="maxTipPercent">Massima Mancia (%)</label>
                        <input type="number" name="maxTipPercent" id="maxTipPercent" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['maxTipPercent']) ?>"
                               placeholder="20" min="0" max="100">
                        <small class="text-muted">% massima mancia</small>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="allowTip" value="1" id="allowTip" class="form-check-input"
                                   <?= $currentConfig['allowTip'] ? 'checked' : '' ?>>
                            <label for="allowTip" class="form-check-label">
                                <strong>Abilita Mancia</strong>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="forcePIN" value="1" id="forcePIN" class="form-check-input"
                                   <?= $currentConfig['forcePIN'] ? 'checked' : '' ?>>
                            <label for="forcePIN" class="form-check-label">
                                <strong>Forza PIN</strong>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="allowRefund" value="1" id="allowRefund" class="form-check-input"
                                   <?= $currentConfig['allowRefund'] ? 'checked' : '' ?>>
                            <label for="allowRefund" class="form-check-label">
                                <strong>Abilita Rimborsi</strong>
                            </label>
                            <br>
                            <input type="checkbox" name="allowPartialRefund" value="1" id="allowPartialRefund" class="form-check-input"
                                   <?= $currentConfig['allowPartialRefund'] ? 'checked' : '' ?>>
                            <label for="allowPartialRefund" class="form-check-label">
                                Rimborsi Parziali
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Limiti Transazioni -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-chart-line"></i> Limiti Transazioni</h5>
                <small>Limiti operativi e transazionali</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="maxTransactionAmount">Importo Massimo Transazione</label>
                        <input type="number" name="maxTransactionAmount" id="maxTransactionAmount" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['maxTransactionAmount']) ?>"
                               placeholder="99999999" min="1">
                        <small class="text-muted">Centesimi (999999.99€ = 99999999)</small>
                    </div>
                    <div class="col-md-4">
                        <label for="dailyLimit">Limite Giornaliero</label>
                        <input type="number" name="dailyLimit" id="dailyLimit" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['dailyLimit']) ?>"
                               placeholder="500000000" min="1">
                        <small class="text-muted">Centesimi per giorno</small>
                    </div>
                    <div class="col-md-4">
                        <label for="maxRefundDays">Giorni Massimi per Rimborso</label>
                        <input type="number" name="maxRefundDays" id="maxRefundDays" class="form-control"
                               value="<?= htmlspecialchars($currentConfig['maxRefundDays']) ?>"
                               placeholder="365" min="1" max="730">
                        <small class="text-muted">Giorni entro cui rimborsare</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurazione Ricevute -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-receipt"></i> Configurazione Ricevute</h5>
                <small>Header e Footer personalizzati</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Header Ricevuta</h6>
                        <div class="form-group">
                            <input type="text" name="receiptHeader1" id="receiptHeader1" class="form-control"
                                   value="<?= htmlspecialchars($currentConfig['receiptHeader1']) ?>"
                                   placeholder="Nome negozio">
                            <small class="text-muted">Riga 1 - Nome negozio</small>
                        </div>
                        <div class="form-group">
                            <input type="text" name="receiptHeader2" id="receiptHeader2" class="form-control"
                                   value="<?= htmlspecialchars($currentConfig['receiptHeader2']) ?>"
                                   placeholder="Indirizzo">
                            <small class="text-muted">Riga 2 - Indirizzo (opzionale)</small>
                        </div>
                        <div class="form-group">
                            <input type="text" name="receiptHeader3" id="receiptHeader3" class="form-control"
                                   value="<?= htmlspecialchars($currentConfig['receiptHeader3']) ?>"
                                   placeholder="Telefono/Email">
                            <small class="text-muted">Riga 3 - Contatti (opzionale)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Footer Ricevuta</h6>
                        <div class="form-group">
                            <input type="text" name="receiptFooter1" id="receiptFooter1" class="form-control"
                                   value="<?= htmlspecialchars($currentConfig['receiptFooter1']) ?>"
                                   placeholder="Messaggio di ringraziamento">
                            <small class="text-muted">Riga 1 - Ringraziamento</small>
                        </div>
                        <div class="form-group">
                            <input type="text" name="receiptFooter2" id="receiptFooter2" class="form-control"
                                   value="<?= htmlspecialchars($currentConfig['receiptFooter2']) ?>"
                                   placeholder="Sito web">
                            <small class="text-muted">Riga 2 - Sito web</small>
                        </div>
                        <div class="form-group">
                            <input type="text" name="receiptFooter3" id="receiptFooter3" class="form-control"
                                   value="<?= htmlspecialchars($currentConfig['receiptFooter3']) ?>"
                                   placeholder="Info aggiuntive">
                            <small class="text-muted">Riga 3 - Note (opzionale)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configurazione Fiscale -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-file-invoice"></i> Configurazione Fiscale</h5>
                <small>Parametri per la gestione fiscale italiana</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="fiscalPrinter" value="1" id="fiscalPrinter" class="form-check-input"
                                   <?= $currentConfig['fiscalPrinter'] ? 'checked' : '' ?>>
                            <label for="fiscalPrinter" class="form-check-label">
                                <strong>Stampante Fiscale</strong><br>
                                <small class="text-muted">Abilita stampa fiscale</small>
                            </label>
                        </div>
                        <div class="form-group mt-3">
                            <label for="fiscalMode">Modalità Fiscale</label>
                            <select name="fiscalMode" id="fiscalMode" class="form-control">
                                <option value="IT_STANDARD" <?= $currentConfig['fiscalMode'] === 'IT_STANDARD' ? 'selected' : '' ?>>
                                    Italia Standard
                                </option>
                                <option value="IT_RT" <?= $currentConfig['fiscalMode'] === 'IT_RT' ? 'selected' : '' ?>>
                                    Italia RT
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h6>Aliquote IVA</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="vatRateA">Aliquota A (%)</label>
                                <input type="number" name="vatRateA" id="vatRateA" class="form-control"
                                       value="<?= htmlspecialchars($currentConfig['vatRateA']) ?>"
                                       placeholder="22.0" step="0.1" min="0" max="50">
                                <small class="text-muted">Ordinaria (22%)</small>
                            </div>
                            <div class="col-md-3">
                                <label for="vatRateB">Aliquota B (%)</label>
                                <input type="number" name="vatRateB" id="vatRateB" class="form-control"
                                       value="<?= htmlspecialchars($currentConfig['vatRateB']) ?>"
                                       placeholder="10.0" step="0.1" min="0" max="50">
                                <small class="text-muted">Ridotta (10%)</small>
                            </div>
                            <div class="col-md-3">
                                <label for="vatRateC">Aliquota C (%)</label>
                                <input type="number" name="vatRateC" id="vatRateC" class="form-control"
                                       value="<?= htmlspecialchars($currentConfig['vatRateC']) ?>"
                                       placeholder="4.0" step="0.1" min="0" max="50">
                                <small class="text-muted">Minima (4%)</small>
                            </div>
                            <div class="col-md-3">
                                <label for="vatRateD">Aliquota D (%)</label>
                                <input type="number" name="vatRateD" id="vatRateD" class="form-control"
                                       value="<?= htmlspecialchars($currentConfig['vatRateD']) ?>"
                                       placeholder="0.0" step="0.1" min="0" max="50">
                                <small class="text-muted">Esente (0%)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selectedTerminalId): ?>
        <!-- Pulsanti di azione -->
        <div class="card">
            <div class="card-body text-center">
                <button type="submit" class="btn btn-success btn-lg mr-3">
                    <i class="fas fa-save"></i> Salva Configurazione
                </button>
                <button type="reset" class="btn btn-secondary btn-lg">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </div>
    </form>
        <?php endif; ?>

    <!-- Info Box -->
    <div class="alert alert-info mt-4">
        <h6><i class="fas fa-info-circle"></i> Informazioni</h6>
        <ul class="mb-0">
            <li><strong>Campi obbligatori:</strong> sono contrassegnati con asterisco (*)</li>
            <li><strong>Validazione:</strong> i campi vengono validati secondo le specifiche PAX</li>
            <li><strong>Backup:</strong> le configurazioni di backup sono per il failover automatico</li>
            <li><strong>APN:</strong> necessari solo per connessioni GPRS</li>
        </ul>
    </div>
</div>

<!-- Modal Attivazione Satispay -->
<div class="modal fade" id="satispayActivationModal" tabindex="-1" role="dialog" aria-labelledby="satispayActivationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="satispayActivationModalLabel">
                    <i class="fas fa-play-circle"></i> Attivazione Satispay
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="satispayActivationAlert" class="alert" style="display: none;"></div>

                <div class="form-group">
                    <label for="satispayActivationToken">
                        <strong>Codice di Attivazione Satispay</strong>
                    </label>
                    <input type="text" class="form-control" id="satispayActivationToken"
                           placeholder="Inserisci il codice di attivazione dalla Dashboard Satispay">
                    <small class="form-text text-muted">
                        Vai su <strong>Dashboard Satispay → Negozio → Codice di Attivazione</strong> e copia il codice
                    </small>
                </div>

                <div class="form-group">
                    <label for="satispayActivationEnvironment">
                        <strong>Ambiente</strong>
                    </label>
                    <select class="form-control" id="satispayActivationEnvironment">
                        <option value="SANDBOX">SANDBOX (Test)</option>
                        <option value="PROD">PRODUCTION (Live)</option>
                    </select>
                    <small class="form-text text-muted">
                        Seleziona l'ambiente corrispondente al codice di attivazione
                    </small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Il Terminal ID utilizzato sarà <strong><?= htmlspecialchars($selectedTerminalId) ?></strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annulla
                </button>
                <button type="button" class="btn btn-primary" id="btnActivateSatispay">
                    <i class="fas fa-play-circle"></i> Attiva
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Validazione form
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
})();

// Gestione attivazione Satispay
<?php if ($selectedTerminalId): ?>
document.getElementById('btnActivateSatispay').addEventListener('click', function() {
    const token = document.getElementById('satispayActivationToken').value.trim();
    const environment = document.getElementById('satispayActivationEnvironment').value;
    const alertDiv = document.getElementById('satispayActivationAlert');
    const btn = this;

    // Validazione
    if (token === '') {
        alertDiv.className = 'alert alert-danger';
        alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Inserisci il codice di attivazione';
        alertDiv.style.display = 'block';
        return;
    }

    // Disabilita il bottone e mostra loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Attivazione in corso...';
    alertDiv.style.display = 'none';

    // Chiamata AJAX
    fetch('satispay_activation_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            terminalId: '<?= htmlspecialchars($selectedTerminalId) ?>',
            token: token,
            environment: environment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            // Successo
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Attivazione completata con successo!</strong><br>' +
                                 '<small>Ambiente: ' + data.environment + '</small>';
            alertDiv.style.display = 'block';

            // Ricarica la pagina dopo 2 secondi per aggiornare lo stato
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        } else {
            // Errore
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = '<i class="fas fa-times-circle"></i> <strong>Errore durante l\'attivazione</strong><br>' +
                                 '<small>' + (data.error || 'Errore sconosciuto') + '</small>';
            alertDiv.style.display = 'block';

            // Riabilita il bottone
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play-circle"></i> Attiva';
        }
    })
    .catch(error => {
        // Errore di rete o parsing
        alertDiv.className = 'alert alert-danger';
        alertDiv.innerHTML = '<i class="fas fa-times-circle"></i> <strong>Errore di connessione</strong><br>' +
                             '<small>' + error.message + '</small>';
        alertDiv.style.display = 'block';

        // Riabilita il bottone
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play-circle"></i> Attiva';
    });
});

// Reset del form quando si chiude il modal
$('#satispayActivationModal').on('hidden.bs.modal', function () {
    document.getElementById('satispayActivationToken').value = '';
    document.getElementById('satispayActivationEnvironment').value = 'SANDBOX';
    document.getElementById('satispayActivationAlert').style.display = 'none';
    document.getElementById('btnActivateSatispay').disabled = false;
    document.getElementById('btnActivateSatispay').innerHTML = '<i class="fas fa-play-circle"></i> Attiva';
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>