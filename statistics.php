<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';
include 'config.php';

// Funzione per estrarre il BIN (prime 6 cifre del PAN)
function extractBIN($pan) {
    return substr($pan, 0, 6);
}

// Database BIN delle principali banche italiane ed europee
function getBankName($bin) {
    $binDatabase = [
        // BANCHE ITALIANE
        '400115' => 'Poste Italiane',
        '400124' => 'Poste Italiane',
        '542927' => 'Poste Italiane',
        '513226' => 'Poste Italiane',
        '400593' => 'Banco BPM',
        '454393' => 'Banco BPM',
        '543471' => 'Banco BPM',
        '400594' => 'Intesa Sanpaolo',
        '492901' => 'Intesa Sanpaolo', 
        '454347' => 'Intesa Sanpaolo',
        '454617' => 'Intesa Sanpaolo',
        '542116' => 'Intesa Sanpaolo',
        '400595' => 'UniCredit',
        '549530' => 'UniCredit',
        '454360' => 'UniCredit',
        '454625' => 'UniCredit',
        '400596' => 'Monte dei Paschi',
        '454379' => 'Monte dei Paschi',
        '549538' => 'Monte dei Paschi',
        '486493' => 'Mediolanum',
        '520308' => 'Mediolanum',
        '543357' => 'Fineco',
        '454382' => 'Fineco',
        '400592' => 'BNL',
        '454824' => 'BNL',
        '549927' => 'Ing Bank',
        '465922' => 'CartaSi',
        '465923' => 'CartaSi',
        '465924' => 'CartaSi',
        
        // BANCHE EUROPEE PRINCIPALI
        '417500' => 'Visa France',
        '434307' => 'Société Générale',
        '533844' => 'BNP Paribas',
        '522189' => 'Credit Agricole',
        '400115' => 'Deutsche Bank',
        '454736' => 'Commerzbank',
        '549167' => 'ING Deutschland',
        '516378' => 'Santander ES',
        '454313' => 'BBVA',
        '454630' => 'CaixaBank',
        '543471' => 'Revolut',
        '533317' => 'N26',
    ];
    
    return $binDatabase[$bin] ?? "Banca " . substr($bin, 0, 4) . "**";
}

// Funzione per determinare il paese dal BIN
function getCountryFromBIN($bin) {
    $first4 = substr($bin, 0, 4);
    // Range approssimativi per alcuni paesi
    if ($first4 >= '4000' && $first4 <= '4999') {
        if ($first4 >= '4001' && $first4 <= '4009') return 'Italia';
        if ($first4 >= '4970' && $first4 <= '4999') return 'Francia';
        return 'Europa/USA';
    }
    if ($first4 >= '5000' && $first4 <= '5999') {
        if ($first4 >= '5132' && $first4 <= '5139') return 'Italia';
        return 'Europa';
    }
    return 'Altro';
}

// Controllo BU
$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');

// Limite righe per admin - ottimizzato per velocità (no crash 504)
$adminRowLimit = 1000;
$limitReached = false;

// Parametri filtri
// Admin: periodo default 7 giorni per velocità. Altri utenti: 30 giorni
$defaultDays = $isAdmin ? '-7 days' : '-30 days';
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime($defaultDays));
$endDate = $_GET['endDate'] ?? date('Y-m-d');
$filterStore = $_GET['filterStore'] ?? '';
$periodType = $_GET['periodType'] ?? 'daily'; // daily, weekly, monthly

// Lista negozi per dropdown - raggruppati per punto vendita
// Per admin: query ultra-semplificata senza JOIN per evitare timeout 504
if ($isAdmin) {
    $storeQuery = "SELECT GROUP_CONCAT(DISTINCT s.TerminalID ORDER BY s.TerminalID SEPARATOR ',') as TerminalIDs,
                          s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta,
                          COUNT(DISTINCT s.TerminalID) as terminal_count
                   FROM stores s
                   WHERE s.TerminalID IS NOT NULL
                   GROUP BY s.Ragione_Sociale, s.indirizzo, s.citta
                   ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo
                   LIMIT 500";
    $storeStmt = $conn->prepare($storeQuery);
} else {
    $storeQuery = "SELECT GROUP_CONCAT(DISTINCT s.TerminalID ORDER BY s.TerminalID SEPARATOR ',') as TerminalIDs,
                          s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta,
                          COUNT(DISTINCT s.TerminalID) as terminal_count
                   FROM stores s INNER JOIN transactions t ON s.TerminalID = t.posid
                   WHERE s.TerminalID IS NOT NULL AND s.bu = ?
                   GROUP BY s.Ragione_Sociale, s.indirizzo, s.citta
                   ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo";
    $storeStmt = $conn->prepare($storeQuery);
    $storeStmt->bind_param("s", $userBU);
}
$storeStmt->execute();
$stores = $storeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$storeStmt->close();

