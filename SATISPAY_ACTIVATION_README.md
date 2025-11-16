# Guida Attivazione Satispay

## Panoramica

È stata implementata una nuova funzionalità per attivare Satispay direttamente dalla **Configurazione Sistema** del terminale PAX.

## File Creati/Modificati

### Nuovi File
- `satispay_activation_handler.php` - Handler AJAX per gestire l'attivazione Satispay

### File Modificati
- `terminal_config_editor.php` - Aggiunta sezione attivazione Satispay con bottone e modal
- `terminal_config_handler.php` - Aggiunti campi default per stato attivazione

## Come Funziona

### 1. Ottenere il Codice di Attivazione Satispay

1. Accedi alla **Dashboard Satispay** (https://dashboard.satispay.com)
2. Vai su **Negozio → Codice di Attivazione**
3. Copia il codice di attivazione generato

### 2. Attivare Satispay nel Sistema

1. Accedi al sistema merchant
2. Vai su **Configurazione Sistema** (terminal_config_editor.php)
3. Seleziona il **Terminal ID** che vuoi configurare
4. Scorri fino alla sezione **"Configurazione Sistema"**
5. Troverai una card con lo **Stato Attivazione Satispay**:
   - Se **NON attivato**: badge giallo con avviso
   - Se **ATTIVATO**: badge verde con conferma

### 3. Processo di Attivazione

1. Clicca sul bottone **"Attiva Satispay"**
2. Si aprirà un modal con:
   - Campo per inserire il **Codice di Attivazione**
   - Selezione dell'**Ambiente** (SANDBOX o PRODUCTION)
   - Info sul Terminal ID utilizzato
3. Inserisci il codice di attivazione
4. Seleziona l'ambiente corretto:
   - **SANDBOX**: per test e sviluppo
   - **PRODUCTION**: per transazioni reali
5. Clicca su **"Attiva"**
6. Il sistema farà:
   - Validazione del token
   - Chiamata al server remoto (`activate_store.php`)
   - Autenticazione con Satispay
   - Salvataggio delle chiavi nel database
   - Aggiornamento dello stato di attivazione

### 4. Verifica Stato Attivazione

Dopo l'attivazione, la pagina si ricaricherà automaticamente e vedrai:
- Badge **verde** con stato "Satispay Attivato"
- Ambiente utilizzato (SANDBOX o PROD)
- Terminal ID configurato
- Bottone "Ri-attiva" (nel caso si voglia cambiare configurazione)

## Flusso Tecnico

```
1. Frontend (terminal_config_editor.php)
   ↓
2. AJAX Request → satispay_activation_handler.php
   ↓
3. Validazione parametri (terminalId, token, environment)
   ↓
4. cURL Request → https://pgbe2.payglobe.it/satispay/activate_store.php
   ↓
5. Satispay API Authentication
   ↓
6. Salvataggio chiavi in database (terminal_config)
   - satispay_activated = '1'
   - satispay_environment = 'SANDBOX' o 'PROD'
   ↓
7. Audit Log (config_audit_log)
   ↓
8. Response JSON al frontend
```

## Database

Le informazioni di attivazione sono salvate nella tabella `terminal_config`:

| config_key | config_value | terminal_id | updated_by |
|-----------|--------------|-------------|------------|
| satispay_activated | 1 | 00000000 | admin |
| satispay_environment | SANDBOX | 00000000 | admin |

## Gestione Errori

Il sistema gestisce vari tipi di errori:

- **Token mancante**: Alert rosso nel modal
- **Terminal ID non trovato**: Errore 404
- **Errore autenticazione Satispay**: Messaggio specifico dall'API
- **Errore database**: Rollback automatico delle modifiche
- **Errore di rete**: Timeout o problemi di connessione

Tutti gli errori vengono mostrati nel modal con messaggi chiari.

## Sicurezza

- ✅ Validazione sessione utente
- ✅ Prepared statements per query DB
- ✅ Transazioni DB con rollback
- ✅ Sanitizzazione input
- ✅ SSL per chiamate remote
- ✅ Audit log di tutte le operazioni

## Ri-attivazione

Se Satispay è già attivato, è possibile ri-attivarlo:
- Clicca sul bottone **"Ri-attiva"**
- Inserisci un nuovo codice di attivazione
- Il sistema aggiornerà le chiavi esistenti

## Note Importanti

⚠️ **ATTENZIONE**:
- Usa **SANDBOX** per test e sviluppo
- Usa **PRODUCTION** solo per transazioni reali
- Il codice di attivazione è usa-e-getta
- Ogni attivazione genera nuove chiavi pubbliche/private
- Le chiavi vengono salvate in modo sicuro nel database

## Supporto

Per problemi o domande:
1. Controlla i log di sistema
2. Verifica la connessione a `pgbe2.payglobe.it`
3. Controlla lo stato del database
4. Verifica il codice di attivazione su Dashboard Satispay

---

**Versione**: 1.0
**Data**: 2025-10-09
