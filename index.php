<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';
include 'config.php';

// Funzioni per trascodificare i circuiti - VERSIONE COMPLETA CON CODICI FTFS
function translateCircuitCode($code) {
    $circuits = [
        // Codici esistenti del dashboard
        'ED' => 'MasterCard Debit Extra-EEA',
        'EC' => 'MasterCard Debit EEA',
        'MBK' => 'MyBank',
        'MC' => 'MasterCard Credit Extra-EEA',
        'MCE' => 'MasterCard Credit EEA',
        'MD' => 'MasterCard Debit Extra-EEA',
        'ME' => 'MasterCard Debit EEA',
        'ML' => 'MasterCard Commercial Extra-EEA',
        'MLE' => 'MasterCard Commercial EEA',
        'MN' => 'MasterCard Commercial Extra-EEA',
        'MNE' => 'MasterCard Commercial EEA',
        'MP' => 'MasterCard PrePaid EEA',
        'PA' => 'Bancomat',
        'PB' => 'Bancomat',
        'PP' => 'Bancomat',
        'VC' => 'Visa Credit Extra-EEA',
        'VCE' => 'Visa Credit EEA',
        'VD' => 'Visa Debit Extra-EEA',
        'VDE' => 'Visa Debit EEA',
        'VL' => 'Visa Commercial Extra-EEA',
        'VLE' => 'Visa Commercial EEA',
        'VN' => 'Visa Commercial Extra-EEA',
        'VNE' => 'Visa Commercial EEA',
        'VP' => 'Visa PrePaid Extra-EEA',
        'VPE' => 'Visa Debit EEA',
        'VR' => 'Visa PrePaid Extra-EEA',
        'VRE' => 'Visa PrePaid EEA',

        // CODICI FTFS UFFICIALI - Carte domestiche (Italia)
        'DAACQU' => 'Vendita (Bancomat)',
        'DSESTO' => 'Storno Operatore (Bancomat)', 
        'DSISTO' => 'Storno Tecnico (Bancomat)',
        'DPACQU' => 'Preautorizzazione (Bancomat)',
        'DVACQU' => 'Verifica Carta (Bancomat)',
        'DNACQU' => 'Avviso Post-Autorizzazione (Bancomat)',

        // CODICI FTFS UFFICIALI - Altre carte
        'CAACQU' => 'Vendita (Sale)',
        'CPACQU' => 'Preautorizzazione',
        'CNACQU' => 'Avviso Post-Autorizzazione', 
        'CSESTO' => 'Storno Operatore',
        'CSISTO' => 'Storno Tecnico',
        'CXACQU' => 'Credito GT-PO',
        'CXECRE' => 'Credito E-Commerce',
    ];

    return $circuits[$code] ?? $code;
}

// Funzione per raggruppare i circuiti - VERSIONE COMPLETA
function getCircuitGroup($code) {
    // Se il codice è già un nome di gruppo, ritornalo direttamente
    if ($code === 'PagoBancomat' || $code === 'Bancomat' || $code === 'PAGOBANCOMAT') {
        return 'PagoBancomat';
    }

    // Carte domestiche italiane (PagoBancomat)
    $pagobancomat = ['PA', 'PB', 'PP', 'DAACQU', 'DSESTO', 'DSISTO', 'DPACQU', 'DVACQU', 'DNACQU'];

    // Altre carte (codici C- FTFS)
    $altreCarteC = ['CAACQU', 'CPACQU', 'CNACQU', 'CSESTO', 'CSISTO', 'CXACQU', 'CXECRE'];

    if (in_array($code, $pagobancomat)) {
        return 'PagoBancomat';
    } elseif (in_array($code, $altreCarteC)) {
        return 'Altre Carte';
    } elseif (strpos($code, 'M') === 0 || $code === 'ED' || $code === 'EC') {
        return 'MasterCard';
    } elseif (strpos($code, 'V') === 0) {
        return 'Visa';
    } elseif ($code === 'MBK') {
        return 'MyBank';
    } else {
        return 'Altri (' . $code . ')';
    }
}

