<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['bu'] !== '9999') {
    header("Location: login.php");
    exit();
}

include 'header.php';
include 'config.php';

// Gestione azioni CRUD
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$messageType = 'success';

switch ($action) {
    case 'create':
        if ($_POST) {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $bu = $_POST['bu'];
            $ragione_sociale = trim($_POST['ragione_sociale']);
            $active = isset($_POST['active']) ? 1 : 0;
            $force_password_change = isset($_POST['force_password_change']) ? 1 : 0;
            
            // Validazioni
            if (empty($email) || empty($password) || empty($bu)) {
                $message = "Email, password e BU sono obbligatori";
                $messageType = 'danger';
            } else {
                // Verifica se email già esiste
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                
                if ($checkStmt->get_result()->num_rows > 0) {
                    $message = "Email già esistente";
                    $messageType = 'danger';
                } else {
                    // Crea nuovo utente
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (email, password, bu, ragione_sociale, active, force_password_change, created_at, password_last_changed) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->bind_param("ssssii", $email, $hashedPassword, $bu, $ragione_sociale, $active, $force_password_change);
                    
                    if ($stmt->execute()) {
                        $message = "Utente creato con successo";
                        $messageType = 'success';
                    } else {
                        $message = "Errore nella creazione utente: " . $conn->error;
                        $messageType = 'danger';
                    }
                    $stmt->close();
                }
                $checkStmt->close();
            }
        }
        break;
        
    case 'update':
        if ($_POST) {
            $id = (int)$_POST['id'];
            $email = trim($_POST['email']);
            $bu = $_POST['bu'];
            $ragione_sociale = trim($_POST['ragione_sociale']);
            $active = isset($_POST['active']) ? 1 : 0;
            $force_password_change = isset($_POST['force_password_change']) ? 1 : 0;
            
            $stmt = $conn->prepare("UPDATE users SET email = ?, bu = ?, ragione_sociale = ?, active = ?, force_password_change = ? WHERE id = ?");
            $stmt->bind_param("sssiil", $email, $bu, $ragione_sociale, $active, $force_password_change, $id);
            
            if ($stmt->execute()) {
                $message = "Utente aggiornato con successo";
                $messageType = 'success';
            } else {
                $message = "Errore nell'aggiornamento: " . $conn->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
        break;
        
    case 'reset_password':
        if ($_POST) {
            $id = (int)$_POST['id'];
            $newPassword = $_POST['new_password'];
            
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, password_last_changed = NOW(), force_password_change = 1 WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $id);
                
                if ($stmt->execute()) {
                    $message = "Password resetata con successo. L'utente dovrà cambiarla al prossimo login";
                    $messageType = 'success';
                } else {
                    $message = "Errore nel reset password: " . $conn->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
        break;
        
    case 'delete':
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            // Verifica che non si stia eliminando se stesso
            if ($_SESSION['user_id'] == $id) {
                $message = "Non puoi eliminare il tuo stesso account";
                $messageType = 'danger';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Utente eliminato con successo";
                    $messageType = 'success';
                } else {
                    $message = "Errore nell'eliminazione: " . $conn->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
        break;
}

// Recupera tutti gli utenti
$usersQuery = "SELECT *, DATEDIFF(NOW(), password_last_changed) as password_age FROM users ORDER BY created_at DESC";
$users = $conn->query($usersQuery);

// Statistiche
$statsQuery = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN active = 1 THEN 1 END) as active_users,
    COUNT(CASE WHEN force_password_change = 1 THEN 1 END) as pending_password_change,
    COUNT(CASE WHEN DATEDIFF(NOW(), password_last_changed) >= 45 THEN 1 END) as expired_passwords
FROM users";
$stats = $conn->query($statsQuery)->fetch_assoc();

// Lista BU distinte
$buQuery = "SELECT DISTINCT bu FROM stores ORDER BY bu";
$buList = $conn->query($buQuery);
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-users-cog"></i> Amministrazione Utenti</h2>
            <p class="text-muted">Gestione completa degli utenti del sistema</p>
        </div>
        <div>
            <span class="badge badge-danger badge-lg">
                <i class="fas fa-shield-alt"></i> Solo Amministratori
            </span>
        </div>
    </div>

    <!-- Messaggi -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'danger' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Statistiche -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['total_users'] ?></h3>
                    <small>Utenti Totali</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['active_users'] ?></h3>
                    <small>Utenti Attivi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['pending_password_change'] ?></h3>
                    <small>Password da Cambiare</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['expired_passwords'] ?></h3>
                    <small>Password Scadute</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pulsante Nuovo Utente -->
    <div class="mb-3">
        <button class="btn btn-success" data-toggle="modal" data-target="#createUserModal">
            <i class="fas fa-plus"></i> Nuovo Utente
        </button>
        <button class="btn btn-info" onclick="exportUsers()">
            <i class="fas fa-download"></i> Esporta Utenti
        </button>
    </div>

    <!-- Tabella Utenti -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>BU</th>
                            <th>Ragione Sociale</th>
                            <th>Stato</th>
                            <th>Password</th>
                            <th>Ultimo Login</th>
                            <th>Creato il</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><code><?= $user['id'] ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($user['email']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($user['bu']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($user['ragione_sociale'] ?: 'N/D') ?></td>
                            <td>
                                <?php if ($user['active']): ?>
                                    <span class="badge badge-success">Attivo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inattivo</span>
                                <?php endif; ?>
                                
                                <?php if ($user['force_password_change']): ?>
                                    <br><small class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Deve cambiare password
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
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
                                </small>
                            </td>
                            <td>
                                <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Mai' ?>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                            </td>
                            <td>
                                <div class="btn-group-vertical btn-group-sm">
                                    <button class="btn btn-primary btn-sm" onclick="editUser(<?= $user['id'] ?>)">
                                        <i class="fas fa-edit"></i> Modifica
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="resetPassword(<?= $user['id'] ?>)">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['email']) ?>')">
                                        <i class="fas fa-trash"></i> Elimina
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuovo Utente -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuovo Utente</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" class="form-control" name="password" required minlength="8">
                        <small class="form-text text-muted">Minimo 8 caratteri</small>
                    </div>
                    <div class="form-group">
                        <label>Business Unit *</label>
                        <select class="form-control" name="bu" required>
                            <option value="">Seleziona BU</option>
                            <option value="9999">9999 - Amministratore</option>
                            <?php 
                            $buList->data_seek(0);
                            while ($bu = $buList->fetch_assoc()): 
                                if ($bu['bu'] != '9999'):
                            ?>
                                <option value="<?= htmlspecialchars($bu['bu']) ?>"><?= htmlspecialchars($bu['bu']) ?></option>
                            <?php 
                                endif;
                            endwhile; 
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ragione Sociale</label>
                        <input type="text" class="form-control" name="ragione_sociale">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="active" checked>
                        <label class="form-check-label">Utente Attivo</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="force_password_change">
                        <label class="form-check-label">Forza cambio password al primo login</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Crea Utente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Utente -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Utente</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" id="editUserForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-body" id="editUserContent">
                    <!-- Contenuto caricato via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nuova Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <small class="form-text text-muted">L'utente sarà forzato a cambiare la password al prossimo login</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(userId) {
    $('#edit_user_id').val(userId);
    $('#editUserModal').modal('show');
    
    // Carica dati utente via AJAX
    $.get('user_details_ajax.php', {id: userId}, function(response) {
        $('#editUserContent').html(response);
    }).fail(function() {
        $('#editUserContent').html('<div class="alert alert-danger">Errore nel caricamento dati utente</div>');
    });
}

function resetPassword(userId) {
    $('#reset_user_id').val(userId);
    $('#resetPasswordModal').modal('show');
}

function deleteUser(userId, email) {
    if (confirm(`Sei sicuro di voler eliminare l'utente "${email}"?\n\nQuesta operazione non può essere annullata.`)) {
        window.location.href = `?action=delete&id=${userId}`;
    }
}

function exportUsers() {
    window.open('export_users.php', '_blank');
}

// Auto dismiss alerts
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);
</script>

<?php include 'footer.php'; ?>
<?php $conn->close(); ?>
