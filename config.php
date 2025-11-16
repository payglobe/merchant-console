<?php
// config.php
// Connessione CoNN robusta con eccezioni e utf8mb4

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$servername = "10.10.10.13";
$username   = "PGDBUSER";
$password   = "PNeNkar{K1.%D~V";
$dbname     = "payglobe";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');

    // Modalità SQL "strict" (evita inserimenti tronchi o silenziosi)
    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    // Loggare $e->getMessage() su file/monitoring, non mostrarlo all'utente
    exit('Errore di connessione al database.');
}

// ============================================
// CONFIGURAZIONI AGGIUNTIVE SISTEMA ATTIVAZIONE
// ============================================

// Timezone Italia
date_default_timezone_set('Europe/Rome');
$conn->query("SET time_zone = '+01:00'");

// Configurazioni sistema
define('APP_NAME', 'PayGlobe Admin');
define('APP_VERSION', '1.0');
define('ACTIVATION_CODE_EXPIRY_DAYS', 21);  // 3 settimane
define('TERMINAL_ID_LENGTH', 8);
define('ADMIN_BU', '9999');  // BU per super admin

// URL API (aggiorna se diverso)
define('API_BASE_URL', 'https://ricevute.payglobe.it/api');
define('ACTIVATION_API_URL', API_BASE_URL . '/terminal/config.php');

// Configurazione logging
define('LOG_ACTIVATION_EVENTS', true);
define('LOG_FILE_PATH', 'logs/activation.log');

// Funzioni utility per sistema attivazione
function isAdmin() {
    return isset($_SESSION['bu']) && $_SESSION['bu'] === ADMIN_BU;
}

function getUserBU() {
    return $_SESSION['bu'] ?? '';
}

function isValidActivationCode($code) {
    return preg_match('/^ACT-[A-Z0-9]{9}$/', $code);
}

function generateSecureActivationCode($conn) {
    $maxAttempts = 100;
    $attempt = 0;
    
    do {
        $random = strtoupper(bin2hex(random_bytes(5)));
        $code = 'ACT-' . substr($random, 0, 9);
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM activation_codes WHERE code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        
        $attempt++;
        if ($attempt > $maxAttempts) {
            throw new Exception('Unable to generate unique activation code');
        }
        
    } while ($exists > 0);
    
    return $code;
}

function logActivationEvent($message, $context = '') {
    if (LOG_ACTIVATION_EVENTS) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message";
        if ($context) $logEntry .= " - $context";
        $logEntry .= "\n";
        
        $logDir = dirname(LOG_FILE_PATH);
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        
        file_put_contents(LOG_FILE_PATH, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Avvia sessione se non attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variabili globali per compatibilità
$isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);
define('DEBUG_MODE', !$isProduction);

// Mantieni le variabili originali per compatibilità con codice esistente  
$db_host = $servername;
$db_username = $username;  
$db_password = $password;
$db_name = $dbname;
