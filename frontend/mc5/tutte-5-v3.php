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
    $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $query = "dataOperazione >= '$date' and tipoOperazione <> 'e-Commerce'";
    $dallaData = $date;
    $allaData = $date;
} else {
    $dallaData = $_POST['DALLADATA'];
    $allaData = $_POST['ALLADATA'];
    $city = isset($_POST['SELECTCITTA']) ? $_POST['SELECTCITTA'] : '';

    $query = "dataOperazione >= '$dallaData' AND dataOperazione <= '$allaData' and tipoOperazione <> 'e-Commerce'";
    if (!empty($city)) {
        $query .= " AND localita IN ($city)";
    }
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
                    background: var(--gradient-primary);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin: 0 0 var(--space-2) 0;
                ">
                    <i class="fas fa-credit-card" style="margin-right: var(--space-2);"></i>
                    Transazioni - In Negozio
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: var(--text-base);">
                    Visualizza e filtra tutte le transazioni effettuate nei punti vendita
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
            <a href="tutte-5.php" class="btn btn-primary flex-1" style="text-align: center;">
                <i class="fas fa-flag"></i>
                <span>Italia</span>
            </a>
            <a href="tutte-es.php" class="btn btn-outline flex-1" style="text-align: center;">
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
            <i class="fas fa-filter" style="color: var(--primary-500);"></i>
            Filtri Ricerca
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
                    <button type="submit" class="btn btn-primary w-full">
                        <i class="fas fa-search"></i>
                        Esegui Ricerca
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Current Filter Badge -->
    <?php if (isset($_POST['WHERE'])): ?>
    <div class="badge badge-primary mb-6" style="padding: var(--space-3) var(--space-5); font-size: var(--text-sm);">
        <i class="fas fa-info-circle"></i>
        <span>Periodo: <?php echo $dallaData; ?> → <?php echo $allaData; ?></span>
        <?php if (!empty($city)): ?>
            <span style="margin-left: var(--space-2);">| Negozio selezionato</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Transactions Table -->
    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <iframe
            id="tabellatrx"
            src="tracciato_vista.html?q=<?php echo urlencode($query); ?>"
            style="
                width: 100%;
                height: 800px;
                border: none;
                border-radius: var(--radius-lg);
            "
            frameborder="0"
        ></iframe>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-auto gap-4 mt-6" x-data="{
        stats: { total: 0, approved: 0, rejected: 0, amount: 0 }
    }">
        <div class="glass-card text-center">
            <div style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--primary-500);">
                <i class="fas fa-receipt"></i>
            </div>
            <div style="font-size: var(--text-lg); font-weight: var(--font-semibold); margin-top: var(--space-2);">
                Caricamento...
            </div>
            <div style="color: var(--text-secondary); font-size: var(--text-sm); margin-top: var(--space-1);">
                Totale Transazioni
            </div>
        </div>

        <div class="glass-card text-center">
            <div style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--success-500);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div style="font-size: var(--text-lg); font-weight: var(--font-semibold); margin-top: var(--space-2);">
                Visualizza tabella
            </div>
            <div style="color: var(--text-secondary); font-size: var(--text-sm); margin-top: var(--space-1);">
                Per statistiche dettagliate
            </div>
        </div>

        <div class="glass-card text-center">
            <div style="font-size: var(--text-2xl); font-weight: var(--font-bold);" style="color: #f093fb;">
                <i class="fas fa-file-excel"></i>
            </div>
            <div style="font-size: var(--text-lg); font-weight: var(--font-semibold); margin-top: var(--space-2);">
                Excel Export
            </div>
            <div style="color: var(--text-secondary); font-size: var(--text-sm); margin-top: var(--space-1);">
                Disponibile nella tabella
            </div>
        </div>
    </div>

</div>

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
    var comboTree = $('#cittaInputBox').comboTree({
        source: datatxt,
        isMultiple: true,
        cascadeSelect: true,
        selected: []
    });

    // Handle selection changes
    $('#cittaInputBox').on('change', function() {
        var selectedIds = comboTree.getSelectedIds();
        $('#SELECTCITTA').val(selectedIds.join(','));
    });
});

// Auto-resize iframe based on content
function resizeIframe() {
    var iframe = document.getElementById('tabellatrx');
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
            // Cross-origin restriction, use default height
            iframe.style.height = '800px';
        }
    }
}

// Try to resize after load
document.getElementById('tabellatrx')?.addEventListener('load', function() {
    setTimeout(resizeIframe, 500);
});
</script>

<?php require_once('footer-v3.php'); ?>
