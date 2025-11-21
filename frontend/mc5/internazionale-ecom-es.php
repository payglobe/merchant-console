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

// Build query - tracciato_pos_es VIEW doesn't have tipoOperazione column
// We filter for Internazionale only (e-Commerce is implicit for Spain data)
if (!isset($_POST['WHERE'])){
    $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $query = "dataOperazione >= '$date' AND domestico = 'Internazionale'";
    $dallaData = $date;
    $allaData = $date;
} else {
    $dallaData = $_POST['DALLADATA'];
    $allaData = $_POST['ALLADATA'];
    $query = "dataOperazione >= '$dallaData' AND dataOperazione <= '$allaData' AND domestico = 'Internazionale'";
}
?>

<style>
/* Modern Table */
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
  cursor: pointer;
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

.row-approved {
  background: rgba(0, 217, 36, 0.03) !important;
  border-left: 3px solid var(--success-500) !important;
}

.row-approved:hover {
  background: rgba(0, 217, 36, 0.08) !important;
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
        <path style="opacity: 0.75;" fill="var(--primary-600)" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
          e-Commerce Internacional
        </h1>
        <p style="color: var(--text-secondary); margin: 0;">
          Transacciones TPV virtual internacional
        </p>
      </div>
      <div style="display: flex; gap: var(--space-2);">
        <button @click="exportToExcel()" class="btn btn-primary">
          <i class="fas fa-file-excel"></i>
          <span>Exportar Excel</span>
        </button>
        <button @click="showFilters = !showFilters" class="btn btn-outline">
          <i class="fas" :class="showFilters ? 'fa-eye-slash' : 'fa-filter'"></i>
          <span x-text="showFilters ? 'Ocultar' : 'Filtros'"></span>
        </button>
      </div>
    </div>

    <!-- Country Tabs -->
    <div class="flex gap-2">
      <a href="internazionale-ecom-4.php" class="btn btn-outline flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> Italia
      </a>
      <a href="internazionale-ecom-es.php" class="btn btn-primary flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> España
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
        <div style="display: flex; align-items: end; gap: var(--space-2);">
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
          <button type="button" @click="exportExcel()" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Excel
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Stats -->
  <div class="card mb-6">
    <div class="grid grid-cols-4 gap-6">
      <!-- Totale -->
      <div style="text-align: center; padding: var(--space-4);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.total"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Totale Transazioni</div>
        <div style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--primary-600); margin-top: var(--space-2);" x-text="formatCurrency(stats.amount)"></div>
      </div>

      <!-- e-Commerce -->
      <div style="text-align: center; padding: var(--space-4); border-left: 1px solid var(--border-secondary);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(0, 217, 36, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-store" style="font-size: 1.5rem; color: var(--success-500);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.ecommerce"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">e-Commerce</div>
        <div style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--success-500); margin-top: var(--space-2);" x-text="formatCurrency(stats.ecommerceAmount)"></div>
      </div>

      <!-- A2P -->
      <div style="text-align: center; padding: var(--space-4); border-left: 1px solid var(--border-secondary);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(79, 172, 254, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-university" style="font-size: 1.5rem; color: #4facfe;"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.a2p"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Bonifico (A2P)</div>
        <div style="font-size: var(--text-lg); font-weight: var(--font-bold); color: #4facfe; margin-top: var(--space-2);" x-text="formatCurrency(stats.a2pAmount)"></div>
      </div>

      <!-- Filter Buttons -->
      <div style="display: flex; align-items: center; justify-content: center; border-left: 1px solid var(--border-secondary);">
        <div style="display: flex; flex-direction: column; gap: var(--space-2); width: 100%; padding: var(--space-2);">
          <button @click="typeFilter = 'all'; filterData()" :class="typeFilter === 'all' ? 'btn-primary' : 'btn-outline'" class="btn" style="font-size: 12px; padding: 8px;">
            <i class="fas fa-list"></i> Tutti
          </button>
          <button @click="typeFilter = 'ecommerce'; filterData()" :class="typeFilter === 'ecommerce' ? 'btn-primary' : 'btn-outline'" class="btn" style="font-size: 12px; padding: 8px;">
            <i class="fas fa-store"></i> e-Commerce
          </button>
          <button @click="typeFilter = 'a2p'; filterData()" :class="typeFilter === 'a2p' ? 'btn-primary' : 'btn-outline'" class="btn" style="font-size: 12px; padding: 8px;">
            <i class="fas fa-university"></i> A2P
          </button>
        </div>
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
            <th>Tipo</th>
            <th>Terminal</th>
            <th>Data/Ora</th>
            <th>Importo</th>
            <th>PAN</th>
            <th>Circuito</th>
            <th>Codice Aut.</th>
            <th>Negozio</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="row in paginatedData" :key="row.id">
            <tr :class="row.approved ? 'row-approved' : ''" @click="selectedRow = row" :class="selectedRow?.id === row.id ? 'row-selected' : ''">
              <td>
                <span style="padding: 4px 8px; border-radius: 8px; font-size: 10px; font-weight: 700; text-transform: uppercase;"
                      :style="{
                        background: row.paymentType === 'ecommerce' ? 'rgba(0, 217, 36, 0.1)' : 'rgba(79, 172, 254, 0.1)',
                        color: row.paymentType === 'ecommerce' ? 'var(--success-500)' : '#4facfe'
                      }"
                      x-text="row.paymentType === 'ecommerce' ? 'eShop' : 'A2P'">
                </span>
              </td>
              <td><span class="mono" style="font-weight: 600;" x-text="row.terminalID"></span></td>
              <td>
                <div x-text="row.dataOperazione" style="font-weight: 600;"></div>
                <div x-text="row.oraOperazione" style="font-size: 12px; color: var(--text-secondary);"></div>
              </td>
              <td><span class="amount" style="font-size: 16px; font-weight: 700;" x-text="formatCurrency(row.importo)"></span></td>
              <td><span class="mono" x-text="row.pan"></span></td>
              <td><span style="font-weight: 600;" x-text="row.tag4f"></span></td>
              <td><span class="mono" x-text="row.codiceAutorizzativo"></span></td>
              <td x-text="row.insegna"></td>
            </tr>
          </template>
          <tr x-show="filteredData.length === 0">
            <td colspan="8" style="text-align: center; padding: var(--space-8); color: var(--text-secondary);">
              Nessuna transazione e-Commerce internazionale trovata
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
    typeFilter: 'all',

    stats: {
      total: 0,
      amount: 0,
      ecommerce: 0,
      ecommerceAmount: 0,
      a2p: 0,
      a2pAmount: 0
    },

    async loadData() {
      this.loading = true;
      try {
        const res = await fetch('scripts/tracciato_vista_server_es.php?where=<?php echo urlencode($query); ?>');
        const json = await res.json();
        console.log('e-Commerce internazionale data:', json);

        // Convert DataTables array format to objects - tracciato_pos VIEW mapping
        this.data = (json.data || []).map((row, idx) => {
          const approved = (row[11] === '00' && row[12] === '00') || row[11] === 'D';
          const codificaStab = row[0] || '';

          // Classify payment type based on codificaStab
          let paymentType = 'ecommerce';
          if (codificaStab === 'MC_IRIS_A2P') {
            paymentType = 'a2p';
          }

          return {
            id: idx,
            codificaStab: codificaStab,
            terminalID: row[1] || '',
            modelloPos: row[2] || '',
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
            tipoOperazione: 'e-Commerce',  // Not in ES VIEW, set manually
            insegna: row[13] || '',
            ragioneSociale: row[14] || '',  // Ragione_Sociale in ES
            indirizzo: row[15] || '',
            localita: row[16] || '',
            prov: row[17] || '',
            cap: row[18] || '',
            approved: approved,
            paymentType: paymentType
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

      // e-Commerce
      const ecommerceData = this.data.filter(d => d.paymentType === 'ecommerce');
      this.stats.ecommerce = ecommerceData.length;
      this.stats.ecommerceAmount = ecommerceData.reduce((sum, d) => sum + parseFloat(d.importo || 0), 0);

      // A2P (Bonifico)
      const a2pData = this.data.filter(d => d.paymentType === 'a2p');
      this.stats.a2p = a2pData.length;
      this.stats.a2pAmount = a2pData.reduce((sum, d) => sum + parseFloat(d.importo || 0), 0);
    },

    filterData() {
      const q = this.search.toLowerCase();
      this.filteredData = this.data.filter(d => {
        // Filter by payment type
        const typeMatch = this.typeFilter === 'all' || d.paymentType === this.typeFilter;

        // Filter by search query
        const searchMatch = !q ||
          (d.terminalID || '').toLowerCase().includes(q) ||
          (d.pan || '').toLowerCase().includes(q) ||
          (d.tag4f || '').toLowerCase().includes(q) ||
          (d.codiceAutorizzativo || '').toLowerCase().includes(q) ||
          (d.insegna || '').toLowerCase().includes(q) ||
          (d.localita || '').toLowerCase().includes(q) ||
          (d.codificaStab || '').toLowerCase().includes(q);

        return typeMatch && searchMatch;
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
    },

    exportToExcel() {
      // Prepare data for export with all available fields
      const exportData = this.filteredData.map(row => ({
        'Tipo': row.paymentType === 'ecommerce' ? 'e-Commerce' : 'Bonifico A2P',
        'Codifica Stab': row.codificaStab,
        'Terminal ID': row.terminalID,
        'Modello POS': row.modelloPos,
        'Domestico': row.domestico,
        'PAN': row.pan,
        'Circuito (Tag 4F)': row.tag4f,
        'Data Operazione': row.dataOperazione,
        'Ora Operazione': row.oraOperazione,
        'Importo (EUR)': row.importo,
        'Codice Autorizzativo': row.codiceAutorizzativo,
        'Acquirer': row.acquirer,
        'Flag Log': row.flagLog,
        'Actin Code': row.actinCode,
        'Tipo Operazione': row.tipoOperazione,
        'Insegna': row.insegna,
        'Ragione Sociale': row.ragioneSociale,
        'Indirizzo': row.indirizzo,
        'Località': row.localita,
        'Provincia': row.prov,
        'CAP': row.cap,
        'Stato': row.approved ? 'Approvata' : 'Rifiutata'
      }));

      // Create CSV content
      const headers = Object.keys(exportData[0] || {});
      const csvContent = [
        headers.join(','),
        ...exportData.map(row =>
          headers.map(header => {
            const value = row[header] || '';
            // Escape commas and quotes in CSV
            return typeof value === 'string' && (value.includes(',') || value.includes('"'))
              ? `"${value.replace(/"/g, '""')}"`
              : value;
          }).join(',')
        )
      ].join('\n');

      // Create download link
      const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);

      const filename = `ecommerce_internazionale_${new Date().toISOString().split('T')[0]}.csv`;
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

    exportExcel() {
      window.location.href = 'scripts/export_excel.php?table=tracciato_pos_es&where=<?php echo urlencode($query); ?>';
    }
  };
}
</script>

<?php require_once('footer-v3.php'); ?>
