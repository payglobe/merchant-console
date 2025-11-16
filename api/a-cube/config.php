<?php
return [
  'AUTH_MODE'           => 'login_json',
  'ACUBE_BASE_URL'      => 'https://api-sandbox.acubeapi.com',

  // Login endpoint corretto (sandbox)
  'ACUBE_AUTH_URL'      => 'https://api-sandbox.acubeapi.com/login',
  'ACUBE_USERNAME'      => 'marco.costanzo@eupayglobe.com',   // email
  'ACUBE_PASSWORD'      => 'wjTll0$1l6Ms',

  // mapping campi della risposta di login
  'LOGIN_JSON_TOKEN_FIELD'   => 'token',
  'LOGIN_JSON_EXPIRES_FIELD' => null, // se in futuro la risposta include expires_in, metti il nome del campo

  // sicurezza app → wrapper
  'APP_SHARED_SECRET'   => 'JH/SJSdjsjf&hbjas,.mafoi0',

  // opzionale: allowlist fiscal_id
  // 'ALLOWED_FISCAL_IDS' => ['09931100961'],
    // ====== DB (MySQL) ======
  // Esempio DSN: mysql:host=localhost;dbname=serverpos;charset=utf8mb4
  'DB_DSN'   => 'mysql:host=10.10.10.13;dbname=serverpos;charset=utf8mb4',
  'DB_USER'  => 'PGDBUSER',
  'DB_PASS'  => 'PNeNkar{K1.%D~V',

  // Abilita/disabilita il salvataggio ricevute in DB
  'SAVE_RECEIPTS' => true,

  // Tabella dove salvare (db.table)
  'RECEIPTS_TABLE' => 'serverpos.receipts',
];
