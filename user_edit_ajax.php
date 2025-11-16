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

// Recupera dati utente
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo '<div class="alert alert-danger">Utente non trovato</div>';
    exit();
}

// Lista BU
$buQuery = "SELECT DISTINCT bu FROM stores ORDER BY bu";
$buList = $conn->query($buQuery);
?>

<div class="form-group">
    <label>Email *</label>
    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
</div>

<div class="form-group">
    <label>Business Unit *</label>
    <select class="form-control" name="bu" required>
        <option value="">Seleziona BU</option>
        <option value="9999" <?= $user['bu'] == '9999' ? 'selected' : '' ?>>9999 - Amministratore</option>
        <?php while ($bu = $buList->fetch_assoc()):
            if ($bu['bu'] != '9999'): ?>
            <option value="<?= htmlspecialchars($bu['bu']) ?>" <?= $user['bu'] == $bu['bu'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($bu['bu']) ?>
            </option>
        <?php
            endif;
        endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Ragione Sociale</label>
    <input type="text" class="form-control" name="ragione_sociale" value="<?= htmlspecialchars($user['ragione_sociale'] ?? '') ?>">
</div>

<div class="form-check">
    <input type="checkbox" class="form-check-input" name="active" <?= $user['active'] ? 'checked' : '' ?>>
    <label class="form-check-label">Utente Attivo</label>
</div>

<div class="form-check">
    <input type="checkbox" class="form-check-input" name="force_password_change" <?= $user['force_password_change'] ? 'checked' : '' ?>>
    <label class="form-check-label">Forza cambio password al prossimo login</label>
</div>

<?php $conn->close(); ?>