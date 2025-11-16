<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';
include 'config.php';

// Auto-migration: Aggiungi colonna language se non esiste
try {
    $checkColumn = $conn->query("SHOW COLUMNS FROM activation_codes LIKE 'language'");
    if ($checkColumn && $checkColumn->num_rows === 0) {
        $conn->query("ALTER TABLE activation_codes ADD COLUMN language VARCHAR(5) DEFAULT 'it' COMMENT 'Codice lingua: it, en, de, fr, es'");
        $conn->query("UPDATE activation_codes SET language = 'it' WHERE language IS NULL OR language = ''");
        // Verifica se l'indice esiste prima di crearlo
        $checkIndex = $conn->query("SHOW INDEX FROM activation_codes WHERE Key_name = 'idx_language'");
        if ($checkIndex && $checkIndex->num_rows === 0) {
            $conn->query("CREATE INDEX idx_language ON activation_codes(language)");
        }
    }
} catch (Exception $e) {
    // Ignora errori migration se già eseguita
    error_log("Migration language column: " . $e->getMessage());
}

// Rimuovi foreign key constraints se esistono
try {
    $conn->query("ALTER TABLE activation_codes DROP FOREIGN KEY activation_codes_ibfk_1");
} catch (Exception $e) {
    // Ignora se non esiste
}
try {
    $conn->query("ALTER TABLE activated_terminals DROP FOREIGN KEY activated_terminals_ibfk_1");
} catch (Exception $e) {
    // Ignora se non esiste
}

$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');
$username = $_SESSION['username'];

function generateActivationCode($conn) {
    do {
        $random = strtoupper(bin2hex(random_bytes(5)));
        $code = 'ACT-' . substr($random, 0, 9);
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM activation_codes WHERE code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        
    } while ($count > 0);
    
    return $code;
}