// Costruzione WHERE clause e parametri base
$baseWhere = "WHERE DATE(t.transaction_date) BETWEEN ? AND ? AND t.settlement_flag = '1'";
$baseParams = [$startDate, $endDate];
$baseTypes = "ss";

if (!$isAdmin) {
    $baseWhere .= " AND s.bu = ?";
    $baseParams[] = $userBU;
    $baseTypes .= "s";
}

if ($filterStore) {
    // Il filtro contiene una lista di TerminalID separati da virgola
    $terminalList = explode(',', $filterStore);
    $placeholders = str_repeat('?,', count($terminalList) - 1) . '?';
    $baseWhere .= " AND t.posid IN ($placeholders)";

    foreach ($terminalList as $terminal) {
        $baseParams[] = trim($terminal);
        $baseTypes .= "s";
    }
}

// Copia per ogni query
$whereClause = $baseWhere;
$params = $baseParams;
$types = $baseTypes;

// 1. STATISTICHE GENERALI E VOLUME MEDIO
// Per admin: applica filtri PRIMA del LIMIT per evitare timeout
if ($isAdmin) {
    $statsQuery = "
        SELECT
            COUNT(*) as total_transactions,
            SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                     THEN t.amount ELSE -t.amount END) as net_volume,
            AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                     THEN t.amount ELSE NULL END) as avg_ticket,
            DATEDIFF(?, ?) + 1 as period_days
        FROM (
            SELECT * FROM transactions t
            $whereClause
            LIMIT $adminRowLimit
        ) t
    ";
} else {
    $statsQuery = "
        SELECT
            COUNT(*) as total_transactions,
            SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                     THEN t.amount ELSE -t.amount END) as net_volume,
            AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                     THEN t.amount ELSE NULL END) as avg_ticket,
            DATEDIFF(?, ?) + 1 as period_days
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        $whereClause
    ";
}

// Parametri per statsQuery: endDate, startDate + parametri base
$statsParams = [$endDate, $startDate];
$statsParams = array_merge($statsParams, $params);
$statsTypes = "ss" . $types;

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param($statsTypes, ...$statsParams);
$stmt->execute();
$generalStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Verifica se il limite è stato raggiunto per admin
if ($isAdmin && $generalStats['total_transactions'] >= $adminRowLimit) {
    $limitReached = true;
}

$dailyAvgVolume = $generalStats['net_volume'] / $generalStats['period_days'];
$dailyAvgTransactions = $generalStats['total_transactions'] / $generalStats['period_days'];

