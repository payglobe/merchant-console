<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /merchant/login.php");
    exit();
}
include '../header.php';
?>


<body class="bg-light">
<div class="container py-4">
  <h1 class="mb-3">Onboarding Satispay</h1>
  <p class="text-muted mb-4">I campi contrassegnati con <span class="text-danger">*</span> sono obbligatori.</p>

  <form id="onboardingForm" class="needs-validation" novalidate method="post" action="/onboarding/merchants">
    <!-- Metadati -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Metadati</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label required">Source Internal Code</label>
            <input type="text" name="source_internal_code" class="form-control" required>
            <div class="invalid-feedback">Obbligatorio (univoco per idempotenza).</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Referral ID</label>
            <input type="text" name="referral_id" class="form-control" maxlength="32">
          </div>
        </div>
      </div>
    </div>

    <!-- Dati azienda -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Dati azienda</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label required">Ragione sociale</label>
            <input type="text" name="company_name" class="form-control" required>
            <div class="invalid-feedback">Inserisci la ragione sociale.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">Forma giuridica</label>
            <select name="legal_form" class="form-select" required>
              <option value="">Seleziona…</option>
              <option>AIMP</option><option>ASPR</option><option>COOP</option><option>DI</option>
              <option>ENR</option><option>ENT</option><option>ENTE</option><option>NOIVA</option>
              <option>PA</option><option>SAPA</option><option>SAS</option><option>SNC</option>
              <option>SPA</option><option>SRL</option><option>SRLS</option><option>SS</option>
            </select>
            <div class="invalid-feedback">Seleziona la forma giuridica.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">Partita IVA</label>
            <input type="text" name="vat_code" class="form-control" required>
            <div class="invalid-feedback">Inserisci una P.IVA valida.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Email</label>
            <input type="email" name="email" class="form-control" required>
            <div class="invalid-feedback">Email non valida.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">MCC</label>
            <input type="text" name="mcc_code" pattern="^[0-9]{4}$" class="form-control" required>
            <div class="invalid-feedback">Inserisci un MCC a 4 cifre.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">IBAN</label>
            <input type="text" name="iban" class="form-control" required>
            <div class="invalid-feedback">IBAN obbligatorio.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label required">Mobile</label>
            <input type="tel" name="mobile_number" class="form-control" required>
            <div class="invalid-feedback">Inserisci il numero mobile.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Telefono fisso</label>
            <input type="tel" name="landline_number" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Data fondazione</label>
            <input type="datetime-local" name="foundation_date" class="form-control">
            <div class="form-text">Verrà convertita in UTC ISO8601.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Indirizzo azienda -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Indirizzo azienda</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label required">Indirizzo</label>
            <input type="text" name="address[address]" class="form-control" required>
            <div class="invalid-feedback">Indirizzo obbligatorio.</div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Civico</label>
            <input type="text" name="address[address_number]" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label required">Città</label>
            <input type="text" name="address[city]" class="form-control" required>
            <div class="invalid-feedback">Città obbligatoria.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">Provincia</label>
            <input type="text" name="address[district]" class="form-control" required>
            <div class="invalid-feedback">Provincia obbligatoria.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">CAP</label>
            <input type="text" name="address[zip_code]" class="form-control" required>
            <div class="invalid-feedback">CAP obbligatorio.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label required">Nazione (2 char)</label>
            <input type="text" name="address[country]" class="form-control" value="IT" maxlength="2" required>
            <div class="invalid-feedback">Inserisci ISO2 (es. IT).</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Privacy -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Privacy</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label required">Tipo consenso</label>
            <input type="text" name="privacy_consent[type]" class="form-control" required>
            <div class="invalid-feedback">Campo obbligatorio.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Versione</label>
            <input type="text" name="privacy_consent[version]" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label required">Data accettazione</label>
            <input type="datetime-local" name="privacy_consent[acceptance_date]" class="form-control" required>
            <div class="invalid-feedback">Obbligatoria.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Due diligence (opzionali) -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Due diligence</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Prima due diligence</label>
            <input type="datetime-local" name="first_due_diligence_date" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Ultima due diligence</label>
            <input type="datetime-local" name="last_due_diligence_date" class="form-control">
          </div>
        </div>
      </div>
    </div>

    <!-- Persone (almeno 1 LEGAL_REPRESENTATIVE obbligatoria) -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="form-section-title mb-0">Persone</h5>
          <button class="btn btn-sm btn-outline-primary" type="button" id="addPersonBtn">Aggiungi persona</button>
        </div>

        <div id="peopleContainer">
          <!-- Persona #1 -->
          <div class="border rounded p-3 mt-3 person-block">
            <div class="d-flex justify-content-between">
              <h6 class="mb-3">Persona</h6>
              <button class="btn btn-sm btn-outline-danger d-none remove-person" type="button">Rimuovi</button>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label required">Ruolo</label>
                <select name="people_list[0][role]" class="form-select person-role" required>
                  <option value="">Seleziona…</option>
                  <option value="LEGAL_REPRESENTATIVE">LEGAL_REPRESENTATIVE</option>
                  <option value="BENEFICIAL_OWNER">BENEFICIAL_OWNER</option>
                </select>
                <div class="invalid-feedback">Obbligatorio.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label required">Nome</label>
                <input type="text" name="people_list[0][name]" class="form-control" required>
                <div class="invalid-feedback">Obbligatorio.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label required">Cognome</label>
                <input type="text" name="people_list[0][surname]" class="form-control" required>
                <div class="invalid-feedback">Obbligatorio.</div>
              </div>

              <div class="col-md-2">
                <label class="form-label required">Cittadinanza (2)</label>
                <input type="text" name="people_list[0][citizenship]" class="form-control" maxlength="2" required>
              </div>
              <div class="col-md-4">
                <label class="form-label required">Codice fiscale</label>
                <input type="text" name="people_list[0][tax_code]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Data nascita</label>
                <input type="datetime-local" name="people_list[0][birth_date]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Luogo di nascita</label>
                <input type="text" name="people_list[0][birth_place]" class="form-control" required>
              </div>

              <div class="col-md-3">
                <label class="form-label required">Paese nascita (2)</label>
                <input type="text" name="people_list[0][birth_country]" class="form-control" maxlength="2" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Genere (M/F)</label>
                <input type="text" name="people_list[0][gender]" class="form-control" maxlength="1" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Mobile</label>
                <input type="tel" name="people_list[0][mobile_phone_number]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Email</label>
                <input type="email" name="people_list[0][email]" class="form-control" required>
              </div>

              <div class="col-12">
                <label class="form-label required">Residenza - Indirizzo</label>
                <input type="text" name="people_list[0][address][address]" class="form-control" required>
              </div>
              <div class="col-md-2">
                <label class="form-label">Civico</label>
                <input type="text" name="people_list[0][address][address_number]" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label required">Città</label>
                <input type="text" name="people_list[0][address][city]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Provincia</label>
                <input type="text" name="people_list[0][address][district]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">CAP</label>
                <input type="text" name="people_list[0][address][zip_code]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Nazione (2)</label>
                <input type="text" name="people_list[0][address][country]" class="form-control" maxlength="2" required>
              </div>

              <div class="col-md-3">
                <label class="form-label required">Tipo documento</label>
                <select name="people_list[0][id_type]" class="form-select" required>
                  <option value="">Seleziona…</option>
                  <option value="IDENTITY_CARD">IDENTITY_CARD</option>
                  <option value="DRIVING_LICENSE">DRIVING_LICENSE</option>
                  <option value="PASSPORT">PASSPORT</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Numero documento</label>
                <input type="text" name="people_list[0][id_number]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Rilasciato da</label>
                <input type="text" name="people_list[0][id_issuer]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Città rilascio</label>
                <input type="text" name="people_list[0][id_release_city]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Paese rilascio (2)</label>
                <input type="text" name="people_list[0][id_release_country]" class="form-control" maxlength="2" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Data rilascio</label>
                <input type="datetime-local" name="people_list[0][id_release_date]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label required">Scadenza</label>
                <input type="datetime-local" name="people_list[0][id_expiration_date]" class="form-control" required>
              </div>
            </div>
          </div>
          <!-- /Persona #1 -->
        </div>
      </div>
    </div>

    <!-- Extra -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Extra</h5>
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Informazioni aggiuntive (max 200)</label>
            <textarea name="additional_info" class="form-control" maxlength="200" rows="2"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Ambiente -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="form-section-title">Ambiente</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label required">Ambiente</label>
            <select name="env" class="form-select" required>
              <option value="sandbox">Sandbox</option>
              <option value="production">Production</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Invia registrazione</button>
      <button class="btn btn-outline-secondary" type="reset">Reset</button>
    </div>
  </form>
