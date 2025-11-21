<?php
require_once 'authentication.php';
require_once("../conf.php");
require_once('menu-v3.php');
require_once 'jsNegozi.php';

if (!($role=="Admin")) {
  echo "Non Hai i permessi per accedere";
  die();
}

if (!session_id() && !headers_sent()) {
   session_start();
}

// Build query
if (!isset($_POST['WHERE'])){
    $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $query = "dataOperazione >= '$date'";
    $dallaData = $date;
    $allaData = $date;
} else {
    $dallaData = $_POST['DALLADATA'];
    $allaData = $_POST['ALLADATA'];
    $otherwhere = "";

    if (isset($_POST['SELECTCITTA'])){
        if (strlen($_POST['SELECTCITTA'])>0)
            $otherwhere = " and ".$_POST['SELECTCITTA'];
    }

    if (isset($_POST['SELECTPOS'])){
        if (strlen($_POST['SELECTPOS'])>0)
            $otherwhere .= " and ".$_POST['SELECTPOS'];
    }

    if (isset($_POST['terminalid'])){
        if (strlen($_POST['terminalid'])>0)
            $otherwhere .= " and terminalID='".$_POST['terminalid']."'";
    }

    $query = "dataOperazione >= '$dallaData' AND dataOperazione <= '$allaData' ".$otherwhere;
}
?>

<!-- jQuery and plugins -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="css/comboTreePlugin.css" />
<script type="text/javascript" src="js/comboTreePlugin.js"></script>

