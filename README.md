# PayGlobe Merchant Console

Sistema completo di gestione merchant per PayGlobe, composto da backend Spring Boot e frontend web.

## Architettura

Il progetto è composto da tre componenti principali:

### 1. Backend Spring Boot (merchant-api)
- **Percorso produzione**: `/opt/merchant-api/`
- **Porta**: 8082
- **Service**: `merchant-api`
- **Database**: MySQL 8.0 su 10.10.10.13
- **API Gateway**: Integrato con API Gateway PayGlobe

### 2. Frontend Dashboard (Alpine.js)
- **Percorso produzione**: `/var/www/html/merchant/frontend/dashboard/`
- **Stack**: Alpine.js + Dashboard.js v2.5.0
- **Features**:
  - Gestione transazioni in tempo reale
  - Top 10 banche per volume
  - Upload BIN Table asincrono con progress tracking
  - Statistiche e grafici interattivi

### 3. Frontend React (Nuova versione)
- **Percorso produzione**: `/opt/merchant-console/frontend/` o `/var/www/html/merchant/`
- **Stack**: React + API Gateway integration
- **Status**: In sviluppo

## Requisiti

- Java 17 o superiore
- Maven 3.6+
- MySQL 8.0+
- Node.js 14+ (per frontend React)
- Apache/Nginx configurato

## Installazione Backend (merchant-api)

### 1. Prerequisiti

```bash
# Verifica Java
java -version

# Installa Maven se necessario
wget https://dlcdn.apache.org/maven/maven-3/3.9.11/binaries/apache-maven-3.9.11-bin.tar.gz
tar xzvf apache-maven-3.9.11-bin.tar.gz
sudo mv apache-maven-3.9.11 /opt/
```

### 2. Configurazione Database

Importa lo schema del database:

```bash
mysql -h 10.10.10.13 -u PGDBUSER -p payglobe < database/schema.sql
```

### 3. Configurazione Applicazione

Modifica il file di configurazione:

```bash
# File: spring-boot-backend/src/main/resources/application.properties

# Database Configuration
spring.datasource.url=jdbc:mysql://10.10.10.13:3306/payglobe?useUnicode=true&characterEncoding=UTF-8&serverTimezone=Europe/Rome&useLegacyDatetimeCode=false
spring.datasource.username=PGDBUSER
spring.datasource.password=<PASSWORD>

# JPA Configuration
spring.jpa.hibernate.ddl-auto=update
spring.jpa.show-sql=false
spring.jpa.properties.hibernate.dialect=org.hibernate.dialect.MySQL8Dialect

# Server Configuration
server.port=8082
server.address=0.0.0.0

# Async Configuration
spring.task.execution.pool.core-size=5
spring.task.execution.pool.max-size=10
spring.task.execution.pool.queue-capacity=100

# File Upload
spring.servlet.multipart.max-file-size=100MB
spring.servlet.multipart.max-request-size=100MB
```

### 4. Build del Progetto

```bash
cd spring-boot-backend
mvn clean package -DskipTests
```

Il JAR compilato sarà in `target/merchant-console-1.0.0.jar`

### 5. Deployment su Server

```bash
# Copia il JAR sul server
scp target/merchant-console-1.0.0.jar pguser@pgbe2:/opt/merchant-api/

# Configura il service systemd
# File: /etc/systemd/system/merchant-api.service
```

Contenuto del service file:

```ini
[Unit]
Description=PayGlobe Merchant API
After=network.target

[Service]
Type=simple
User=pguser
WorkingDirectory=/opt/merchant-api
ExecStart=/usr/bin/java -jar /opt/merchant-api/merchant-console-1.0.0.jar --spring.config.location=/opt/merchant-api/config/application.properties
Restart=on-failure
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

### 6. Gestione Service

```bash
# Avvia il service
sudo systemctl start merchant-api

# Verifica status
sudo systemctl status merchant-api

# Abilita all'avvio
sudo systemctl enable merchant-api

# Riavvia (dopo modifiche)
sudo systemctl restart merchant-api

# Stop
sudo systemctl stop merchant-api
```

**IMPORTANTE**: Non riavviare mai il backend automaticamente durante il deploy - lasciare che lo faccia l'utente manualmente con `sudo service merchant-api restart`

### 7. Verifica Installazione

```bash
# Test endpoint health
curl http://localhost:8082/actuator/health

# Test endpoint API
curl http://localhost:8082/api/v2/admin/health
```

## Installazione Frontend

### Dashboard Alpine.js (Versione attuale)

```bash
# Deploy su server
cd frontend/dashboard
scp -r * pguser@pgbe2:/var/www/html/merchant/frontend/dashboard/

# Verifica permessi
ssh pguser@pgbe2 "chmod -R 755 /var/www/html/merchant/frontend/dashboard"
```

### Frontend React (Nuova versione)

```bash
cd frontend/react

# Installa dipendenze
npm install

# Build per produzione
npm run build