</div>

<script>
(function () {
  'use strict';

  // Bootstrap validation
  const form = document.getElementById('onboardingForm');
  form.addEventListener('submit', function (event) {
    // Verifica almeno 1 LEGAL_REPRESENTATIVE
    const roles = form.querySelectorAll('.person-block select.person-role');
    let hasLR = false;
    roles.forEach(r => { if (r.value === 'LEGAL_REPRESENTATIVE') hasLR = true; });
    if (!hasLR) {
      alert('È richiesta almeno una persona con ruolo LEGAL_REPRESENTATIVE.');
      event.preventDefault(); event.stopPropagation();
      return;
    }

    if (!form.checkValidity()) {
      event.preventDefault(); event.stopPropagation();
    } else {
      // Converti tutti i datetime-local in ISO8601 UTC (Z)
      const dtInputs = form.querySelectorAll('input[type="datetime-local"]');
      dtInputs.forEach(inp => {
        if (inp.value) {
          const local = new Date(inp.value);
          const iso = new Date(local.getTime() - (local.getTimezoneOffset()*60000)).toISOString();
          // crea un hidden con lo stesso name e sostituisci
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = inp.name;
          hidden.value = iso;
          inp.parentNode.appendChild(hidden);
          inp.disabled = true; // evita doppio invio
        }
      });
    }
    form.classList.add('was-validated');
  }, false);

  // Aggiungi persona dinamicamente
  const addBtn = document.getElementById('addPersonBtn');
  const peopleContainer = document.getElementById('peopleContainer');

  addBtn.addEventListener('click', () => {
    const count = peopleContainer.querySelectorAll('.person-block').length;
    const idx = count; // nuovo indice
    const tpl = peopleContainer.querySelector('.person-block');
    const clone = tpl.cloneNode(true);
    // Aggiorna nomi dei campi
    clone.querySelectorAll('[name]').forEach(el => {
      el.name = el.name.replace(/\[\d+\]/, `[${idx}]`);
      if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.value = '';
      if (el.tagName === 'SELECT') el.selectedIndex = 0;
      el.disabled = false;
    });
    // Mostra bottone rimozione
    clone.querySelector('.remove-person').classList.remove('d-none');
    clone.querySelector('.remove-person').addEventListener('click', () => {
      clone.remove();
    });
    peopleContainer.appendChild(clone);
  });
})();
</script>
</body>
<?php include '../footer.php'; ?>