// 2. ANALISI PER GIORNO DELLA SETTIMANA
if ($limitReached) {
    $dayAnalysis = [];
} else {
    if ($isAdmin) {
        $dayAnalysisQuery = "
            SELECT
                DAYNAME(t.transaction_date) as day_name,
                DAYOFWEEK(t.transaction_date) as day_num,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as daily_volume,
                AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE NULL END) as avg_ticket
            FROM (SELECT * FROM transactions t $whereClause LIMIT $adminRowLimit) t
            GROUP BY DAYOFWEEK(t.transaction_date), DAYNAME(t.transaction_date)
            ORDER BY day_num
        ";
    } else {
        $dayAnalysisQuery = "
            SELECT
                DAYNAME(t.transaction_date) as day_name,
                DAYOFWEEK(t.transaction_date) as day_num,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as daily_volume,
                AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE NULL END) as avg_ticket
            FROM transactions t
            INNER JOIN stores s ON t.posid = s.TerminalID
            $whereClause
            GROUP BY DAYOFWEEK(t.transaction_date), DAYNAME(t.transaction_date)
            ORDER BY day_num
        ";
    }

    $stmt = $conn->prepare($dayAnalysisQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $dayAnalysis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 3. ANALISI ORARIA
if ($limitReached) {
    $hourlyAnalysis = [];
} else {
    if ($isAdmin) {
        $hourlyAnalysisQuery = "
            SELECT
                HOUR(t.transaction_date) as hour_of_day,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as hourly_volume
            FROM (SELECT * FROM transactions t $whereClause LIMIT $adminRowLimit) t
            GROUP BY HOUR(t.transaction_date)
            ORDER BY hour_of_day
        ";
    } else {
        $hourlyAnalysisQuery = "
            SELECT
                HOUR(t.transaction_date) as hour_of_day,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as hourly_volume
            FROM transactions t
            INNER JOIN stores s ON t.posid = s.TerminalID
            $whereClause
            GROUP BY HOUR(t.transaction_date)
            ORDER BY hour_of_day
        ";
    }

    $stmt = $conn->prepare($hourlyAnalysisQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $hourlyAnalysis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 4. ANALISI TOP BIN (BANCHE)
if ($limitReached) {
    $binAnalysis = [];
} else {
    if ($isAdmin) {
        $binAnalysisQuery = "
            SELECT
                LEFT(t.pan, 6) as bin,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as bin_volume,
                AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE NULL END) as avg_ticket
            FROM (SELECT * FROM transactions t $whereClause AND LEFT(t.pan, 6) != '' LIMIT $adminRowLimit) t
            GROUP BY LEFT(t.pan, 6)
            HAVING COUNT(*) >= 5
            ORDER BY bin_volume DESC
            LIMIT 15
        ";
    } else {
        $binAnalysisQuery = "
            SELECT
                LEFT(t.pan, 6) as bin,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as bin_volume,
                AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE NULL END) as avg_ticket
            FROM transactions t
            INNER JOIN stores s ON t.posid = s.TerminalID
            $whereClause AND LEFT(t.pan, 6) != ''
            GROUP BY LEFT(t.pan, 6)
            HAVING COUNT(*) >= 5
            ORDER BY bin_volume DESC
            LIMIT 15
        ";
    }

    $stmt = $conn->prepare($binAnalysisQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $binAnalysis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 5. ANALISI CIRCUITI (versione semplificata senza subquery)
if ($limitReached) {
    $circuitAnalysis = [];
    $totalTransactions = 0;
} else {
    if ($isAdmin) {
        $circuitAnalysisQuery = "
            SELECT
                t.card_brand,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as circuit_volume,
                AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE NULL END) as avg_ticket
            FROM (SELECT * FROM transactions t $whereClause LIMIT $adminRowLimit) t
            GROUP BY t.card_brand
            ORDER BY circuit_volume DESC
        ";
    } else {
        $circuitAnalysisQuery = "
            SELECT
                t.card_brand,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as circuit_volume,
                AVG(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE NULL END) as avg_ticket
            FROM transactions t
            INNER JOIN stores s ON t.posid = s.TerminalID
            $whereClause
            GROUP BY t.card_brand
            ORDER BY circuit_volume DESC
        ";
    }

    $stmt = $conn->prepare($circuitAnalysisQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $circuitAnalysis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calcola le percentuali in PHP
    $totalTransactions = array_sum(array_column($circuitAnalysis, 'transaction_count'));
    foreach ($circuitAnalysis as &$circuit) {
        $circuit['percentage'] = ($totalTransactions > 0) ? ($circuit['transaction_count'] / $totalTransactions) * 100 : 0;
    }
}

// 6. TREND TEMPORALE (ultimi 30 giorni)
if ($limitReached) {
    $trendData = [];
} else {
    if ($isAdmin) {
        $trendQuery = "
            SELECT
                DATE(t.transaction_date) as transaction_date,
                COUNT(*) as daily_transactions,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as daily_volume
            FROM (SELECT * FROM transactions t $whereClause LIMIT $adminRowLimit) t
            GROUP BY DATE(t.transaction_date)
            ORDER BY DATE(t.transaction_date)
        ";
    } else {
        $trendQuery = "
            SELECT
                DATE(t.transaction_date) as transaction_date,
                COUNT(*) as daily_transactions,
                SUM(CASE WHEN t.transaction_type NOT IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                         THEN t.amount ELSE -t.amount END) as daily_volume
            FROM transactions t
            INNER JOIN stores s ON t.posid = s.TerminalID
            $whereClause
            GROUP BY DATE(t.transaction_date)
            ORDER BY DATE(t.transaction_date)
        ";
    }

    $stmt = $conn->prepare($trendQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trendData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar"></i> Statistiche Avanzate Merchant</h2>
        <div>
            <?php if ($isAdmin): ?>
                <span class="badge badge-warning"><i class="fas fa-crown"></i> Admin</span>
            <?php else: ?>
                <span class="badge badge-info"><i class="fas fa-building"></i> BU: <?= htmlspecialchars($userBU) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($limitReached): ?>
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <strong>Limite raggiunto:</strong>
            I risultati sono basati su un campione limitato di <?= number_format($adminRowLimit) ?> righe per evitare timeout del server.
            Alcuni dati dettagliati sono mostrati come "N/A". Utilizza i filtri per restringere la ricerca.
        </div>
    <?php endif; ?>

    <!-- Filtri -->
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-2">
                <label>Data Inizio</label>
                <input type="date" name="startDate" class="form-control" value="<?= $startDate ?>">
            </div>
            <div class="col-md-2">
                <label>Data Fine</label>
                <input type="date" name="endDate" class="form-control" value="<?= $endDate ?>">
            </div>
            <div class="col-md-3">
                <label>Punto Vendita</label>
                <select name="filterStore" class="form-control">
                    <option value="">— Tutti i Punti Vendita —</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= htmlspecialchars($store['TerminalIDs']) ?>"
                                <?= $filterStore === $store['TerminalIDs'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($store['Insegna'] ?: $store['Ragione_Sociale']) ?>
                            <?php if ($store['indirizzo']): ?>
                                - <?= htmlspecialchars($store['indirizzo']) ?>
                            <?php endif; ?>
                            <?php if ($store['citta']): ?>
                                , <?= htmlspecialchars($store['citta']) ?>
                            <?php endif; ?>
                            <?php if ($store['terminal_count'] > 1): ?>
                                (<?= $store['terminal_count'] ?> POS)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <div><button type="submit" class="btn btn-primary">Filtra</button></div>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <div><a href="?" class="btn btn-secondary">Reset</a></div>
            </div>
        </div>
    </form>

    <!-- KPI Principali -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4>Volume Medio Giornaliero</h4>
                    <h2>€ <?= number_format($dailyAvgVolume, 0) ?></h2>
                    <small><?= number_format($dailyAvgTransactions, 0) ?> transazioni/giorno</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4>Ticket Medio</h4>
                    <h2>€ <?= number_format($generalStats['avg_ticket'], 2) ?></h2>
                    <small>Su <?= number_format($generalStats['total_transactions']) ?> transazioni</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4>Volume Totale Periodo</h4>
                    <h2>€ <?= number_format($generalStats['net_volume'], 0) ?></h2>
                    <small>In <?= $generalStats['period_days'] ?> giorni</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4>Giorno + Affollato</h4>
                    <?php
                    if ($limitReached) {
                        $busiest = null;
                    } else {
                        $busiest = null;
                        foreach ($dayAnalysis as $day) {
                            if (!$busiest || $day['transaction_count'] > $busiest['transaction_count']) {
                                $busiest = $day;
                            }
                        }
                    }
                    ?>
                    <h2><?= $limitReached ? 'N/A' : ($busiest ? $busiest['day_name'] : 'N/D') ?></h2>
                    <small><?= ($limitReached || !$busiest) ? '' : number_format($busiest['transaction_count']) . ' transazioni medie' ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Grafico Trend Volume -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Trend Volume Temporale</h5>
                </div>
                <div class="card-body">
                    <?php if ($limitReached): ?>
                        <div class="alert alert-secondary text-center" style="margin-top: 150px;">
                            <i class="fas fa-info-circle"></i> N/A - Dati non disponibili (limite raggiunto)
                        </div>
                    <?php else: ?>
                        <canvas id="trendChart" style="height: 400px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Circuiti -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-credit-card"></i> Distribuzione Circuiti</h5>
                </div>
                <div class="card-body">
                    <?php if ($limitReached): ?>
                        <div class="alert alert-secondary text-center" style="margin-top: 130px;">
                            <i class="fas fa-info-circle"></i> N/A - Dati non disponibili (limite raggiunto)
                        </div>
                    <?php else: ?>
                        <canvas id="circuitChart" style="height: 350px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Analisi per Giorni della Settimana -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-week"></i> Analisi per Giorni della Settimana</h5>
                </div>
                <div class="card-body">
                    <?php if ($limitReached): ?>
                        <div class="alert alert-secondary text-center" style="margin-top: 100px;">
                            <i class="fas fa-info-circle"></i> N/A - Dati non disponibili (limite raggiunto)
                        </div>
                    <?php else: ?>
                        <canvas id="weekdayChart" style="height: 300px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Analisi Oraria -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Distribuzione Oraria</h5>
                </div>
                <div class="card-body">
                    <?php if ($limitReached): ?>
                        <div class="alert alert-secondary text-center" style="margin-top: 100px;">
                            <i class="fas fa-info-circle"></i> N/A - Dati non disponibili (limite raggiunto)
                        </div>
                    <?php else: ?>
                        <canvas id="hourlyChart" style="height: 300px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Banche (BIN Analysis) -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-university"></i> Top 15 Banche per Volume (Analisi BIN)</h5>
                </div>
                <div class="card-body">
                    <?php if ($limitReached): ?>
                        <div class="alert alert-secondary text-center">
                            <i class="fas fa-info-circle"></i> N/A - Dati non disponibili (limite raggiunto)
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Pos.</th>
                                        <th>BIN</th>
                                        <th>Banca</th>
                                        <th>Paese</th>
                                        <th>Transazioni</th>
                                        <th>Volume</th>
                                        <th>Ticket Medio</th>
                                        <th>% Volume</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($binAnalysis as $index => $bin): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><code><?= htmlspecialchars($bin['bin']) ?></code></td>
                                        <td><?= getBankName($bin['bin']) ?></td>
                                        <td><?= getCountryFromBIN($bin['bin']) ?></td>
                                        <td><?= number_format($bin['transaction_count']) ?></td>
                                        <td class="text-success">€ <?= number_format($bin['bin_volume'], 0) ?></td>
                                        <td>€ <?= number_format($bin['avg_ticket'], 2) ?></td>
                                        <td><?= number_format(($bin['bin_volume'] / $generalStats['net_volume']) * 100, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    const limitReached = <?= $limitReached ? 'true' : 'false' ?>;

    // 1. Grafico Trend Temporale
    const trendData = <?= json_encode($trendData) ?>;
    if (!limitReached && trendData.length > 0) {
        const trendDates = trendData.map(d => new Date(d.transaction_date).toLocaleDateString('it-IT'));
        const trendVolumes = trendData.map(d => parseFloat(d.daily_volume));
        const trendTransactions = trendData.map(d => parseInt(d.daily_transactions));

        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: trendDates,
                datasets: [{
                    label: 'Volume Giornaliero (€)',
                    data: trendVolumes,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    yAxisID: 'y',
                    tension: 0.1
                }, {
                    label: 'Transazioni',
                    data: trendTransactions,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Volume (€)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Transazioni' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // 2. Grafico Circuiti
    const circuitData = <?= json_encode($circuitAnalysis) ?>;
    if (!limitReached && circuitData.length > 0) {
        const circuitLabels = circuitData.map(c => c.card_brand);
        const circuitCounts = circuitData.map(c => parseFloat(c.percentage));

        new Chart(document.getElementById('circuitChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: circuitLabels,
                datasets: [{
                    data: circuitCounts,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Grafico Giorni Settimana
    const dayData = <?= json_encode($dayAnalysis) ?>;
    if (!limitReached && dayData.length > 0) {
        const dayLabels = dayData.map(d => d.day_name);
        const dayVolumes = dayData.map(d => parseFloat(d.daily_volume));
        const dayTransactions = dayData.map(d => parseInt(d.transaction_count));

        new Chart(document.getElementById('weekdayChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: dayLabels,
                datasets: [{
                    label: 'Volume (€)',
                    data: dayVolumes,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    yAxisID: 'y'
                }, {
                    label: 'Transazioni',
                    data: dayTransactions,
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Volume (€)' }},
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Transazioni' }, grid: { drawOnChartArea: false }}
                }
            }
        });
    }

    // 4. Grafico Orario
    const hourlyData = <?= json_encode($hourlyAnalysis) ?>;
    if (!limitReached && hourlyData.length > 0) {
        // Crea array completo 0-23 ore
        const hourlyComplete = [];
        for (let h = 0; h <= 23; h++) {
            const found = hourlyData.find(item => parseInt(item.hour_of_day) === h);
            hourlyComplete.push({
                hour: h + ":00",
                volume: found ? parseFloat(found.hourly_volume) : 0,
                transactions: found ? parseInt(found.transaction_count) : 0
            });
        }

        new Chart(document.getElementById('hourlyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: hourlyComplete.map(h => h.hour),
                datasets: [{
                    label: 'Volume Orario (€)',
                    data: hourlyComplete.map(h => h.volume),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Ora del Giorno' }},
                    y: { title: { display: true, text: 'Volume (€)' }}
                }
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>
