# Analisi Directory API e Satispay

## Sommario Esecutivo

Le directory `api/` e `satispay/` sono **componenti della vecchia console merchant basata su PHP**. Il nuovo backend Spring Boot **NON le utilizza** - ha implementato le proprie API REST in Java.

---

## Directory `api/`

### Struttura
```
api/
├── .htaccess                      # Configurazione Apache
├── index.php                      # REST API Stores (CRUD negozi)
├── receipts.php                   # Gestione ricevute
├── config.php                     # Configurazione database
├── acube.tar                      # Archive A-Cube
├── a-cube/                        # Integrazione A-Cube (ricevute elettroniche)
│   ├── index.php
│   ├── receipts.php
│   ├── config.php
│   ├── .htaccess
│   ├── src/
│   │   ├── AcubeClient.php       # Client HTTP per API A-Cube
│   │   ├── Db.php                # Database helper
│   │   ├── ReceiptStore.php      # Storage ricevute
│   │   └── TokenProvider.php     # Gestione token autenticazione
│   └── _cache/
│       └── acube_token.json      # Cache token A-Cube
└── terminal/
    └── config.php                # Configurazione terminali (fornisce config JSON per attivazione)
```

### Funzionalità Principali

#### 1. **Stores REST API** (`api/index.php`)
**Scopo**: API RESTful per gestione CRUD dei negozi (tabella `stores`)

**Endpoints**:
- `GET /merchant/api/stores` - Lista negozi con paginazione e filtri
- `GET /merchant/api/stores/{TerminalID}` - Dettaglio singolo negozio
- `POST /merchant/api/stores` - Creazione nuovo negozio
- `PUT /merchant/api/stores/{TerminalID}` - Aggiornamento completo
- `PATCH /merchant/api/stores/{TerminalID}` - Aggiornamento parziale
- `DELETE /merchant/api/stores/{TerminalID}` - Eliminazione negozio

**Autenticazione**:
- Basic Auth (username/password hardcoded)
  ```
  'admin' => 'password123'
  'moneynet' => 'demo123'
  ```
- Bearer Token (JWT con HS256)
  - Secret: `ASJHC/Snaks67l2wd9j2djkdcx3123SSDFFaqc`

**Caratteristiche**:
- Connessione diretta al database MySQL (10.10.10.13)
- CORS abilitato (`Access-Control-Allow-Origin: *`)
- Supporta filtri: `citta`, `prov`, `country`, `bu`, `q` (ricerca full-text)
- Paginazione: `page`, `page_size` (max 200 record/pagina)

**Campi gestiti**:
- TerminalID (chiave primaria)
- Ragione_Sociale, Insegna
- Indirizzo, citta, cap, prov, country
- sia_pagobancomat, six, amex
- Modello_pos, bu, bu1, bu2

**⚠️ Security Issues**:
- Credenziali hardcoded nel codice
- Password in chiaro
- Database password nel file
- JWT secret statico

#### 2. **A-Cube Integration** (`api/a-cube/`)
**Scopo**: Integrazione con A-Cube per gestione ricevute elettroniche/scontrini

**Componenti**:
- `AcubeClient.php`: Client HTTP per chiamate alle API A-Cube
- `TokenProvider.php`: Gestione autenticazione con cache
- `ReceiptStore.php`: Archiviazione ricevute in database
- `Db.php`: Helper connessione database

**Endpoint**: `/merchant/api/a-cube/receipts`
- Accetta POST con ricevute
- Autentica con A-Cube usando token cached
- Memorizza ricevute nel database PayGlobe

**Features**:
- Token caching in `_cache/acube_token.json` per evitare ri-autenticazioni
- CORS enabled
- Headers supportati: `X-Idempotency-Key`, `X-App-Signature`

#### 3. **Terminal Config API** (`api/terminal/config.php`)
**Scopo**: Fornisce configurazione JSON per terminali in fase di attivazione

**Funzionamento**:
1. Riceve `activationCode` via GET
2. Verifica il codice nella tabella `activation_codes`
3. Recupera configurazione da `terminal_config`
4. Genera JSON con:
   - Configurazioni circuiti (Bancomat, AMEX, PagoBancomat, etc.)
   - Gateway URL
   - Configurazione Satispay
   - Info negozio/terminale

**Output Example**:
```json
{
  "terminalId": "T12345",
  "gateway": "https://api.payglobe.it",
  "circuits": {
    "bancomat": true,
    "amex": false,
    "pagoBancomat": true
  },
  "satispayConfig": {
    "store": "STORE12345",
    "environment": "SANDBOX"
  }
}
```

