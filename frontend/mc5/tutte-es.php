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
if (!isset($_POST['WHERE'])){
    $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
    $query = "dataOperazione >= '$date'";
    $dallaData = $date;
    $allaData = $date;
} else {
    $dallaData = $_POST['DALLADATA'];
    $allaData = $_POST['ALLADATA'];
    $query = "dataOperazione >= '$dallaData' AND dataOperazione <= '$allaData'";
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
</style>

<div x-data="app()" x-init="loadData()">

  <!-- Header -->
  <div class="card mb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 style="font-size: 28px; font-weight: 700; color: var(--gray-900); margin: 0 0 8px 0;">
          Transazioni
        </h1>
        <p style="color: var(--text-secondary); margin: 0;">
          En tienda - España
        </p>
      </div>
      <button @click="showFilters = !showFilters" class="btn btn-outline">
        <i class="fas" :class="showFilters ? 'fa-eye-slash' : 'fa-filter'"></i>
        <span x-text="showFilters ? 'Nascondi' : 'Filtri'"></span>
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2">
      <a href="tutte-5.php" class="btn btn-outline flex-1" style="text-align: center;">Italia</a>
      <a href="tutte-es.php" class="btn btn-primary flex-1" style="text-align: center;">España</a>
    </div>
  </div>

  <!-- Filters -->
  <div x-show="showFilters" x-transition class="card mb-6">
    <form method="POST">
      <div class="grid grid-cols-auto gap-4">
        <div>
          <label class="label">Dalla Data</label>
          <input type="date" name="DALLADATA" class="input" value="<?php echo $dallaData; ?>" required />
        </div>
        <div>
          <label class="label">Alla Data</label>
          <input type="date" name="ALLADATA" class="input" value="<?php echo $allaData; ?>" required />
        </div>
        <div>
          <label class="label">Provenienza</label>
          <select x-model="provFilter" @change="filterData()" class="input">
            <option value="">Tutti</option>
            <option value="PV">PV - Punti Vendita</option>
            <option value="SDT">SDT - Società Trasporti</option>
            <option value="XX">Altro</option>
          </select>
        </div>
        <div style="display: flex; align-items: end;">
          <input type="hidden" name="WHERE" value="1">
          <button type="submit" class="btn btn-primary w-full">Cerca</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 gap-4 mb-6">
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary">
        <i class="fas fa-receipt"></i>
      </div>
      <div class="stat-value" x-text="stats.total"></div>
      <div class="stat-label">Transazioni</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary">
        <i class="fas fa-euro-sign"></i>
      </div>
      <div class="stat-value" x-text="formatCurrency(stats.amount)"></div>
      <div class="stat-label">Importo Totale</div>
    </div>
  </div>

  <!-- Breakdown Tabs -->
  <div class="card mb-6">
    <div style="border-bottom: 1px solid var(--border-secondary); padding: 16px; display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button @click="activeTab = 'acquirer'" :class="activeTab === 'acquirer' ? 'btn-primary' : 'btn-outline'" class="btn">
          <i class="fas fa-building"></i> Acquirer
        </button>
      <button @click="activeTab = 'insegna'" :class="activeTab === 'insegna' ? 'btn-primary' : 'btn-outline'" class="btn">
        <i class="fas fa-store"></i> Insegna
      </button>
      <button @click="activeTab = 'provenienza'" :class="activeTab === 'provenienza' ? 'btn-primary' : 'btn-outline'" class="btn">
        <i class="fas fa-tag"></i> Provenienza
      </button>
      <button @click="activeTab = 'citta'" :class="activeTab === 'citta' ? 'btn-primary' : 'btn-outline'" class="btn">
        <i class="fas fa-map-marker"></i> Città
      </button>
      </div>
      <button @click="showBreakdown = !showBreakdown" class="btn btn-outline">
        <i class="fas" :class="showBreakdown ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
      </button>
    </div>
    <div x-show="showBreakdown" x-transition style="padding: 24px;">
      <div x-show="activeTab === 'acquirer'" class="grid grid-cols-auto gap-4">
        <template x-for="item in breakdown.acquirer" :key="item.name">
          <div @click="selectedAcquirer = selectedAcquirer === item.name ? '' : item.name; filterData();"
               :class="selectedAcquirer === item.name ? 'breakdown-card-active' : 'breakdown-card'"
               style="cursor: pointer;">
            <div style="font-weight: 600; color: var(--gray-700); margin-bottom: 8px;" x-text="item.name"></div>
            <div style="font-size: 24px; font-weight: 700; color: var(--primary-600); margin-bottom: 4px;" x-text="formatCurrency(item.total)"></div>
            <div style="font-size: 13px; color: var(--gray-500);" x-text="item.count + ' trx'"></div>
          </div>
        </template>
      </div>
      <div x-show="activeTab === 'insegna'" class="grid grid-cols-auto gap-4">
        <template x-for="item in breakdown.insegna" :key="item.name">
          <div @click="selectedInsegna = selectedInsegna === item.name ? '' : item.name; filterData();"
               :class="selectedInsegna === item.name ? 'breakdown-card-active' : 'breakdown-card'"
               style="cursor: pointer;">
            <div style="font-weight: 600; color: var(--gray-700); margin-bottom: 8px;" x-text="item.name"></div>
            <div style="font-size: 24px; font-weight: 700; color: var(--primary-600); margin-bottom: 4px;" x-text="formatCurrency(item.total)"></div>
            <div style="font-size: 13px; color: var(--gray-500);" x-text="item.count + ' trx'"></div>
          </div>
        </template>
      </div>
      <div x-show="activeTab === 'provenienza'" class="grid grid-cols-auto gap-4">
        <template x-for="item in breakdown.provenienza" :key="item.name">
          <div @click="selectedProvenienza = selectedProvenienza === item.name ? '' : item.name; filterData();"
               :class="selectedProvenienza === item.name ? 'breakdown-card-active' : 'breakdown-card'"
               style="cursor: pointer;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
              <span style="font-weight: 600; color: var(--gray-700);" x-text="item.name"></span>
              <span class="badge" :class="item.name === 'PV' ? 'badge-primary' : 'badge-success'" x-text="item.name === 'PV' ? 'Punti Vendita' : item.name === 'SDT' ? 'Società Trasporti' : 'Altro'"></span>
            </div>
            <div style="font-size: 24px; font-weight: 700; color: var(--primary-600); margin-bottom: 4px;" x-text="formatCurrency(item.total)"></div>
            <div style="font-size: 13px; color: var(--gray-500);" x-text="item.count + ' trx'"></div>
          </div>
        </template>
      </div>
      <div x-show="activeTab === 'citta'" class="grid grid-cols-auto gap-4">
        <template x-for="item in breakdown.citta" :key="item.name">
          <div @click="selectedCitta = selectedCitta === item.name ? '' : item.name; filterData();"
               :class="selectedCitta === item.name ? 'breakdown-card-active' : 'breakdown-card'"
               style="cursor: pointer;">
            <div style="font-weight: 600; color: var(--gray-700); margin-bottom: 8px;" x-text="item.name"></div>
            <div style="font-size: 24px; font-weight: 700; color: var(--primary-600); margin-bottom: 4px;" x-text="formatCurrency(item.total)"></div>
            <div style="font-size: 13px; color: var(--gray-500);" x-text="item.count + ' trx'"></div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- Active filters badge -->
  <div x-show="selectedAcquirer || selectedInsegna || selectedProvenienza || selectedCitta"
       class="mb-6"
       style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
    <span style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
      <i class="fas fa-filter"></i> Filtri attivi:
    </span>
    <span class="badge badge-success" style="font-weight: 700; padding: 6px 12px;">
      <span x-text="filteredData.length"></span> trx -
      <span x-text="formatCurrency(filteredData.reduce((sum, d) => sum + (d.importo || 0), 0))"></span>
    </span>
    <span x-show="selectedAcquirer" class="badge badge-primary" style="cursor: pointer;" @click="selectedAcquirer = ''; filterData();">
      Acquirer: <span x-text="selectedAcquirer"></span> <i class="fas fa-times"></i>
    </span>
    <span x-show="selectedInsegna" class="badge badge-primary" style="cursor: pointer;" @click="selectedInsegna = ''; filterData();">
      Insegna: <span x-text="selectedInsegna"></span> <i class="fas fa-times"></i>
    </span>
    <span x-show="selectedProvenienza" class="badge badge-primary" style="cursor: pointer;" @click="selectedProvenienza = ''; filterData();">
      Provenienza: <span x-text="selectedProvenienza"></span> <i class="fas fa-times"></i>
    </span>
    <span x-show="selectedCitta" class="badge badge-primary" style="cursor: pointer;" @click="selectedCitta = ''; filterData();">
      Città: <span x-text="selectedCitta"></span> <i class="fas fa-times"></i>
    </span>
    <button @click="selectedAcquirer = ''; selectedInsegna = ''; selectedProvenienza = ''; selectedCitta = ''; filterData();"
            class="btn btn-outline" style="height: 28px; padding: 0 12px; font-size: 12px;">
      <i class="fas fa-times-circle"></i> Resetta tutti
    </button>
  </div>

  <!-- Table -->
  <div class="card" style="padding: 0;">
    <div style="padding: 24px; border-bottom: 1px solid var(--border-secondary); display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 style="font-weight: 600; margin: 0;">Elenco Transazioni</h3>
        <p style="color: var(--text-secondary); font-size: 13px; margin: 4px 0 0;">
          <?php echo $dallaData; ?> - <?php echo $allaData; ?>
        </p>
      </div>
      <div class="flex gap-2">
        <button @click="exportExcel()" class="btn btn-outline">
          <i class="fas fa-file-excel"></i> Excel
        </button>
      </div>
    </div>

    <div style="padding: 16px; border-bottom: 1px solid var(--border-secondary);">
      <input
        type="text"
        class="input"
        placeholder="Cerca..."
        x-model="search"
        @input="filterData()"
      />
    </div>

    <div x-show="loading" style="text-align: center; padding: 60px;">
      <div style="max-width: 400px; margin: 0 auto;">
        <div class="spinner" style="margin: 0 auto 24px;"></div>
        <p style="color: var(--gray-900); font-weight: 600; margin-bottom: 16px; font-size: 16px;">Cargando transacciones...</p>
        <div style="background: var(--gray-200); height: 8px; border-radius: var(--radius-full); overflow: hidden; margin-bottom: 12px;">
          <div style="
            background: linear-gradient(90deg, var(--primary-600), var(--primary-400), var(--primary-600));
            height: 100%;
            width: 100%;
            animation: progress-indeterminate 1.5s ease-in-out infinite;
            background-size: 200% 100%;
          "></div>
        </div>
        <p style="color: var(--gray-500); font-size: 13px;">Por favor espere, cargando hasta 200.000 transacciones...</p>
      </div>
    </div>

    <style>
    @keyframes progress-indeterminate {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    </style>

    <!-- Message when no filters active -->
    <div x-show="!loading && !selectedAcquirer && !selectedInsegna && !selectedProvenienza && !selectedCitta && !search"
         style="text-align: center; padding: 80px 40px;">
      <i class="fas fa-filter" style="font-size: 48px; color: var(--gray-300); margin-bottom: 16px;"></i>
      <h3 style="color: var(--gray-700); margin-bottom: 8px;">Selecciona un filtro</h3>
      <p style="color: var(--gray-500); font-size: 14px;">
        Haz clic en un Acquirer, Tienda, Procedencia o Ciudad arriba para ver las transacciones.<br>
        O usa la barra de búsqueda.
      </p>
    </div>

    <div x-show="!loading && (selectedAcquirer || selectedInsegna || selectedProvenienza || selectedCitta || search)" style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Terminal ID</th>
            <th>Data/Ora</th>
            <th>Importo</th>
            <th>Circuito</th>
            <th>Insegna</th>
            <th>Città</th>
            <th>Prov.</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="row in paginatedData" :key="row.id">
            <tr style="cursor: pointer;" @click="selectedRow = row" :class="selectedRow?.id === row.id ? 'row-selected' : ''">
              <td><span class="mono" style="font-weight: 600;" x-text="row.terminalID || ''"></span></td>
              <td>
                <div style="font-size: 12px; font-weight: 600;" x-text="row.dataOperazione || ''"></div>
                <div style="font-size: 11px; color: var(--gray-500);" x-text="row.oraOperazione || ''"></div>
              </td>
              <td><span class="amount" style="font-size: 16px; font-weight: 700;" x-text="formatCurrency(row.importo || 0)"></span></td>
              <td><span style="font-size: 11px; font-weight: 600; color: var(--primary-600);" x-text="row.tag4f || ''"></span></td>
              <td><span style="font-weight: 600;" x-text="row.insegna || ''"></span></td>
              <td x-text="row.localita || ''"></td>
              <td><span style="font-size: 11px; font-weight: 600;" :style="row.prov === 'PV' ? 'color: var(--primary-600);' : row.prov === 'SDT' ? 'color: var(--success-500);' : ''" x-text="row.prov || ''"></span></td>
              <td><i class="fas fa-chevron-right" style="color: var(--gray-400);"></i></td>
            </tr>
          </template>
        </tbody>
      </table>

      <div x-show="filteredData.length === 0" style="text-align: center; padding: 60px; color: var(--gray-400);">
        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
        <p>Nessuna transazione</p>
      </div>
    </div>

    <div x-show="!loading && filteredData.length > 0" style="padding: 16px; border-top: 1px solid var(--border-secondary); display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 13px; color: var(--gray-600);">
        <strong x-text="startIdx + 1"></strong>-<strong x-text="Math.min(endIdx, filteredData.length)"></strong> di <strong x-text="filteredData.length"></strong>
      </div>
      <div style="display: flex; gap: 8px;">
        <button @click="prevPage()" :disabled="page === 1" class="page-btn">
          <i class="fas fa-chevron-left"></i>
        </button>
        <template x-for="p in pages" :key="p">
          <button @click="page = p" :class="{ 'active': page === p }" class="page-btn" x-text="p"></button>
        </template>
        <button @click="nextPage()" :disabled="page === totalPages" class="page-btn">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Detail Sidebar -->
  <div x-show="selectedRow" x-transition
       style="position: fixed; top: 0; right: 0; bottom: 0; width: 480px; background: white; box-shadow: var(--shadow-xl); z-index: 1000; overflow-y: auto;">
    <div style="position: sticky; top: 0; background: white; border-bottom: 1px solid var(--border-primary); padding: 24px; z-index: 10;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Dettaglio Transazione</h3>
        <button @click="selectedRow = null" class="btn btn-outline" style="padding: 8px; width: 36px; height: 36px;">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <div x-show="selectedRow" style="padding: 24px;">
      <!-- Terminal & Amount -->
      <div style="text-align: center; padding: 24px; background: var(--gray-50); border-radius: 12px; margin-bottom: 24px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-500); margin-bottom: 8px;">Terminal ID</div>
        <div style="font-size: 24px; font-weight: 700; font-family: var(--font-mono); margin-bottom: 16px;" x-text="selectedRow?.terminalID"></div>
        <div style="font-size: 32px; font-weight: 800; color: var(--primary-600);" x-text="formatCurrency(selectedRow?.importo || 0)"></div>
      </div>

      <!-- Details Grid -->
      <div style="display: grid; gap: 16px;">
        <div class="detail-row">
          <div class="detail-label">Data e Ora</div>
          <div class="detail-value">
            <span x-text="selectedRow?.dataOperazione"></span> - <span x-text="selectedRow?.oraOperazione"></span>
          </div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Circuito</div>
          <div class="detail-value" style="color: var(--primary-600); font-weight: 700;" x-text="selectedRow?.tag4f"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">PAN</div>
          <div class="detail-value mono" x-text="selectedRow?.pan"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Codice Autorizzativo</div>
          <div class="detail-value mono" x-text="selectedRow?.codiceAutorizzativo"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Acquirer</div>
          <div class="detail-value" style="font-weight: 700;" x-text="selectedRow?.acquirer"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Flag Log / Actin Code</div>
          <div class="detail-value">
            <span :style="selectedRow?.flagLog === '00' ? 'color: var(--success-500); font-weight: 700;' : 'color: var(--danger-500); font-weight: 700;'" x-text="selectedRow?.flagLog"></span>
            /
            <span :style="selectedRow?.actinCode === '00' ? 'color: var(--success-500); font-weight: 700;' : 'color: var(--danger-500); font-weight: 700;'" x-text="selectedRow?.actinCode"></span>
          </div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Domestico / Internazionale</div>
          <div class="detail-value" x-text="selectedRow?.domestico === '1' ? 'Domestico (IT)' : 'Internazionale'"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Modello POS</div>
          <div class="detail-value" x-text="selectedRow?.modelloPos"></div>
        </div>

        <hr style="border: none; border-top: 1px solid var(--border-secondary);">

        <div class="detail-row">
          <div class="detail-label">Insegna</div>
          <div class="detail-value" style="font-weight: 700;" x-text="selectedRow?.insegna"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Ragione Sociale</div>
          <div class="detail-value" x-text="selectedRow?.ragioneSociale"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Codifica Stabilimento</div>
          <div class="detail-value mono" x-text="selectedRow?.codificaStab"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Indirizzo</div>
          <div class="detail-value" x-text="selectedRow?.indirizzo"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Città</div>
          <div class="detail-value" x-text="selectedRow?.localita"></div>
        </div>

        <div class="detail-row">
          <div class="detail-label">Provenienza</div>
          <div class="detail-value" style="font-weight: 700;" :style="selectedRow?.prov === 'PV' ? 'color: var(--primary-600);' : selectedRow?.prov === 'SDT' ? 'color: var(--success-500);' : ''">
            <span x-text="selectedRow?.prov"></span>
            <span style="font-size: 11px; font-weight: 400; color: var(--gray-500);" x-text="selectedRow?.prov === 'PV' ? '(Punti Vendita)' : selectedRow?.prov === 'SDT' ? '(Società Trasporti)' : ''"></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Overlay -->
  <div x-show="selectedRow" x-transition @click="selectedRow = null"
       style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.3); z-index: 999;"></div>

</div>

<style>
/* Detail Panel */
.detail-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 12px;
  align-items: start;
}