// Funzione per ottenere il colore del badge in base al gruppo circuito (stessi colori del grafico a torta)
function getCircuitColor($code) {
    $group = getCircuitGroup($code);

    $colors = [
        'PagoBancomat' => 'background-color: rgb(255, 99, 132); color: white;',   // Rosso
        'Visa' => 'background-color: rgb(54, 162, 235); color: white;',            // Blu
        'MasterCard' => 'background-color: rgb(255, 205, 86); color: black;',      // Giallo
        'MyBank' => 'background-color: rgb(75, 192, 192); color: white;',          // Verde
        'Altre Carte' => 'background-color: rgb(255, 159, 64); color: white;',     // Arancione
    ];

    // Se inizia con "Altri (", usa viola
    if (strpos($group, 'Altri (') === 0) {
        return 'background-color: rgb(153, 102, 255); color: white;';  // Viola
    }

    return $colors[$group] ?? 'background-color: rgb(108, 117, 125); color: white;'; // Grigio default
}

// Funzione per ottenere l'icona del circuito
function getCircuitIcon($code) {
    $group = getCircuitGroup($code);

    $icons = [
        'PagoBancomat' => '<i class="fas fa-university"></i>',      // Banca per PagoBancomat
        'Visa' => '<i class="fab fa-cc-visa"></i>',                 // Logo Visa
        'MasterCard' => '<i class="fab fa-cc-mastercard"></i>',     // Logo MasterCard
        'MyBank' => '<i class="fas fa-landmark"></i>',              // Banca per MyBank
        'Altre Carte' => '<i class="fas fa-credit-card"></i>',      // Carta generica
    ];

    // Se inizia con "Altri (", usa icona generica
    if (strpos($group, 'Altri (') === 0) {
        return '<i class="fas fa-credit-card"></i>';  // Carta generica
    }

    return $icons[$group] ?? '<i class="fas fa-credit-card"></i>'; // Carta generica default
}

// Funzione per determinare se una transazione contribuisce al volume
function shouldIncludeInVolume($transactionType, $settlementFlag) {
    // Storni vanno sottratti (hanno segno negativo)
    $storni = ['DSESTO', 'DSISTO', 'CSESTO', 'CSISTO'];
    
    // Solo transazioni con settlement OK (flag = '1') contribuiscono al volume
    if ($settlementFlag != '1') {
        return false;
    }
    
    return true;
}

// Funzione per calcolare il moltiplicatore per il volume
function getVolumeMultiplier($transactionType) {
    // Storni hanno moltiplicatore negativo
    $storni = ['DSESTO', 'DSISTO', 'CSESTO', 'CSISTO'];

    if (in_array($transactionType, $storni)) {
        return -1;
    }

    return 1;
}

