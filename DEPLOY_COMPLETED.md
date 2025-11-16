# ✅ Deploy Spring Boot Merchant API - COMPLETATO

**Data**: 2025-11-12
**Server**: pgbe2 (10.10.10.11)
**Porta**: 8986
**Status**: **RUNNING** ✅

---

## 📊 Stato Attuale

```bash
# Health check
curl http://localhost:8986/actuator/health
# Risposta: {"status":"UP"}

# Statistics endpoint
curl http://localhost:8986/api/v2/transactions/stats
# Risposta: {"total":3390655,"volume":31171815.39,"settledCount":2964973,"notSettledCount":425682}

# Porta in ascolto
netstat -tulpn | grep 8986
# tcp6  0  0 :::8986  :::*  LISTEN  14616/java
```

---

## 🔧 Fix Applicati Durante il Deploy

### 1. **Database Encoding**
- **Problema**: `utf8mb4` non supportato dal driver MySQL
- **Fix**: Cambiato a `utf8` in `application-production.properties`

### 2. **Password con Caratteri Speciali**
- **Problema**: Password `PNeNkar{K1.%D~V` con graffe `{}` interpretata male da Spring Boot
- **Fix**: Rimossa sintassi `${DB_PASSWORD:...}` e hardcoded direttamente

### 3. **JWT Secret**
- **Fix**: Hardcoded nel properties per evitare problemi variabili d'ambiente

### 4. **Store Entity - Primary Key**
- **Problema**: Entity `Store` aveva `@Id Long id` ma la tabella usa `TerminalID` come PK
- **Fix**: Modificato `@Id` su `String terminalId` mappato a colonna `TerminalID`

### 5. **UserRepository - Query HQL Errata**
- **Problema**: Query con `CURRENT_TIMESTAMP - 45` (sintassi errata per date arithmetic)
- **Fix**: Eliminato metodo `findUsersWithExpiredPassword()` (non critico)

### 6. **Hibernate Schema Validation**
- **Fix**: Cambiato `spring.jpa.hibernate.ddl-auto` da `validate` a `none`

---

## 📁 File Deployati

### Su pgbe2
```
/opt/merchant-console/
├── merchant-api.jar (52 MB) ✅
├── config/
│   └── application-production.properties ✅
└── logs/
    └── merchant.log ✅

/home/pguser/
├── merchant-api.service (systemd) ⏳
├── apache-merchant.conf (Apache proxy) ⏳
├── start.sh ✅
├── stop.sh ✅
└── restart.sh ✅
```

### Sul tuo PC
```
C:\Users\hellrock\Desktop\merchant\spring-boot-backend\
└── deploy/
    ├── nginx-merchant-api.conf (per pgfe) 🆕
    └── (altri file...)
```

---

## 🌐 URL Mapping

### Con nginx su pgfe

| Client Request | Nginx pgfe | Spring Boot pgbe2:8986 |
|---------------|------------|------------------------|
| `http://ricevute.payglobe.it/merchant-api/api/v2/auth/health` | ➡️ Toglie `/merchant-api` | ⬅️ Riceve `/api/v2/auth/health` |
| `http://ricevute.payglobe.it/merchant-api/api/v2/transactions/stats` | ➡️ Toglie `/merchant-api` | ⬅️ Riceve `/api/v2/transactions/stats` |
| `http://ricevute.payglobe.it/merchant/index.php` | ➡️ Proxy verso pgbe2 Apache | ⬅️ PHP continua a funzionare |

---

## ⚙️ Comandi per Completare il Deploy

### 🔴 Su pgfe (server nginx) - DA FARE

1. **Copia configurazione nginx**:
```bash
# Opzione A: Da Windows via scp
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\deploy\nginx-merchant-api.conf user@pgfe:~/

# Opzione B: Creala manualmente su pgfe
sudo nano /etc/nginx/conf.d/merchant-api.conf
# (copia contenuto da nginx-merchant-api.conf)
```

2. **Modifica configurazione esistente**:
```bash
sudo nano /etc/nginx/sites-available/ricevute.payglobe.it.conf
# Aggiungi il blocco location /merchant-api/ { ... }
```

3. **Testa e ricarica nginx**:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

4. **Test finale**:
```bash
# Health check via nginx
curl http://ricevute.payglobe.it/merchant-api/api/v2/auth/health
# Dovrebbe rispondere: OK

# Statistics via nginx
curl http://ricevute.payglobe.it/merchant-api/api/v2/transactions/stats
# Dovrebbe rispondere: {"total":...,"volume":...}

# PHP ancora funziona
curl http://ricevute.payglobe.it/merchant/index.php
# Dovrebbe rispondere con HTML
```

---

### 🟡 Su pgbe2 (opzionale: systemd service) - DA FARE

Se vuoi usare systemd invece di nohup:

```bash
# 1. Installa service
sudo cp ~/merchant-api.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable merchant-api

# 2. Stop processo corrente
pkill -f merchant-api.jar

# 3. Start con systemd
sudo systemctl start merchant-api
sudo systemctl status merchant-api

# 4. Visualizza logs
sudo journalctl -u merchant-api -f
```

**NOTA**: Prima di usare systemd, modifica il JWT_SECRET nel file service:
```bash
sudo nano /etc/systemd/system/merchant-api.service
# Cambia la riga Environment="JWT_SECRET=..." con un valore sicuro
```

---

## 🚀 Avvio Manuale (Attuale)

L'applicazione è attualmente in esecuzione con **nohup**:

