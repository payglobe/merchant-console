<?php
require_once 'authentication.php';
require_once('menu-v3.php');

if (!($role=="Admin")) {
    die();
}

if (!session_id() && !headers_sent()) {
    session_start();
}

// Data logic
if (!isset($_POST['WHERE'])){
    $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $query="dataOperazione >= '$date'";
    $dallaData =  $date;
    $allaData =  $date;
} else {
    $dallaData = $_POST['DALLADATA'];
    $allaData = $_POST['ALLADATA'];
    $query="dataOperazione >= '$dallaData' AND  dataOperazione <= '$allaData'";
}
date_default_timezone_set('Europe/Vatican');
?>

<!-- jQuery (required for jQuery UI) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- jQuery UI for datepicker -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Alpine.js Dashboard Component -->
<div x-data="dashboard()" x-init="init()">

    <!-- Loading Spinner with Progress Bar -->
    <div x-show="loading" style="text-align: center; padding: 80px 40px;">
      <div style="max-width: 500px; margin: 0 auto;">
        <div class="spinner" style="margin: 0 auto 32px;"></div>
        <h3 style="color: var(--gray-900); font-weight: 700; margin-bottom: 12px; font-size: 20px;">
          <i class="fas fa-chart-line" style="color: var(--primary-500);"></i>
          Cargando Panel de Control...
        </h3>
        <div style="background: var(--gray-200); height: 10px; border-radius: var(--radius-full); overflow: hidden; margin-bottom: 16px;">
          <div style="
            background: linear-gradient(90deg, var(--primary-600), var(--primary-400), var(--primary-600));
            height: 100%;
            width: 100%;
            animation: progress-indeterminate 1.5s ease-in-out infinite;
            background-size: 200% 100%;
          "></div>
        </div>
        <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 8px;">Cargando datos y gráficos...</p>
        <p style="color: var(--gray-500); font-size: 13px;">Por favor espere, procesando miles de transacciones.</p>
      </div>
    </div>

    <div x-show="!loading">

    <!-- Country Tabs -->
    <div class="glass-card mb-6" style="padding: var(--space-3);">
      <div class="flex gap-2">
        <a href="index.php" class="btn btn-outline flex-1" style="text-align: center;">
          <i class="fas fa-flag"></i>
          <span>Italia</span>
        </a>
        <a href="index-es.php" class="btn btn-primary flex-1" style="text-align: center;">
          <i class="fas fa-flag"></i>
          <span>España</span>
        </a>
      </div>
    </div>

    <!-- Date Filter -->
    <div class="glass-card mb-6">
      <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-5);">
        <i class="fas fa-calendar-alt" style="color: var(--primary-500);"></i>
        Filtro Periodo
      </h3>
      <form method="POST">
        <div class="grid grid-cols-auto gap-4">
          <div>
            <label class="label" for="DALLADATA">Dalla Data</label>
            <input type="text" name="DALLADATA" id="DALLADATA" class="input" placeholder="aaaa-mm-gg" value="<?php echo $dallaData; ?>">
          </div>
          <div>
            <label class="label" for="ALLADATA">Alla Data</label>
            <input type="text" name="ALLADATA" id="ALLADATA" class="input" placeholder="aaaa-mm-gg" value="<?php echo $allaData; ?>">
          </div>
          <div style="display: flex; align-items: end;">
            <input type="hidden" name="WHERE" value="1">
            <button type="submit" class="btn btn-primary w-full">
              <i class="fas fa-search"></i>
              Applica Filtro
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Current Filter Badge -->
    <div class="badge badge-primary mb-6" style="padding: var(--space-3) var(--space-5); font-size: var(--text-sm);">
      <i class="fas fa-filter"></i>
      <span>Periodo: <?php echo $dallaData; ?> → <?php echo $allaData; ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-auto gap-6 mb-8">
      <!-- Total Transactions -->
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
          <i class="fas fa-receipt" style="color: white; font-size: 1.5rem;"></i>
        </div>
        <div style="
          font-size: var(--text-3xl);
          font-weight: var(--font-extrabold);
          color: var(--primary-600);
          margin-bottom: var(--space-2);
        " x-text="numTransactions.toLocaleString('it-IT')">0</div>
        <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
          Totale Transazioni
        </div>
      </div>

      <!-- Total Amount -->
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
          <i class="fas fa-euro-sign" style="color: white; font-size: 1.5rem;"></i>
        </div>
        <div style="
          font-size: var(--text-3xl);
          font-weight: var(--font-extrabold);
          color: var(--success-600);
          margin-bottom: var(--space-2);
        " x-text="formatCurrency(totale)">€0</div>
        <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
          Importo Totale
        </div>
      </div>

      <!-- International -->
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
          <i class="fas fa-globe" style="color: white; font-size: 1.5rem;"></i>
        </div>
        <div style="
          font-size: var(--text-3xl);
          font-weight: var(--font-extrabold);
          color: #f59e0b;
          margin-bottom: var(--space-2);
        " x-text="formatCurrency(internazionale)">€0</div>
        <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
          Internazionale
        </div>
      </div>

      <!-- eCommerce -->
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
          <i class="fas fa-shopping-cart" style="color: white; font-size: 1.5rem;"></i>
        </div>
        <div style="
          font-size: var(--text-3xl);
          font-weight: var(--font-extrabold);
          color: #f97316;
          margin-bottom: var(--space-2);
        " x-text="formatCurrency(internazionaleComm)">€0</div>
        <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
          eCommerce
        </div>
      </div>
    </div>

    <!-- Charts Grid - 2 columns for compact layout -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <!-- Distribution Pie -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-chart-pie" style="color: var(--primary-500); font-size: 14px;"></i>
          Distribuzione Tipo
        </h3>
        <div id="distributionChart"></div>
      </div>

      <!-- PV vs SDT -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-store-alt" style="color: var(--warning-500); font-size: 14px;"></i>
          PV vs SDT
        </h3>
        <div id="pvSdtChart"></div>
      </div>
    </div>

    <!-- Transactions and Approval Rate -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <!-- Transactions Bar -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-chart-bar" style="color: var(--primary-500); font-size: 14px;"></i>
          Transazioni Ultimi 7 Giorni
        </h3>
        <div id="transactionsChart"></div>
      </div>

      <!-- Average Amount -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-euro-sign" style="color: var(--success-500); font-size: 14px;"></i>
          Importe Medio & Totales
        </h3>
        <div id="avgAmountChart"></div>
      </div>
    </div>

    <!-- Card Schemes Compact -->
    <div class="glass-card mb-6" style="padding: var(--space-5);">
      <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
        <i class="fas fa-credit-card" style="color: var(--primary-500); font-size: 14px;"></i>
        Circuiti Carte
      </h3>
      <div id="schemesChart"></div>
    </div>

    <!-- Top Rankings - 3 columns -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <!-- Top Acquirer -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-building" style="color: var(--primary-500); font-size: 14px;"></i>
          Top 5 Acquirer
        </h3>
        <div id="acquirerChart"></div>
      </div>

      <!-- Top Stores -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-store" style="color: var(--success-500); font-size: 14px;"></i>
          Top 5 Negozi
        </h3>
        <div id="storesChart"></div>
      </div>

      <!-- Top Cities -->
      <div class="glass-card" style="padding: var(--space-5);">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-3); font-size: var(--text-base);">
          <i class="fas fa-map-marker-alt" style="color: var(--warning-500); font-size: 14px;"></i>
          Top 5 Città
        </h3>
        <div id="citiesChart"></div>
      </div>
    </div>

    </div> <!-- End x-show="!loading" -->

