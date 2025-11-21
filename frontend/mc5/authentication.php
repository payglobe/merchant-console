<?php
/**
 * MC5 Authentication System
 * Gestisce autenticazione e autorizzazione per PayGlobe MC5 v3.0
 */

// Avvia sessione se non già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connessione database
require_once __DIR__ . '/../../config.php';

// Configurazioni sessione
define('SESSION_TIMEOUT', 86400); // 24 ore in secondi
define('SESSION_REGENERATE_TIME', 3600); // Rigenera ID sessione ogni ora

/**
 * Verifica se l'utente è autenticato
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) &&
           isset($_SESSION['username']) &&
           isset($_SESSION['login_time']);
}

/**
 * Verifica se la sessione è scaduta
 */
function isSessionExpired() {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }

    $elapsed = time() - $_SESSION['login_time'];
    return $elapsed > SESSION_TIMEOUT;
}

/**
 * Rigenera ID sessione per sicurezza
 */
function regenerateSessionIfNeeded() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }

    $elapsed = time() - $_SESSION['last_regeneration'];
    if ($elapsed > SESSION_REGENERATE_TIME) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Ottiene informazioni utente corrente
 */
function getCurrentUser() {
    global $conn;

    if (!isAuthenticated()) {
        return null;
    }

    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, email, bu, active, last_login FROM users WHERE id = ? AND active = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Verifica se utente ha ruolo Admin
 */
function isAdmin() {
    return isset($_SESSION['bu']) && $_SESSION['bu'] === '9999';
}

/**
 * Verifica se utente ha ruolo Reader o superiore
 */
function isReader() {
    // Implementa logica di verifica ruolo Reader
    // Per ora ritorna true se autenticato
    return isAuthenticated();
}

/**
 * Logout utente
 */
function logout() {
    // Distruggi tutte le variabili di sessione
    $_SESSION = array();

    // Distruggi il cookie di sessione
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Distruggi la sessione
    session_destroy();
}

/**
 * Protegge la pagina richiedendo autenticazione
 */
function requireAuth($redirectToLogin = true) {
    // Verifica autenticazione
    if (!isAuthenticated()) {
        if ($redirectToLogin) {
            header("Location: login.php");
            exit;
        }
        return false;
    }

    // Verifica scadenza sessione
    if (isSessionExpired()) {
        logout();
        if ($redirectToLogin) {
            header("Location: login.php?session_expired=1");
            exit;
        }
        return false;
    }

    // Rigenera ID sessione periodicamente
    regenerateSessionIfNeeded();

    // Verifica che l'utente esista ancora nel database
    $user = getCurrentUser();
    if (!$user) {
        logout();
        if ($redirectToLogin) {
            header("Location: login.php?account_disabled=1");
            exit;
        }
        return false;
    }

    return true;
}

/**
 * Protegge la pagina richiedendo ruolo Admin
 */
function requireAdmin() {
    requireAuth();

    if (!isAdmin()) {
        http_response_code(403);
        die('Accesso negato: privilegi amministrativi richiesti');
    }
}

/**
 * Ottiene il ruolo dell'utente corrente
 */
function getUserRole() {
    if (!isAuthenticated()) {
        return null;
    }

    if (isAdmin()) {
        return 'Admin';
    }

    // Implementa logica per altri ruoli se necessario
    return 'Reader';
}

/**
 * Formatta ultimo login
 */
function formatLastLogin($lastLogin) {
    if (!$lastLogin) {
        return 'Mai';
    }

    $date = new DateTime($lastLogin);
    $now = new DateTime();
    $diff = $now->diff($date);

    if ($diff->days == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'Proprio ora';
            }
            return $diff->i . ' minuti fa';
        }
        return $diff->h . ' ore fa';
    } elseif ($diff->days == 1) {
        return 'Ieri';
    } elseif ($diff->days < 7) {
        return $diff->days . ' giorni fa';
    }

    return $date->format('d/m/Y H:i');
}

// Esegui controllo autenticazione se richiesto
// Le pagine pubbliche (login, forgot_password, reset_password) non devono includere questo controllo
$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'forgot_password.php', 'reset_password.php'];

if (!in_array($currentPage, $publicPages)) {
    // Questa è una pagina protetta, richiedi autenticazione
    requireAuth();

    // Carica informazioni utente per uso nelle pagine
    $user = getCurrentUser();
    $role = getUserRole();
    $application = $user['bu'] ?? 'N/A';
}

// Gestione messaggi di sessione (flash messages)
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Gestione parametri URL per messaggi
if (isset($_GET['session_expired'])) {
    setFlashMessage('warning', 'La tua sessione è scaduta. Effettua nuovamente il login.');
}

if (isset($_GET['account_disabled'])) {
    setFlashMessage('danger', 'Il tuo account è stato disabilitato. Contatta l\'amministratore.');
}

if (isset($_GET['password_changed'])) {
    setFlashMessage('success', 'Password aggiornata con successo!');
}
