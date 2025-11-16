<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';
include 'config.php';

// Controllo BU
$userBU = $_SESSION['bu'];
$isAdmin = ($userBU === '9999');

// Parametri di ricerca e filtri
$search = $_GET['search'] ?? '';
$filterCity = $_GET['filterCity'] ?? '';
$filterProvince = $_GET['filterProvince'] ?? '';
$filterCountry = $_GET['filterCountry'] ?? '';
$sortBy = $_GET['sortBy'] ?? 'TerminalID';
$sortOrder = $_GET['sortOrder'] ?? 'ASC';

// Parametri di paginazione
$limit = $_GET['limit'] ?? 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Costruzione della query di base
$baseQuery = "FROM stores";
$whereConditions = [];
$params = [];
$types = "";

// Filtro BU (se non admin)
if (!$isAdmin) {
    $whereConditions[] = "bu = ?";
    $params[] = $userBU;
    $types .= "s";
}

// Filtro ricerca globale
if ($search) {
    $whereConditions[] = "(TerminalID LIKE ? OR Ragione_Sociale LIKE ? OR Insegna LIKE ? OR indirizzo LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

// Filtri specifici
if ($filterCity) {
    $whereConditions[] = "citta = ?";
    $params[] = $filterCity;
    $types .= "s";
}

if ($filterProvince) {
    $whereConditions[] = "prov = ?";
    $params[] = $filterProvince;
    $types .= "s";
}

if ($filterCountry) {
    $whereConditions[] = "country = ?";
    $params[] = $filterCountry;
    $types .= "s";
}

// Costruzione WHERE clause
$whereClause = "";
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Validazione colonna di ordinamento
$validSortColumns = ['TerminalID', 'Ragione_Sociale', 'Insegna', 'citta', 'prov', 'country'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'TerminalID';
}
$sortOrder = ($sortOrder === 'DESC') ? 'DESC' : 'ASC';

// Query per il conteggio totale
$countQuery = "SELECT COUNT(*) as total $baseQuery $whereClause";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRecords = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Query per i dati
$dataQuery = "SELECT TerminalID, Ragione_Sociale, Insegna, indirizzo, citta, cap, prov, Modello_pos, country, 
                     (SELECT COUNT(*) FROM transactions WHERE posid = stores.TerminalID AND DATE(transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as recent_transactions,
                     (SELECT MAX(transaction_date) FROM transactions WHERE posid = stores.TerminalID) as last_transaction
              $baseQuery $whereClause 
              ORDER BY $sortBy $sortOrder 
              LIMIT ? OFFSET ?";

$stmt = $conn->prepare($dataQuery);
$queryParams = $params;
$queryTypes = $types . "ii";
$queryParams[] = $limit;
$queryParams[] = $offset;
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Ottieni liste per dropdown filtri (con LIMIT per evitare timeout)
$citiesQuery = "SELECT DISTINCT citta FROM stores " . ($isAdmin ? "" : "WHERE bu = '$userBU'") . " ORDER BY citta LIMIT 500";
$cities = $conn->query($citiesQuery)->fetch_all(MYSQLI_ASSOC);

$provincesQuery = "SELECT DISTINCT prov FROM stores " . ($isAdmin ? "" : "WHERE bu = '$userBU'") . " ORDER BY prov LIMIT 200";
$provinces = $conn->query($provincesQuery)->fetch_all(MYSQLI_ASSOC);

$countriesQuery = "SELECT DISTINCT country FROM stores " . ($isAdmin ? "" : "WHERE bu = '$userBU'") . " ORDER BY country LIMIT 100";
$countries = $conn->query($countriesQuery)->fetch_all(MYSQLI_ASSOC);

$totalPages = ceil($totalRecords / $limit);
?>

<div class="container-fluid mt-4">
    <!-- Header con statistiche -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-store"></i> Gestione Punti Vendita</h2>
            <p class="text-muted">Totale: <?= number_format($totalRecords) ?> negozi</p>
        </div>
        <div class="text-right">
            <?php if ($isAdmin): ?>
                <span class="badge badge-warning badge-lg">
                    <i class="fas fa-crown"></i> Amministratore - Tutti i POS
                </span>
            <?php else: ?>
                <span class="badge badge-info badge-lg">
                    <i class="fas fa-building"></i> BU: <?= htmlspecialchars($userBU) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiche rapide -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= number_format($totalRecords) ?></h3>
                    <small>Punti Vendita Totali</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <?php
                    // Per admin: troppi dati, mostriamo N/D
                    if ($isAdmin) {
                        $activePos = 'N/D';
                        $activePosNote = 'Troppi dati';
                    } else {
                        $activePosQuery = "SELECT COUNT(DISTINCT posid) as active
                                          FROM transactions
                                          WHERE DATE(transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                          AND posid IN (SELECT TerminalID FROM stores WHERE bu = '$userBU')";
                        $activePos = number_format($conn->query($activePosQuery)->fetch_assoc()['active']);
                        $activePosNote = 'Attivi (7 giorni)';
                    }
                    ?>
                    <h3><?= $activePos ?></h3>
                    <small><?= $activePosNote ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <?php
                    // Per admin: troppi dati, mostriamo N/D
                    if ($isAdmin) {
                        $cityCount = 'N/D';
                    } else {
                        $cityCountQuery = "SELECT COUNT(DISTINCT citta) as cities FROM stores WHERE bu = '$userBU'";
                        $cityCount = number_format($conn->query($cityCountQuery)->fetch_assoc()['cities']);
                    }
                    ?>
                    <h3><?= $cityCount ?></h3>
                    <small>Città Coperte</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <?php
                    // Per admin: troppi dati, mostriamo N/D
                    if ($isAdmin) {
                        $countryCount = 'N/D';
                    } else {
                        $countryCountQuery = "SELECT COUNT(DISTINCT country) as countries FROM stores WHERE bu = '$userBU'";
                        $countryCount = number_format($conn->query($countryCountQuery)->fetch_assoc()['countries']);
                    }
                    ?>
                    <h3><?= $countryCount ?></h3>
                    <small>Paesi</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri avanzati -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtri di Ricerca</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3">
                    <label>Ricerca Globale</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="TID, Nome, Insegna, Indirizzo..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label>Città</label>
                    <select name="filterCity" class="form-control">
                        <option value="">— Tutte le Città —</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city['citta']) ?>" 
                                    <?= $filterCity === $city['citta'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['citta']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Provincia</label>
                    <select name="filterProvince" class="form-control">
                        <option value="">— Tutte le Province —</option>
                        <?php foreach ($provinces as $prov): ?>
                            <option value="<?= htmlspecialchars($prov['prov']) ?>" 
                                    <?= $filterProvince === $prov['prov'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['prov']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Paese</label>
                    <select name="filterCountry" class="form-control">
                        <option value="">— Tutti i Paesi —</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country['country']) ?>" 
                                    <?= $filterCountry === $country['country'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($country['country']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Righe</label>
                    <select name="limit" class="form-control">
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtra
                        </button>
                        <a href="stores.php" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Azioni bulk -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Esporta Excel
            </button>
        </div>
        <div>
            <small class="text-muted">
                Mostra <?= ($offset + 1) ?>-<?= min($offset + $limit, $totalRecords) ?> di <?= number_format($totalRecords) ?> risultati
            </small>
        </div>
    </div>

    <!-- Tabella -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="storesTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sortBy' => 'TerminalID', 'sortOrder' => $sortBy === 'TerminalID' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">
                                    Terminal ID 
                                    <?php if ($sortBy === 'TerminalID'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sortBy' => 'Ragione_Sociale', 'sortOrder' => $sortBy === 'Ragione_Sociale' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">
                                    Ragione Sociale
                                    <?php if ($sortBy === 'Ragione_Sociale'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Insegna</th>
                            <th>Indirizzo Completo</th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sortBy' => 'citta', 'sortOrder' => $sortBy === 'citta' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">
                                    Città
                                    <?php if ($sortBy === 'citta'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Modello POS</th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sortBy' => 'country', 'sortOrder' => $sortBy === 'country' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">
                                    Paese
                                    <?php if ($sortBy === 'country'): ?>
                                        <i class="fas fa-sort-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Attività</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <code class="text-primary"><?= htmlspecialchars($row['TerminalID']) ?></code>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['Ragione_Sociale'] ?: 'N/D') ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($row['Insegna'] ?: 'N/D') ?></span>
                                </td>
                                <td>
                                    <small>
                                        <?= htmlspecialchars($row['indirizzo']) ?><br>
                                        <?= htmlspecialchars($row['cap']) ?> <?= htmlspecialchars($row['citta']) ?> (<?= htmlspecialchars($row['prov']) ?>)
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($row['citta']) ?></span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($row['Modello_pos'] ?: 'N/D') ?></small>
                                </td>
                                <td>
                                    <img src="flags/<?= strtolower($row['country']) ?>.svg" alt="<?= $row['country'] ?>"
                                         style="width: 20px; height: 15px; margin-right: 5px;" onerror="this.style.display='none'">
                                    <?= htmlspecialchars($row['country']) ?>
                                </td>
                                <td>
                                    <?php if ($row['recent_transactions'] > 0): ?>
                                        <span class="badge badge-success" title="<?= $row['recent_transactions'] ?> transazioni negli ultimi 30 giorni">
                                            <i class="fas fa-check-circle"></i> Attivo
                                        </span>
                                        <br><small class="text-muted">
                                            Ultima: <?= $row['last_transaction'] ? date('d/m/Y', strtotime($row['last_transaction'])) : 'N/D' ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge badge-warning" title="Nessuna transazione negli ultimi 30 giorni">
                                            <i class="fas fa-exclamation-triangle"></i> Inattivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="https://ricevute.payglobe.it/merchant/dashboard_transazioni.php?tid=<?= htmlspecialchars(trim($row['TerminalID']), ENT_QUOTES, 'UTF-8') ?>"
                                           class="btn btn-success btn-sm" title="Cassa express - Gestione Prodotti" target="_blank">
                                            <i class="fas fa-receipt"></i> Cassa express
                                        </a>
                                        <a href="index.php?filterStore=<?= htmlspecialchars(trim($row['TerminalID']), ENT_QUOTES, 'UTF-8') ?>"
                                           class="btn btn-primary btn-sm" title="Dashboard transazioni">
                                            <i class="fas fa-chart-bar"></i> Dashboard
                                        </a>
                                        <a href="statistics.php?filterStore=<?= htmlspecialchars(trim($row['TerminalID']), ENT_QUOTES, 'UTF-8') ?>"
                                           class="btn btn-info btn-sm" title="Statistiche avanzate">
                                            <i class="fas fa-analytics"></i> Statistiche
                                        </a>
                                        <button class="btn btn-secondary btn-sm" onclick="showStoreDetails('<?= htmlspecialchars(trim($row['TerminalID']), ENT_QUOTES, 'UTF-8') ?>')"
                                                title="Dettagli store completi">
                                            <i class="fas fa-eye"></i> Dettagli Store
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    <i class="fas fa-search"></i> Nessun risultato trovato
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginazione migliorata -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Navigazione pagine" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal per dettagli store -->
<div class="modal fade" id="storeDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dettagli Punto Vendita</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="storeDetailsContent">
                <!-- Contenuto caricato via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Esportazione Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.delete('page'); // Esporta tutti i risultati
    window.open('export_excel_stores.php?' + params.toString(), '_blank');
}

// Mostra dettagli store in modal
function showStoreDetails(terminalId) {
    $('#storeDetailsModal').modal('show');
    $('#storeDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Caricamento...</div>');
    
    $.ajax({
        url: 'store_details_ajax.php',
        method: 'GET',
        data: { tid: terminalId },
        success: function(response) {
            $('#storeDetailsContent').html(response);
        },
        error: function() {
            $('#storeDetailsContent').html('<div class="alert alert-danger">Errore nel caricamento dei dettagli.</div>');
        }
    });
}

// Evidenziazione risultati ricerca
$(document).ready(function() {
    const searchTerm = '<?= addslashes($search) ?>';
    if (searchTerm) {
        $('#storesTable td').each(function() {
            const text = $(this).text();
            if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
                const highlightedText = text.replace(
                    new RegExp(searchTerm, 'gi'), 
                    '<mark>$&</mark>'
                );
                $(this).html(highlightedText);
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>
<?php $conn->close(); ?>
