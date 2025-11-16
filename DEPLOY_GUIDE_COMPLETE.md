# 🚀 Guida Deploy Completa - PayGlobe Merchant Console v2.0

## 🖥️ Server Info

**Host**: pgbe2
**User**: pguser
**Frontend Path**: `/opt/merchant-console/frontend`
**Backend Path**: `/opt/merchant-console/`

---

## 📦 Deployment Path

**⚠️ IMPORTANTE**: NON usare `/tmp/` per staging! Deploy direttamente in `/opt/merchant-console/`

### Backend Spring Boot
```
Path deploy: /opt/merchant-console/merchant-api.jar
SQL scripts: /opt/merchant-console/sql/ (se necessario)
```

### Frontend React
```
Path progetto: /opt/merchant-console/frontend/
Sorgenti: /opt/merchant-console/frontend/src/
Build output: /opt/merchant-console/frontend/dist/
```

---

## 🔧 Deploy Backend (Spring Boot)

### 1. Connettiti a pgbe2
```bash
ssh pguser@pgbe2
```

### 2. Crea tabella BIN (se necessario)
```bash
# SQL script deve essere in /opt/merchant-console/sql/
mysql -u root -p payglobe < /opt/merchant-console/sql/create_bin_table.sql
# Password: PNeNkar{K1.%D~V
```

### 3. Deploy JAR (da Windows con SCP diretto)
```bash
# Da Windows - Backup remoto prima di copiare
ssh pguser@pgbe2 "sudo cp /opt/merchant-console/merchant-api.jar /opt/merchant-console/merchant-api.jar.backup-\$(date +%Y%m%d-%H%M%S)"

# Copia JAR direttamente da build locale (NON usare /tmp!)
scp C:\path\to\merchant-api.jar pguser@pgbe2:/opt/merchant-console/merchant-api.jar

# Su pgbe2 - Fix permessi
ssh pguser@pgbe2 "sudo chown pguser:pguser /opt/merchant-console/merchant-api.jar && sudo chmod 644 /opt/merchant-console/merchant-api.jar"
```

### Alternativa: Build diretto su pgbe2
```bash
ssh pguser@pgbe2
cd /opt/merchant-console/backend-source/  # se hai sorgenti su server

# Build con Maven
mvn clean package -DskipTests

# Backup e deploy
sudo cp /opt/merchant-console/merchant-api.jar \
  /opt/merchant-console/merchant-api.jar.backup-$(date +%Y%m%d-%H%M%S)
sudo cp target/merchant-api.jar /opt/merchant-console/merchant-api.jar
sudo chown pguser:pguser /opt/merchant-console/merchant-api.jar
sudo chmod 644 /opt/merchant-console/merchant-api.jar
```

### 4. Riavvia servizio
```bash
sudo systemctl restart merchant-api.service

# Verifica stato
sudo systemctl status merchant-api.service

# Segui log in tempo reale
sudo journalctl -u merchant-api.service -f
```

### 5. Verifica API funzionanti
```bash
# Health check
curl http://localhost:8080/merchant-api/api/v2/auth/health

# Dovrebbe rispondere: {"status":"UP"}
```

---

## 🎨 Deploy Frontend (React)

**IMPORTANTE**: Il frontend è deployato come **progetto sorgente** con build su server!

### Opzione 1: SCP componenti da Windows

```bash
# Da Windows - Copia componenti aggiornati direttamente
scp C:\Users\hellrock\Desktop\merchant\merchant-dashboard-react\src\components\*.jsx \
  pguser@pgbe2:/opt/merchant-console/frontend/src/components/

scp C:\Users\hellrock\Desktop\merchant\merchant-dashboard-react\src\App.jsx \
  pguser@pgbe2:/opt/merchant-console/frontend/src/

scp C:\Users\hellrock\Desktop\merchant\merchant-dashboard-react\src\services\api.js \
  pguser@pgbe2:/opt/merchant-console/frontend/src/services/
```

### Opzione 2: Edit diretto su server