<div x-data="{ showFilters: true }">

    <!-- Page Header -->
    <div class="glass-card mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 style="
                    font-size: var(--text-3xl);
                    font-weight: var(--font-extrabold);
                    background: var(--gradient-danger);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin: 0 0 var(--space-2) 0;
                ">
                    <i class="fas fa-exclamation-triangle" style="margin-right: var(--space-2);"></i>
                    Transazioni Scartate
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: var(--text-base);">
                    Visualizza e analizza le transazioni rifiutate o non completate
                </p>
            </div>
            <button
                @click="showFilters = !showFilters"
                class="btn btn-outline"
                style="white-space: nowrap;"
            >
                <i class="fas" :class="showFilters ? 'fa-eye-slash' : 'fa-filter'"></i>
                <span x-text="showFilters ? 'Nascondi Filtri' : 'Mostra Filtri'"></span>
            </button>
        </div>

        <!-- Country Tabs -->
        <div class="flex gap-2">
            <a href="scarti.php" class="btn btn-danger flex-1" style="text-align: center;">
                <i class="fas fa-flag"></i>
                <span>Italia</span>
            </a>
            <a href="scarti-es.php" class="btn btn-outline flex-1" style="text-align: center;">
                <i class="fas fa-flag"></i>
                <span>Spagna</span>
            </a>
        </div>
    </div>

    <!-- Filters Panel -->
    <div x-show="showFilters"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="glass-card mb-6">

        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-5);">
            <i class="fas fa-filter" style="color: var(--danger-500);"></i>
            Filtri Ricerca Avanzata
        </h3>

        <form method="POST">
            <div class="grid grid-cols-auto gap-4">
                <!-- Store Selector -->
                <div>
                    <label class="label">
                        <i class="fas fa-store"></i>
                        Negozio
                    </label>
                    <input type="hidden" name="SELECTCITTA" id="SELECTCITTA" />
                    <input type="text" id="cittaInputBox" class="input" placeholder="Seleziona il Negozio" />
                </div>

                <!-- POS Model Selector -->
                <div>
                    <label class="label">
                        <i class="fas fa-cash-register"></i>
                        Modello POS
                    </label>
                    <input type="hidden" name="SELECTPOS" id="SELECTPOS" />
                    <input type="text" id="posInputBox" class="input" placeholder="Seleziona il Modello POS" />
                </div>

                <!-- Terminal ID -->
                <div>
                    <label class="label" for="terminalid">
                        <i class="fas fa-terminal"></i>
                        Terminal ID
                    </label>
                    <input type="text" name="terminalid" id="terminalid" class="input" placeholder="Terminal ID" />
                </div>

                <!-- Date From -->
                <div>
                    <label class="label" for="DALLADATA">
                        <i class="fas fa-calendar-alt"></i>
                        Dalla Data
                    </label>
                    <input type="text" name="DALLADATA" id="DALLADATA" class="input" placeholder="aaaa-mm-gg" value="<?php echo $dallaData; ?>" required />
                </div>

                <!-- Date To -->
                <div>
                    <label class="label" for="ALLADATA">
                        <i class="fas fa-calendar-alt"></i>
                        Alla Data
                    </label>
                    <input type="text" name="ALLADATA" id="ALLADATA" class="input" placeholder="aaaa-mm-gg" value="<?php echo $allaData; ?>" required />
                </div>

                <!-- Submit Button -->
                <div style="display: flex; align-items: end;">
                    <input type="hidden" name="WHERE" value="1">
                    <button type="submit" class="btn btn-danger w-full">
                        <i class="fas fa-search"></i>
                        Esegui Ricerca
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Current Filter Badge -->
    <?php if (isset($_POST['WHERE'])): ?>
    <div class="badge badge-danger mb-6" style="padding: var(--space-3) var(--space-5); font-size: var(--text-sm);">
        <i class="fas fa-info-circle"></i>
        <span>Periodo: <?php echo $dallaData; ?> → <?php echo $allaData; ?></span>
        <?php if (!empty($otherwhere)): ?>
            <span style="margin-left: var(--space-2);">| Filtri applicati</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Info Cards -->
    <div class="grid grid-cols-auto gap-4 mb-6">
        <div class="glass-card">
            <div style="
                width: 56px;
                height: 56px;
                border-radius: var(--radius-lg);
                background: var(--gradient-danger);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-times-circle" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Scarti</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Transazioni rifiutate
            </div>
        </div>

        <div class="glass-card">
            <div style="
                width: 56px;
                height: 56px;
                border-radius: var(--radius-lg);
                background: var(--gradient-warning);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-exclamation-circle" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Motivi</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Analisi errori
            </div>
        </div>

        <div class="glass-card">
            <div style="
                width: 56px;
                height: 56px;
                border-radius: var(--radius-lg);
                background: var(--gradient-primary);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-chart-line" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Statistiche</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Tasso di rifiuto
            </div>
        </div>

        <div class="glass-card">
            <div style="
                width: 56px;
                height: 56px;
                border-radius: var(--radius-lg);
                background: var(--gradient-success);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-file-excel" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Export</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Excel / PDF / CSV
            </div>
        </div>
    </div>

    <!-- Rejected Transactions Table -->
    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <div style="
            background: var(--gradient-danger);
            color: white;
            padding: var(--space-5) var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        ">
            <i class="fas fa-table" style="font-size: 1.5rem;"></i>
            <div>
                <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold);">
                    Elenco Transazioni Scartate
                </h3>
                <p style="margin: var(--space-1) 0 0 0; opacity: 0.9; font-size: var(--text-sm);">
                    Filtro applicato: <?php echo $query; ?>
                </p>
            </div>
        </div>

        <iframe
            id="scartiFrame"
            src="https://ricevute.payglobe.it/payglobe/mc4/scarti_vista.html?q=<?php echo urlencode($query); ?>"
            style="
                width: 100%;
                height: 800px;
                border: none;
            "
            frameborder="0"
        ></iframe>
    </div>

    <!-- Features Info -->
    <div class="grid grid-cols-2 gap-6 mt-6">
        <div class="glass-card">
            <h4 style="
                display: flex;
                align-items: center;
                gap: var(--space-2);
                font-size: var(--text-lg);
                font-weight: var(--font-bold);
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-search" style="color: var(--danger-500);"></i>
                Ricerca Avanzata
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Filtra le transazioni scartate per negozio, modello POS, Terminal ID e intervallo di date per analizzare problemi specifici.
            </p>
        </div>

        <div class="glass-card">
            <h4 style="
                display: flex;
                align-items: center;
                gap: var(--space-2);
                font-size: var(--text-lg);
                font-weight: var(--font-bold);
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-exclamation-triangle" style="color: var(--warning-500);"></i>
                Motivi Rifiuto
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Visualizza i codici di errore e i motivi del rifiuto per identificare problemi ricorrenti e migliorare il tasso di approvazione.
            </p>
        </div>

        <div class="glass-card">
            <h4 style="
                display: flex;
                align-items: center;
                gap: var(--space-2);
                font-size: var(--text-lg);
                font-weight: var(--font-bold);
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-download" style="color: var(--success-500);"></i>
                Export Report
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Esporta i report delle transazioni scartate in formato Excel, PDF o CSV per analisi dettagliate offline.
            </p>
        </div>

        <div class="glass-card">
            <h4 style="
                display: flex;
                align-items: center;
                gap: var(--space-2);
                font-size: var(--text-lg);
                font-weight: var(--font-bold);
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-shield-alt" style="color: var(--primary-500);"></i>
                Prevenzione Frodi
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Monitora i pattern di rifiuto per identificare potenziali tentativi di frode o problemi di configurazione dei terminali.
            </p>
        </div>
    </div>

