<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';
include 'config.php'; // Assicurati che questo file esista e sia corretto
?>

<div class="container mt-5">
    <h2 class="mb-4">Transazioni FTFS</h2>

    <!-- Filtri -->
    <div class="row mb-3">
        <div class="col-md-2">
            <label for="startDate">Data Inizio:</label>
            <input type="text" id="startDate" autocomplete="off" class="form-control datepicker" placeholder="Seleziona data inizio">
        </div>
        <div class="col-md-2">
            <label for="endDate">Data Fine:</label>
            <input type="text" id="endDate" autocomplete="off" class="form-control datepicker" placeholder="Seleziona data fine">
        </div>
        <div class="col-md-2">
            <label for="minAmount">Importo Min:</label>
            <input type="number" id="minAmount" autocomplete="off" class="form-control" placeholder="Min">
        </div>
        <div class="col-md-2">
            <label for="maxAmount">Importo Max:</label>
            <input type="number" id="maxAmount" autocomplete="off" class="form-control" placeholder="Max">
        </div>
        <div class="col-md-2">
            <label for="terminalID">TML:</label>
            <input type="text" id="terminalID" autocomplete="off" class="form-control" placeholder="Terminal ID">
        </div>
        <div class="col-md-2">
            <label for="siaCode">Sia Code:</label>
            <input type="text" id="siaCode" class="form-control" placeholder="Sia Code">
        </div>

        <div class="col-md-2">
            <button type="button" id="filterButton" class="btn btn-primary mt-4">Filtra</button>
            <button type="button" id="resetButton" class="btn btn-secondary mt-4">Reset</button>
        </div>
    </div>
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Cerca nella tabella...">

    <a href="#" id="exportButton" class="btn btn-success mb-3">Esporta in Excel</a>
    <!-- Diagramma a torta -->
    <div class="row mb-3">
        <div class="col-md-6">
            <canvas id="acquirerChart"></canvas>
        </div>
        <div class="col-md-6">
            <div id="summary" class="border p-3">
                <!-- Riepilogo verrà inserito qui -->
                <p id="queryDescription"></p>
            </div>
        </div>
    </div>
    <?php if (!isAdmin()): ?>
    <div class="">
        <table class="table table-bordered" id="myTable">
            <thead>
                <tr>
                    <th>Data Transazione</th>
                    <th>Ora Transazione</th>
                    <th>TML</th>
                    <th>Sia Code</th>
                    <th>Circuito</th>
                    <th>PAN</th>
                    <th>Importo</th>
                    <th>Appr Num</th>
                    <th>Acquirer</th>
                    <th>Gt Resp</th>
                    <th>Tr Key</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Funzione per ottenere i dati per il diagramma a torta
                function getAcquirerData($conn, $startDate = null, $endDate = null, $minAmount = null, $maxAmount = null, $terminalID = null, $siaCode = null) {
                    $whereClause = " WHERE 1=1 ";
                    $params = [];
                    $types = "";

                    if ($startDate && $endDate) {
                        $whereClause .= " AND DtTrans BETWEEN ? AND ? ";
                        $params[] = $startDate;
                        $params[] = $endDate;
                        $types .= "ss";
                    }

                    if ($minAmount !== null) {
                        $whereClause .= " AND Amount >= ? ";
                        $params[] = $minAmount;
                        $types .= "d";
                    }

                    if ($maxAmount !== null) {
                        $whereClause .= " AND Amount <= ? ";
                        $params[] = $maxAmount;
                        $types .= "d";
                    }

                    if ($terminalID !== null && $terminalID !== "") {
                        $whereClause .= " AND TermId = ? ";
                        $params[] = $terminalID;
                        $types .= "s";
                    }
                    if ($siaCode !== null && $siaCode !== "") {
                        $whereClause .= " AND SiaCode = ? ";
                        $params[] = $siaCode;
                        $types .= "s";
                    }

                    $sql = "SELECT Acquirer, COUNT(*) AS count, SUM(Amount) AS total_amount FROM ftfs_transactions " . $whereClause . " GROUP BY Acquirer";
                    $stmt = $conn->prepare($sql);

                    if (strlen($types) > 0) {
                        $stmt->bind_param($types, ...$params);
                    }

                    $stmt->execute();
                    $result = $stmt->get_result();
                    $data = [];
                    $totalTransactions = 0;
                    $totalAmount = 0;
                    while ($row = $result->fetch_assoc()) {
                        $data[$row['Acquirer']] = ['count' => $row['count'], 'total_amount' => $row['total_amount']];
                        $totalTransactions += $row['count'];
                        $totalAmount += $row['total_amount'];
                    }
                    $stmt->close();
                    return ['acquirerData' => $data, 'totalTransactions' => $totalTransactions, 'totalAmount' => $totalAmount];
                }

                // Funzione per eseguire la query e restituire i risultati
                function getTableData($conn, $limit, $offset, $startDate = null, $endDate = null, $minAmount = null, $maxAmount = null, $terminalID = null, $siaCode = null) {
                    $whereClause = " WHERE 1=1 ";
                    $params = [];
                    $types = "";

                    if ($startDate && $endDate) {
                        $whereClause .= " AND DtTrans BETWEEN ? AND ? ";
                        $params[] = $startDate;
                        $params[] = $endDate;
                        $types .= "ss";
                    }

                    if ($minAmount !== null) {
                        $whereClause .= " AND Amount >= ? ";
                        $params[] = $minAmount;
                        $types .= "d"; // 'd' for double (or float)
                    }

                    if ($maxAmount !== null) {
                        $whereClause .= " AND Amount <= ? ";
                        $params[] = $maxAmount;
                        $types .= "d"; // 'd' for double (or float)
                    }

                    if ($terminalID !== null && $terminalID !== "") {
                        $whereClause .= " AND TermId = ? ";
                        $params[] = $terminalID;
                        $types .= "s"; // 's' for string
                    }
                    if ($siaCode !== null && $siaCode !== "") {
                        $whereClause .= " AND SiaCode = ? ";
                        $params[] = $siaCode;
                        $types .= "s"; // 's' for string
                    }

                    $sqlCount = "SELECT COUNT(*) AS total FROM ftfs_transactions " . $whereClause;
                    $stmtCount = $conn->prepare($sqlCount);
                    if (strlen($types) > 0) {
                        $stmtCount->bind_param($types, ...$params);
                    }
                    $stmtCount->execute();
                    $resultCount = $stmtCount->get_result();
                    $rowCount = $resultCount->fetch_assoc()['total'];
                    $stmtCount->close();

                    $sql = "SELECT DtTrans, TermId, SiaCode, ApprNum, Acquirer, Pan, Amount, GtResp, TrKey FROM ftfs_transactions " . $whereClause . " LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($sql);

                    if (strlen($types) > 0) {
                        $types .= "ii";
                        $params[] = $limit;
                        $params[] = $offset;
                        $stmt->bind_param($types, ...$params);
                    } else {
                        $stmt->bind_param("ii", $limit, $offset);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $data = ['result' => $result, 'rowCount' => $rowCount];
                    $stmt->close();
                    return $data;
                }

                // Parametri di paginazione
                $limit = 25;
                $page = isset($_GET['page']) ? $_GET['page'] : 1;
                $offset = ($page - 1) * $limit;

                // Recupera i parametri dai parametri GET
                $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date("Y-m-d", strtotime("-1 day")); // Imposta la data di ieri come default
                $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date("Y-m-d"); // Imposta la data di oggi come default
                $minAmount = isset($_GET['minAmount']) ? $_GET['minAmount'] : null;
                $maxAmount = isset($_GET['maxAmount']) ? $_GET['maxAmount'] : null;
                $terminalID = isset($_GET['terminalID']) ? $_GET['terminalID'] : null;
                $siaCode = isset($_GET['siaCode']) ? $_GET['siaCode'] : null;

                // Ottieni i dati per il diagramma a torta
                $acquirerDataResult = getAcquirerData($conn, $startDate, $endDate, $minAmount, $maxAmount, $terminalID, $siaCode);
                $acquirerData = $acquirerDataResult['acquirerData'];
                $totalTransactions = $acquirerDataResult['totalTransactions'];
                $totalAmount = $acquirerDataResult['totalAmount'];

                // Ottieni i dati della tabella
                $tableData = getTableData($conn, $limit, $offset, $startDate, $endDate, $minAmount, $maxAmount, $terminalID, $siaCode);
                $result = $tableData['result'];
                $rowCount = $tableData['rowCount'];

                // Costruisci la descrizione della query
                $queryDescription = "Visualizzazione delle transazioni FTFS";
                if ($startDate && $endDate) {
                    $queryDescription .= " dal " . date("d/m/Y", strtotime($startDate)) . " al " . date("d/m/Y", strtotime($endDate));
                }
                if ($minAmount !== null) {
                    $queryDescription .= " con importo minimo di " . number_format($minAmount, 2, ',', '.') . " €";
                }
                if ($maxAmount !== null) {
                    $queryDescription .= " e importo massimo di " . number_format($maxAmount, 2, ',', '.') . " €";
                }
                if ($terminalID !== null && $terminalID !== "") {
                    $queryDescription .= " per il terminale con ID " . $terminalID;
                }
                if ($siaCode !== null && $siaCode !== "") {
                    $queryDescription .= " con Sia Code " . $siaCode;
                }

                echo "<script>document.getElementById('queryDescription').textContent = '" . $queryDescription . "';</script>";

                $row_count = 0;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row_class = ($row_count % 2 == 0) ? 'table-light' : 'table-secondary';
                        echo "<tr class='$row_class'>";
                        echo "<td>" . $row["DtTrans"] . "</td>";
                        echo "<td>" . substr($row["DtTrans"],11,8) . "</td>";
                        echo "<td>" . $row["TermId"] . "</td>";
                        echo "<td>" . $row["SiaCode"] . "</td>";
                        echo "<td>" . $row["Acquirer"] . "</td>";
                        echo "<td>" . $row["Pan"] . "</td>";
                        echo "<td style='text-align: right;'>" . number_format($row["Amount"], 2, ',', '.') . " €</td>";
                        echo "<td>" . $row["ApprNum"] . "</td>";
                        echo "<td>" . $row["Acquirer"] . "</td>";
                        echo "<td>" . $row["GtResp"] . "</td>";
                        echo "<td>" . $row["TrKey"] . "</td>";
                        echo "</tr>";
                        $row_count++;
                    }
                } else {
                    echo "<tr><td colspan='11'>Nessun dato trovato</td></tr>";
                }

                // Calcola il numero totale di pagine
                $totalPages = ceil($rowCount / $limit);

                // Link per la paginazione
                echo "<div class='pagination-container'>";
                echo "<nav aria-label='Navigazione paginazione'>";
                echo "<ul class='pagination justify-content-center'>";

                $maxLinks = 5; // Number of links to show around the current page

                // Link alla pagina precedente
                if ($page > 1) {
                    echo "<li class='page-item'><a class='page-link' href='ftfs_index.php?page=" . ($page - 1) . ($startDate ? "&startDate=" . $startDate : "") . ($endDate ? "&endDate=" . $endDate : "") . ($minAmount ? "&minAmount=" . $minAmount : "") . ($maxAmount ? "&maxAmount=" . $maxAmount : "") . ($terminalID ? "&terminalID=" . $terminalID : "") . ($siaCode ? "&siaCode=" . $siaCode : "") . "'>Precedente</a></li>";
                }

                // Link alla prima pagina
                if ($totalPages > 1 && $page > ($maxLinks / 2 + 1)) {
                    echo "<li class='page-item'><a class='page-link' href='ftfs_index.php?page=1" . ($startDate ? "&startDate=" . $startDate : "") . ($endDate ? "&endDate=" . $endDate : "") . ($minAmount ? "&minAmount=" . $minAmount : "") . ($maxAmount ? "&maxAmount=" . $maxAmount : "") . ($terminalID ? "&terminalID=" . $terminalID : "") . ($siaCode ? "&siaCode=" . $siaCode : "") . "'>1</a></li>";
                    if ($page > ($maxLinks / 2 + 2)) {
                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                }

                // Link alle pagine intermedie
                $start = max(1, $page - floor($maxLinks / 2));
                $end = min($totalPages, $page + floor($maxLinks / 2));

                for ($i = $start; $i <= $end; $i++) {
                    echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'><a class='page-link' href='ftfs_index.php?page=" . $i . ($startDate ? "&startDate=" . $startDate : "") . ($endDate ? "&endDate=" . $endDate : "") . ($minAmount ? "&minAmount=" . $minAmount : "") . ($maxAmount ? "&maxAmount=" . $maxAmount : "") . ($terminalID ? "&terminalID=" . $terminalID : "") . ($siaCode ? "&siaCode=" . $siaCode : "") . "'>" . $i . "</a></li>";
                }

  // Link all'ultima pagina
  if ($totalPages > 1 && $page < $totalPages - ($maxLinks / 2)) {
    if ($page < $totalPages - ($maxLinks / 2 + 1)) {
        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
    }
    echo "<li class='page-item'><a class='page-link' href='ftfs_index.php?page=" . $totalPages . ($startDate ? "&startDate=" . $startDate : "") . ($endDate ? "&endDate=" . $endDate : "") . ($minAmount ? "&minAmount=" . $minAmount : "") . ($maxAmount ? "&maxAmount=" . $maxAmount : "") . ($terminalID ? "&terminalID=" . $terminalID : "") . ($siaCode ? "&siaCode=" . $siaCode : "") . "'>" . $totalPages . "</a></li>";
}



              

                // Link alla pagina successiva
                if ($page < $totalPages) {
                    echo "<li class='page-item'><a class='page-link' href='ftfs_index.php?page=" . ($page + 1) . ($startDate ? "&startDate=" . $startDate : "") . ($endDate ? "&endDate=" . $endDate : "") . ($minAmount ? "&minAmount=" . $minAmount : "") . ($maxAmount ? "&maxAmount=" . $maxAmount : "") . ($terminalID ? "&terminalID=" . $terminalID : "") . ($siaCode ? "&siaCode=" . $siaCode : "") . "'>Successiva</a></li>";
                }

                echo "</ul>";
                echo "</nav>";
                echo "</div>";
                ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Funzione per creare il grafico a torta
    function createAcquirerChart(data) {
        const ctx = document.getElementById('acquirerChart').getContext('2d');
        const labels = Object.keys(data);
        const counts = Object.values(data).map(item => item.count);

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Numero di Transazioni',
                    data: counts,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Distribuzione Transazioni per Acquirer'
                    }
                }
            }
        });
    }

    // Funzione per aggiornare il riepilogo
    function updateSummary(totalTransactions, totalAmount) {
        const summaryDiv = document.getElementById('summary');
        summaryDiv.innerHTML = `
            <p>Totale Transazioni: ${totalTransactions}</p>
            <p>Importo Totale: ${totalAmount.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })}</p>
        `;
    }

    // Dati per il grafico a torta
    const acquirerData = <?php echo json_encode($acquirerData); ?>;
    const totalTransactions = <?php echo json_encode($totalTransactions); ?>;
    const totalAmount = <?php echo json_encode($totalAmount); ?>;

    // Crea il grafico a torta
    createAcquirerChart(acquirerData);

    // Aggiorna il riepilogo
    updateSummary(totalTransactions, totalAmount);

    // Gestione dei filtri
    document.getElementById('filterButton').addEventListener('click', function () {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const minAmount = document.getElementById('minAmount').value;
        const maxAmount = document.getElementById('maxAmount').value;
        const terminalID = document.getElementById('terminalID').value;
        const siaCode = document.getElementById('siaCode').value;

        let url = 'ftfs_index.php?';
        if (startDate) url += `startDate=${startDate}&`;
        if (endDate) url += `endDate=${endDate}&`;
        if (minAmount) url += `minAmount=${minAmount}&`;
        if (maxAmount) url += `maxAmount=${maxAmount}&`;
        if (terminalID) url += `terminalID=${terminalID}&`;
        if (siaCode) url += `siaCode=${siaCode}&`;

        window.location.href = url.slice(0, -1); // Rimuove l'ultimo '&'
    });

    // Gestione del reset dei filtri
    document.getElementById('resetButton').addEventListener('click', function () {
        window.location.href = 'ftfs_index.php';
    });

    // Gestione della ricerca nella tabella
    document.getElementById('searchInput').addEventListener('input', function () {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll('#myTable tbody tr');

        rows.forEach(row => {
            let found = false;
            row.querySelectorAll('td').forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchText)) {
                    found = true;
                }
            });
            row.style.display = found ? '' : 'none';
        });
    });

    // Gestione dell'esportazione in Excel
    document.getElementById('exportButton').addEventListener('click', function (e) {
        e.preventDefault();
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const minAmount = document.getElementById('minAmount').value;
        const maxAmount = document.getElementById('maxAmount').value;
        const terminalID = document.getElementById('terminalID').value;
        const siaCode = document.getElementById('siaCode').value;

        let url = 'export_ftfs.php?';
        if (startDate) url += `startDate=${startDate}&`;
        if (endDate) url += `endDate=${endDate}&`;
        if (minAmount) url += `minAmount=${minAmount}&`;
        if (maxAmount) url += `maxAmount=${maxAmount}&`;
        if (terminalID) url += `terminalID=${terminalID}&`;
        if (siaCode) url += `siaCode=${siaCode}&`;

        window.location.href = url.slice(0, -1); // Rimuove l'ultimo '&'
    });
</script>
<?php include 'footer.php'; ?>