- salva
- add to memory
- ADD
- morize tabelle errori
- save
- merorize memorizza nel progetto e in deploy!

## SERVER DI PRODUZIONE
- **pgbe** (PRODUZIONE): pguser@pgbe:/var/www/html/payglobe/mc5
- pgbe2: user:pguser (server secondario)
- IMPORTANTE: Deploy MC5 su pgbe, NON pgbe2 o pgbesrv01

Front end react deploy in
/opt/merchant-console/frontend/ oppure /var/www/html/merchant/
Front end Alpine.js (vecchio dashboard) in
/var/www/html/merchant/frontend/dashboard/
Front end MC5 PayGlobe in
/var/www/html/payglobe/mc5 (su pgbe)
Back end spring-boot
/opt/merchant-api/
Backend service: sudo service merchant-api start/stop/restart
- memorize claude have access with pguser on target diretoty do not use TMP
- NON riavviare mai il backend automaticamente - lasciare che lo faccia l'utente con sudo service merchant-api restart
- Apache restart: solo utente ha permessi sudo (NON riavviare automaticamente)

## BIN Table Upload - Implementato
- Endpoint SINCRONO: POST /api/v2/admin/bin-table/upload (può dare timeout nginx 503)
- Endpoint ASINCRONO: POST /api/v2/admin/bin-table/upload-async (RACCOMANDATO)
  - Restituisce importId subito (no timeout)
  - Import procede in background con @Async
  - Polling status: GET /api/v2/admin/bin-table/import-status/{importId}
  - Response: status (processing/completed/failed), progressPercentage, processedRecords
- Supporta ZIP e CSV
- Max size: 100MB
- Database: bin_table con campi nullable (bin_length, start_bin, end_bin)
- Config: ddl-auto=update in /opt/merchant-api/config/application.properties
- Fix "Stream closed": rimosso zipInputStream.closeEntry() in importFromZip()

## Top 10 Banche - Implementato
- dashboard.js v2.3.8
- Mostra Top 10 BIN per volume transazioni
- Visualizza codice BIN nel grafico
- Fix login blank screen: window.location.reload() dopo login per garantire caricamento completo

## Import Progress Tracking - IMPLEMENTATO
Backend:
- ImportProgress DTO con stato real-time
- Progresso aggiornato ogni 1000 record
- ConcurrentHashMap per tracciare import multipli
- AsyncConfig con @EnableAsync per elaborazione asincrona

Frontend (dashboard.js v2.3.6):
- Usa endpoint /upload-async invece di /upload
- Polling ogni 2 secondi su /import-status/{importId}
- Barra di progresso animata con percentuale
- Mostra record processati in tempo reale
- Spinner animato durante import
- UI migliorata con gradient e animazioni
- NO timeout 503 durante import lunghi (600k+ record)
- memorize BIN TABLE
- memorize sudo service   merchant-api start
- mc4 directory su pgbe intoccabile
- deploy su pgbe!
- memorizza deploy mc5 /var/www/html/payglobe/mc5