function logAudit($conn, $code, $action, $username, $details = '') {
    $stmt = $conn->prepare("
        INSERT INTO activation_audit_log (activation_code, action, user_agent, ip_address, performed_by, details) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $stmt->bind_param("ssssss", $code, $action, $userAgent, $ipAddress, $username, $details);
    $stmt->execute();
    $stmt->close();
}

// Pulizia automatica
$conn->query("UPDATE activation_codes SET status = 'EXPIRED' WHERE expires_at < NOW() AND status = 'PENDING'");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'create') {
        $terminalId = trim(isset($_POST['terminal_id']) ? $_POST['terminal_id'] : '');
        $bu = trim(isset($_POST['bu']) ? $_POST['bu'] : '---');
        $notes = trim(isset($_POST['notes']) ? $_POST['notes'] : '');
        $language = trim(isset($_POST['language']) ? $_POST['language'] : 'it');

        if (!$terminalId) {
            $error = "Inserisci un Terminal ID";
        } elseif (!preg_match('/^[A-Za-z0-9]{6,15}$/', $terminalId)) {
            $error = "Terminal ID deve contenere 6-15 caratteri alfanumerici";
        } else {
            if (!$isAdmin && $bu !== $userBU && $userBU !== '---') {
                $error = "Non hai autorizzazione per creare codici per la BU: " . $bu;
            } else {
                try {
                    $conn->begin_transaction();
                    
                    $code = generateActivationCode($conn);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+21 days'));
                    
                    $stmt = $conn->prepare("
                        INSERT INTO activation_codes
                        (code, store_terminal_id, bu, expires_at, created_by, notes, language)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("sssssss", $code, $terminalId, $bu, $expiresAt, $username, $notes, $language);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();

                    $success = "Codice di attivazione creato con successo!<br>";
                    $success .= "<strong>Codice:</strong> <code class='text-primary'>$code</code><br>";
                    $success .= "<strong>Terminal ID:</strong> $terminalId<br>";
                    $success .= "<strong>Business Unit:</strong> $bu<br>";
                    $success .= "<strong>Scade il:</strong> " . date('d/m/Y H:i', strtotime($expiresAt)) . "<br><br>";
                    $success .= "<a href='terminal_config_editor.php?terminal_id=" . urlencode($terminalId) . "' class='btn btn-success'>";
                    $success .= "<i class='fas fa-cogs'></i> Configura Parametri PAX</a>";

                    logAudit($conn, $code, 'CREATED', $username, "Terminal: $terminalId");
                    
                } catch (Exception $e) {
                    try {
                        $conn->rollback();
                    } catch (Exception $rollbackError) {
                        // Ignora errori rollback
                    }
                    $error = "Errore nella creazione: " . $e->getMessage();
                }
            }
        }
    // Funzione di modifica rimossa - si usa ora "Configura PAX"
    } elseif ($action === 'deactivate' && $isAdmin) {
        $codeId = intval(isset($_POST['code_id']) ? $_POST['code_id'] : 0);
        
        $stmt = $conn->prepare("SELECT code FROM activation_codes WHERE id = ?");
        $stmt->bind_param("i", $codeId);
        $stmt->execute();
        $codeInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($codeInfo) {
            $stmt = $conn->prepare("UPDATE activation_codes SET status = 'EXPIRED' WHERE id = ? AND status = 'PENDING'");
            $stmt->bind_param("i", $codeId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = "Codice disattivato con successo";
                logAudit($conn, $codeInfo['code'], 'EXPIRED', $username, 'Manually deactivated');
            } else {
                $error = "Errore nella disattivazione";
            }
            $stmt->close();
        } else {
            $error = "Codice non trovato";
        }
    } elseif ($action === 'delete' && $isAdmin) {
        $codeId = intval(isset($_POST['code_id']) ? $_POST['code_id'] : 0);
        
        $stmt = $conn->prepare("SELECT code, status, store_terminal_id FROM activation_codes WHERE id = ?");
        $stmt->bind_param("i", $codeId);
        $stmt->execute();
        $codeInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($codeInfo) {
            try {
                $conn->begin_transaction();
                
                $stmt = $conn->prepare("DELETE FROM activation_codes WHERE id = ?");
                $stmt->bind_param("i", $codeId);
                $stmt->execute();
                $stmt->close();
                
                if ($codeInfo['status'] === 'USED') {
                    $stmt = $conn->prepare("DELETE FROM activated_terminals WHERE activation_code = ?");
                    $stmt->bind_param("s", $codeInfo['code']);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Gateway non più utilizzati - rimossi
                
                $conn->commit();
                
                $success = "Codice eliminato con successo" . ($codeInfo['status'] === 'USED' ? " (terminale disattivato)" : "");
                logAudit($conn, $codeInfo['code'], 'DELETED', $username, 'Deleted by admin');
                
            } catch (Exception $e) {
                try {
                    $conn->rollback();
                } catch (Exception $rollbackError) {
                    // Ignora
                }
                $error = "Errore nell'eliminazione: " . $e->getMessage();
            }
        } else {
            $error = "Codice non trovato";
        }
    } elseif ($action === 'bulk_cleanup' && $isAdmin) {
        $result = $conn->query("SELECT COUNT(*) as count FROM activation_codes WHERE status = 'EXPIRED'");
        $expiredCount = $result->fetch_assoc()['count'];
        
        if ($expiredCount > 0) {
            $conn->query("DELETE FROM activation_codes WHERE status = 'EXPIRED' AND used_at IS NULL");
            $success = "Pulizia completata: eliminati $expiredCount codici scaduti";
            logAudit($conn, 'BULK', 'CLEANUP', $username, "Cleaned $expiredCount codes");
        } else {
            $success = "Nessun codice scaduto da pulire";
        }
    }
}

// Query codici con filtri
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$buFilter = isset($_GET['bu']) ? $_GET['bu'] : '';
$searchFilter = trim(isset($_GET['search']) ? $_GET['search'] : '');

$whereConditions = array();
$params = array();
$types = '';

$baseQuery = "
    SELECT ac.*,
           CASE
               WHEN ac.expires_at < NOW() AND ac.status = 'PENDING' THEN 'EXPIRED'
               ELSE ac.status
           END as current_status,
           DATEDIFF(ac.expires_at, NOW()) as days_left
    FROM activation_codes ac
";

if (!$isAdmin) {
    $whereConditions[] = "ac.bu = ?";
    $params[] = $userBU;
    $types .= 's';
}

if ($statusFilter !== 'all') {
    if ($statusFilter === 'expired') {
        $whereConditions[] = "(ac.expires_at < NOW() OR ac.status = 'EXPIRED')";
    } else {
        $whereConditions[] = "ac.status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }
}

if ($isAdmin && $buFilter) {
    $whereConditions[] = "ac.bu = ?";
    $params[] = $buFilter;
    $types .= 's';
}

if ($searchFilter) {
    $whereConditions[] = "(ac.code LIKE ? OR ac.store_terminal_id LIKE ? OR ac.notes LIKE ?)";
    $searchParam = "%$searchFilter%";
    $params = array_merge($params, array($searchParam, $searchParam, $searchParam));
    $types .= 'sss';
}

$query = $baseQuery;
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}
$query .= " ORDER BY ac.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$codes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Statistiche
if ($isAdmin) {
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'PENDING' AND expires_at > NOW() THEN 1 END) as active,
            COUNT(CASE WHEN status = 'USED' THEN 1 END) as used,
            COUNT(CASE WHEN status = 'EXPIRED' OR expires_at <= NOW() THEN 1 END) as expired
        FROM activation_codes
    ";
    $statsStmt = $conn->prepare($statsQuery);
} else {
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'PENDING' AND expires_at > NOW() THEN 1 END) as active,
            COUNT(CASE WHEN status = 'USED' THEN 1 END) as used,
            COUNT(CASE WHEN status = 'EXPIRED' OR expires_at <= NOW() THEN 1 END) as expired
        FROM activation_codes
        WHERE bu = ?
    ";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param("s", $userBU);
}
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

