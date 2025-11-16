# PayGlobe Merchant API - Spring Boot

REST API per dashboard merchant PayGlobe con coesistenza graduale con sistema PHP esistente.

## Stack Tecnologico

- **Java**: 17
- **Spring Boot**: 3.2.1
- **Database**: MySQL 10.10.10.13 (condiviso con PHP, zero modifiche)
- **Auth**: JWT (stateless)
- **Porta**: 8986

## Struttura Progetto

```
spring-boot-backend/
├── src/main/java/com/payglobe/merchant/
│   ├── entity/           # JPA Entities (mappano tabelle PHP)
│   ├── repository/       # Spring Data JPA
│   ├── service/          # Business logic
│   ├── controller/       # REST API endpoints
│   ├── dto/              # Data Transfer Objects
│   ├── security/         # JWT + Spring Security
│   ├── util/             # Utilities (CircuitMapper, etc.)
│   └── config/           # Configuration classes
├── src/main/resources/
│   ├── application.yml             # Config sviluppo
│   └── application-production.yml  # Config produzione
└── deploy/               # Script deployment
```

## Build Locale

### Prerequisiti

- Java 17 JDK
- Maven 3.8+

### Compilazione

```bash
# Build JAR
mvn clean package -DskipTests

# Output: target/merchant-api.jar (~40-50 MB)
```

### Test Locale

```bash
# Run con profilo development
java -jar target/merchant-api.jar

# Oppure con Maven
mvn spring-boot:run
```

Applicazione disponibile su: `http://localhost:8986`

### Test Health Check

```bash
curl http://localhost:8986/actuator/health
# Output: {"status":"UP"}

curl http://localhost:8986/actuator/health/db
# Output: {"status":"UP"} (se DB raggiungibile)
```

## Endpoints API

### Autenticazione

```bash
# Login
POST /api/v2/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

# Response:
{
  "accessToken": "eyJhbGc...",
  "refreshToken": "eyJhbGc...",
  "userId": 1,
  "email": "user@example.com",
  "bu": "1234",
  "isAdmin": false
}
```

### Transazioni

```bash
# Lista transazioni
GET /api/v2/transactions?startDate=2025-01-01&endDate=2025-01-31&page=0&size=25

# Statistiche dashboard
GET /api/v2/transactions/stats?startDate=2025-01-01&endDate=2025-01-31

# Distribuzione circuiti
GET /api/v2/transactions/circuits?startDate=2025-01-01&endDate=2025-01-31

# Trend giornaliero
GET /api/v2/transactions/trend?startDate=2025-01-01&endDate=2025-01-31
```

## Deploy su pgbe2

### 1. Installazione Java 17

```bash
ssh pguser@pgbe2

# Installa OpenJDK 17
sudo apt update
sudo apt install -y openjdk-17-jdk

# Verifica
java -version
# Output: openjdk version "17.0.x"

# Configura JAVA_HOME
echo 'export JAVA_HOME=/usr/lib/jvm/java-17-openjdk-amd64' | sudo tee -a /etc/environment
echo 'export PATH=$JAVA_HOME/bin:$PATH' | sudo tee -a /etc/environment
source /etc/environment
```

### 2. Deploy Applicazione

```bash
# Crea directory applicazione
sudo mkdir -p /opt/merchant-api
sudo chown pguser:pguser /opt/merchant-api

# Copia JAR su server (da locale)
scp target/merchant-api.jar pguser@pgbe2:/opt/merchant-api/

# Crea directory logs
sudo mkdir -p /var/log/merchant-api
sudo chown pguser:pguser /var/log/merchant-api
```

### 3. Systemd Service

```bash
# Copia service file
sudo cp deploy/merchant-api.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Abilita e avvia servizio
sudo systemctl enable merchant-api
sudo systemctl start merchant-api

# Verifica status
sudo systemctl status merchant-api

# Visualizza logs
sudo journalctl -u merchant-api -f
```

### 4. Configurazione Apache Reverse Proxy

```bash
# Abilita moduli necessari
sudo a2enmod proxy proxy_http headers rewrite

# Copia configurazione
sudo cp deploy/apache-merchant.conf /etc/apache2/sites-available/

# Abilita sito
sudo a2ensite apache-merchant

# Test configurazione
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

### 5. Test Deploy

```bash
# Test diretto su porta 8986
curl http://localhost:8986/actuator/health

# Test via Apache proxy
curl http://ricevute.payglobe.it/api/v2/auth/health

# Test login
curl -X POST http://ricevute.payglobe.it/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@payglobe.it","password":"admin123"}'
```

## Troubleshooting

### Applicazione non si avvia

```bash
# Verifica logs
sudo journalctl -u merchant-api -n 100

# Verifica porte
sudo netstat -tulpn | grep 8986

# Verifica connessione DB
telnet 10.10.10.13 3306
```

### Errore "Connection refused" al DB

```bash
# Verifica credenziali in application-production.yml
# Verifica che DB sia raggiungibile da pgbe2
ping 10.10.10.13
mysql -h 10.10.10.13 -u PGDBUSER -p payglobe
```

### Apache proxy non funziona

```bash
# Verifica moduli abilitati
apache2ctl -M | grep proxy

# Verifica logs Apache
sudo tail -f /var/log/apache2/merchant-error.log

# Verifica configurazione
sudo apache2ctl configtest
```

### Spazio disco insufficiente

```bash
# Libera spazio logs
sudo journalctl --vacuum-time=7d
sudo find /var/log/apache2 -name "*.log.*" -mtime +7 -delete

# Verifica spazio
df -h /
```

## Monitoraggio

### Health Checks

```bash
# Application health
curl http://localhost:8986/actuator/health

# Database health
curl http://localhost:8986/actuator/health/db

# Metrics
curl http://localhost:8986/actuator/metrics
```

### Logs

```bash
# Application logs
sudo journalctl -u merchant-api -f

# Application log file
tail -f /var/log/merchant-api/application.log

# Apache logs
sudo tail -f /var/log/apache2/merchant-error.log
```

## Rollback

Se ci sono problemi, rollback a PHP in 5 minuti:

```bash
# Stop Spring Boot
sudo systemctl stop merchant-api

# Disabilita proxy Apache
sudo a2disconf apache-merchant
sudo systemctl reload apache2

# PHP continua a funzionare normalmente
```

## Migrazione da PHP

Il sistema è progettato per **coesistenza graduale**:

1. ✅ **Fase 1** (corrente): Spring Boot + PHP attivi insieme
2. ✅ **Fase 2**: Testing parallelo (utenti scelgono quale usare)
3. ✅ **Fase 3**: Migrazione completa (PHP deprecato)
4. ✅ **Fase 4**: Mantieni solo `/api/terminal/config.php` per dispositivi PAX

## Contatti

Per supporto: admin@payglobe.it