```bash
ssh pguser@pgbe2
cd /opt/merchant-console/frontend

# 1. BACKUP sorgenti attuali
cp -r src src.backup-$(date +%Y%m%d-%H%M%S)

# 2. Modifica file con nano/vim direttamente
nano src/components/Dashboard.jsx

# 3. REBUILD e RESTART
./react-build.sh
./react-restart.sh

# 4. Verifica log
tail -f frontend.log
```

### Scripts disponibili su server
```bash
./react-start.sh      # Avvia server React
./react-stop.sh       # Ferma server React
./react-restart.sh    # Riavvia server React
```

---

## 🌐 URL di Accesso

```
https://ricevute.payglobe.it/merchant-dashboard
```

---

## ✅ Funzionalità Implementate

### 🎯 Per TUTTI gli utenti:

#### 1. **Dashboard** (`/dashboard`)
- Transazioni con filtri avanzati
- Grafici circuiti e trend
- Statistiche KPI
- Export Excel

#### 2. **Codici Attivazione** (`/activation-codes`)
- ✅ Crea codice attivazione PAX (formato: ACT-XXXXXXXXX)
- ✅ Lista codici con filtri (status, bu, search)
- ✅ Statistiche (totale, attivi, usati, scaduti)
- ✅ Stati: PENDING, USED, EXPIRED
- ✅ Scadenza automatica: 21 giorni
- ⚠️ Disattiva/Elimina (solo admin)

**API Endpoints:**
- `POST /api/v2/activation-codes` - Crea codice
- `GET /api/v2/activation-codes` - Lista con filtri
- `GET /api/v2/activation-codes/stats` - Statistiche
- `POST /api/v2/activation-codes/{id}/deactivate` - Disattiva (admin)
- `DELETE /api/v2/activation-codes/{id}` - Elimina (admin)
- `POST /api/v2/activation-codes/cleanup` - Cleanup bulk (admin)

---

### 👑 Solo per ADMIN:

#### 3. **Gestione Utenti** (`/admin/users`)
- ✅ CRUD completo utenti
- ✅ Reset password (forza cambio al login)
- ✅ Attiva/Disattiva utenti
- ✅ Statistiche (totale, attivi, password scadute)
- ✅ Password expire: 45 giorni
- ✅ Ricerca per email, BU, ragione sociale

**API Endpoints:**
- `POST /api/v2/admin/users` - Crea utente
- `GET /api/v2/admin/users` - Lista utenti
- `GET /api/v2/admin/users/{id}` - Dettagli utente
- `PUT /api/v2/admin/users/{id}` - Aggiorna utente
- `DELETE /api/v2/admin/users/{id}` - Elimina utente (no self-delete)
- `POST /api/v2/admin/users/{id}/reset-password` - Reset password
- `GET /api/v2/admin/users/stats` - Statistiche utenti

#### 4. **Aggiorna BIN Table** (`/admin/bin-table`)
- ✅ Drag & Drop file CSV
- ✅ Upload multipart/form-data
- ✅ Stato database (vuoto/popolato)
- ✅ Elimina tutti i dati (con conferma)
- ✅ Batch import 1000 record per volta
- ✅ Lookup banca da PAN

**API Endpoints:**
- `POST /api/v2/admin/bin-table/upload` - Upload CSV (multipart) ⭐
- `GET /api/v2/admin/bin-table/status` - Stato database
- `DELETE /api/v2/admin/bin-table` - Elimina tutti i dati
- `GET /api/v2/admin/bin-table/lookup?pan=XXX` - Test lookup

---

## 🔐 Test Login

### Admin
```
Email: admin@payglobe.com
Password: (la tua password admin)
```

### User normale
```
Email: (email utente)
Password: (password utente)
```

---

## 📱 Interfaccia React

### Menu Laterale
```
┌──────────────────────────────────────────┐
│  PayGlobe Merchant Console      [Esci]   │
│  user@email.com  [BU: 001] [ADMIN]       │
├──────────┬───────────────────────────────┤
│          │                               │
│  📊 Dashboard                            │
│  💳 Codici Attivazione                   │
│  👥 Gestione Utenti     [Admin]         │
│  🗄️ Aggiorna BIN Table  [Admin]          │
│          │                               │
│          │   <Main Content>              │
└──────────┴───────────────────────────────┘
```

