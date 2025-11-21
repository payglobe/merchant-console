<?php
require_once 'authentication.php';
require_once("../conf.php");
require_once('menu-v3.php');

if (!($role=="Admin")) {
  echo "Non Hai i permessi per accedere";
  die();
}

if (!session_id() && !headers_sent()) {
   session_start();
}
?>

<div x-data="{ activeTab: 'IT' }">

    <!-- Page Header -->
    <div class="glass-card mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex-1">
                <h1 style="
                    font-size: var(--text-3xl);
                    font-weight: var(--font-extrabold);
                    background: var(--gradient-secondary);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin: 0 0 var(--space-2) 0;
                ">
                    <i class="fas fa-store" style="margin-right: var(--space-2);"></i>
                    Gestione Negozi
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: var(--text-base);">
                    Visualizza e gestisci tutti i punti vendita e terminali POS
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex gap-2">
                <button class="btn btn-outline" onclick="document.getElementById('storesFrame').contentWindow.location.reload();">
                    <i class="fas fa-sync-alt"></i>
                    Aggiorna
                </button>
            </div>
        </div>

        <!-- Country Tabs -->
        <div class="flex gap-2 mt-6">
            <button
                @click="activeTab = 'IT'"
                :class="activeTab === 'IT' ? 'btn btn-secondary' : 'btn btn-outline'"
                class="flex-1"
                style="text-align: center;"
            >
                <i class="fas fa-flag"></i>
                <span>Italia</span>
            </button>
            <button
                @click="activeTab = 'ES'"
                :class="activeTab === 'ES' ? 'btn btn-secondary' : 'btn btn-outline'"
                class="flex-1"
                style="text-align: center;"
            >
                <i class="fas fa-flag"></i>
                <span>Spagna</span>
            </button>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-auto gap-4 mb-6">
        <div class="glass-card">
            <div style="
                width: 56px;
                height: 56px;
                border-radius: var(--radius-lg);
                background: var(--gradient-secondary);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-store-alt" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Tutti i Negozi</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Punti vendita attivi
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
                <i class="fas fa-cash-register" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Terminali POS</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Dispositivi configurati
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

    <!-- Stores Table -->
    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <div style="
            background: var(--gradient-secondary);
            color: white;
            padding: var(--space-5) var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        ">
            <i class="fas fa-table" style="font-size: 1.5rem;"></i>
            <div>
                <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold);">
                    Elenco Completo Negozi
                </h3>
                <p style="margin: var(--space-1) 0 0 0; opacity: 0.9; font-size: var(--text-sm);">
                    Dati aggiornati in tempo reale
                </p>
            </div>
        </div>

        <iframe
            id="storesFrame"
            :src="'stores_table.html?q=' + activeTab"
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
                <i class="fas fa-search" style="color: var(--primary-500);"></i>
                Ricerca Avanzata
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Utilizza la barra di ricerca nella tabella per filtrare rapidamente per Terminal ID, nome negozio, insegna, città o provincia.
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
                Export Dati
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Esporta i dati in formato Excel, PDF o CSV utilizzando i pulsanti nella toolbar della tabella.
            </p>
        </div>
    </div>

</div>

<script>
// Auto-resize iframe
function resizeIframe() {
    var iframe = document.getElementById('storesFrame');
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

document.getElementById('storesFrame')?.addEventListener('load', function() {
    setTimeout(resizeIframe, 500);
});
</script>

<?php require_once('footer-v3.php'); ?>