**Usato da**: Frontend dashboard per configurare nuovi terminali

---

## Directory `satispay/`

### Struttura
```
satispay/
├── config.php                    # Configurazione (DB, Satispay keys, endpoints)
├── db.php                        # Database helper
├── satispay_client.php           # Client HTTP Signature per API Satispay
├── activate_store.php            # Attivazione store con token Satispay
├── onboarding.php                # Form HTML onboarding merchant
├── onboarding/
│   ├── merchant.php              # Backend POST /merchants a Satispay
│   ├── status.php                # Pagina HTML status onboarding
│   └── status_api.php            # API JSON per polling status
└── cron/
    └── poll_open_registrations.php  # CRON job polling status da Satispay
```

### Funzionalità Principali

#### 1. **Onboarding Merchant Satispay** (`satispay/onboarding.php`)
**Scopo**: Form HTML per registrare nuovi merchant su Satispay

**Workflow**:
1. Form raccoglie dati merchant:
   - Dati azienda (ragione sociale, P.IVA, MCC, IBAN)
   - Indirizzo sede legale
   - Persone (almeno 1 LEGAL_REPRESENTATIVE obbligatorio)
   - Privacy consent
   - Due diligence dates
   - Ambiente (SANDBOX/PRODUCTION)

2. Submit → `onboarding/merchant.php`
3. Validazione e conversione datetime in ISO8601 UTC
4. POST a Satispay API `/g_provider/v1/merchants`
5. Salvataggio in `satispay_merchant_registration`

**Campi chiave persona**:
- Ruolo: LEGAL_REPRESENTATIVE | BENEFICIAL_OWNER
- Dati anagrafici completi
- Documento identità
- Residenza

**Tabella database**: `satispay_merchant_registration`

#### 2. **Satispay Client** (`satispay/satispay_client.php`)
**Scopo**: Client HTTP con firma digitale per API Satispay Provider

**Metodi**:
- `createMerchant(array $payload)`: POST /g_provider/v1/merchants
- `getRegistration(string $registrationId)`: GET /g_provider/v1/merchants/{id}

**Autenticazione**: HTTP Signature (RFC standard)
- Headers firmati: `(request-target)`, `host`, `date`, `digest`
- Algoritmo: RSA-SHA256
- Chiave privata da config

**Signature Process**:
1. Calcola SHA-256 digest del body
2. Costruisce signing string con headers
3. Firma con chiave privata RSA
4. Invia con header `Authorization: Signature keyId="...",algorithm="rsa-sha256",...`

**Ambienti**:
- SANDBOX: `staging.authservices.satispay.com`
- PRODUCTION: `authservices.satispay.com`

#### 3. **Store Activation** (`satispay/activate_store.php`)
**Scopo**: Attiva uno store usando il token dalla Satispay Dashboard

**Workflow**:
1. Riceve POST con:
   - `store`: Store ID
   - `token`: Activation token da Satispay Dashboard
   - `env`: SANDBOX | PROD

2. Chiama `SatispayGBusiness\Api::authenticateWithToken($token)`
3. Ottiene chiavi (publicKey, privateKey, keyId)
4. Salva in `KeyStore::upsertKeys()`

**Usato da**:
- `terminal_config_editor.php` via JavaScript fetch
- `satispay_activation_handler.php` (wrapper con validazione session)

**Tabella**: Probabilmente `satispay_keys` o simile (non visibile nel codice analizzato)

#### 4. **Status Polling** (`satispay/cron/poll_open_registrations.php`)
**Scopo**: CRON job che aggiorna lo stato delle registrazioni in corso

**Funzionamento**:
```sql
SELECT registration_id, env
FROM satispay_merchant_registration
WHERE status NOT IN ('COMPLETE','NOT_VALID','FAILED')
  AND registration_id IS NOT NULL
```

Per ogni registrazione:
1. Chiama `SatispayClient::getRegistration($registrationId)`
2. Aggiorna stato e `merchant_id` in DB
3. Salva evento in `satispay_onboarding_event`
4. Gestisce errori con logging

**Stati possibili**:
- PENDING
- PROCESSING
- COMPLETE
- NOT_VALID
- FAILED

**Frequenza consigliata**: Ogni 5-15 minuti

#### 5. **Configuration** (`satispay/config.php`)
**Scopo**: Configurazione centralizzata