</div>

<style>
@keyframes progress-indeterminate {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.spinner {
  border: 5px solid rgba(102, 126, 234, 0.2);
  border-radius: 50%;
  border-top: 5px solid var(--primary-600);
  width: 60px;
  height: 60px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<script>
$(function() {
    $("#DALLADATA, #ALLADATA").datepicker({
        dateFormat: 'yy-mm-dd',
        changeYear: true,
        changeMonth: true
    });
});

function dashboard() {
    return {
        loading: true,
        numTransactions: 0,
        totale: 0,
        internazionale: 0,
        internazionaleComm: 0,
        pagoBancomat: 0,
        charts: {},

        formatCurrency(value) {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        },

        async init() {
            try {
                this.loading = true;
                await this.loadTotals();
                await this.loadTransactionsChart();
                await this.loadSchemesChart();
                await this.loadPvSdtChart();
                await this.loadAvgAmountChart();
                await this.loadTopCharts();
            } catch (err) {
                console.error('Error cargando dashboard:', err);
                alert('Error al cargar el panel de control. Inténtalo de nuevo más tarde.');
            } finally {
                this.loading = false;
            }
        },

        async loadTotals() {
            const response = await fetch("totals-generale-es.php?WHERE=<?php echo urlencode($query); ?>");
            const data = await response.json();

            this.internazionale = parseFloat(data.internazionale);
            this.pagoBancomat = parseFloat(data.pagobancomat);
            this.internazionaleComm = parseFloat(data.internazionalecomm);
            this.numTransactions = parseInt(data.numtransactions);
            this.totale = parseFloat(data.totale);

            this.renderDistributionChart();
        },

        renderDistributionChart() {
            // Destroy existing chart if it exists
            if (this.charts.distribution) {
                this.charts.distribution.destroy();
            }

            const options = {
                series: [this.internazionale, this.internazionaleComm, this.pagoBancomat],
                chart: {
                    type: 'donut',
                    height: 240,
                    fontFamily: 'Inter, sans-serif',
                    animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    background: '#ffffff'
                },
                labels: ['Internazionale', 'eCommerce', 'PagoBancomat'],
                colors: ['#ff9f40', '#50d515', '#36a2eb'],
                legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
                dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%', style: { fontSize: '10px' } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Totale',
                                    fontSize: '14px',
                                    fontWeight: 700,
                                    formatter: () => this.formatCurrency(this.totale)
                                }
                            }
                        }
                    }
                },
                tooltip: { y: { formatter: (val) => this.formatCurrency(val) } }
            };
            this.charts.distribution = new ApexCharts(document.querySelector("#distributionChart"), options);
            this.charts.distribution.render();
        },

        async loadTransactionsChart() {
            // Destroy existing chart if it exists
            if (this.charts.transactions) {
                this.charts.transactions.destroy();
            }

            const response = await fetch("bar-transazioni-es.php");
            const data = await response.json();

            const dates = data.map(item => item.dataOperazione);
            const counts = data.map(item => parseInt(item.count));

            const options = {
                series: [{ name: 'Transazioni', data: counts }],
                chart: {
                    type: 'bar',
                    height: 280,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    background: '#ffffff'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        dataLabels: { position: 'top' },
                        colors: { ranges: [{ from: 0, to: 100000, color: '#667eea' }] }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -16,
                    style: { fontSize: '10px', fontWeight: 700, colors: ['#667eea'] }
                },
                xaxis: {
                    categories: dates,
                    labels: { style: { fontSize: '10px', fontWeight: 600 } }
                },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        shadeIntensity: 0.5,
                        gradientToColors: ['#764ba2'],
                        inverseColors: false,
                        opacityFrom: 1,
                        opacityTo: 0.9,
                        stops: [0, 100]
                    }
                },
                grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
                tooltip: { y: { formatter: (val) => val.toLocaleString('it-IT') + ' trx' } }
            };

            this.charts.transactions = new ApexCharts(document.querySelector("#transactionsChart"), options);
            this.charts.transactions.render();
        },

        async loadSchemesChart() {
            // Destroy existing chart if it exists
            if (this.charts.schemes) {
                this.charts.schemes.destroy();
            }

            const response = await fetch("pie-schemas-es.php?WHERE=<?php echo urlencode($query); ?>");
            const data = await response.json();

            const labels = data.map(item => item.tag4f);
            const values = data.map(item => parseInt(item.count));
            const colors = data.map(item => item.colore);

            const options = {
                series: values,
                chart: {
                    type: 'donut',
                    height: 300,
                    fontFamily: 'Inter, sans-serif',
                    animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    background: '#ffffff'
                },
                labels: labels,
                colors: colors,
                legend: { position: 'right', fontSize: '11px', fontWeight: 600, offsetY: 0, height: 300 },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => val.toFixed(1) + '%',
                    style: { fontSize: '10px', fontWeight: 600 },
                    dropShadow: { enabled: false }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Tot. Carte',
                                    fontSize: '14px',
                                    fontWeight: 700,
                                    formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('it-IT')
                                }
                            }
                        }
                    }
                },
                responsive: [{ breakpoint: 768, options: { legend: { position: 'bottom' } } }]
            };

            this.charts.schemes = new ApexCharts(document.querySelector("#schemesChart"), options);
            this.charts.schemes.render();
        },


        async loadPvSdtChart() {
            // Destroy existing chart if it exists
            if (this.charts.pvSdt) {
                this.charts.pvSdt.destroy();
            }

            const response = await fetch("scripts/stores__server.php?where=country='ES'");
            const json = await response.json();

            // Count from stores table: row[6] = prov (PV/SDT)
            const pvCount = json.data.filter(row => row[6] === 'PV').length;
            const sdtCount = json.data.filter(row => row[6] === 'SDT').length;
            const otherCount = json.data.length - pvCount - sdtCount;

            const options = {
                series: [pvCount, sdtCount, otherCount].filter(v => v > 0),
                chart: { type: 'donut', height: 240, fontFamily: 'Inter, sans-serif', background: '#ffffff' },
                labels: [pvCount > 0 ? 'PV - Puntos Venta' : null, sdtCount > 0 ? 'SDT - Transportes' : null, otherCount > 0 ? 'Otro' : null].filter(l => l),
                colors: ['#667eea', '#10b981', '#94a3b8'],
                legend: { position: 'bottom', fontSize: '11px', fontWeight: 600 },
                dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%', style: { fontSize: '10px' } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Tiendas',
                                    fontSize: '14px',
                                    fontWeight: 700,
                                    formatter: () => json.data.length.toLocaleString('it-IT')
                                }
                            }
                        }
                    }
                }
            };

            this.charts.pvSdt = new ApexCharts(document.querySelector("#pvSdtChart"), options);
            this.charts.pvSdt.render();
        },

        async loadAvgAmountChart() {
            const response = await fetch("scripts/tracciato_vista_server_es.php?where=<?php echo urlencode($query); ?>");
            const json = await response.json();

            // Calculate stats
            const total = json.data.length;
            const amounts = json.data.map(row => parseFloat(row[8] || 0));
            const totalAmount = amounts.reduce((sum, amt) => sum + amt, 0);
            const avgAmount = total > 0 ? totalAmount / total : 0;
            const maxAmount = amounts.length > 0 ? Math.max(...amounts) : 0;

            // Create custom HTML for stats display
            document.querySelector("#avgAmountChart").innerHTML = `
                <div style="display: grid; gap: 16px;">
                    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px;">
                        <div style="color: rgba(255,255,255,0.9); font-size: 12px; font-weight: 600; margin-bottom: 8px;">IMPORTE MEDIO</div>
                        <div style="color: white; font-size: 28px; font-weight: 800;">${this.formatCurrency(avgAmount)}</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div style="text-align: center; padding: 16px; background: var(--gray-50); border-radius: 8px;">
                            <div style="color: var(--text-secondary); font-size: 11px; font-weight: 600; margin-bottom: 4px;">TOTAL TRX</div>
                            <div style="color: var(--primary-600); font-size: 20px; font-weight: 700;">${total.toLocaleString('it-IT')}</div>
                        </div>
                        <div style="text-align: center; padding: 16px; background: var(--gray-50); border-radius: 8px;">
                            <div style="color: var(--text-secondary); font-size: 11px; font-weight: 600; margin-bottom: 4px;">IMPORTE MAX</div>
                            <div style="color: var(--danger-500); font-size: 20px; font-weight: 700;">${this.formatCurrency(maxAmount)}</div>
                        </div>
                    </div>
                </div>
            `;
        },

        async loadTopCharts() {
            const response = await fetch("scripts/tracciato_vista_server_es.php?where=<?php echo urlencode($query); ?>");
            const json = await response.json();

            // Top Acquirer
            const acquirerMap = {};
            json.data.forEach(row => {
                const acq = row[10] || 'N/A';
                acquirerMap[acq] = (acquirerMap[acq] || 0) + 1;
            });
            const topAcquirers = Object.entries(acquirerMap)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 5);

            const acquirerOptions = {
                series: [{ name: 'Transacciones', data: topAcquirers.map(a => a[1]) }],
                chart: { type: 'bar', height: 240, fontFamily: 'Inter, sans-serif', toolbar: { show: false }, background: '#ffffff' },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
                dataLabels: { enabled: true, offsetX: 30, style: { fontSize: '10px', fontWeight: 700, colors: ['#667eea'] } },
                xaxis: { categories: topAcquirers.map(a => a[0]), labels: { style: { fontSize: '10px' } } },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                colors: ['#667eea'],
                grid: { borderColor: '#e2e8f0', strokeDashArray: 4 }
            };
            new ApexCharts(document.querySelector("#acquirerChart"), acquirerOptions).render();

            // Top Stores - Row 13 = insegna in ES VIEW
            const storeMap = {};
            json.data.forEach(row => {
                const store = row[13] || 'N/A'; // insegna in ES VIEW
                storeMap[store] = (storeMap[store] || 0) + 1;
            });
            const topStores = Object.entries(storeMap)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 5);

            const storeOptions = {
                series: [{ name: 'Tienda', data: topStores.map(s => s[1]) }],
                chart: { type: 'bar', height: 240, fontFamily: 'Inter, sans-serif', toolbar: { show: false }, background: '#ffffff' },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
                dataLabels: { enabled: true, offsetX: 30, style: { fontSize: '10px', fontWeight: 700, colors: ['#10b981'] } },
                xaxis: { categories: topStores.map(s => s[0]), labels: { style: { fontSize: '10px' } } },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                colors: ['#10b981'],
                grid: { borderColor: '#e2e8f0', strokeDashArray: 4 }
            };
            new ApexCharts(document.querySelector("#storesChart"), storeOptions).render();

            // Top Cities - Row 16 = localita in ES VIEW
            const cityMap = {};
            json.data.forEach(row => {
                const city = row[16] || 'N/A'; // localita in ES VIEW
                cityMap[city] = (cityMap[city] || 0) + 1;
            });
            const topCities = Object.entries(cityMap)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 5);

            const cityOptions = {
                series: [{ name: 'Ciudad', data: topCities.map(c => c[1]) }],
                chart: { type: 'bar', height: 240, fontFamily: 'Inter, sans-serif', toolbar: { show: false }, background: '#ffffff' },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
                dataLabels: { enabled: true, offsetX: 30, style: { fontSize: '10px', fontWeight: 700, colors: ['#f59e0b'] } },
                xaxis: { categories: topCities.map(c => c[0]), labels: { style: { fontSize: '10px' } } },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                colors: ['#f59e0b'],
                grid: { borderColor: '#e2e8f0', strokeDashArray: 4 }
            };
            new ApexCharts(document.querySelector("#citiesChart"), cityOptions).render();
        }
    }
}
</script>

<?php require_once('footer-v3.php'); ?>
