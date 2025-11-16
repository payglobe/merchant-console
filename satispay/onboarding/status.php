// =========================================
// 6) onboarding/status.php – HTML UI with polling
// =========================================


<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Stato registrazione Satispay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .step {opacity:.5}
    .step.active {opacity:1; font-weight:600}
    .step.complete {opacity:1; color:#198754}
  </style>
</head>
<body class="bg-light">
<?php
$registrationId = $_GET['registration_id'] ?? '';
if (!$registrationId) { echo '<div class="container py-5"><div class="alert alert-danger">registration_id mancante</div></div>'; exit; }
?>
<div class="container py-4">
  <h1 class="mb-3">Stato registrazione</h1>
  <p class="text-muted">Registration ID: <code id="rid"><?php echo htmlspecialchars($registrationId, ENT_QUOTES); ?></code></p>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-auto"><div class="spinner-border" role="status" id="spinner"><span class="visually-hidden">Loading...</span></div></div>
        <div class="col"><div id="statusText" class="fs-5">Caricamento…</div></div>
      </div>
      <div class="progress mt-3" role="progressbar" aria-label="Progress" style="height:8px">
        <div id="bar" class="progress-bar" style="width:0%"></div>
      </div>
      <ul class="list-inline mt-3 mb-0">
        <li class="list-inline-item step" id="s1">EMAIL_VALIDATION</li>
        <li class="list-inline-item">→</li>
        <li class="list-inline-item step" id="s2">TERMS_PENDING</li>
        <li class="list-inline-item">→</li>
        <li class="list-inline-item step" id="s3">DATA_VALIDATION</li>
        <li class="list-inline-item">→</li>
        <li class="list-inline-item step" id="s4">ACCOUNT_OPENING</li>
        <li class="list-inline-item">→</li>
        <li class="list-inline-item step" id="s5">COMPLETE</li>
      </ul>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Dettagli</h5>
      <div id="details" class="small text-muted">–</div>
      <div id="errorBox" class="alert alert-danger d-none mt-3"></div>
    </div>
  </div>
</div>

<script>
const rid = document.getElementById('rid').textContent;
const statusToPct = {
  'EMAIL_VALIDATION': 10,
  'TERMS_PENDING': 25,
  'DATA_VALIDATION': 60,
  'ACCOUNT_OPENING': 85,
  'COMPLETE': 100,
  'NOT_VALID': 100,
  'FAILED': 100
};
const finals = ['COMPLETE','NOT_VALID','FAILED'];

function updateSteps(st){
  const order = ['EMAIL_VALIDATION','TERMS_PENDING','DATA_VALIDATION','ACCOUNT_OPENING','COMPLETE'];
  order.forEach((k,i)=>{
    const el = document.getElementById('s'+(i+1));
    el.classList.remove('active','complete');
    if (st === k) el.classList.add('active');
    if (order.indexOf(st) > i) el.classList.add('complete');
  });
}

async function poll(){
  const r = await fetch('/onboarding/status_api.php?registration_id='+encodeURIComponent(rid));
  if(!r.ok){ document.getElementById('errorBox').classList.remove('d-none'); document.getElementById('errorBox').textContent='Errore di polling'; return; }
  const j = await r.json();
  if(!j.ok){ document.getElementById('errorBox').classList.remove('d-none'); document.getElementById('errorBox').textContent=j.error||'Errore'; return; }

  const d = j.data;
  const st = d.status;
  document.getElementById('statusText').textContent = 'Stato: ' + st + (d.merchant_id ? ' • Merchant ID: '+ d.merchant_id : '');
  document.getElementById('bar').style.width = (statusToPct[st]||0) + '%';
  updateSteps(st);
  document.getElementById('spinner').style.display = finals.includes(st) ? 'none' : 'inline-block';

  const det = `Azienda: ${d.company_name || '-'}<br>Email: ${d.email || '-'}<br>IBAN: ${d.iban || '-'}<br>Ambiente: ${d.env || '-'}<br>Inserito: ${d.sat_insert_date || '-'}<br>Aggiornato: ${d.sat_update_date || '-'}`;
  document.getElementById('details').innerHTML = det;

  const errBox = document.getElementById('errorBox');
  if (d.last_error_message){ errBox.textContent = d.last_error_code + ': ' + d.last_error_message; errBox.classList.remove('d-none'); } else { errBox.classList.add('d-none'); }

  if (!finals.includes(st)) setTimeout(poll, 4000);
}

poll();
</script>
</body>
</html>