.detail-label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--gray-500);
  padding-top: 2px;
}

.detail-value {
  font-size: 14px;
  color: var(--gray-900);
  font-weight: 500;
}

.mono {
  font-family: var(--font-mono);
}

/* Table row selection */
.row-selected {
  background: rgba(99, 91, 255, 0.08) !important;
  border-left: 3px solid var(--primary-600) !important;
}

.data-table tbody tr:hover {
  background: var(--gray-50);
}

/* Breakdown cards */
.breakdown-card {
  border: 1px solid var(--border-primary);
  border-radius: 8px;
  padding: 16px;
  transition: all 0.2s ease;
}

.breakdown-card:hover {
  border-color: var(--primary-600);
  box-shadow: 0 2px 8px rgba(99, 91, 255, 0.15);
  transform: translateY(-2px);
}

.breakdown-card-active {
  border: 2px solid var(--primary-600);
  border-radius: 8px;
  padding: 16px;
  background: rgba(99, 91, 255, 0.05);
  box-shadow: 0 4px 12px rgba(99, 91, 255, 0.2);
}

.page-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--border-primary);
  background: white;
  color: var(--gray-700);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.page-btn:hover:not(:disabled) {
  background: var(--gray-50);
}
.page-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
.page-btn.active {
  background: var(--primary-600);
  color: white;
  border-color: var(--primary-600);
}
</style>

