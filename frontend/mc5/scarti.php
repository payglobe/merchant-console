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

// Build query - ONLY rifiutate (exclude approved and stornate) AND exclude e-Commerce
if (!isset($_POST['WHERE'])){
    $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $query = "dataOperazione >= '$date' AND tipoOperazione <> 'e-Commerce' AND flagLog <> '00' AND flagLog <> 'D' AND flagLog <> 'Stornata'";
    $dallaData = $date;
    $allaData = $date;
} else {
    $dallaData = $_POST['DALLADATA'];
    $allaData = $_POST['ALLADATA'];
    $query = "dataOperazione >= '$dallaData' AND dataOperazione <= '$allaData' AND tipoOperazione <> 'e-Commerce' AND flagLog <> '00' AND flagLog <> 'D' AND flagLog <> 'Stornata'";
}
?>

<style>
/* Stripe Table */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: var(--gray-50);
  position: sticky;
  top: 0;
  z-index: 10;
}

.data-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--gray-600);
  border-bottom: 1px solid var(--border-primary);
}

.data-table tbody tr {
  border-bottom: 1px solid var(--border-secondary);
  transition: background 0.15s;
}

.data-table tbody tr:hover {
  background: var(--gray-50);
}

.data-table td {
  padding: 14px 16px;
  font-size: 14px;
  color: var(--gray-900);
}

.mono {
  font-family: var(--font-mono);
  font-size: 13px;
}

.amount {
  font-family: var(--font-mono);
  font-weight: 600;
  color: var(--primary-600);
}

/* Row states */
.row-selected {
  background: rgba(99, 91, 255, 0.05) !important;
  border-left: 3px solid var(--primary-600) !important;
}

.row-rejected {
  background: rgba(223, 27, 65, 0.03) !important;
  border-left: 3px solid var(--danger-500) !important;
}

.row-rejected:hover {
  background: rgba(223, 27, 65, 0.08) !important;
}
</style>

