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

/* BIN Badge */
.bin-code {
  font-family: var(--font-mono);
  font-weight: 700;
  font-size: 14px;
  color: var(--primary-600);
  background: rgba(99, 91, 255, 0.1);
  padding: 6px 12px;
  border-radius: 8px;
  display: inline-block;
}

/* Card Type Badges */
.card-type-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  display: inline-block;
}

.card-type-credit {
  background: rgba(0, 217, 36, 0.1);
  color: var(--success-500);
}

.card-type-debit {
  background: rgba(99, 91, 255, 0.1);
  color: var(--primary-600);
}

.card-type-prepaid {
  background: rgba(255, 106, 57, 0.1);
  color: var(--warning-500);
}

/* Row states */
.row-selected {
  background: rgba(99, 91, 255, 0.05) !important;
  border-left: 3px solid var(--primary-600) !important;
}

/* Search box */
.search-box {
  position: relative;
}

.search-box input {
  padding-left: 40px;
}

.search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
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
        Caricamento BIN Table...
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
          BIN Table
        </h1>
        <p style="color: var(--text-secondary); margin: 0;">
          Database codici BIN bancari
        </p>
      </div>
      <button @click="exportToExcel()" class="btn btn-primary">
        <i class="fas fa-file-excel"></i>
        <span>Esporta Excel</span>
      </button>
    </div>
  </div>

  <!-- Stats -->
  <div class="card mb-6">
    <div class="grid grid-cols-3 gap-6">
      <!-- Total BIN -->
      <div style="text-align: center; padding: var(--space-4);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-credit-card" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.total"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">BIN Totali</div>
      </div>

      <!-- Circuiti -->
      <div style="text-align: center; padding: var(--space-4); border-left: 1px solid var(--border-secondary); border-right: 1px solid var(--border-secondary);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(0, 217, 36, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-network-wired" style="font-size: 1.5rem; color: var(--success-500);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.circuiti"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Circuiti</div>
      </div>

      <!-- Nazioni -->
      <div style="text-align: center; padding: var(--space-4);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(255, 106, 57, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-globe" style="font-size: 1.5rem; color: var(--warning-500);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.nazioni"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Nazioni</div>
      </div>
    </div>
  </div>

  <!-- Search & Table -->
  <div class="card">
    <div style="padding: var(--space-6); border-bottom: 1px solid var(--border-secondary);">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text"
               class="input"
               placeholder="Cerca per BIN, circuito, banca, tipo carta, nazione..."
               x-model="search"
               @input="filterData()">
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>BIN</th>
            <th>Circuito</th>
            <th>Banca Emettitrice</th>
            <th>Livello Carta</th>
            <th>Tipo Carta</th>
            <th>Nazione</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="bin in paginatedData" :key="bin.pan">
            <tr @click="selectedRow = bin" :class="selectedRow?.pan === bin.pan ? 'row-selected' : ''">
              <td><span class="bin-code" x-text="bin.pan"></span></td>
              <td><span style="font-weight: 600;" x-text="bin.circuito"></span></td>
              <td x-text="bin.bancaEmettitrice"></td>
              <td x-text="bin.livelloCarta"></td>
              <td>
                <span class="card-type-badge"
                      :class="{
                        'card-type-credit': bin.tipoCarta?.toLowerCase() === 'credit',
                        'card-type-debit': bin.tipoCarta?.toLowerCase() === 'debit',
                        'card-type-prepaid': bin.tipoCarta?.toLowerCase() === 'prepaid'
                      }"
                      x-text="bin.tipoCarta"></span>
              </td>
              <td x-text="bin.nazione"></td>
            </tr>
          </template>
          <tr x-show="filteredData.length === 0">
            <td colspan="6" style="text-align: center; padding: var(--space-8); color: var(--text-secondary);">
              Nessun BIN trovato
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div style="padding: var(--space-6); border-top: 1px solid var(--border-secondary); display: flex; align-items: center; justify-content: space-between;">
      <div style="color: var(--text-secondary); font-size: var(--text-sm);">
        Mostrando <span x-text="startIdx + 1"></span> - <span x-text="Math.min(endIdx, filteredData.length)"></span> di <span x-text="filteredData.length"></span> BIN
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
    loading: true,
    data: [],
    filteredData: [],
    search: '',
    selectedRow: null,
    page: 1,
    perPage: 50,

    stats: {
      total: 0,
      circuiti: 0,
      nazioni: 0
    },

    async loadData() {
      this.loading = true;
      try {
        const res = await fetch('scripts/binlist_array.php');
        const json = await res.json();
        console.log('BIN data received:', json);

        // Convert DataTables array format to objects
        this.data = (json.data || []).map((row, idx) => ({
          id: idx,
          pan: row[0] || '',
          circuito: row[1] || '',
          bancaEmettitrice: row[2] || '',
          livelloCarta: row[3] || '',
          tipoCarta: row[4] || '',
          nazione: row[5] || ''
        }));

        // Sort by PAN
        this.data.sort((a, b) => (a.pan || '').localeCompare(b.pan || ''));

        this.filteredData = this.data;
        this.calculateStats();
      } catch (err) {
        console.error('Errore caricamento BIN:', err);
      } finally {
        this.loading = false;
      }
    },

    calculateStats() {
      this.stats.total = this.data.length;

      // Count unique circuiti
      const uniqueCircuiti = new Set(this.data.map(d => d.circuito).filter(c => c));
      this.stats.circuiti = uniqueCircuiti.size;

      // Count unique nazioni
      const uniqueNazioni = new Set(this.data.map(d => d.nazione).filter(n => n));
      this.stats.nazioni = uniqueNazioni.size;
    },

    filterData() {
      const q = this.search.toLowerCase();
      this.filteredData = this.data.filter(d => {
        return !q ||
          (d.pan || '').toLowerCase().includes(q) ||
          (d.circuito || '').toLowerCase().includes(q) ||
          (d.bancaEmettitrice || '').toLowerCase().includes(q) ||
          (d.livelloCarta || '').toLowerCase().includes(q) ||
          (d.tipoCarta || '').toLowerCase().includes(q) ||
          (d.nazione || '').toLowerCase().includes(q);
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

    exportToExcel() {
      // Prepare data for export with all BIN fields
      const exportData = this.filteredData.map(bin => ({
        'BIN (PAN)': bin.pan,
        'Circuito': bin.circuito,
        'Banca Emettitrice': bin.bancaEmettitrice,
        'Livello Carta': bin.livelloCarta,
        'Tipo Carta': bin.tipoCarta,
        'Nazione': bin.nazione
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

      const filename = `bin_table_${new Date().toISOString().split('T')[0]}.csv`;
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  };
}
</script>

<?php require_once('footer-v3.php'); ?>
