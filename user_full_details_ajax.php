<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['bu'] !== '9999') {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit();
}

include 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID utente non valido</div>';
    exit();
}

$userId = (int)$_GET['id'];

// Recupera dati utente completi
$stmt = $conn->prepare("
    SELECT u.*,
           DATEDIFF(NOW(), u.password_last_changed) as password_age,
           COUNT(DISTINCT ac.id) as total_codes,
           COUNT(DISTINCT CASE WHEN ac.status = 'USED' THEN ac.id END) as used_codes,
           COUNT(DISTINCT at.id) as active_terminals
    FROM users u
    LEFT JOIN activation_codes ac ON u.bu = ac.bu
    LEFT JOIN activated_terminals at ON ac.code = at.activation_code
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo '<div class="alert alert-danger">Utente non trovato</div>';
    exit();
}

// Ultimi login
$loginStmt = $conn->prepare("
    SELECT login_time, ip_address, user_agent
    FROM login_log
    WHERE user_id = ?
    ORDER BY login_time DESC
    LIMIT 5
");
$loginStmt->bind_param("i", $userId);
$loginStmt->execute();
$recentLogins = $loginStmt->get_result();
$loginStmt->close();
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-user"></i> Informazioni Generali</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>ID:</strong></td>
                <td><code><?= $user['id'] ?></code></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
            </tr>
            <tr>
                <td><strong>Business Unit:</strong></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($user['bu']) ?></span></td>
            </tr>
            <tr>
                <td><strong>Ragione Sociale:</strong></td>
                <td><?= htmlspecialchars($user['ragione_sociale'] ?? 'N/D') ?></td>
            </tr>
            <tr>
                <td><strong>Stato:</strong></td>
                <td>
                    <?php if ($user['active']): ?>
                        <span class="badge badge-success">Attivo</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inattivo</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Creato il:</strong></td>
                <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h6><i class="fas fa-key"></i> Sicurezza Password</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Ultima modifica:</strong></td>
                <td><?= date('d/m/Y H:i', strtotime($user['password_last_changed'])) ?></td>
            </tr>
            <tr>
                <td><strong>Età password:</strong></td>
                <td>
                    <?php if ($user['password_age'] >= 45): ?>
                        <span class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Scaduta (<?= $user['password_age'] ?> giorni)
                        </span>
                    <?php elseif ($user['password_age'] >= 35): ?>
                        <span class="text-warning">
                            <i class="fas fa-clock"></i>
                            Scade presto (<?= $user['password_age'] ?> giorni)
                        </span>
                    <?php else: ?>
                        <span class="text-success">
                            <i class="fas fa-check"></i>
                            Valida (<?= $user['password_age'] ?> giorni)
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Cambio forzato:</strong></td>
                <td>
                    <?php if ($user['force_password_change']): ?>
                        <span class="badge badge-warning">Sì</span>
                    <?php else: ?>
                        <span class="badge badge-success">No</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Ultimo login:</strong></td>
                <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Mai' ?></td>
            </tr>
        </table>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-chart-bar"></i> Statistiche Attivazioni</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Codici totali (BU):</strong></td>
                <td><span class="badge badge-primary"><?= $user['total_codes'] ?></span></td>
            </tr>
            <tr>
                <td><strong>Codici utilizzati:</strong></td>
                <td><span class="badge badge-success"><?= $user['used_codes'] ?></span></td>
            </tr>
            <tr>
                <td><strong>Terminali attivi:</strong></td>
                <td><span class="badge badge-info"><?= $user['active_terminals'] ?></span></td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h6><i class="fas fa-history"></i> Ultimi Login</h6>
        <?php if ($recentLogins->num_rows > 0): ?>
            <div style="max-height: 150px; overflow-y: auto;">
                <table class="table table-sm">
                    <?php while ($login = $recentLogins->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <small>
                                <strong><?= date('d/m/Y H:i', strtotime($login['login_time'])) ?></strong><br>
                                <code><?= htmlspecialchars($login['ip_address']) ?></code>
                            </small>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Nessun login registrato</p>
        <?php endif; ?>
    </div>
</div>

<?php $conn->close(); ?>