// Funzione per tradurre i codici di risposta ISO 8583 Field 039 (Action Code)
function translateResponseCode($code) {
    $codes = [
        // 000-099: Approved
        '000' => 'Approvato',
        '001' => 'Autorizzato con identificazione',
        '002' => 'Approvato per importo parziale',
        '003' => 'Approvato (VIP)',
        '004' => 'Approvato, aggiornare track 3',
        '005' => 'Approvato, tipo account specificato',
        '006' => 'Approvato parziale, tipo account specificato',
        '007' => 'Approvato, aggiornare ICC',
        '008' => 'Continua, richiesta autenticazione aggiuntiva',
        '009' => 'Approvato con saldo',
        '060' => 'Nessun motivo per rifiutare',
        '061' => 'Approvato solo per acquisto, no CashBack',

        // 100-199: Denied (no card pickup)
        '100' => 'Non autorizzato',
        '101' => 'Carta scaduta',
        '102' => 'Sospetta frode',
        '103' => 'Contattare l\'acquirer',
        '104' => 'Carta ristretta',
        '105' => 'Chiamare sicurezza acquirer',
        '106' => 'Tentativi PIN superati',
        '107' => 'Riferirsi all\'emittente',
        '108' => 'Riferirsi a condizioni speciali emittente',
        '109' => 'Merchant non valido',
        '110' => 'Importo non valido',
        '111' => 'Numero carta non valido',
        '112' => 'Dati PIN richiesti',
        '113' => 'Commissione non accettabile',
        '114' => 'Nessun account del tipo richiesto',
        '115' => 'Funzione richiesta non supportata',
        '116' => 'Fondi insufficienti',
        '117' => 'PIN errato',
        '118' => 'Nessun record carta',
        '119' => 'Transazione non permessa al titolare',
        '120' => 'Transazione non permessa al terminale',
        '121' => 'Superato limite prelievo',
        '122' => 'Violazione sicurezza',
        '123' => 'Superata frequenza prelievi / Autenticazione richiesta (PSD2)',
        '124' => 'Violazione di legge',
        '125' => 'Carta non effettiva',
        '126' => 'PIN block non valido',
        '127' => 'Errore lunghezza PIN',
        '128' => 'Errore sincronizzazione chiave PIN',
        '129' => 'Sospetta carta contraffatta',
        '130' => 'Servizio Visa Mobile non disponibile',
        '131' => 'PIN non cambiato',
        '132' => 'PIN non accettabile - Riprovare',
        '165' => 'Conto chiuso',
        '166' => 'Superato plafond mensile PT Gaming',
        '167' => 'MC Decline Reason Code Service - Lifecycle',
        '170' => 'PIN online richiesto per SCA',
        '172' => 'Autenticazione cliente aggiuntiva richiesta',
        '175' => 'Servizio CashBack non disponibile',
        '176' => 'Importo CashBack supera limiti',
        '177' => 'Importo CashBack uguale al totale',
        '178' => 'Autenticazione non valida',
        '179' => 'Dati ICC non validi o mancanti',
        '187' => 'Carta non idonea per rateizzazione',
        '188' => 'CVV2/CVC2 non valido',
        '189' => 'CAVV/AAV non valido',
        '190' => 'Errore crittografico',
        '195' => 'Geoblocking [RIFIUTO]',
        '196' => 'Rifiuto Risk/Fraud Management acquirer',
        '199' => 'Sospetto AA non valido',

        // 200-299: Denied (require card pickup)
        '200' => 'Non autorizzato - Ritirare carta',
        '201' => 'Carta scaduta - Ritirare carta',
        '202' => 'Sospetta frode - Ritirare carta',
        '203' => 'Contattare acquirer - Ritirare carta',
        '204' => 'Carta ristretta - Ritirare carta',
        '205' => 'Chiamare sicurezza - Ritirare carta',
        '206' => 'Tentativi PIN superati - Ritirare carta',
        '207' => 'Condizioni speciali - Ritirare carta',
        '208' => 'Carta smarrita - Ritirare carta',
        '209' => 'Carta rubata - Ritirare carta',
        '210' => 'Sospetta carta contraffatta - Ritirare carta',

        // 300-399: File actions
        '300' => 'Operazione file riuscita',
        '301' => 'Non supportato dal ricevente',
        '302' => 'Record non trovato',
        '303' => 'Errore di modifica campo',
        '304' => 'File bloccato',
        '305' => 'File bloccato',
        '306' => 'Operazione non riuscita',
        '307' => 'Errore di formato',
        '308' => 'Duplicato, nuovo record rifiutato',
        '309' => 'File sconosciuto',
        '360' => 'Record non in stato attivo',
        '361' => 'Record cancellato permanentemente',
        '362' => 'Richiesta cancellazione < 540 giorni',
        '363' => 'Violazione sicurezza',

        // 400-499: Reversals/Chargebacks
        '400' => 'Storno accettato',

        // 800-899: Network management
        '800' => 'Accettato',
        '860' => 'Impossibile aprire sessione',
        '900' => 'Avviso riconosciuto, nessuna responsabilità finanziaria',
        '901' => 'Avviso riconosciuto, responsabilità finanziaria accettata',

        // 902-949: Transaction processing errors
        '902' => 'Transazione non valida',
        '903' => 'Reinserire transazione',
        '904' => 'Errore di formato',
        '905' => 'Acquirer non supportato dallo switch',
        '906' => 'Cutover in corso',
        '907' => 'Emittente o switch inoperativo',
        '908' => 'Destinazione transazione non trovata per routing',
        '909' => 'Malfunzionamento sistema',
        '910' => 'Emittente disconnesso',
        '911' => 'Timeout emittente',
        '912' => 'Emittente non disponibile',
        '913' => 'Trasmissione duplicata',
        '914' => 'Impossibile tracciare transazione originale',
        '915' => 'Errore riconciliazione cutover/checkpoint',
        '916' => 'MAC errato',
        '917' => 'Errore sincronizzazione chiave MAC',
        '918' => 'Nessuna chiave comunicazione disponibile',
        '919' => 'Errore sincronizzazione chiave cifratura',
        '920' => 'Errore sicurezza software/hardware - Riprovare',
        '921' => 'Errore sicurezza software/hardware - Nessuna azione',
        '922' => 'Numero messaggio fuori sequenza',
        '923' => 'Richiesta in corso',
        '924' => 'Servizio MDES File Update non disponibile',
        '925' => 'Servizio VTS File Update non disponibile',
        '926' => 'Dati mancanti o non validi',
        '927' => 'SCK non valido',

        // 950-999: Advice rejection
        '950' => 'Violazione accordo commerciale',
    ];

    $code = trim($code);

    // Gestione codici con zeri iniziali (es. "00" vs "000")
    if (strlen($code) == 2 && isset($codes['0' . $code])) {
        $code = '0' . $code;
    }

    return isset($codes[$code]) ? "$code - {$codes[$code]}" : ($code ? "Codice $code" : '-');
}