<div x-data="app()" x-init="loadData()">

  <!-- Global Loading Spinner -->
  <div x-show="loading" style="
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
  ">
    <div style="
      background: white;
      border-radius: var(--radius-2xl);
      padding: var(--space-10) var(--space-8);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      text-align: center;
      min-width: 320px;
      border: 1px solid var(--border-primary);
    ">
      <svg style="width: 80px; height: 80px; animation: spin 1s linear infinite; margin: 0 auto 24px;" viewBox="0 0 24 24" fill="none">
        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="var(--primary-600)" stroke-width="4"></circle>
        <path style="opacity: 0.75;" fill="var(--primary-600)" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <div style="font-size: var(--text-xl); font-weight: var(--font-semibold); color: var(--primary-600); margin-bottom: 8px;">
        Caricamento dati...
      </div>
      <div style="font-size: var(--text-sm); color: var(--text-secondary);">
        Attendere prego
      </div>
    </div>
  </div>

  <style>
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>

  <!-- Header -->
  <div class="card mb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 style="font-size: 28px; font-weight: 700; color: var(--gray-900); margin: 0 0 8px 0;">
          Transazioni Rifiutate
        </h1>
        <p style="color: var(--text-secondary); margin: 0;">
          Solo transazioni rifiutate (no e-Commerce)
        </p>
      </div>
      <button @click="showFilters = !showFilters" class="btn btn-outline">
        <i class="fas" :class="showFilters ? 'fa-eye-slash' : 'fa-filter'"></i>
        <span x-text="showFilters ? 'Nascondi' : 'Filtri'"></span>
      </button>
    </div>

    <!-- Country Tabs -->
    <div class="flex gap-2">
      <a href="scarti.php" class="btn btn-primary flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> Italia
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div x-show="showFilters" x-transition class="card mb-6">
    <form method="POST" x-data="{ loading: false }" @submit="loading = true">
      <div class="grid grid-cols-auto gap-4">
        <div>
          <label class="label">Dalla Data</label>
          <input type="date" name="DALLADATA" class="input" value="<?php echo $dallaData; ?>" required />
        </div>
        <div>
          <label class="label">Alla Data</label>
          <input type="date" name="ALLADATA" class="input" value="<?php echo $allaData; ?>" required />
        </div>
        <div style="display: flex; align-items: end;">
          <input type="hidden" name="WHERE" value="1">
          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            <template x-if="!loading">
              <span>Cerca</span>
            </template>
            <template x-if="loading">
              <div style="display: flex; align-items: center; gap: 8px;">
                <svg style="width: 16px; height: 16px; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none">
                  <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Caricamento...</span>
              </div>
            </template>
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Stats -->
  <div class="card mb-6">
    <div class="grid grid-cols-2 gap-6">
      <!-- Transazioni Rifiutate -->
      <div style="text-align: center; padding: var(--space-4);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(223, 27, 65, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-times-circle" style="font-size: 1.5rem; color: var(--danger-500);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.total"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Transazioni Rifiutate</div>
      </div>

      <!-- Importo Totale -->
      <div style="text-align: center; padding: var(--space-4); border-left: 1px solid var(--border-secondary);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-euro-sign" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="formatCurrency(stats.amount)"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Importo Totale Rifiutato</div>
      </div>
    </div>
  </div>

  <!-- Search & Table -->
  <div class="card">
    <div style="padding: var(--space-6); border-bottom: 1px solid var(--border-secondary);">
      <input type="text"
             class="input"
             placeholder="Cerca..."
             x-model="search"
             @input="filterData()">
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Terminal</th>
            <th>Data/Ora</th>
            <th>Importo</th>
            <th>PAN</th>
            <th>Flag Log</th>
            <th>Actin Code</th>
            <th>Negozio</th>
            <th>Città</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="row in paginatedData" :key="row.id">
            <tr class="row-rejected" @click="selectedRow = row" :class="selectedRow?.id === row.id ? 'row-selected' : ''">
              <td><span class="mono" style="font-weight: 600;" x-text="row.terminalID"></span></td>
              <td>
                <div x-text="row.dataOperazione" style="font-weight: 600;"></div>
                <div x-text="row.oraOperazione" style="font-size: 12px; color: var(--text-secondary);"></div>
              </td>
              <td><span class="amount" style="font-size: 16px; font-weight: 700; color: var(--danger-500);" x-text="formatCurrency(row.importo)"></span></td>
              <td><span class="mono" x-text="row.pan"></span></td>
              <td><span style="font-weight: 700; color: var(--danger-500);" x-text="row.flagLog"></span></td>
              <td><span style="font-weight: 700; color: var(--danger-500);" x-text="row.actinCode"></span></td>
              <td x-text="row.insegna"></td>
              <td x-text="row.localita"></td>
            </tr>
          </template>
          <tr x-show="filteredData.length === 0">
            <td colspan="8" style="text-align: center; padding: var(--space-8); color: var(--text-secondary);">
              Nessuna transazione rifiutata trovata
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div style="padding: var(--space-6); border-top: 1px solid var(--border-secondary); display: flex; align-items: center; justify-content: space-between;">
      <div style="color: var(--text-secondary); font-size: var(--text-sm);">
        Mostrando <span x-text="startIdx + 1"></span> - <span x-text="Math.min(endIdx, filteredData.length)"></span> di <span x-text="filteredData.length"></span> transazioni
      </div>

      <div style="display: flex; gap: var(--space-2);">
        <button @click="prevPage()" :disabled="page === 1" class="btn btn-outline" style="padding: var(--space-2) var(--space-3);">
          <i class="fas fa-chevron-left"></i>
        </button>

        <template x-for="p in pages" :key="p">
          <button @click="page = p"
                  :class="page === p ? 'btn-primary' : 'btn-outline'"
                  class="btn"
                  style="padding: var(--space-2) var(--space-3); min-width: 40px;"
                  x-text="p"></button>
        </template>

        <button @click="nextPage()" :disabled="page === totalPages" class="btn btn-outline" style="padding: var(--space-2) var(--space-3);">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function app() {
  return {
    showFilters: true,
    loading: true,
    data: [],
    filteredData: [],
    search: '',
    selectedRow: null,
    page: 1,
    perPage: 25,

    stats: {
      total: 0,
      amount: 0
    },

    async loadData() {
      this.loading = true;
      try {
        const res = await fetch('scripts/scarti_array.php?where=<?php echo urlencode($query); ?>');
        const json = await res.json();
        console.log('Rejected transactions data:', json);

        // Convert DataTables array format to objects - scarti table mapping
        this.data = (json.data || []).map((row, idx) => {
          return {
            id: idx,
            codificaStab: row[0] || '',
            terminalID: row[1] || '',
            tipoRiep: row[2] || '',
            domestico: row[3] || '',
            pan: row[4] || '',
            tag4f: row[5] || '',
            dataOperazione: row[6] || '',
            oraOperazione: row[7] || '',
            importo: parseFloat(row[8]) || 0,
            codiceAutorizzativo: row[9] || '',
            acquirer: row[10] || '',
            flagLog: row[11] || '',
            actinCode: row[12] || '',
            insegna: row[13] || '',
            indirizzo: row[14] || '',
            localita: row[15] || '',
            cap: row[16] || ''
          };
        });

        // Sort by date and time (most recent first)
        this.data.sort((a, b) => {
          const dateTimeA = `${a.dataOperazione} ${a.oraOperazione}`;
          const dateTimeB = `${b.dataOperazione} ${b.oraOperazione}`;
          return dateTimeB.localeCompare(dateTimeA);
        });

        this.filteredData = this.data;
        this.calculateStats();
      } catch (err) {
        console.error('Errore caricamento dati:', err);
      } finally {
        this.loading = false;
      }
    },

    calculateStats() {
      this.stats.total = this.data.length;
      this.stats.amount = this.data.reduce((sum, d) => sum + parseFloat(d.importo || 0), 0);
    },

    filterData() {
      const q = this.search.toLowerCase();
      this.filteredData = this.data.filter(d => {
        return !q ||
          (d.terminalID || '').toLowerCase().includes(q) ||
          (d.pan || '').toLowerCase().includes(q) ||
          (d.flagLog || '').toLowerCase().includes(q) ||
          (d.actinCode || '').toLowerCase().includes(q) ||
          (d.insegna || '').toLowerCase().includes(q) ||
          (d.localita || '').toLowerCase().includes(q);
      });
      this.page = 1;
    },

    get totalPages() {
      return Math.ceil(this.filteredData.length / this.perPage);
    },

    get startIdx() {
      return (this.page - 1) * this.perPage;
    },

    get endIdx() {
      return this.page * this.perPage;
    },

    get paginatedData() {
      return this.filteredData.slice(this.startIdx, this.endIdx);
    },

    get pages() {
      const arr = [];
      const start = Math.max(1, this.page - 2);
      const end = Math.min(this.totalPages, this.page + 2);
      for (let i = start; i <= end; i++) arr.push(i);
      return arr;
    },

    prevPage() {
      if (this.page > 1) this.page--;
    },

    nextPage() {
      if (this.page < this.totalPages) this.page++;
    },

    formatCurrency(val) {
      return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
      }).format(val);
    }
  };
}
</script>

<?php require_once('footer-v3.php'); ?>
