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

<div x-data="{ activeTab: 'IT', showInfo: true }">

    <!-- Page Header -->
    <div class="glass-card mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex-1">
                <h1 style="
                    font-size: var(--text-3xl);
                    font-weight: var(--font-extrabold);
                    background: var(--gradient-cyan);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin: 0 0 var(--space-2) 0;
                ">
                    <i class="fas fa-credit-card" style="margin-right: var(--space-2);"></i>
                    BIN Table Database
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: var(--text-base);">
                    Database completo dei BIN (Bank Identification Number) con informazioni bancarie
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex gap-2">
                <button
                    @click="showInfo = !showInfo"
                    class="btn btn-outline"
                >
                    <i class="fas" :class="showInfo ? 'fa-eye-slash' : 'fa-info-circle'"></i>
                    <span x-text="showInfo ? 'Nascondi Info' : 'Mostra Info'"></span>
                </button>
                <button class="btn btn-outline" onclick="document.getElementById('binlistFrame').contentWindow.location.reload();">
                    <i class="fas fa-sync-alt"></i>
                    Aggiorna
                </button>
            </div>
        </div>

        <!-- Country Tabs -->
        <div class="flex gap-2 mt-6">
            <button
                @click="activeTab = 'IT'"
                :class="activeTab === 'IT' ? 'btn btn-cyan' : 'btn btn-outline'"
                class="flex-1"
                style="text-align: center;"
            >
                <i class="fas fa-flag"></i>
                <span>Italia</span>
            </button>
            <button
                @click="activeTab = 'ES'"
                :class="activeTab === 'ES' ? 'btn btn-cyan' : 'btn btn-outline'"
                class="flex-1"
                style="text-align: center;"
            >
                <i class="fas fa-flag"></i>
                <span>Spagna</span>
            </button>
            <button
                @click="activeTab = 'ALL'"
                :class="activeTab === 'ALL' ? 'btn btn-cyan' : 'btn btn-outline'"
                class="flex-1"
                style="text-align: center;"
            >
                <i class="fas fa-globe"></i>
                <span>Tutti i Paesi</span>
            </button>
        </div>
    </div>

    <!-- Info Cards -->
    <div x-show="showInfo"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="grid grid-cols-auto gap-4 mb-6">

        <div class="glass-card">
            <div style="
                width: 56px;
                height: 56px;
                border-radius: var(--radius-lg);
                background: var(--gradient-cyan);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: var(--space-4);
            ">
                <i class="fas fa-database" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Database BIN</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Codici BIN completi
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
                <i class="fas fa-university" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Banche</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Informazioni bancarie
            </div>
        </div>

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
                <i class="fas fa-flag" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div style="
                font-size: var(--text-2xl);
                font-weight: var(--font-extrabold);
                color: var(--text-primary);
                margin-bottom: var(--space-2);
            ">Paesi</div>
            <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
                Coverage internazionale
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

    <!-- BIN Table -->
    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <div style="
            background: var(--gradient-cyan);
            color: white;
            padding: var(--space-5) var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        ">
            <i class="fas fa-table" style="font-size: 1.5rem;"></i>
            <div>
                <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold);">
                    Elenco Completo BIN
                </h3>
                <p style="margin: var(--space-1) 0 0 0; opacity: 0.9; font-size: var(--text-sm);">
                    Ricerca per BIN, banca, paese, circuito carta
                </p>
            </div>
        </div>

        <iframe
            id="binlistFrame"
            :src="'binlist_table.html?q=' + activeTab"
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
                <i class="fas fa-search" style="color: var(--cyan-500);"></i>
                Ricerca BIN Avanzata
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Utilizza la barra di ricerca nella tabella per cercare rapidamente per codice BIN, nome banca, paese, o circuito carta (Visa, Mastercard, etc.).
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
                <i class="fas fa-info-circle" style="color: var(--primary-500);"></i>
                Cos'è un BIN?
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Il BIN (Bank Identification Number) sono le prime 6-8 cifre di una carta di pagamento che identificano la banca emittente, il paese e il circuito.
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
                Esporta i dati BIN in formato Excel, PDF o CSV utilizzando i pulsanti nella toolbar della tabella.
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
                <i class="fas fa-shield-alt" style="color: var(--secondary-500);"></i>
                Sicurezza
            </h4>
            <p style="color: var(--text-secondary); font-size: var(--text-sm); line-height: 1.6; margin: 0;">
                Il database BIN aiuta a identificare la provenienza delle carte per prevenire frodi e garantire transazioni sicure.
            </p>
        </div>
    </div>

</div>

<style>
/* Cyan button for BIN page */
.btn-cyan {
    background: var(--gradient-cyan);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(34, 211, 238, 0.3);
}

.btn-cyan:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 211, 238, 0.4);
}

.btn-cyan:active {
    transform: translateY(0);
}

/* Cyan color in CSS vars */
:root {
    --cyan-500: #22d3ee;
    --gradient-cyan: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
}
</style>

<script>
// Auto-resize iframe
function resizeIframe() {
    var iframe = document.getElementById('binlistFrame');
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

document.getElementById('binlistFrame')?.addEventListener('load', function() {
    setTimeout(resizeIframe, 500);
});
</script>

<?php require_once('footer-v3.php'); ?>