// Controllo BU
$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');

// Parametri filtri
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-1 day'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');
$filterStore = $_GET['filterStore'] ?? '';

// Lista negozi per dropdown (filtrata per BU se non admin) - raggruppati per punto vendita
// Ottimizzato: LIMIT aggiunto per evitare timeout con admin
if ($isAdmin) {
    $storeQuery = "SELECT GROUP_CONCAT(DISTINCT s.TerminalID ORDER BY s.TerminalID SEPARATOR ',') as TerminalIDs,
                          s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta,
                          COUNT(DISTINCT s.TerminalID) as terminal_count
                   FROM stores s
                   INNER JOIN (SELECT DISTINCT posid FROM transactions LIMIT 500000) t ON s.TerminalID = t.posid
                   WHERE s.TerminalID IS NOT NULL
                   GROUP BY s.Ragione_Sociale, s.indirizzo, s.citta
                   ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo
                   LIMIT 1000";
    $storeStmt = $conn->prepare($storeQuery);
} else {
    $storeQuery = "SELECT GROUP_CONCAT(DISTINCT s.TerminalID ORDER BY s.TerminalID SEPARATOR ',') as TerminalIDs,
                          s.Insegna, s.Ragione_Sociale, s.indirizzo, s.citta,
                          COUNT(DISTINCT s.TerminalID) as terminal_count
                   FROM stores s
                   INNER JOIN transactions t ON s.TerminalID = t.posid
                   WHERE s.TerminalID IS NOT NULL AND s.bu = ?
                   GROUP BY s.Ragione_Sociale, s.indirizzo, s.citta
                   ORDER BY s.Insegna, s.Ragione_Sociale, s.indirizzo";
    $storeStmt = $conn->prepare($storeQuery);
    $storeStmt->bind_param("s", $userBU);
}
$storeStmt->execute();
$stores = $storeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$storeStmt->close();

// Query base con filtro BU
if ($isAdmin) {
    $whereClause = "WHERE DATE(transaction_date) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
    $types = "ss";
} else {
    $whereClause = "WHERE DATE(t.transaction_date) BETWEEN ? AND ? AND s.bu = ?";
    $params = [$startDate, $endDate, $userBU];
    $types = "sss";
}

if ($filterStore) {
    // Il filtro contiene una lista di TerminalID separati da virgola
    $terminalList = explode(',', $filterStore);
    $placeholders = str_repeat('?,', count($terminalList) - 1) . '?';

    if ($isAdmin) {
        $whereClause .= " AND posid IN ($placeholders)";
    } else {
        $whereClause .= " AND t.posid IN ($placeholders)";
    }

    foreach ($terminalList as $terminal) {
        $params[] = trim($terminal);
        $types .= "s";
    }
}

