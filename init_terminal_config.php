<?php
// File per inizializzare le tabelle per la configurazione terminali
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'terminal_config_handler.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'init_tables') {
    try {
        createConfigTables($conn);
        $success = "Tabelle create con successo!";
    } catch (Exception $e) {
        $error = "Errore nella creazione delle tabelle: " . $e->getMessage();
    }
}

// Controlla se le tabelle esistono
$tablesExist = false;
try {
    $result = $conn->query("DESCRIBE terminal_config");
    if ($result) {
        $tablesExist = true;
    }
} catch (Exception $e) {
    $tablesExist = false;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inizializzazione Database PAX</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-database"></i> Inizializzazione Database PAX</h4>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <br><br>
                    <a href="terminal_config_editor.php" class="btn btn-success">
                        <i class="fas fa-cogs"></i> Vai al Configuratore PAX
                    </a>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($tablesExist): ?>
                <div class="alert alert-info">
                    <i class="fas fa-check-circle"></i>
                    <strong>Le tabelle esistono già!</strong><br>
                    Il sistema di configurazione PAX è pronto per l'uso.
                    <br><br>
                    <a href="terminal_config_editor.php" class="btn btn-success">
                        <i class="fas fa-cogs"></i> Vai al Configuratore PAX
                    </a>
                    <a href="activation_codes.php" class="btn btn-primary ml-2">
                        <i class="fas fa-qrcode"></i> Gestione Codici
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Le tabelle del database non esistono ancora.</strong><br>
                    È necessario creare le tabelle per il sistema di configurazione PAX.
                </div>

                <h5>Tabelle da creare:</h5>
                <ul>
                    <li><strong>terminal_config</strong> - Configurazioni PAX per Terminal ID</li>
                    <li><strong>config_audit_log</strong> - Log delle modifiche alle configurazioni</li>
                </ul>

                <form method="post">
                    <input type="hidden" name="action" value="init_tables">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-database"></i> Crea Tabelle Database
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>