**Costanti**:
```php
const SATISPAY_KEY_ID = '...';
const SATISPAY_PRIVATE_KEY_PEM_PATH = '/path/to/key.pem';
const DEFAULT_ENV = 'sandbox';
const HTTP_TIMEOUT_SEC = 30;
```

**Database Tables** (prefisso `satispay_`):
- `satispay_merchant_registration`: Registrazioni merchant
- `satispay_onboarding_event`: Eventi audit trail
- Altre tabelle per chiavi, store, etc.

---

## Chi Usa Queste API?

### ✅ **Vecchia Console PHP** (Alpine.js Dashboard)

#### API Stores (`api/index.php`)
**NON trovato utilizzo diretto** nella dashboard Alpine.js analizzata.

**Probabile utilizzo**:
- Script esterni di terze parti
- Integrazioni MoneyNet o partner
- Tool amministrativi non inclusi nel repository

**Evidenza**:
- Presenza di user `moneynet` nell'auth
- Commenti con esempi `curl` per partner esterni

#### Terminal Config API (`api/terminal/config.php`)
**USATO da**: `frontend/dashboard/index.php` linea 1070

```html
<a :href="'https://ricevute.payglobe.it/merchant/api/terminal/config.php?activationCode=' + code.code"
   class="btn btn-sm btn-info" target="_blank">
    <i class="fas fa-download"></i> Scarica Config
</a>
```

**Funzione**: Link per scaricare configurazione JSON terminale dopo attivazione

#### A-Cube Integration (`api/a-cube/`)
**USO**: Integrazione esterna, non chiamata direttamente dalla console

**Evidenza**:
- Presenza di cache token
- Endpoint configurato ma non referenziato nel frontend
- Probabilmente chiamato da terminali POS/casse

#### Satispay Integration (`satispay/`)
**USATO da**:
- `terminal_config_editor.php` (linee 622-1080)
  - Form attivazione Satispay
  - Modal con campi token/environment
  - JavaScript fetch a `satispay_activation_handler.php`

- `satispay_activation_handler.php`
  - Wrapper con validazione sessione
  - Chiama `satispay/activate_store.php`
  - Salva config in `terminal_config`

**Workflow attivazione completo**:
```
1. User apre Terminal Config Editor
2. Click "Attiva Satispay" → Modal
3. Inserisce token da Satispay Dashboard
4. JavaScript POST a satispay_activation_handler.php
5. Handler valida sessione + terminalId
6. cURL POST a satispay/activate_store.php
7. activate_store chiama SatispayGBusiness\Api
8. Salva chiavi in KeyStore
9. Salva flag in terminal_config (satispay_activated=1)
10. Frontend mostra successo/errore
```

### ❌ **Nuovo Backend Spring Boot**

**NESSUN UTILIZZO** di `api/` o `satispay/`

**Evidenza**:
```bash
grep -r "api/stores|satispay" spring-boot-backend/
# No matches found
```

Il backend Spring Boot ha implementato:
- Proprie API REST (`/api/v2/*`)
- Propria autenticazione JWT
- Propri controller e service layer
- Non dipende dalle vecchie API PHP

---

## Relazione tra Vecchia e Nuova Console

### Architettura

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────────────┐  ┌─────────────────────────┐ │
│  │ Vecchia Console (Alpine) │  │ Nuova Console (React)   │ │
│  │ /frontend/dashboard/     │  │ /opt/merchant-console/  │ │
│  │                          │  │      frontend/          │ │
│  └─────────┬────────────────┘  └──────────┬──────────────┘ │
│            │                              │                │
└────────────┼──────────────────────────────┼────────────────┘
             │                              │
             │                              │