// Query statistiche CORRETTE con filtro BU e logica di volume
if ($isAdmin) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE 
                WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO') 
                THEN -amount 
                WHEN settlement_flag = '1' 
                THEN amount 
                ELSE 0 
            END) as volume,
            COUNT(CASE WHEN settlement_flag = '1' THEN 1 END) as settled_count,
            COUNT(CASE WHEN settlement_flag != '1' THEN 1 END) as not_settled_count
        FROM transactions $whereClause
    ");
} else {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE 
                WHEN t.settlement_flag = '1' AND t.transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO') 
                THEN -t.amount 
                WHEN t.settlement_flag = '1' 
                THEN t.amount 
                ELSE 0 
            END) as volume,
            COUNT(CASE WHEN t.settlement_flag = '1' THEN 1 END) as settled_count,
            COUNT(CASE WHEN t.settlement_flag != '1' THEN 1 END) as not_settled_count
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        $whereClause
    ");
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Dati per grafico temporale CORRETTI con filtro BU (SEMPRE limitato al mese corrente)
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t');

// Parametri separati per il grafico (sempre mese corrente)
$paramsChart = [$firstDayOfMonth, $lastDayOfMonth];
$typesChart = "ss";

if (!$isAdmin) {
    $paramsChart[] = $userBU;
    $typesChart .= "s";
}

if ($filterStore) {
    // Il filtro contiene una lista di TerminalID separati da virgola
    $terminalList = explode(',', $filterStore);
    foreach ($terminalList as $terminal) {
        $paramsChart[] = trim($terminal);
        $typesChart .= "s";
    }
}

