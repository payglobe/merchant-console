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
          Negozi
        </h1>
        <p style="color: var(--text-secondary); margin: 0;">
          Tutti i punti vendita
        </p>
      </div>
    </div>

    <!-- Country Tabs -->
    <div class="flex gap-2">
      <a href="stores.php" class="btn btn-primary flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> Italia
      </a>
      <a href="stores-es.php" class="btn btn-outline flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> España
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="card mb-6">
    <div class="grid grid-cols-3 gap-6">
      <!-- Total Stores -->
      <div @click="filterByType('all')" style="text-align: center; padding: var(--space-4); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(99, 91, 255, 0.03)'" onmouseout="this.style.background=''">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-store" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.total"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Totali</div>
      </div>

      <!-- PV -->
      <div @click="filterByType('PV')" style="text-align: center; padding: var(--space-4); border-left: 1px solid var(--border-secondary); border-right: 1px solid var(--border-secondary); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(99, 91, 255, 0.03)'" onmouseout="this.style.background=''">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.pv"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Punti Vendita (PV)</div>
      </div>

      <!-- SDT -->
      <div @click="filterByType('SDT')" style="text-align: center; padding: var(--space-4); cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='rgba(99, 91, 255, 0.03)'" onmouseout="this.style.background=''">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.sdt"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Società Trasporti (SDT)</div>
      </div>
    </div>
  </div>

  <!-- Search & Table -->
  <div class="card" x-show="showTable">
    <div style="padding: var(--space-6); border-bottom: 1px solid var(--border-secondary);">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text"
               class="input"
               placeholder="Cerca per terminal ID, ragione sociale, città, indirizzo..."
               x-model="search"
               @input="filterData()">
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Terminal ID</th>
            <th>Ragione Sociale</th>
            <th>Insegna</th>
            <th>Indirizzo</th>
            <th>Città</th>
            <th>CAP</th>
            <th>Provenienza</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="store in paginatedData" :key="store.terminalID">
            <tr @click="selectedRow = store" :class="selectedRow?.terminalID === store.terminalID ? 'row-selected' : ''">
              <td><span class="mono" style="font-weight: 600;" x-text="store.terminalID"></span></td>
              <td><span style="font-weight: 600;" x-text="store.ragioneSociale"></span></td>
              <td x-text="store.insegna"></td>
              <td x-text="store.indirizzo"></td>
              <td x-text="store.citta"></td>
              <td><span class="mono" x-text="store.cap"></span></td>
              <td>
                <span class="badge"
                      :class="store.prov === 'PV' ? 'badge-primary' : store.prov === 'SDT' ? 'badge-success' : ''"
                      x-text="store.prov"></span>
              </td>
            </tr>
          </template>
          <tr x-show="filteredData.length === 0">
            <td colspan="7" style="text-align: center; padding: var(--space-8); color: var(--text-secondary);">
              Nessun negozio trovato
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div style="padding: var(--space-6); border-top: 1px solid var(--border-secondary); display: flex; align-items: center; justify-content: space-between;">
      <div style="color: var(--text-secondary); font-size: var(--text-sm);">
        Mostrando <span x-text="startIdx + 1"></span> - <span x-text="Math.min(endIdx, filteredData.length)"></span> di <span x-text="filteredData.length"></span> negozi
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
    provFilter: null, // Start with null = no filter selected = table hidden
    selectedRow: null,
    page: 1,
    perPage: 25,
    showTable: false, // Initially hide table

    stats: {
      total: 0,
      pv: 0,
      sdt: 0
    },

    async loadData() {
      this.loading = true;
      try {
        // Load PV and SDT separately, then merge - SLOT DA 5000
        console.log('Loading PV stores...');
        const pvData = await this.loadBatchData("country='IT' AND prov='PV'", 5000);
        console.log(`PV stores loaded: ${pvData.length}`);

        console.log('Loading SDT stores...');
        const sdtData = await this.loadBatchData("country='IT' AND prov='SDT'", 5000);
        console.log(`SDT stores loaded: ${sdtData.length}`);

        // Merge both datasets
        this.data = [...pvData, ...sdtData];
        console.log(`Total stores merged: ${this.data.length}`);

        // Sort by terminalID
        this.data.sort((a, b) => (a.terminalID || '').localeCompare(b.terminalID || ''));

        this.filteredData = [...this.data];
        this.calculateStats();

        console.log('Stats calculated:', this.stats);
      } catch (err) {
        console.error('Errore caricamento dati:', err);
        alert('Errore: ' + err.message);
      } finally {
        this.loading = false;
      }
    },

    async loadBatchData(where, batchSize = 5000) {
      let allData = [];
      let start = 0;
      let hasMore = true;
      let batchCount = 0;

      console.log(`  Starting batch load with WHERE: ${where}`);

      while (hasMore) {
        batchCount++;
        console.log(`  Fetching batch ${batchCount}: start=${start}, length=${batchSize}`);

        const res = await fetch(`scripts/stores_simple.php?where=${encodeURIComponent(where)}&start=${start}&length=${batchSize}`);

        if (!res.ok) {
          console.error(`  HTTP error! status: ${res.status}`);
          break;
        }

        const text = await res.text();
        if (!text || text.trim() === '') {
          console.warn(`  Empty response at batch ${batchCount}, stopping`);
          break;
        }

        const json = JSON.parse(text);
        console.log(`  Batch ${batchCount} response: total=${json.total}, loaded=${json.loaded}, data.length=${json.data?.length}`);

        if (json.data && json.data.length > 0) {
          allData = allData.concat(json.data);
          console.log(`  Progress: ${allData.length}/${json.total || '?'} records loaded`);

          start += batchSize;

          // Continue if we haven't reached the total
          if (json.total && allData.length >= parseInt(json.total)) {
            console.log(`  All ${json.total} records loaded - stopping`);
            hasMore = false;
          } else if (json.data.length < batchSize) {
            console.log(`  Partial batch (${json.data.length} < ${batchSize}) - stopping`);
            hasMore = false;
          } else {
            console.log(`  Full batch received, continuing...`);
          }
        } else {
          console.warn(`  No data in batch ${batchCount} - stopping`);
          hasMore = false;
        }
      }

      console.log(`  Batch loading complete: ${allData.length} total records`);
      return allData;
    },

    calculateStats() {
      // Count TOTAL records (not unique insegne)
      this.stats.total = this.data.length;

      // Count TOTAL records for PV
      this.stats.pv = this.data.filter(d => d.prov === 'PV').length;

      // Count TOTAL records for SDT
      this.stats.sdt = this.data.filter(d => d.prov === 'SDT').length;
    },

    filterByType(type) {
      this.provFilter = type;
      this.showTable = true; // Show table when user clicks a card
      this.page = 1;
      this.filterData();
    },

    filterData() {
      const q = this.search.toLowerCase();
      this.filteredData = this.data.filter(d => {
        // Apply prov filter
        if (this.provFilter && this.provFilter !== 'all' && d.prov !== this.provFilter) {
          return false;
        }

        // Apply search filter
        return !q ||
          (d.terminalID || '').toLowerCase().includes(q) ||
          (d.ragioneSociale || '').toLowerCase().includes(q) ||
          (d.insegna || '').toLowerCase().includes(q) ||
          (d.indirizzo || '').toLowerCase().includes(q) ||
          (d.citta || '').toLowerCase().includes(q) ||
          (d.cap || '').toLowerCase().includes(q) ||
          (d.prov || '').toLowerCase().includes(q);
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
    }
  };
}
</script>

<?php require_once('footer-v3.php'); ?>