### Caratteristiche UI
- ✅ Menu laterale fisso (desktop)
- ✅ Menu hamburger (mobile)
- ✅ Badge "Admin" su voci riservate
- ✅ Highlight blu su pagina attiva
- ✅ Modali per create/edit
- ✅ Paginazione tabelle
- ✅ Filtri avanzati
- ✅ Messaggi successo/errore

---

## 🔍 Validazione Date per Admin

**Problema risolto**: Admin con BU 9999 causava timeout 504 per troppi dati

**Soluzione implementata:**
- ✅ Max 1 giorno di differenza tra startDate e endDate
- ✅ Default period: 7 giorni (vs 30 per utenti normali)
- ✅ Applicato a tutti gli endpoint `/api/v2/transactions/*`
- ✅ Messaggio errore chiaro: "Troppi dati!"

---

## 🗄️ BIN Table - Upload CSV

### Formato File
```
BT_20251013_2.csv
```

**Separatore**: `;` (punto e virgola)

**Colonne (15 totali)**:
1. Run Date
2. Start BIN Value
3. End BIN Value
4. BIN Length
5. BIN Country
6. BIN Country Description
7. Country Code
8. Card Brand Description
9. Service Type Description
10. Card Organisation Description
11. Card Product
12. **Issuer Name** ⭐ (campo più importante!)
13. Tipo Carta
14. Paese
15. Transcodifica

### Come usare:
1. Login come admin
2. Menu laterale → "Aggiorna BIN Table"
3. Drag & Drop file CSV o "Seleziona File"
4. Clicca "Carica e Importa"
5. Attendi (~30 sec per 100k record)
6. ✅ Conferma: "Import completato - Record importati: 125430"

---

## 🐛 Troubleshooting

### Backend non parte
```bash
# Verifica log
sudo journalctl -u merchant-api.service -n 100 --no-pager

# Verifica processo
ps aux | grep merchant-api

# Verifica porta 8080
netstat -tlnp | grep 8080
```

### Frontend non carica
```bash
# Verifica files React
ls -la /opt/merchant-console/frontend/

# Verifica log frontend
tail -50 /opt/merchant-console/frontend/frontend.log

# Verifica processo React
ps aux | grep node

# Test URL
curl -I https://ricevute.payglobe.it/merchant-dashboard
```

### API 401 Unauthorized
```bash
# Verifica JWT token nel browser
# Developer Tools → Application → Local Storage → accessToken

# Verifica backend health
curl http://localhost:8080/merchant-api/api/v2/auth/health
```

### BIN Table upload fallisce
```bash
# Verifica tabella esiste
mysql -u root -p payglobe -e "SHOW TABLES LIKE 'bin_table';"

# Verifica colonne
mysql -u root -p payglobe -e "DESCRIBE bin_table;"

# Conta record
mysql -u root -p payglobe -e "SELECT COUNT(*) FROM bin_table;"
```

---

## 📊 Statistiche Sistema

### Database
- `users` - Utenti sistema
- `stores` - Negozi/Terminal
- `transactions` - Transazioni
- `activation_codes` - Codici attivazione PAX ⭐ NEW
- `bin_table` - Database BIN banche ⭐ NEW

### Performance
- Build React: ~4 secondi
- Build Spring Boot: ~6 secondi
- BIN import: ~30 sec per 100k record
- API response time: <100ms

---

## ✅ Checklist Post-Deploy

- [ ] Backend Spring Boot avviato (porta 8080)
- [ ] Frontend React deployato
- [ ] Login funzionante
- [ ] Dashboard visualizza transazioni
- [ ] Menu laterale visibile
- [ ] Codici Attivazione accessibile
- [ ] Gestione Utenti accessibile (admin)
- [ ] BIN Table upload funziona (admin)
- [ ] Tabella `bin_table` creata
- [ ] Tabella `activation_codes` creata

---

## 🎉 Deploy Completato!

Tutte le funzionalità admin sono ora disponibili via React!

**Prossimi Step Opzionali**:
1. Statistiche Avanzate (BIN analysis, hourly, weekday)
2. Stores Management (lista negozi avanzata)

---

**Documentazione creata**: 2025-11-13
**Versione**: 2.0.0
**Stack**: Spring Boot 3.2.1 + React 18 + Vite + Tailwind CSS