if ($isAdmin) {
    $whereClauseChart = "WHERE DATE(transaction_date) BETWEEN ? AND ?";
    if ($filterStore) {
        $terminalList = explode(',', $filterStore);
        $placeholders = str_repeat('?,', count($terminalList) - 1) . '?';
        $whereClauseChart .= " AND posid IN ($placeholders)";
    }
    $stmt = $conn->prepare("
        SELECT
            DATE(transaction_date) as day,
            COUNT(*) as daily_count,
            SUM(CASE
                WHEN settlement_flag = '1' AND transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -amount
                WHEN settlement_flag = '1'
                THEN amount
                ELSE 0
            END) as daily_volume
        FROM transactions
        $whereClauseChart
        GROUP BY DATE(transaction_date)
        ORDER BY DATE(transaction_date)
    ");
} else {
    $whereClauseChart = "WHERE DATE(t.transaction_date) BETWEEN ? AND ? AND s.bu = ?";
    if ($filterStore) {
        $terminalList = explode(',', $filterStore);
        $placeholders = str_repeat('?,', count($terminalList) - 1) . '?';
        $whereClauseChart .= " AND t.posid IN ($placeholders)";
    }
    $stmt = $conn->prepare("
        SELECT
            DATE(t.transaction_date) as day,
            COUNT(*) as daily_count,
            SUM(CASE
                WHEN t.settlement_flag = '1' AND t.transaction_type IN ('DSESTO', 'DSISTO', 'CSESTO', 'CSISTO')
                THEN -t.amount
                WHEN t.settlement_flag = '1'
                THEN t.amount
                ELSE 0
            END) as daily_volume
        FROM transactions t
        INNER JOIN stores s ON t.posid = s.TerminalID
        $whereClauseChart
        GROUP BY DATE(t.transaction_date)
        ORDER BY DATE(t.transaction_date)
    ");
}
$stmt->bind_param($typesChart, ...$paramsChart);
$stmt->execute();
$dailyData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dati per grafico a torta circuiti con filtro BU (solo transazioni settled)
if ($isAdmin) {
    $stmt = $conn->prepare("SELECT card_brand, COUNT(*) as count FROM transactions $whereClause AND settlement_flag = '1' GROUP BY card_brand");
} else {
    $stmt = $conn->prepare("SELECT t.card_brand, COUNT(*) as count
                           FROM transactions t
                           INNER JOIN stores s ON t.posid = s.TerminalID
                           $whereClause AND t.settlement_flag = '1'
                           GROUP BY t.card_brand");
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$circuitData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Raggruppa i dati per il grafico
$circuitGroups = [];
foreach ($circuitData as $row) {
    $group = getCircuitGroup($row['card_brand']);
    if (!isset($circuitGroups[$group])) {
        $circuitGroups[$group] = 0;
    }
    $circuitGroups[$group] += $row['count'];
}

// Dati tabella
$limit = 25;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

if ($isAdmin) {
    $stmt = $conn->prepare("SELECT t.*, s.Insegna, s.Ragione_Sociale FROM transactions t
                            LEFT JOIN stores s ON t.posid = s.TerminalID
                            $whereClause
                            ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?");
} else {
    $stmt = $conn->prepare("SELECT t.*, s.Insegna, s.Ragione_Sociale FROM transactions t
                            INNER JOIN stores s ON t.posid = s.TerminalID
                            $whereClause
                            ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?");
}
$types .= "ii";
$params[] = $limit;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$totalPages = ceil($stats['total'] / $limit);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Dashboard</h2>
        <div>
            <?php if ($isAdmin): ?>
                <span class="badge badge-warning">
                    <i class="fas fa-crown"></i> Modalità Admin - Tutti i POS
                </span>
            <?php else: ?>
                <span class="badge badge-info">
                    <i class="fas fa-building"></i> BU: <?= htmlspecialchars($userBU) ?> - Solo i tuoi POS
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grafici -->
    <div class="row mb-4">
        <!-- Grafico Temporale -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Trend Transazioni e Volume nel Tempo (Mese Corrente)</h5>
                </div>
                <div class="card-body">
                    <canvas id="timeSeriesChart" style="height: 350px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Grafico a Torta Circuiti -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Distribuzione Circuiti (Solo Settled)</h5>
                </div>
                <div class="card-body">
                    <canvas id="circuitChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI CORRETTI -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Transazioni Totali</h5>
                    <h3><?= number_format($stats['total']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Volume Netto</h5>
                    <h3>€ <?= number_format($stats['volume'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Transazioni Settled</h5>
                    <h3><?= number_format($stats['settled_count']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Non Settled</h5>
                    <h3><?= number_format($stats['not_settled_count']) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri con dropdown negozi -->
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
            <div class="col-md-4">
                <label>Punto Vendita / Negozio</label>
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
                <div>
                    <button type="submit" class="btn btn-primary">Filtra</button>
                </div>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <div>
                    <a href="?" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Tabella -->
    <?php if (!$isAdmin): ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Transazioni (<?= number_format($stats['total']) ?>)</h5>
                <div>
                    <input type="text" id="searchTable" class="form-control form-control-sm d-inline-block"
                           placeholder="🔍 Cerca nella tabella..." style="width: 250px; margin-right: 10px;">
                    <a href="export_transactions.php?<?= http_build_query($_GET) ?>"
                       class="btn btn-success btn-sm" title="Esporta transazioni in CSV">
                        <i class="fas fa-file-excel"></i> Esporta CSV
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="transactionsTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Data/Ora</th>
                            <th>POSID</th>
                            <th>Store</th>
                            <th>Tipo</th>
                            <th>Importo</th>
                            <th>PAN</th>
                            <th>Circuito</th>
                            <th>Settlement</th>
                            <th>Esito</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                        // Determina il colore dell'importo basato sul tipo e settlement
                        $amountClass = 'text-right';
                        $amount = $row['amount'];
                        
                        if ($row['settlement_flag'] != '1') {
                            $amountClass .= ' text-muted';
                        } elseif (in_array($row['transaction_type'], ['DSESTO', 'DSISTO', 'CSESTO', 'CSISTO'])) {
                            $amountClass .= ' text-danger';
                            $amount = -$amount;
                        } else {
                            $amountClass .= ' text-success';
                        }
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($row['transaction_date'])) ?></td>
                            <td><?= htmlspecialchars($row['posid']) ?></td>
                            <td><?= htmlspecialchars($row['Insegna'] ?: $row['Ragione_Sociale'] ?: 'N/A') ?></td>
                            <td><span class="badge badge-success"><?= translateCircuitCode($row['transaction_type']) ?></span></td>
                            <td class="<?= $amountClass ?>">€ <?= number_format($amount, 2) ?></td>
                            <td><?= htmlspecialchars($row['pan']) ?></td>
                            <td><span class="badge" style="<?= getCircuitColor($row['card_brand']) ?>"><?= getCircuitIcon($row['card_brand']) ?> <?= translateCircuitCode($row['card_brand']) ?></span></td>
                            <td>
                                <?php if ($row['settlement_flag'] == '1'): ?>
                                    <span class="badge badge-success">OK</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">NO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['settlement_flag'] == '1'): ?>
                                    <small class="text-success">00 - Approvato</small>
                                <?php else: ?>
                                    <?php
                                    $responseCode = $row['response_code'] ?: $row['ib_response_code'] ?: '';
                                    ?>
                                    <small class="text-danger"><?= htmlspecialchars(translateResponseCode($responseCode)) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginazione -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php
                        $currentFilters = http_build_query($_GET);
                        $currentFilters = preg_replace('/&?page=\d+/', '', $currentFilters);

                        for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= $currentFilters ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Stripe-like Table Styling -->
<style>
/* Font moderno system-ui - ultra compatto */
#transactionsTable {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 10px;
    border-collapse: separate !important;
    border-spacing: 0;
    width: 100%;
    margin: 0;
    line-height: 1.2;
}

/* Header pulito e moderno - ultra compatto */
#transactionsTable thead th {
    background: linear-gradient(180deg, #fafbfc 0%, #f4f6f8 100%);
    color: #425466;
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 6px;
    border-top: 1px solid #e3e8ee;
    border-bottom: 2px solid #e3e8ee;
    white-space: nowrap;
}

/* Righe della tabella */
#transactionsTable tbody tr {
    transition: all 0.15s ease;
    background-color: #ffffff;
    border-bottom: 1px solid #f0f0f0;
}

/* Hover effect elegante */
#transactionsTable tbody tr:hover {
    background-color: #fafbfc;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    transform: translateY(-1px);
}

/* Celle della tabella - ultra compatte */
#transactionsTable tbody td {
    padding: 6px 6px;
    color: #32325d;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
    font-size: 10px;
    line-height: 1.2;
}

/* Badge moderni e sofisticati - ultra compatti */
#transactionsTable .badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 500;
    font-size: 10px;
    letter-spacing: 0.1px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

/* Badge success più delicato */
#transactionsTable .badge-success {
    background-color: #00d4aa;
    color: #ffffff;
}

/* Importi con font tabular */
#transactionsTable .text-right {
    font-variant-numeric: tabular-nums;
    font-weight: 500;
}

#transactionsTable .text-success {
    color: #00d924;
    font-weight: 600;
}

#transactionsTable .text-danger {
    color: #e25950;
    font-weight: 600;
}

#transactionsTable .text-muted {
    color: #8898aa;
}