</div>

<style>
/* Danger button for scarti page */
.btn-danger {
    background: var(--gradient-danger);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

.btn-danger:active {
    transform: translateY(0);
}

.badge-danger {
    background: var(--gradient-danger);
    color: white;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    font-weight: var(--font-semibold);
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

/* Danger colors */
:root {
    --danger-500: #ef4444;
    --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
</style>

<script>
$(function() {
    // Initialize datepickers
    $("#DALLADATA, #ALLADATA").datepicker({
        dateFormat: 'yy-mm-dd',
        changeYear: true,
        changeMonth: true
    });

    // Initialize store combo tree
    var datatxt = <?php getNegoziJson(); ?>;
    var comboTree3 = $('#cittaInputBox').comboTree({
        source: datatxt,
        isMultiple: true,
        cascadeSelect: true,
        collapse: true
    });

    // Initialize POS model combo tree
    var datapos = <?php getModelloPosJson(); ?>;
    var comboTree4 = $('#posInputBox').comboTree({
        source: datapos,
        isMultiple: true,
        cascadeSelect: true,
        collapse: true
    });

    // Handle store selection changes
    $('#cittaInputBox').on('change', function() {
        var selectedNames = comboTree3.getSelectedNames();
        var iterator = selectedNames.values();
        var sqlFilter = "(";

        for (let elements of iterator) {
            sqlFilter += "localita='" + elements.substr(0, elements.length - 7) + "' or ";
        }

        sqlFilter = sqlFilter.substr(0, sqlFilter.length - 3);
        sqlFilter += ")";

        $('#SELECTCITTA').val(sqlFilter);
    });

    // Handle POS model selection changes
    $('#posInputBox').on('change', function() {
        var selectedNames = comboTree4.getSelectedNames();
        var iterator = selectedNames.values();
        var sqlFilter = "(";

        for (let elements of iterator) {
            sqlFilter += "Modello_pos='" + elements + "' or ";
        }

        sqlFilter = sqlFilter.substr(0, sqlFilter.length - 3);
        sqlFilter += ")";

        $('#SELECTPOS').val(sqlFilter);
    });
});

// Auto-resize iframe
function resizeIframe() {
    var iframe = document.getElementById('scartiFrame');
    if (iframe) {
        try {
            var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            if (iframeDoc && iframeDoc.body) {
                var height = Math.max(
                    iframeDoc.body.scrollHeight,
                    iframeDoc.documentElement.scrollHeight,
                    800
                );
                iframe.style.height = height + 'px';
            }
        } catch(e) {
            iframe.style.height = '800px';
        }
    }
}

document.getElementById('scartiFrame')?.addEventListener('load', function() {
    setTimeout(resizeIframe, 500);
});
</script>

<?php require_once('footer-v3.php'); ?>
