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
        Cargando datos...
      </div>
      <div style="font-size: var(--text-sm); color: var(--text-secondary);">
        Por favor espere
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
          Tiendas
        </h1>
        <p style="color: var(--text-secondary); margin: 0;">
          Todos los puntos de venta
        </p>
      </div>
    </div>

    <!-- Country Tabs -->
    <div class="flex gap-2">
      <a href="stores.php" class="btn btn-outline flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> Italia
      </a>
      <a href="stores-es.php" class="btn btn-primary flex-1" style="text-align: center;">
        <i class="fas fa-flag"></i> España
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="card mb-6">
    <div class="grid grid-cols-3 gap-6">
      <!-- Total Stores -->
      <div style="text-align: center; padding: var(--space-4);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-store" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.total"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Tiendas (Insegnas)</div>
      </div>

      <!-- PV -->
      <div style="text-align: center; padding: var(--space-4); border-left: 1px solid var(--border-secondary); border-right: 1px solid var(--border-secondary);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-shopping-cart" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.pv"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Puntos de Venta (PV)</div>
      </div>

      <!-- SDT -->
      <div style="text-align: center; padding: var(--space-4);">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: rgba(99, 91, 255, 0.1); border-radius: var(--radius-xl); margin-bottom: var(--space-3);">
          <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--primary-600);"></i>
        </div>
        <div style="font-size: var(--text-3xl); font-weight: var(--font-bold); color: var(--gray-900); margin-bottom: var(--space-2);" x-text="stats.sdt"></div>
        <div style="font-size: var(--text-sm); font-weight: var(--font-medium); color: var(--text-secondary);">Sociedades Transporte (SDT)</div>
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
               placeholder="Buscar por terminal ID, razón social, ciudad, dirección..."
               x-model="search"
               @input="filterData()">
      </div>
    </div>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Terminal ID</th>
            <th>Razón Social</th>
            <th>Insegna</th>
            <th>Dirección</th>
            <th>Ciudad</th>
            <th>CP</th>
            <th>Procedencia</th>
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
              Ninguna tienda encontrada
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div style="padding: var(--space-6); border-top: 1px solid var(--border-secondary); display: flex; align-items: center; justify-content: space-between;">
      <div style="color: var(--text-secondary); font-size: var(--text-sm);">
        Mostrando <span x-text="startIdx + 1"></span> - <span x-text="Math.min(endIdx, filteredData.length)"></span> de <span x-text="filteredData.length"></span> tiendas
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
    perPage: 25,

    stats: {
      total: 0,
      pv: 0,
      sdt: 0
    },

    async loadData() {
      this.loading = true;
      try {
        // Fetch stores data from server - FILTER FOR SPAIN
        const where = "country='ES'";
        const res = await fetch(`scripts/stores_simple.php?where=${encodeURIComponent(where)}&start=0&length=10000`);
        const json = await res.json();
        console.log('Stores España data received:', json);

        // Data is already in object format
        this.data = json.data || [];

        // Sort by terminalID
        this.data.sort((a, b) => (a.terminalID || '').localeCompare(b.terminalID || ''));

        this.filteredData = this.data;
        this.calculateStats();
      } catch (err) {
        console.error('Error cargando datos:', err);
      } finally {
        this.loading = false;
      }
    },

    calculateStats() {
      // Count unique INSEGNA (stores), not terminals
      const uniqueInsegne = new Set(this.data.map(d => d.insegna).filter(i => i));
      this.stats.total = uniqueInsegne.size;

      // Count unique INSEGNA for PV
      const pvInsegne = new Set(
        this.data
          .filter(d => d.prov === 'PV')
          .map(d => d.insegna)
          .filter(i => i)
      );
      this.stats.pv = pvInsegne.size;

      // Count unique INSEGNA for SDT
      const sdtInsegne = new Set(
        this.data
          .filter(d => d.prov === 'SDT')
          .map(d => d.insegna)
          .filter(i => i)
      );
      this.stats.sdt = sdtInsegne.size;
    },

    filterData() {
      const q = this.search.toLowerCase();
      this.filteredData = this.data.filter(d => {
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
