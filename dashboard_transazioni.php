<?php
/* =====================================================================
 * Payglobe – Gestione Prodotti APM
 * ===================================================================== */
$DEFAULT_TID = '00000000';
$tid   = preg_match('/^\d{6,12}$/', $_GET['tid'] ?? '') ? $_GET['tid'] : $DEFAULT_TID;
session_start();
require __DIR__.'/config.php'; // -> $conn CoNN (utf8mb4)

/* ---------- util ---------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
function check_csrf($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }
function vat_valid($v){ return in_array((int)$v,[0,4,5,10,22],true); }

/* =====================================================================
 * TAB: Prodotti (MySQL) -> CRUD
 * ===================================================================== */
/* ---------- PRODUCTS: azioni POST (per terminale) ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['_product_action'])) {
  if (!check_csrf($_POST['_csrf'] ?? '')) { http_response_code(400); die('CSRF non valido'); }
  $act = $_POST['_product_action'];
  $tidPost = $tid; // usa il TID “attivo” della pagina

  if ($act==='add') {
    $name = trim((string)($_POST['name'] ?? ''));
    $price_eur = str_replace(',', '.', (string)($_POST['price_eur'] ?? '0'));
    //$price_cents = (int)round(max(0,(float)$price_eur)*100); Solo positivi
    $price_cents = (int)round((float)$price_eur * 100);
    $vat = (int)($_POST['vat_percent'] ?? 22);

    if ($name!=='' && vat_valid($vat)) {
      $stmt = $conn->prepare("INSERT INTO products(terminal_id,name,price_cents,vat_percent) VALUES (?,?,?,?)");
      $stmt->bind_param('ssii',$tidPost,$name,$price_cents,$vat);
      @$stmt->execute(); $stmt->close();
    }
    header('Location: ?tid='.$tid); exit;
  }

  if ($act==='update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $price_eur = str_replace(',', '.', (string)($_POST['price_eur'] ?? '0'));
    //$price_cents = (int)round(max(0,(float)$price_eur)*100); solo positivi
    $price_cents = (int)round((float)$price_eur * 100);
    $vat = (int)($_POST['vat_percent'] ?? 22);
    if ($id>0 && $name!=='' && vat_valid($vat)) {
      $stmt = $conn->prepare("UPDATE products
                                SET name=?, price_cents=?, vat_percent=?
                                WHERE id=? AND terminal_id=?");
      $stmt->bind_param('siiis',$name,$price_cents,$vat,$id,$tidPost);
      @$stmt->execute(); $stmt->close();
    }
    header('Location: ?tid='.$tid); exit;
  }

  if ($act==='delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id>0) {
      $stmt = $conn->prepare("DELETE FROM products WHERE id=? AND terminal_id=?");
      $stmt->bind_param('is',$id,$tidPost);
      @$stmt->execute(); $stmt->close();
    }
    header('Location: ?tid='.$tid); exit;
  }
}


/* Carica Prodotti */
/* Carica Prodotti SOLO del terminale corrente */
$products = [];
//
//if (preg_match('/^\d{6,12}$/', $tid)) {
  $stmt = $conn->prepare("SELECT id, name, price_cents, vat_percent FROM products WHERE terminal_id=? ORDER BY name");
  $stmt->bind_param('s', $tid);
  $stmt->execute();
  $res = $stmt->get_result();
  while($row=$res->fetch_assoc()){ $products[]=$row; }
  $stmt->close();
//}