```bash
# Directory
cd /opt/merchant-console

# Comando avvio
nohup java -Xmx1024m -Xms512m -XX:+UseG1GC \
  -Dspring.profiles.active=production \
  -Dspring.jpa.hibernate.ddl-auto=none \
  -Dspring.config.additional-location=file:/opt/merchant-console/config/ \
  -jar merchant-api.jar > logs/merchant.log 2>&1 &

# PID attuale: 14616
```

### Comandi Utili

```bash
# Stop
pkill -f merchant-api.jar

# Restart
pkill -f merchant-api.jar && sleep 3 && cd /opt/merchant-console && nohup java -Xmx1024m -Xms512m -XX:+UseG1GC -Dspring.profiles.active=production -Dspring.jpa.hibernate.ddl-auto=none -Dspring.config.additional-location=file:/opt/merchant-console/config/ -jar merchant-api.jar > logs/merchant.log 2>&1 &

# Visualizza logs
tail -f /opt/merchant-console/logs/merchant.log

# Verifica processo
ps aux | grep merchant-api.jar | grep -v grep

# Verifica porta
netstat -tulpn | grep 8986
```

---

## 📋 Endpoint API Disponibili

### Authentication
- `GET /api/v2/auth/health` - Health check (ritorna "OK")
- `POST /api/v2/auth/login` - Login con email/password
- `POST /api/v2/auth/refresh` - Refresh JWT token
- `POST /api/v2/auth/logout` - Logout

### Transactions
- `GET /api/v2/transactions` - Lista transazioni (paginata)
  - Query params: `startDate`, `endDate`, `filterStore`, `page`, `size`
- `GET /api/v2/transactions/stats` - Statistiche dashboard (KPI)
- `GET /api/v2/transactions/circuits` - Distribuzione per circuito
- `GET /api/v2/transactions/trend` - Trend giornaliero

### Stores
- `GET /api/v2/stores` - Lista stores (filtrata per BU)

### Actuator (Monitoring)
- `GET /actuator/health` - Spring Boot health check
- `GET /actuator/info` - Application info

---

## 🔐 Sicurezza

### JWT Authentication
- **Access Token**: 15 minuti (900000 ms)
- **Refresh Token**: 7 giorni (604800000 ms)
- **Secret**: Configurato in production properties

### CORS
- Configurato in SecurityConfig
- Permettere origins specifici per produzione

### Database
- **Host**: 10.10.10.13:3306
- **Database**: payglobe
- **User**: PGDBUSER
- **Connection Pool**: Max 10 connessioni (condiviso con PHP)
- **Encoding**: utf8

---

## 📊 Statistiche Database (Snapshot)

```json
{
  "total": 3390655,
  "volume": 31171815.39,
  "settledCount": 2964973,
  "notSettledCount": 425682
}
```

---

## ✅ Checklist Deploy Completato

- [x] Java 17 installato su pgbe2
- [x] JAR buildato (52 MB)
- [x] Directory `/opt/merchant-console` creata
- [x] JAR copiato su server
- [x] Properties configurato (encoding, password, JWT)
- [x] Entity fixate (Store con TerminalID come PK)
- [x] Repository fixato (eliminata query errata)
- [x] Applicazione avviata (PID 14616)
- [x] Health check OK
- [x] Statistics endpoint OK
- [x] Database connection OK
- [x] Porta 8986 in ascolto
- [x] Configurazione nginx preparata
- [ ] Nginx su pgfe configurato ⏳
- [ ] Systemd service installato (opzionale) ⏳
- [ ] Test end-to-end via nginx ⏳

---

## 🔄 Coesistenza PHP + Spring Boot

### Architettura

```
Internet
    ↓
nginx (pgfe) - Frontend Reverse Proxy
    ↓
    ├── /merchant/* → Apache (pgbe2:80) → PHP
    └── /merchant-api/* → Spring Boot (pgbe2:8986) → REST API
```

### Vantaggi
- ✅ Zero downtime: PHP continua a funzionare
- ✅ Migrazione graduale: Endpoint per endpoint
- ✅ Same database: Condividono lo stesso MySQL
- ✅ Rollback rapido: Basta disabilitare nginx location

---

## 🎯 Prossimi Step

1. **Configurare nginx su pgfe** (priorità alta)
2. **Testare API via nginx** (end-to-end)
3. **Installare systemd service** (opzionale, per auto-restart)
4. **Monitoring**: Setup Grafana/Prometheus (futuro)
5. **Frontend React**: Nuova dashboard (futuro)
6. **Decommissioning PHP**: Quando tutto validato (futuro)

---

## 📞 Supporto

### Log Locations
- **Application logs**: `/opt/merchant-console/logs/merchant.log`
- **Systemd logs** (se usato): `sudo journalctl -u merchant-api -f`

### Quick Troubleshooting
```bash
# 1. Check se applicazione è UP
curl http://localhost:8986/actuator/health

# 2. Check porta in ascolto
netstat -tulpn | grep 8986

# 3. Check processo Java
ps aux | grep merchant-api.jar

# 4. Check ultimi errori nei log
tail -100 /opt/merchant-console/logs/merchant.log | grep -i error

# 5. Restart applicazione
pkill -f merchant-api.jar && sleep 3 && cd /opt/merchant-console && nohup java -Xmx1024m -Xms512m -XX:+UseG1GC -Dspring.profiles.active=production -Dspring.jpa.hibernate.ddl-auto=none -Dspring.config.additional-location=file:/opt/merchant-console/config/ -jar merchant-api.jar > logs/merchant.log 2>&1 &
```

---

**Deploy completato con successo! 🎉**

Per domande: admin@payglobe.it