┌────────────┼──────────────────────────────┼────────────────┐
│            │      BACKEND LAYER           │                │
├────────────┼──────────────────────────────┼────────────────┤
│            ▼                              ▼                │
│  ┌──────────────────────┐    ┌─────────────────────────┐  │
│  │ PHP API Legacy       │    │ Spring Boot Backend     │  │
│  │ - api/index.php      │    │ merchant-api :8082      │  │
│  │ - api/a-cube/        │    │ /api/v2/*               │  │
│  │ - satispay/*         │    │                         │  │
│  │ - terminal_config    │    │ - REST Controllers      │  │
│  │                      │    │ - JWT Auth              │  │
│  │ (SOLO per vecchia)   │    │ - JPA/Hibernate         │  │
│  └──────────┬───────────┘    └────────┬────────────────┘  │
│             │                         │                   │
└─────────────┼─────────────────────────┼───────────────────┘
              │                         │
              ▼                         ▼
        ┌─────────────────────────────────────┐
        │     MySQL Database 10.10.10.13      │
        │     Database: payglobe              │
        └─────────────────────────────────────┘
```

### Separazione delle Responsabilità

| Funzionalità | Vecchia Console (PHP) | Nuova Console (Spring Boot) |
|--------------|----------------------|----------------------------|
| **CRUD Stores** | `api/index.php` | `StoreController.java` |
| **CRUD Transactions** | PHP legacy (non in api/) | `TransactionController.java` |
| **CRUD Users** | PHP legacy | `UserManagementController.java` |
| **Autenticazione** | Session PHP + Basic/JWT | JWT Token Provider |
| **Satispay Activation** | `satispay/*` + handler | ❌ NON IMPLEMENTATO |
| **Terminal Config** | `api/terminal/config.php` | ❌ NON IMPLEMENTATO |
| **A-Cube Receipts** | `api/a-cube/` | ❌ NON IMPLEMENTATO |
| **BIN Table** | ❌ | `BinTableController.java` |
| **Statistics** | PHP legacy | `AdminController.java` |
| **Activation Codes** | PHP + DB diretta | `ActivationCodeController.java` |

### Migrazione in Corso

**Cosa è stato migrato**:
- ✅ CRUD transazioni
- ✅ CRUD utenti merchant
- ✅ Statistiche dashboard
- ✅ Upload BIN table (con async)
- ✅ Autenticazione JWT
- ✅ Activation codes (parziale)

**Cosa rimane in PHP**:
- ⚠️ Satispay onboarding e activation
- ⚠️ Terminal configuration API
- ⚠️ A-Cube receipts integration
- ⚠️ Stores REST API esterna

---

## Raccomandazioni

### Per la Migrazione Completa

1. **Satispay Integration**
   - Creare `SatispayController` in Spring Boot
   - Implementare HTTP Signature authentication
   - Migrare logica da `satispay_client.php` a Java
   - Usare librerie esistenti (es. `tomitribe/http-signatures-java`)

2. **Terminal Configuration**
   - Endpoint `/api/v2/terminal/config?activationCode=XXX`
   - Generazione JSON configuration
   - Validazione activation code

3. **A-Cube Integration**
   - Valutare se ancora necessaria
   - Se sì, REST client in Spring Boot
   - Altrimenti deprecare

4. **Stores API Esterna**
   - Se usata da partner: mantenere o proxy tramite Spring Boot
   - Se inutilizzata: deprecare
   - Aggiungere autenticazione API key seria

### Per la Sicurezza

**⚠️ URGENTE**:
1. **Rimuovere credenziali hardcoded** da `api/index.php`
2. Spostare password database in environment variables
3. Sostituire Basic Auth con OAuth2 o API Keys
4. Abilitare HTTPS obbligatorio
5. Rimuovere CORS `*` e limitare a domini consentiti
6. Aggiungere rate limiting

### Per la Documentazione

1. Aggiornare README con stato migrazione
2. Documentare endpoint legacy ancora attivi
3. Creare migration plan per deprecare PHP APIs
4. API Documentation per endpoint Spring Boot

---

## Conclusioni

### Stato Attuale

| Componente | Stato | Usato da | Azione Raccomandata |
|------------|-------|----------|---------------------|
| `api/index.php` (Stores) | 🟡 Attivo | Partner esterni? | Verificare uso, poi deprecare |
| `api/a-cube/` | 🟡 Attivo | Terminali POS | Verificare necessità |
| `api/terminal/config.php` | 🟢 Attivo | Dashboard vecchia | Migrare a Spring Boot |
| `satispay/*` | 🟢 Attivo | Dashboard vecchia | Migrare a Spring Boot (priorità) |

### Prossimi Passi

1. **Fase 1 - Analisi** ✅ COMPLETATA
   - Mappatura completa API legacy
   - Identificazione dipendenze

2. **Fase 2 - Migrazione Satispay** (Raccomandato)
   - Creare SatispayController Spring Boot
   - Testare onboarding in SANDBOX
   - Deploy parallelo (vecchio + nuovo)
   - Switch graduale

3. **Fase 3 - Terminal Config Migration**
   - Endpoint Spring Boot per config JSON
   - Aggiornare frontend per usare nuovo endpoint

4. **Fase 4 - Deprecazione**
   - Monitorare uso API legacy
   - Notificare eventuali integratori esterni
   - Spegnere endpoint PHP non più necessari

---

**Versione**: 1.0
**Data**: Novembre 2025
**Autore**: Analisi automatizzata PayGlobe Merchant Console