<script>
function app() {
  return {
    showFilters: true,
    loading: true,
    data: [],
    filteredData: [],
    search: '',
    provFilter: '',  // Filter by provenienza (PV/SDT/XX)
    selectedAcquirer: '',
    selectedInsegna: '',
    selectedProvenienza: '',
    selectedCitta: '',
    selectedRow: null,  // Selected row for detail panel
    showBreakdown: true,  // Show/hide breakdown tabs
    page: 1,
    perPage: 25,
    activeTab: 'acquirer',

    stats: {
      total: 0,
      approved: 0,
      rejected: 0,
      amount: 0
    },

    breakdown: {
      acquirer: [],
      insegna: [],
      provenienza: [],
      citta: []
    },

    async loadData() {
      this.loading = true;
      try {
        const res = await fetch('scripts/tracciato_vista_server_es.php?where=<?php echo urlencode($query); ?>');
        const json = await res.json();
        console.log('Data received:', json);

        // Convert DataTables array format to objects - ALL COLUMNS
        this.data = (json.data || []).map((row, idx) => ({
          id: idx,
          codificaStab: row[0] || '',
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
          approved: (row[11] && row[11] === '00') || (row[12] && row[12] === '00'),
          insegna: row[13] || '',
          ragioneSociale: row[14] || '',
          indirizzo: row[15] || '',
          localita: row[16] || '',
          prov: row[17] || '',
          cap: row[18] || ''
        }));

        // Sort by date and time (most recent first)
        this.data.sort((a, b) => {
          const dateTimeA = `${a.dataOperazione} ${a.oraOperazione}`;
          const dateTimeB = `${b.dataOperazione} ${b.oraOperazione}`;
          return dateTimeB.localeCompare(dateTimeA);
        });

        this.filteredData = this.data;

        // Debug: check first record
        if (this.data.length > 0) {
          console.log('First record:', this.data[0]);
          console.log('flagLog values sample:', this.data.slice(0, 10).map(d => d.flagLog));
          console.log('actinCode values sample:', this.data.slice(0, 10).map(d => d.actinCode));
        }

        this.calculateStats();
        this.calculateBreakdown();
      } catch (err) {
        console.error('Error cargando datos:', err);
        alert('Error al cargar los datos. Inténtalo más tarde.');
      } finally {
        this.loading = false;
      }
    },

    calculateStats() {
      this.stats.total = this.data.length;
      this.stats.approved = this.data.filter(d => d.approved).length;
      this.stats.rejected = this.stats.total - this.stats.approved;
      this.stats.amount = this.data.reduce((sum, d) => sum + parseFloat(d.importo || 0), 0);
    },

    calculateBreakdown() {
      // Acquirer
      const acqMap = {};
      this.data.forEach(d => {
        const k = d.acquirer || 'N/A';
        if (!acqMap[k]) acqMap[k] = { name: k, total: 0, count: 0 };
        acqMap[k].total += parseFloat(d.importo || 0);
        acqMap[k].count++;
      });
      this.breakdown.acquirer = Object.values(acqMap).sort((a, b) => b.total - a.total);

      // Insegna
      const insMap = {};
      this.data.forEach(d => {
        const k = d.insegna || 'N/A';
        if (!insMap[k]) insMap[k] = { name: k, total: 0, count: 0 };
        insMap[k].total += parseFloat(d.importo || 0);
        insMap[k].count++;
      });
      this.breakdown.insegna = Object.values(insMap).sort((a, b) => b.total - a.total);

      // Provenienza (PV = Punti Vendita, SDT = Società Trasporti)
      const provMap = {};
      this.data.forEach(d => {
        const k = d.prov || 'N/A';
        if (!provMap[k]) provMap[k] = { name: k, total: 0, count: 0 };
        provMap[k].total += parseFloat(d.importo || 0);
        provMap[k].count++;
      });
      this.breakdown.provenienza = Object.values(provMap).sort((a, b) => b.total - a.total);

      // Città
      const citMap = {};
      this.data.forEach(d => {
        const k = d.localita || 'N/A';
        if (!citMap[k]) citMap[k] = { name: k, total: 0, count: 0 };
        citMap[k].total += parseFloat(d.importo || 0);
        citMap[k].count++;
      });
      this.breakdown.citta = Object.values(citMap).sort((a, b) => b.total - a.total);
    },

    filterData() {
      const q = this.search.toLowerCase();
      this.filteredData = this.data.filter(d => {
        // Text search - search across ALL fields
        const matchesSearch = !q ||
          (d.codificaStab || '').toLowerCase().includes(q) ||
          (d.terminalID || '').toLowerCase().includes(q) ||
          (d.modelloPos || '').toLowerCase().includes(q) ||
          (d.pan || '').toLowerCase().includes(q) ||
          (d.tag4f || '').toLowerCase().includes(q) ||
          (d.codiceAutorizzativo || '').toLowerCase().includes(q) ||
          (d.acquirer || '').toLowerCase().includes(q) ||
          (d.insegna || '').toLowerCase().includes(q) ||
          (d.ragioneSociale || '').toLowerCase().includes(q) ||
          (d.indirizzo || '').toLowerCase().includes(q) ||
          (d.localita || '').toLowerCase().includes(q) ||
          (d.prov || '').toLowerCase().includes(q);

        // Provenienza dropdown filter
        const matchesProv = !this.provFilter || d.prov === this.provFilter;

        // Breakdown click filters
        const matchesAcquirer = !this.selectedAcquirer || d.acquirer === this.selectedAcquirer;
        const matchesInsegna = !this.selectedInsegna || d.insegna === this.selectedInsegna;
        const matchesProvBreakdown = !this.selectedProvenienza || d.prov === this.selectedProvenienza;
        const matchesCitta = !this.selectedCitta || d.localita === this.selectedCitta;

        return matchesSearch && matchesProv && matchesAcquirer && matchesInsegna && matchesProvBreakdown && matchesCitta;
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

    exportExcel() {
      window.location.href = 'scripts/export_excel.php?table=tracciato_pos_es&where=<?php echo urlencode($query); ?>';
    }
  };
}
</script>

<?php require_once('footer-v3.php'); ?>