# Deploy su uno dei due percorsi
scp -r build/* pguser@pgbe2:/opt/merchant-console/frontend/
# oppure
scp -r build/* pguser@pgbe2:/var/www/html/merchant/
```

## Configurazione Apache/Nginx

### Configurazione Nginx su pgfe (Frontend Server)

Il file di configurazione è incluso in `nginx-config-pgfe.conf`

Punti chiave:
- Proxy pass per API su pgbe2:8082
- Gestione CORS
- Timeout configurati per upload lunghi (BIN table)

```bash
# Copia configurazione
sudo cp nginx-config-pgfe.conf /etc/nginx/sites-available/merchant-console
sudo ln -s /etc/nginx/sites-available/merchant-console /etc/nginx/sites-enabled/

# Test configurazione
sudo nginx -t

# Reload
sudo systemctl reload nginx
```

## Features Principali

### BIN Table Upload
- **Endpoint Sincrono**: `POST /api/v2/admin/bin-table/upload` (può dare timeout 503 per file grandi)
- **Endpoint Asincrono (RACCOMANDATO)**: `POST /api/v2/admin/bin-table/upload-async`
  - Restituisce `importId` immediatamente
  - Import procede in background con `@Async`
  - Polling status: `GET /api/v2/admin/bin-table/import-status/{importId}`
  - Response: `status` (processing/completed/failed), `progressPercentage`, `processedRecords`
- Supporta ZIP e CSV
- Max size: 100MB
- Progresso aggiornato ogni 1000 record

### Top 10 Banche
- Dashboard.js v2.5.0
- Visualizzazione Top 10 BIN per volume transazioni
- Codice BIN mostrato nel grafico
- Aggiornamento real-time

### Gestione Transazioni
- Ricerca avanzata con filtri
- Export Excel
- Dettaglio completo transazione
- Statistiche aggregate

## Struttura Database

Lo schema completo è disponibile in `database/schema.sql`

Tabelle principali:
- `merchant_users` - Utenti merchant
- `transactions` - Transazioni
- `stores` - Negozi/punti vendita
- `bin_table` - Tabella BIN card
- `acquirer` - Acquirer configurati
- `activation_codes` - Codici attivazione Satispay

## API Endpoints

### Authentication
- `POST /api/v2/auth/login` - Login
- `POST /api/v2/auth/logout` - Logout
- `POST /api/v2/auth/change-password` - Cambio password

### Admin
- `GET /api/v2/admin/transactions` - Lista transazioni
- `GET /api/v2/admin/statistics` - Statistiche
- `POST /api/v2/admin/bin-table/upload-async` - Upload BIN table
- `GET /api/v2/admin/bin-table/import-status/{id}` - Status import

### Merchant
- `GET /api/v2/merchant/stores` - Lista negozi
- `GET /api/v2/merchant/transactions` - Transazioni merchant
- `POST /api/v2/merchant/export` - Export dati

## Accesso Server

- **Host**: pgbe2
- **User**: pguser
- **Backend**: `/opt/merchant-api/`
- **Frontend Alpine.js**: `/var/www/html/merchant/frontend/dashboard/`
- **Frontend React**: `/opt/merchant-console/frontend/` o `/var/www/html/merchant/`

## Logs

```bash
# Backend logs (systemd journal)
sudo journalctl -u merchant-api -f

# Nginx logs
tail -f /var/log/nginx/merchant-console-access.log
tail -f /var/log/nginx/merchant-console-error.log

# Apache logs (se usato)
tail -50 /var/log/apache2/error.log
```

## Troubleshooting

### Backend non si avvia
```bash
# Verifica Java
java -version

# Verifica database connectivity
mysql -h 10.10.10.13 -u PGDBUSER -p

# Controlla logs
sudo journalctl -u merchant-api -n 100
```

### Timeout durante upload BIN table
- Usa endpoint `/upload-async` invece di `/upload`
- Verifica configurazione nginx timeout
- Verifica max file size in Spring Boot config

### Login blank screen
- Verifica che dashboard.js sia versione 2.3.8+
- Controlla che API Gateway risponda correttamente
- Verifica CORS configuration

## Sviluppo Locale

### Backend
```bash
cd spring-boot-backend
mvn spring-boot:run
```

### Frontend
```bash
cd frontend/dashboard
# Servire con web server locale
python -m http.server 8080
```

## Documentazione Aggiuntiva

- `API_DOCUMENTATION.txt` - Dettagli endpoint API
- `SATISPAY_ACTIVATION_README.md` - Guida attivazione Satispay
- `DEPLOYMENT_GUIDE.md` - Guida deployment dettagliata
- `NGINX_SETUP.md` - Configurazione Nginx

## Credenziali e Sicurezza

**IMPORTANTE**: Non committare mai su Git:
- Password database
- Chiavi API
- Certificati SSL
- File `.env` o simili

Usa sempre variabili d'ambiente o file di configurazione esterni per le credenziali.

## Licenza

Proprietario - PayGlobe

## Supporto

Per supporto tecnico, contattare il team PayGlobe.

---

**Versione**: 2.5.0
**Ultimo aggiornamento**: Novembre 2025