include 'header.php';
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestione Prodotti (cassa pos smart)</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{ --bg:#f6f8fb; --card:#fff; --muted:#64748b; --text:#1f2937; --ok:#10b981; --ko:#ef4444;
       --sat:#ed1c24; --cardpill:#2563eb; --vchpill:#8b5cf6; }
*{ box-sizing:border-box; }
body{ margin:0; background:var(--bg); font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color:var(--text);}
.container{ max-width:1280px; margin:0 auto; padding:16px;}
h1{ margin:0 0 6px; font-size:20px;}
.muted{ color:var(--muted); font-size:12px;}
.card{ background:var(--card); border:1px solid #e6eaf0; border-radius:14px; padding:16px;}
.row{ display:flex; gap:12px; flex-wrap:wrap;}
.grid-2 > *{ flex:1 1 360px; }
label{ font-size:12px; color:#374151; display:block; margin-bottom:4px;}
input, select{ width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;}
.btn{ display:inline-flex; align-items:center; gap:8px; background:#111827; color:#fff; border:none; border-radius:10px; padding:10px 14px; cursor:pointer; text-decoration:none;}
.btn.out{ background:#fff; color:#111827; border:1px solid #e5e7eb;}
.btn.small{ padding:6px 10px; border-radius:8px; font-size:12px;}
.toolbar{ display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;}
.right{ margin-left:auto; }
.kpi{ display:flex; gap:12px; flex-wrap:wrap;}
.kpi .box{ flex:1 1 180px; background:#fff; border:1px solid #e6eaf0; border-radius:12px; padding:12px;}
.kpi .label{ font-size:12px; color:var(--muted);}
.kpi .val{ font-weight:700; font-size:18px; }
.pill{ display:inline-block; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; color:#fff;}
.pill.card{ background:var(--cardpill); }
.pill.sat{ background:var(--sat); }
.pill.vch{ background:var(--vchpill); }
.badge{ padding:2px 6px; border-radius:6px; font-size:11px; font-weight:700; color:#fff;}
.badge.ok{ background:var(--ok); }
.badge.ko{ background:var(--ko); }
.badge.pending{ background:#f59e0b; }
table{ width:100%; border-collapse:collapse; }
th,td{ padding:10px 8px; border-bottom:1px solid #eef2f7; font-size:14px; vertical-align:top;}
th{ text-align:left; font-size:12px; letter-spacing:.04em; color:#6b7280; text-transform:uppercase;}
tr:hover{ background:#fafbff; }
.amount{ text-align:right; font-variant-numeric: tabular-nums; white-space:nowrap;}
.sub{ color:var(--muted); font-size:12px;}
.detail{ display:none; background:#fcfcff;}
.detail td{ border-bottom:none; }
.totals-head{ display:flex; align-items:center; gap:10px; cursor:pointer; }
.totals-sub{ display:none; margin-top:8px; }
.chip{ background:#e5e7eb; border-radius:999px; padding:6px 10px; font-size:12px;}
.warning{ color:#b45309; }
.footer{ display:flex; gap:8px; align-items:center; justify-content:space-between; margin-top:12px;}
.nowrap{ white-space:nowrap; }
.chartwrap { position:relative; height:280px; }
.chartwrap.sm { height:220px; }

/* Tabs */
.tabs{ display:flex; border-bottom:1px solid #e6eaf0; margin-bottom:12px; }
.tab-btn{
  appearance:none; background:transparent; border:none; padding:10px 14px; cursor:pointer; font-weight:600;
  color:#374151; border-bottom:3px solid transparent; margin-bottom:-1px; border-radius:8px 8px 0 0;
}
.tab-btn.active{ color:#111827; border-color:#111827; background:#fff; }
.tab-pane{ display:none; }
.tab-pane.active{ display:block; }
</style>
</head>
<body>
<div class="container">

  <h2>Gestione Prodotti (cassa pos smart)</h2>
  <div class="muted">TID <strong><?=h($tid)?></strong></div>

  <!-- PRODOTTI -->

    <div class="card">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <h3 style="margin:0; font-size:16px;">Articoli (ProductItem)</h3>
        <span class="muted">prezzi lordi in € (salvati in centesimi), IVA 0/4/5/10/22</span>
      </div>

      <!-- Aggiungi -->
      <form method="post" class="row" style="margin-top:10px; gap:10px; align-items:flex-end;">
        <input type="hidden" name="_product_action" value="add">
        <input type="hidden" name="_csrf" value="<?=h($_SESSION['csrf'])?>">
        <div style="flex:1 1 260px;">
          <label>Nome</label>
          <input name="name" required placeholder="Es. Caffè espresso">
        </div>
        <div style="flex:0 0 160px;">
          <label>Prezzo (€)</label>
          <input name="price_eur" type="number" step="0.01"  inputmode="decimal" placeholder="1.50" required>
        </div>
        <div style="flex:0 0 140px;">
          <label>IVA (%)</label>
          <select name="vat_percent" required>
            <?php foreach ([0,4,5,10,22] as $v): ?>
              <option value="<?=$v?>"><?=$v?>%</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="flex:0 0 auto;">
          <button class="btn" type="submit">Aggiungi</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:12px;">
      <table>
        <thead>
          <tr>
            <th>Articolo</th>
            <th class="amount">Prezzo (€)</th>
            <th>IVA</th>
            <th style="width:1%"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$products): ?>
            <tr><td colspan="4" class="muted">Nessun articolo presente.</td></tr>
          <?php else: foreach ($products as $p): ?>
            <tr>
              <td>
                <form method="post" class="row" style="gap:6px; align-items:center;">
                  <input type="hidden" name="_product_action" value="update">
                  <input type="hidden" name="_csrf" value="<?=h($_SESSION['csrf'])?>">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <input name="name" value="<?=h($p['name'])?>" required style="width:280px; padding:8px; border:1px solid #e5e7eb; border-radius:8px;">
              </td>
              <td class="amount">
                  <input name="price_eur"
                         value="<?= number_format(((int)$p['price_cents'])/100,2, '.', '') ?>"
                         type="number" step="0.01"  inputmode="decimal"
                         style="width:120px; text-align:right; padding:8px; border:1px solid #e5e7eb; border-radius:8px;">
              </td>
              <td>
                <select name="vat_percent" style="padding:8px; border:1px solid #e5e7eb; border-radius:8px;">
                  <?php foreach ([0,4,5,10,22] as $v): ?>
                    <option value="<?=$v?>" <?=$p['vat_percent']==$v?'selected':''?>><?=$v?>%</option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="white-space:nowrap;">
                  <button class="btn small" type="submit">Salva</button>
                </form>
                <form method="post" style="display:inline;" onsubmit="return confirm('Rimuovere questo articolo?')">
                  <input type="hidden" name="_product_action" value="delete">
                  <input type="hidden" name="_csrf" value="<?=h($_SESSION['csrf'])?>">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn out small" type="submit">Rimuovi</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <div class="muted" style="margin-top:8px;">Nota: l’input è in euro.</div>
    </div>
  </div>

</div>

</body>
</html>
<?php include 'footer.php'; ?>
