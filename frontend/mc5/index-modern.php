<?php
require_once 'authentication.php';
require_once('menu.php');

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

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- jQuery UI for datepicker -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
/* Dashboard Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.stat-card .icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 1.5rem;
}

.stat-card .value {
    font-size: 2rem;
    font-weight: 800;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
}

.stat-card .label {
    color: #718096;
    font-size: 0.9rem;
    font-weight: 600;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.chart-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.chart-card h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.chart-card h3 i {
    color: var(--primary-blue);
}

/* Date Filter */
.date-filter {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.date-filter h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-inputs {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: end;
}

.date-input-group {
    flex: 1;
    min-width: 200px;
}

.date-input-group label {
    display: block;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.date-input-group input {
    width: 100%;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    font-family: var(--font-sans);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.date-input-group input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-btn {
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 12px 32px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

.filter-btn i {
    margin-right: 8px;
}

/* Tabs */
.country-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    background: white;
    padding: 8px;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.country-tab {
    flex: 1;
    padding: 12px 24px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    color: #718096;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.country-tab:hover {
    background: rgba(102, 126, 234, 0.05);
    color: var(--primary-blue);
}

.country-tab.active {
    background: var(--primary-gradient);
    color: white;
}

.current-filter {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    margin-bottom: 24px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.current-filter i {
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }

    .date-inputs {
        flex-direction: column;
    }

    .date-input-group {
        width: 100%;
    }
}
</style>

<!-- Alpine.js Dashboard Component -->
<div class="content" x-data="dashboard()" x-init="init()">

    <!-- Country Tabs -->
    <div class="country-tabs">
        <a href="index.php" class="country-tab active">
            <i class="fas fa-flag"></i>
            <span>Italia</span>
        </a>
        <a href="index-es.php" class="country-tab">
            <i class="fas fa-flag"></i>
            <span>Spagna</span>
        </a>
    </div>

    <!-- Date Filter -->
    <div class="date-filter">
        <h3>
            <i class="fas fa-calendar-alt"></i>
            Filtro Periodo
        </h3>
        <form method="POST">
            <div class="date-inputs">
                <div class="date-input-group">
                    <label for="DALLADATA">Dalla Data</label>
                    <input type="text" name="DALLADATA" id="DALLADATA" placeholder="aaaa-mm-gg" value="<?php echo $dallaData; ?>">
                </div>
                <div class="date-input-group">
                    <label for="ALLADATA">Alla Data</label>
                    <input type="text" name="ALLADATA" id="ALLADATA" placeholder="aaaa-mm-gg" value="<?php echo $allaData; ?>">
                </div>
                <input type="hidden" name="WHERE" value="1">
                <button type="submit" class="filter-btn">
                    <i class="fas fa-search"></i>
                    Applica Filtro
                </button>
            </div>
        </form>
    </div>

    <!-- Current Filter Display -->
    <div class="current-filter">
        <i class="fas fa-filter"></i>
        <span>Periodo: <?php echo $dallaData; ?> → <?php echo $allaData; ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-receipt" style="color: white;"></i>
            </div>
            <div class="value" x-text="numTransactions.toLocaleString('it-IT')">0</div>
            <div class="label">Totale Transazioni</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-euro-sign" style="color: white;"></i>
            </div>
            <div class="value" x-text="formatCurrency(totale)">€0</div>
            <div class="label">Importo Totale</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-globe" style="color: white;"></i>
            </div>
            <div class="value" x-text="formatCurrency(internazionale)">€0</div>
            <div class="label">Internazionale</div>
        </div>

        <div class="stat-card">
            <div class="icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <i class="fas fa-shopping-cart" style="color: white;"></i>
            </div>
            <div class="value" x-text="formatCurrency(internazionaleComm)">€0</div>
            <div class="label">eCommerce</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <!-- Distribution Pie Chart -->
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-pie"></i>
                Distribuzione per Tipo
            </h3>
            <div id="distributionChart"></div>
        </div>

        <!-- Transactions Bar Chart -->
        <div class="chart-card">
            <h3>
                <i class="fas fa-chart-bar"></i>
                Transazioni Ultimi 7 Giorni
            </h3>
            <div id="transactionsChart"></div>
        </div>
    </div>

    <!-- Card Schemes Doughnut Chart -->
    <div class="chart-card">
        <h3>
            <i class="fas fa-credit-card"></i>
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

            // Render distribution pie chart
            this.renderDistributionChart();
        },

        renderDistributionChart() {
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

            const chart = new ApexCharts(document.querySelector("#distributionChart"), options);
            chart.render();
        },

        async loadTransactionsChart() {
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
                        distributed: false,
                        dataLabels: {
                            position: 'top'
                        },
                        colors: {
                            ranges: [{
                                from: 0,
                                to: 100000,
                                color: '#667eea'
                            }],
                            backgroundBarColors: ['#f3f4f6'],
                            backgroundBarOpacity: 0.3,
                            backgroundBarRadius: 8
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

            const chart = new ApexCharts(document.querySelector("#transactionsChart"), options);
            chart.render();
        },

        async loadSchemesChart() {
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

            const chart = new ApexCharts(document.querySelector("#schemesChart"), options);
            chart.render();
        }
    }
}
</script>

<?php require_once('footer.php'); ?>