/* Input ricerca stile Stripe */
#searchTable {
    border: 1px solid #e3e8ee;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    transition: all 0.15s ease;
    background-color: #ffffff;
}

#searchTable:focus {
    outline: none;
    border-color: #6772e5;
    box-shadow: 0 0 0 3px rgba(103, 114, 229, 0.1);
}

/* Card più moderna */
.card {
    border-radius: 12px;
    border: 1px solid #e3e8ee;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* Scrollbar personalizzata (Chrome/Safari) */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f0f0f0;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>

<script>
$(document).ready(function() {
    // Grafico temporale con doppio asse Y
    const dailyData = <?= json_encode($dailyData) ?>;

    if (dailyData && dailyData.length > 0) {
        const dates = dailyData.map(d => {
            const date = new Date(d.day + 'T00:00:00');
            return date.toLocaleDateString('it-IT');
        });
        const dailyCounts = dailyData.map(d => parseInt(d.daily_count));
        const dailyVolumes = dailyData.map(d => parseFloat(d.daily_volume));

        const ctx = document.getElementById('timeSeriesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Transazioni',
                    data: dailyCounts,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    yAxisID: 'y',
                    tension: 0.1,
                    fill: false
                }, {
                    label: 'Volume Netto (€)',
                    data: dailyVolumes,
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Data'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Numero Transazioni',
                            color: 'rgb(54, 162, 235)'
                        },
                        ticks: {
                            color: 'rgb(54, 162, 235)',
                            callback: function(value) {
                                return value.toLocaleString('it-IT');
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Volume Netto (€)',
                            color: 'rgb(40, 167, 69)'
                        },
                        ticks: {
                            color: 'rgb(40, 167, 69)',
                            callback: function(value) {
                                return '€ ' + value.toLocaleString('it-IT', {maximumFractionDigits: 0});
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return 'Transazioni: ' + context.parsed.y.toLocaleString('it-IT');
                                } else {
                                    return 'Volume Netto: € ' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('timeSeriesChart').innerHTML = '<p class="text-center text-muted mt-4">Nessun dato disponibile per il periodo selezionato</p>';
    }

    // Grafico a Torta Circuiti - AGGIORNATO con mapping colori per label
    const circuitGroups = <?= json_encode($circuitGroups) ?>;

    if (circuitGroups && Object.keys(circuitGroups).length > 0) {
        const circuitLabels = Object.keys(circuitGroups);
        const circuitCounts = Object.values(circuitGroups);

        // Mappa colori per label (stessi colori dei badge)
        function getCircuitChartColor(label) {
            const colorMap = {
                'PagoBancomat': 'rgba(255, 99, 132, 0.8)',   // Rosso
                'Visa': 'rgba(54, 162, 235, 0.8)',           // Blu
                'MasterCard': 'rgba(255, 205, 86, 0.8)',     // Giallo
                'MyBank': 'rgba(75, 192, 192, 0.8)',         // Verde
                'Altre Carte': 'rgba(255, 159, 64, 0.8)',    // Arancione
            };

            // Se inizia con "Altri (", usa viola
            if (label.startsWith('Altri (')) {
                return 'rgba(153, 102, 255, 0.8)';  // Viola
            }

            return colorMap[label] || 'rgba(108, 117, 125, 0.8)'; // Grigio default
        }

        function getCircuitBorderColor(label) {
            const colorMap = {
                'PagoBancomat': 'rgba(255, 99, 132, 1)',
                'Visa': 'rgba(54, 162, 235, 1)',
                'MasterCard': 'rgba(255, 205, 86, 1)',
                'MyBank': 'rgba(75, 192, 192, 1)',
                'Altre Carte': 'rgba(255, 159, 64, 1)',
            };

            if (label.startsWith('Altri (')) {
                return 'rgba(153, 102, 255, 1)';
            }

            return colorMap[label] || 'rgba(108, 117, 125, 1)';
        }

        // Genera array di colori basati sulle label effettive
        const backgroundColors = circuitLabels.map(label => getCircuitChartColor(label));
        const borderColors = circuitLabels.map(label => getCircuitBorderColor(label));

        const ctxCircuit = document.getElementById('circuitChart').getContext('2d');
        new Chart(ctxCircuit, {
            type: 'pie',
            data: {
                labels: circuitLabels,
                datasets: [{
                    data: circuitCounts,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                let label = context.label + ': ' + context.parsed.toLocaleString('it-IT') + ' (' + percentage + '%)';

                                // Aggiungi info aggiuntive per alcune categorie
                                if (context.label === 'PagoBancomat') {
                                    label += '\n(PA, PB, PP, DAACQU, ecc.)';
                                } else if (context.label.startsWith('Altri (')) {
                                    label += '\n(Circuito non classificato)';
                                }

                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('circuitChart').innerHTML = '<p class="text-center text-muted mt-4">Nessun dato disponibile</p>';
    }
});

// Search functionality per tabella transazioni
$(document).ready(function() {
    $('#searchTable').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#transactionsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>

<?php include 'footer.php'; ?>
