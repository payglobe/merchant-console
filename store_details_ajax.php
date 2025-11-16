<?php
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo '<div class="alert alert-danger">Accesso non autorizzato</div>';
    exit();
}

include 'config.php';

$terminalId = $_GET['tid'] ?? '';

if (!$terminalId) {
    echo '<div class="alert alert-warning">Terminal ID non specificato</div>';
    exit();
}

// Controllo BU
$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');

// Query per i dettagli del store
$storeQuery = "SELECT * FROM stores WHERE TerminalID = ?";
$storeParams = [$terminalId];
$storeTypes = "s";

if (!$isAdmin) {
    $storeQuery .= " AND bu = ?";
    $storeParams[] = $userBU;
    $storeTypes .= "s";
}

$stmt = $conn->prepare($storeQuery);
$stmt->bind_param($storeTypes, ...$storeParams);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$store) {
    echo '<div class="alert alert-danger">Store non trovato o accesso non autorizzato</div>';
    exit();
}

// Statistiche transazioni
$statsQuery = "SELECT 
    COUNT(*) as total_transactions,
    COUNT(CASE WHEN DATE(transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as last_30_days,
    COUNT(CASE WHEN DATE(transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
    SUM(CASE WHEN settlement_flag = '1' AND transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO') 
             THEN amount 
             WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO') 
             THEN -amount 
             ELSE 0 END) as total_volume,
    MAX(transaction_date) as last_transaction,
    MIN(transaction_date) as first_transaction
FROM transactions 
WHERE posid = ?";

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("s", $terminalId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Top circuiti
$circuitsQuery = "SELECT 
    card_brand, 
    COUNT(*) as count,
    SUM(CASE WHEN settlement_flag = '1' AND transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO') 
             THEN amount 
             WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO') 
             THEN -amount 
             ELSE 0 END) as volume
FROM transactions 
WHERE posid = ? AND settlement_flag = '1'
GROUP BY card_brand 
ORDER BY count DESC 
LIMIT 5";

$stmt = $conn->prepare($circuitsQuery);
$stmt->bind_param("s", $terminalId);
$stmt->execute();
$circuits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="row">
    <!-- Informazioni Base -->
    <div class="col-md-6">
        <h6 class="text-primary">Informazioni Generali</h6>
        <table class="table table-sm">
            <tr><td><strong>Terminal ID:</strong></td><td><code><?= htmlspecialchars($store['TerminalID']) ?></code></td></tr>
            <tr><td><strong>Ragione Sociale:</strong></td><td><?= htmlspecialchars($store['Ragione_Sociale'] ?: 'N/D') ?></td></tr>
            <tr><td><strong>Insegna:</strong></td><td><?= htmlspecialchars($store['Insegna'] ?: 'N/D') ?></td></tr>
            <tr><td><strong>BU:</strong></td><td><span class="badge badge-info"><?= htmlspecialchars($store['bu']) ?></span></td></tr>
            <tr><td><strong>Modello POS:</strong></td><td><?= htmlspecialchars($store['Modello_pos'] ?: 'N/D') ?></td></tr>
        </table>
    </div>
    
    <!-- Indirizzo -->
    <div class="col-md-6">
        <h6 class="text-primary">Localizzazione</h6>
        <table class="table table-sm">
            <tr><td><strong>Indirizzo:</strong></td><td><?= htmlspecialchars($store['indirizzo'] ?: 'N/D') ?></td></tr>
            <tr><td><strong>Città:</strong></td><td><?= htmlspecialchars($store['citta'] ?: 'N/D') ?></td></tr>
            <tr><td><strong>CAP:</strong></td><td><?= htmlspecialchars($store['cap'] ?: 'N/D') ?></td></tr>
            <tr><td><strong>Provincia:</strong></td><td><?= htmlspecialchars($store['prov'] ?: 'N/D') ?></td></tr>
            <tr><td><strong>Paese:</strong></td><td>
                <img src="flags/<?= strtolower($store['country']) ?>.svg"
                     alt="<?= $store['country'] ?>" style="width: 20px; height: 15px; margin-right: 5px;" onerror="this.style.display='none'">
                <?= htmlspecialchars($store['country'] ?: 'N/D') ?>
            </td></tr>
        </table>
    </div>
</div>

<hr>

<!-- Statistiche Transazioni -->
<div class="row">
    <div class="col-md-12">
        <h6 class="text-primary">Statistiche Transazioni</h6>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card bg-primary text-white text-center">
            <div class="card-body py-2">
                <h5><?= number_format($stats['total_transactions']) ?></h5>
                <small>Transazioni Totali</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white text-center">
            <div class="card-body py-2">
                <h5><?= number_format($stats['last_30_days']) ?></h5>
                <small>Ultimi 30 giorni</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white text-center">
            <div class="card-body py-2">
                <h5><?= number_format($stats['last_7_days']) ?></h5>
                <small>Ultimi 7 giorni</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white text-center">
            <div class="card-body py-2">
                <h5>€ <?= number_format($stats['total_volume'], 0) ?></h5>
                <small>Volume Totale</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Date Attività -->
    <div class="col-md-6">
        <h6 class="text-primary">Periodo Attività</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Prima Transazione:</strong></td>
                <td><?= $stats['first_transaction'] ? date('d/m/Y H:i', strtotime($stats['first_transaction'])) : 'N/D' ?></td>
            </tr>
            <tr>
                <td><strong>Ultima Transazione:</strong></td>
                <td><?= $stats['last_transaction'] ? date('d/m/Y H:i', strtotime($stats['last_transaction'])) : 'N/D' ?></td>
            </tr>
            <tr>
                <td><strong>Stato:</strong></td>
                <td>
                    <?php if ($stats['last_7_days'] > 0): ?>
                        <span class="badge badge-success">Attivo</span>
                    <?php elseif ($stats['last_30_days'] > 0): ?>
                        <span class="badge badge-warning">Poco Attivo</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inattivo</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Top Circuiti -->
    <div class="col-md-6">
        <h6 class="text-primary">Top Circuiti</h6>
        <?php if (!empty($circuits)): ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Circuito</th>
                        <th class="text-right">Transazioni</th>
                        <th class="text-right">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($circuits as $circuit): ?>
                    <tr>
                        <td><span class="badge badge-secondary"><?= htmlspecialchars($circuit['card_brand']) ?></span></td>
                        <td class="text-right"><?= number_format($circuit['count']) ?></td>
                        <td class="text-right">€ <?= number_format($circuit['volume'], 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Nessuna transazione registrata</p>
        <?php endif; ?>
    </div>
</div>

<hr>

<!-- Azioni Rapide -->
<div class="row">
    <div class="col-md-12 text-center">
        <h6 class="text-primary">Azioni Rapide</h6>
        <a href="index.php?filterStore=<?= urlencode($store['TerminalID']) ?>" 
           class="btn btn-primary" target="_blank">
            <i class="fas fa-chart-bar"></i> Dashboard Transazioni
        </a>
        <a href="statistics.php?filterStore=<?= urlencode($store['TerminalID']) ?>" 
           class="btn btn-info" target="_blank">
            <i class="fas fa-analytics"></i> Statistiche Avanzate
        </a>
        <?php if ($stats['total_transactions'] > 0): ?>
        <a href="" 
           class="btn btn-success" target="_blank">
            <i class="fas fa-file-excel"></i> Export Transazioni
        </a>
        <?php endif; ?>
    </div>
</div>

<?php $conn->close(); ?>
