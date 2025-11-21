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

<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- jQuery UI for datepicker -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Alpine.js Dashboard Component -->
<div x-data="dashboard()" x-init="init()">

    <!-- Country Tabs -->
    <div class="glass-card mb-6" style="padding: var(--space-3);">
      <div class="flex gap-2">
        <a href="index.php" class="btn btn-primary flex-1" style="text-align: center;">
          <i class="fas fa-flag"></i>
          <span>Italia</span>
        </a>
        <a href="index-es.php" class="btn btn-outline flex-1" style="text-align: center;">
          <i class="fas fa-flag"></i>
          <span>Spagna</span>
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
          background: var(--gradient-primary);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
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
          background: var(--gradient-success);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
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
          background: var(--gradient-secondary);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
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
          background: var(--gradient-warning);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          margin-bottom: var(--space-2);
        " x-text="formatCurrency(internazionaleComm)">€0</div>
        <div style="color: var(--text-secondary); font-weight: var(--font-semibold); font-size: var(--text-sm);">
          eCommerce
        </div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-2 gap-6 mb-8">
      <!-- Distribution Pie -->
      <div class="glass-card">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-5);">
          <i class="fas fa-chart-pie" style="color: var(--primary-500);"></i>
          Distribuzione per Tipo
        </h3>
        <div id="distributionChart"></div>
      </div>

      <!-- Transactions Bar -->
      <div class="glass-card">
        <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-5);">
          <i class="fas fa-chart-bar" style="color: var(--primary-500);"></i>
          Transazioni Ultimi 7 Giorni
        </h3>
        <div id="transactionsChart"></div>
      </div>
    </div>

    <!-- Card Schemes Full Width -->
    <div class="glass-card">
      <h3 style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-5);">
        <i class="fas fa-credit-card" style="color: var(--primary-500);"></i>
        Distribuzione Circuiti Carte
      </h3>
      <div id="schemesChart"></div>
    </div>

</div>

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
        numTransactions: 0,
        totale: 0,
        internazionale: 0,
        internazionaleComm: 0,
        pagoBancomat: 0,

        // Chart instances
        distributionChartInstance: null,
        transactionsChartInstance: null,
        schemesChartInstance: null,

        formatCurrency(value) {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        },

        async init() {
            await this.loadTotals();
            await this.loadTransactionsChart();
            await this.loadSchemesChart();
        },

        async loadTotals() {
            const response = await fetch("totals-generale.php?WHERE=<?php echo urlencode($query); ?>");
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
            if (this.distributionChartInstance) {
                this.distributionChartInstance.destroy();
            }

            const options = {
                series: [this.internazionale, this.internazionaleComm, this.pagoBancomat],
                chart: {
                    type: 'donut',
                    height: 350,
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                labels: ['Internazionale', 'eCommerce', 'PagoBancomat'],
                colors: ['#ff9f40', '#50d515', '#36a2eb'],
                legend: {
                    position: 'bottom',
                    fontSize: '14px',
                    fontWeight: 600
                },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => val.toFixed(1) + '%'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Totale',
                                    fontSize: '18px',
                                    fontWeight: 700,
                                    formatter: () => this.formatCurrency(this.totale)
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: (val) => this.formatCurrency(val)
                    }
                }
            };

            this.distributionChartInstance = new ApexCharts(document.querySelector("#distributionChart"), options);
            this.distributionChartInstance.render();
        },

        async loadTransactionsChart() {
            // Destroy existing chart if it exists
            if (this.transactionsChartInstance) {
                this.transactionsChartInstance.destroy();
            }

            const response = await fetch("bar-transazioni.php");
            const data = await response.json();

            const dates = data.map(item => item.dataOperazione);
            const counts = data.map(item => parseInt(item.count));

            const options = {
                series: [{
                    name: 'Transazioni',
                    data: counts
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        dataLabels: {
                            position: 'top'
                        },
                        colors: {
                            ranges: [{
                                from: 0,
                                to: 100000,
                                color: '#667eea'
                            }]
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        fontWeight: 700,
                        colors: ['#667eea']
                    }
                },
                xaxis: {
                    categories: dates,
                    labels: {
                        style: {
                            fontSize: '12px',
                            fontWeight: 600
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Numero Transazioni',
                        style: {
                            fontSize: '14px',
                            fontWeight: 600
                        }
                    }
                },
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
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4
                },
                tooltip: {
                    y: {
                        formatter: (val) => val.toLocaleString('it-IT') + ' transazioni'
                    }
                }
            };

            this.transactionsChartInstance = new ApexCharts(document.querySelector("#transactionsChart"), options);
            this.transactionsChartInstance.render();
        },

        async loadSchemesChart() {
            // Destroy existing chart if it exists
            if (this.schemesChartInstance) {
                this.schemesChartInstance.destroy();
            }

            const response = await fetch("pie-schemas.php?WHERE=<?php echo urlencode($query); ?>");
            const data = await response.json();

            const labels = data.map(item => item.tag4f);
            const values = data.map(item => parseInt(item.count));
            const colors = data.map(item => item.colore);

            const options = {
                series: values,
                chart: {
                    type: 'donut',
                    height: 400,
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                labels: labels,
                colors: colors,
                legend: {
                    position: 'right',
                    fontSize: '14px',
                    fontWeight: 600,
                    offsetY: 0,
                    height: 400
                },
                dataLabels: {
                    enabled: true,
                    formatter: (val, opts) => {
                        return opts.w.globals.labels[opts.seriesIndex] + ': ' + val.toFixed(1) + '%';
                    },
                    style: {
                        fontSize: '12px',
                        fontWeight: 600
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Totale Carte',
                                    fontSize: '18px',
                                    fontWeight: 700,
                                    formatter: (w) => {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('it-IT');
                                    }
                                }
                            }
                        }
                    }
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            this.schemesChartInstance = new ApexCharts(document.querySelector("#schemesChart"), options);
            this.schemesChartInstance.render();
        }
    }
}
</script>

<?php require_once('footer-v3.php'); ?>