$buList = array();
if ($isAdmin) {
    $buResult = $conn->query("SELECT DISTINCT bu FROM activation_codes ORDER BY bu");
    $buList = $buResult->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2><i class="fas fa-qrcode"></i> Gestione Codici di Attivazione PAX</h2>
            <small class="text-muted">Inserimento manuale Terminal ID con gateway personalizzato - Scadenza 21 giorni</small>
        </div>
        <div>
            <?php if ($isAdmin): ?>
                <span class="badge badge-warning">
                    <i class="fas fa-crown"></i> Admin - Tutti i Codici
                </span>
            <?php else: ?>
                <span class="badge badge-info">
                    <i class="fas fa-building"></i> BU: <?= htmlspecialchars($userBU) ?>
                </span>
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

    <!-- Statistiche -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-2x mb-2"></i>
                    <h5>Totali</h5>
                    <h3><?= number_format($stats['total']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-play-circle fa-2x mb-2"></i>
                    <h5>Attivi</h5>
                    <h3><?= number_format($stats['active']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h5>Utilizzati</h5>
                    <h3><?= number_format($stats['used']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h5>Scaduti</h5>
                    <h3><?= number_format($stats['expired']) ?></h3>
                    <?php if ($isAdmin && $stats['expired'] > 0): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="bulk_cleanup">
                            <button type="submit" class="btn btn-sm btn-outline-light mt-1" 
                                    onclick="return confirm('Eliminare tutti i codici scaduti?')">
                                <i class="fas fa-trash"></i> Pulisci
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Form creazione -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5><i class="fas fa-plus-circle"></i> Crea Nuovo Codice di Attivazione</h5>
            <small>Genera codice ACT per Terminal ID - Configura i parametri PAX dopo la creazione</small>
        </div>
        <div class="card-body">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-3">
                        <label for="terminal_id">Terminal ID *</label>
                        <input type="text" name="terminal_id" id="terminal_id" class="form-control"
                               placeholder="12340002" required maxlength="15" pattern="[A-Za-z0-9]{6,15}">
                        <small class="text-muted">6-15 caratteri alfanumerici</small>
                    </div>
                    <div class="col-md-2">
                        <label for="bu">Business Unit</label>
                        <input type="text" name="bu" id="bu" class="form-control"
                               placeholder="001" maxlength="10" value="<?= $isAdmin ? '' : htmlspecialchars($userBU) ?>">
                        <small class="text-muted">P.IVA o codice BU</small>
                    </div>
                    <div class="col-md-2">
                        <label for="language">Lingua</label>
                        <select name="language" id="language" class="form-control">
                            <option value="it" selected>Italiano</option>
                            <option value="en">English</option>
                            <option value="de">Deutsch</option>
                            <option value="fr">Français</option>
                            <option value="es">Español</option>
                        </select>
                        <small class="text-muted">Lingua interfaccia</small>
                    </div>
                    <div class="col-md-3">
                        <label for="notes">Note (opzionali)</label>
                        <input type="text" name="notes" id="notes" class="form-control"
                               placeholder="Descrizione del terminale">
                        <small class="text-muted">Note descrittive</small>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-plus"></i> Crea Codice ACT
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Dopo la creazione</strong> usa il pulsante "Configura PAX" per impostare tutti i parametri di connessione specifici.
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtri -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtri</h5>
        </div>
        <div class="card-body">
            <form method="get">
                <div class="row">
                    <div class="col-md-3">
                        <select name="status" class="form-control">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tutti gli stati</option>
                            <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Attivi</option>
                            <option value="USED" <?= $statusFilter === 'USED' ? 'selected' : '' ?>>Utilizzati</option>
                            <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Scaduti</option>
                        </select>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="col-md-2">
                        <select name="bu" class="form-control">
                            <option value="">Tutte le BU</option>
                            <?php foreach ($buList as $bu): ?>
                                <option value="<?= htmlspecialchars($bu['bu']) ?>" <?= $buFilter === $bu['bu'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bu['bu']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cerca codice, terminal, note..." 
                               value="<?= htmlspecialchars($searchFilter) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtra
                        </button>
                        <a href="?" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabella codici -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Codici di Attivazione (<?= count($codes) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($codes)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nessun codice trovato</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Codice</th>
                                <th>Terminal ID</th>
                                <th>Config PAX</th>
                                <?php if ($isAdmin): ?><th>BU</th><?php endif; ?>
                                <th>Lingua</th>
                                <th>Stato</th>
                                <th>Scadenza</th>
                                <th>Note</th>
                                <th>Creato</th>
                                <?php if ($isAdmin): ?><th>Azioni</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($codes as $code): ?>
                            <?php
                            $status = $code['current_status'];
                            if ($code['expires_at'] < date('Y-m-d H:i:s') && $status === 'PENDING') {
                                $status = 'EXPIRED';
                            }
                            
                            if ($status === 'PENDING') {
                                $badgeClass = ($code['days_left'] >= 0 ? 'badge-success' : 'badge-warning');
                                if ($code['days_left'] > 1) {
                                    $statusText = 'Attivo';
                                } elseif ($code['days_left'] == 1) {
                                    $statusText = 'Scade Domani';
                                } else {
                                    $statusText = 'Scade Oggi';
                                }
                            } elseif ($status === 'USED') {
                                $badgeClass = 'badge-info';
                                $statusText = 'Utilizzato';
                            } elseif ($status === 'EXPIRED') {
                                $badgeClass = 'badge-danger';
                                $statusText = 'Scaduto';
                            } else {
                                $badgeClass = 'badge-secondary';
                                $statusText = 'Sconosciuto';
                            }
                            
                            // Verifica se ha configurazioni PAX
                            $configStmt = $conn->prepare("SELECT COUNT(*) as config_count FROM terminal_config WHERE terminal_id = ?");
                            $configStmt->bind_param("s", $code['store_terminal_id']);
                            $configStmt->execute();
                            $configInfo = $configStmt->get_result()->fetch_assoc();
                            $configStmt->close();
                            $hasConfig = $configInfo['config_count'] > 0;
                            ?>
                            <tr class="<?= $status === 'EXPIRED' ? 'table-secondary' : '' ?>">
                                <td>
                                    <code class="<?= $status === 'PENDING' && $code['days_left'] >= 0 ? 'text-primary' : 'text-muted' ?>">
                                        <?= htmlspecialchars($code['code']) ?>
                                    </code>
                                    <?php if ($status === 'PENDING' && $code['days_left'] >= 0): ?>
                                        <button class="btn btn-sm btn-outline-secondary ml-1" 
                                                onclick="copyToClipboard('<?= htmlspecialchars($code['code']) ?>')"
                                                title="Copia codice">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($code['store_terminal_id']) ?></strong>
                                    <br><small class="text-muted">
                                        <i class="fas fa-microchip"></i> Terminal PAX
                                    </small>
                                </td>
                                <td>
                                    <?php if ($hasConfig): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Configurato
                                        </span>
                                        <br><small class="text-muted">
                                            <i class="fas fa-cogs"></i> Parametri PAX impostati
                                        </small>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Da configurare
                                        </span>
                                        <br><small class="text-muted">
                                            <i class="fas fa-arrow-right"></i> Clicca "Configura PAX"
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isAdmin): ?>
                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($code['bu']) ?></span></td>
                                <?php endif; ?>
                                <td>
                                    <?php
                                    $langLabels = ['it' => 'IT', 'en' => 'EN', 'de' => 'DE', 'fr' => 'FR', 'es' => 'ES'];
                                    $langColors = ['it' => 'success', 'en' => 'primary', 'de' => 'warning', 'fr' => 'info', 'es' => 'danger'];
                                    $lang = isset($code['language']) ? $code['language'] : 'it';
                                    ?>
                                    <span class="badge badge-<?= $langColors[$lang] ?? 'secondary' ?>"><?= $langLabels[$lang] ?? strtoupper($lang) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                    <?php if ($status === 'PENDING' && $code['days_left'] >= 0): ?>
                                        <br><small class="text-muted">
                                            <?php if ($code['days_left'] > 1): ?>
                                                <i class="fas fa-clock"></i> <?= $code['days_left'] ?> giorni
                                            <?php elseif ($code['days_left'] == 1): ?>
                                                <i class="fas fa-exclamation-triangle text-warning"></i> Domani
                                            <?php else: ?>
                                                <i class="fas fa-exclamation-triangle text-danger"></i> Oggi
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('d/m/Y H:i', strtotime($code['expires_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($code['notes']): ?>
                                        <span title="<?= htmlspecialchars($code['notes']) ?>">
                                            <?= htmlspecialchars(strlen($code['notes']) > 30 ? substr($code['notes'], 0, 30) . '...' : $code['notes']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?= htmlspecialchars($code['created_by']) ?>
                                        <br><?= date('d/m/Y H:i', strtotime($code['created_at'])) ?>
                                        <?php if ($code['status'] === 'USED' && $code['used_at']): ?>
                                            <br><span class="text-success">
                                                <i class="fas fa-check-circle"></i> Usato: <?= date('d/m/Y H:i', strtotime($code['used_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <?php if ($isAdmin): ?>
                                    <td style="min-width: 120px;">
                                        <!-- Configura PAX -->
                                        <a href="terminal_config_editor.php?terminal_id=<?= urlencode($code['store_terminal_id']) ?>"
                                           class="btn btn-sm btn-success mb-1"
                                           title="Configura parametri PAX">
                                            <i class="fas fa-cogs"></i>
                                        </a>

                                        <!-- JSON API Link -->
                                        <a href="api/terminal/config.php?activationCode=<?= urlencode($code['code']) ?>"
                                           class="btn btn-sm btn-info mb-1"
                                           title="Visualizza JSON configurazione"
                                           target="_blank">
                                            <i class="fas fa-code"></i>
                                        </a>
                                        
                                        <?php if ($status === 'PENDING' && $code['days_left'] >= 0): ?>
                                            <!-- Disattiva -->
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('Disattivare questo codice?')">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="code_id" value="<?= $code['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-warning mb-1" title="Disattiva">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Elimina - SEMPRE disponibile -->
                                        <form method="post" style="display: inline;" 
                                              onsubmit="return confirm('ELIMINARE questo codice?\n\nPuò essere ricreato successivamente se necessario.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="code_id" value="<?= $code['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Elimina codice">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal di Modifica rimosso - si usa "Configura PAX" -->

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        
        setTimeout(function() {
            btn.innerHTML = originalHTML;
        }, 1500);
    });
}

// Funzione editCode rimossa - si usa "Configura PAX"

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
</script>

<?php include 'footer.php'; ?>