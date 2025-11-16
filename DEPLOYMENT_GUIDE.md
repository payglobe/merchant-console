# Guida Deploy: Spring Boot su pgbe2

## 🎯 Obiettivo

Deployare l'API REST Spring Boot su pgbe2 **coesistendo con PHP**, usando:
- **Porta Tomcat**: 8986
- **Database**: MySQL 10.10.10.13 (esistente, zero modifiche)
- **Apache**: Reverse proxy (PHP + Spring Boot insieme)

---

## 📋 Checklist Pre-Deploy

- [ ] Server pgbe2 accessibile via SSH (`ssh pguser@pgbe2`)
- [ ] Spazio disco disponibile: almeno 200 MB liberi
- [ ] Java 17 installato su pgbe2
- [ ] MySQL database `payglobe` accessibile da pgbe2
- [ ] Apache2 installato e funzionante

---

## 🚀 Procedura Deploy (Step by Step)

### Step 1: Build JAR Locale

```bash
# Nel tuo PC, dalla directory spring-boot-backend
cd C:\Users\hellrock\Desktop\merchant\spring-boot-backend

# Build con Maven
mvn clean package -DskipTests

# Verifica JAR creato
ls -lh target/merchant-api.jar
# Output atteso: ~40-50 MB
```

### Step 2: Installazione Java 17 su pgbe2

```bash
# SSH su pgbe2
ssh pguser@pgbe2

# Verifica se Java 17 è già installato
java -version

# Se NON installato o versione < 17:
sudo apt update
sudo apt install -y openjdk-17-jdk

# Verifica installazione
java -version
# Output atteso: openjdk version "17.0.x"

# Configura JAVA_HOME
echo 'export JAVA_HOME=/usr/lib/jvm/java-17-openjdk-amd64' | sudo tee -a /etc/environment
echo 'export PATH=$JAVA_HOME/bin:$PATH' | sudo tee -a /etc/environment
source /etc/environment

# Verifica
echo $JAVA_HOME
# Output: /usr/lib/jvm/java-17-openjdk-amd64
```

### Step 3: Creazione Directory Applicazione

```bash
# Su pgbe2

# Crea directory applicazione
sudo mkdir -p /opt/merchant-api
sudo chown pguser:pguser /opt/merchant-api

# Crea directory logs
sudo mkdir -p /var/log/merchant-api
sudo chown pguser:pguser /var/log/merchant-api

# Verifica
ls -ld /opt/merchant-api
ls -ld /var/log/merchant-api
```

### Step 4: Copia JAR su Server

```bash
# Dal tuo PC

# Copia JAR
scp spring-boot-backend/target/merchant-api.jar pguser@pgbe2:/opt/merchant-api/

# Verifica su server
ssh pguser@pgbe2 "ls -lh /opt/merchant-api/merchant-api.jar"
```

### Step 5: Configurazione Systemd Service

```bash
# Dal tuo PC, copia service file
scp spring-boot-backend/deploy/merchant-api.service pguser@pgbe2:/tmp/

# Su pgbe2
ssh pguser@pgbe2

# Installa service
sudo mv /tmp/merchant-api.service /etc/systemd/system/

# IMPORTANTE: Modifica JWT_SECRET in produzione!
sudo nano /etc/systemd/system/merchant-api.service
# Cambia: Environment="JWT_SECRET=..." con un valore sicuro random

# Reload systemd
sudo systemctl daemon-reload

# Abilita servizio
sudo systemctl enable merchant-api

# Avvia servizio
sudo systemctl start merchant-api

# Verifica status
sudo systemctl status merchant-api
```

**Output atteso**:
```
● merchant-api.service - PayGlobe Merchant API (Spring Boot)
   Loaded: loaded (/etc/systemd/system/merchant-api.service; enabled)
   Active: active (running) since ...
```

### Step 6: Verifica Logs e Health

```bash
# Su pgbe2

# Visualizza logs in tempo reale
sudo journalctl -u merchant-api -f

# Verifica ultimi 50 log
sudo journalctl -u merchant-api -n 50

# Health check diretto
curl http://localhost:8986/actuator/health
# Output atteso: {"status":"UP"}

# Health check database
curl http://localhost:8986/actuator/health/db
# Output atteso: {"status":"UP"}
```

### Step 7: Configurazione Apache Reverse Proxy

```bash
# Dal tuo PC, copia config Apache
scp spring-boot-backend/deploy/apache-merchant.conf pguser@pgbe2:/tmp/

# Su pgbe2
ssh pguser@pgbe2

# Installa configurazione
sudo mv /tmp/apache-merchant.conf /etc/apache2/sites-available/

# Abilita moduli necessari
sudo a2enmod proxy proxy_http headers rewrite

# Abilita sito
sudo a2ensite apache-merchant

# Test configurazione
sudo apache2ctl configtest
# Output atteso: Syntax OK

# Reload Apache
sudo systemctl reload apache2

# Verifica Apache funziona
sudo systemctl status apache2
```

### Step 8: Test Completo

```bash
# Test 1: Health check via Apache proxy
curl http://ricevute.payglobe.it/api/v2/auth/health
# Output atteso: "OK"

# Test 2: Health check completo
curl http://ricevute.payglobe.it/api/v2/auth/health
curl http://localhost:8986/actuator/health

# Test 3: Login endpoint (senza credenziali, deve dare 401)
curl -X POST http://ricevute.payglobe.it/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"wrong"}'
# Output atteso: 401 Unauthorized

# Test 4: Statistiche (DEVE funzionare anche senza auth per ora)
curl http://ricevute.payglobe.it/api/v2/transactions/stats
# Output atteso: JSON con stats

# Test 5: PHP ancora funziona
curl http://ricevute.payglobe.it/merchant/index.php
# Output atteso: HTML della pagina PHP
```

### Step 9: Verifica Coesistenza PHP + Spring Boot

```bash
# Su pgbe2

# Verifica porte in ascolto
sudo netstat -tulpn | grep -E "(:80|:8986)"
# Output atteso:
# tcp6  0  0 :::80      :::*  LISTEN  <pid>/apache2
# tcp6  0  0 :::8986    :::*  LISTEN  <pid>/java

# Verifica processi
ps aux | grep -E "(apache2|java)"
# Dovresti vedere sia apache2 che java in esecuzione

# Test routing Apache
sudo cat /var/log/apache2/merchant-access.log | tail -20
# Verifica che chiamate a /api/v2/* vadano su proxy
```

---

## 🔍 Troubleshooting

### Problema: Servizio non si avvia

```bash
# Visualizza logs dettagliati
sudo journalctl -u merchant-api -n 100 --no-pager

# Errori comuni:

# 1. "Address already in use" (porta 8986 occupata)
sudo netstat -tulpn | grep 8986
# Soluzione: killare processo o cambiare porta

# 2. "Cannot connect to database"
telnet 10.10.10.13 3306
# Soluzione: verificare credenziali DB e firewall

# 3. "Java not found"
which java
# Soluzione: reinstallare Java 17
```

### Problema: Apache proxy non funziona

```bash
# Verifica moduli abilitati
apache2ctl -M | grep proxy
# Devono esserci: proxy_module, proxy_http_module

# Verifica configurazione
sudo apache2ctl configtest

# Verifica logs
sudo tail -f /var/log/apache2/merchant-error.log

# Test diretto bypassando Apache
curl http://localhost:8986/api/v2/auth/health
# Se funziona, il problema è Apache config
```

### Problema: Spazio disco pieno

```bash
# Verifica spazio disponibile
df -h /

# Libera spazio logs
sudo journalctl --vacuum-time=7d
sudo find /var/log/apache2 -name "*.log.*" -mtime +7 -delete
sudo apt clean

# Verifica dimensione JAR
du -h /opt/merchant-api/merchant-api.jar
```

### Problema: Database connection timeout

```bash
# Verifica connessione da pgbe2 a DB
ping 10.10.10.13

# Test connessione MySQL
mysql -h 10.10.10.13 -u PGDBUSER -p payglobe
# Inserisci password: PNeNkar{K1.%D~V

# Se funziona, problema è config Spring Boot
# Verifica application-production.yml
```

---

## 📊 Monitoraggio Post-Deploy

### Health Checks Automatici (Cron)

```bash
# Su pgbe2
# Crea script health check
sudo nano /usr/local/bin/merchant-health-check.sh
```

```bash
#!/bin/bash
# Merchant API Health Check

HEALTH_URL="http://localhost:8986/actuator/health"
MAX_RETRIES=3

for i in $(seq 1 $MAX_RETRIES); do
    if curl -f -s $HEALTH_URL > /dev/null; then
        echo "$(date) - Health check OK"
        exit 0
    fi
    sleep 2
done

echo "$(date) - Health check FAILED after $MAX_RETRIES retries"
sudo systemctl restart merchant-api
```

```bash
# Rendi eseguibile
sudo chmod +x /usr/local/bin/merchant-health-check.sh

# Aggiungi a cron (ogni 5 minuti)
sudo crontab -e
# Aggiungi linea:
*/5 * * * * /usr/local/bin/merchant-health-check.sh >> /var/log/merchant-api/health-check.log 2>&1
```

### Verifica Logs Periodici

```bash
# Crea script log rotation personalizzato
sudo nano /etc/logrotate.d/merchant-api
```

```
/var/log/merchant-api/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 0640 pguser pguser
}
```

---

## 🔄 Aggiornamento Applicazione

```bash
# 1. Build nuovo JAR (sul tuo PC)
cd spring-boot-backend
mvn clean package -DskipTests

# 2. Stop servizio
ssh pguser@pgbe2 "sudo systemctl stop merchant-api"

# 3. Backup JAR vecchio
ssh pguser@pgbe2 "mv /opt/merchant-api/merchant-api.jar /opt/merchant-api/merchant-api.jar.backup"

# 4. Copia nuovo JAR
scp target/merchant-api.jar pguser@pgbe2:/opt/merchant-api/

# 5. Riavvia servizio
ssh pguser@pgbe2 "sudo systemctl start merchant-api"

# 6. Verifica health
sleep 10
curl http://ricevute.payglobe.it/api/v2/auth/health
```

**Oppure usa lo script automatico**:

```bash
# Rendi eseguibile
chmod +x spring-boot-backend/deploy/deploy.sh

# Esegui
./spring-boot-backend/deploy/deploy.sh
```

---

## 🛑 Rollback Emergency

Se qualcosa va storto:

```bash
# Su pgbe2

# 1. Stop Spring Boot
sudo systemctl stop merchant-api

# 2. Disabilita proxy Apache
sudo a2dissite apache-merchant
sudo systemctl reload apache2

# 3. PHP continua a funzionare normalmente!
curl http://ricevute.payglobe.it/merchant/index.php
# ✅ Dovrebbe funzionare
```

**Tempo di rollback**: ~2 minuti
**Downtime**: ZERO (PHP sempre attivo)

---

## 📞 Supporto

- Logs applicazione: `sudo journalctl -u merchant-api -f`
- Logs Apache: `sudo tail -f /var/log/apache2/merchant-error.log`
- Health check: `curl http://localhost:8986/actuator/health`
- Status servizio: `sudo systemctl status merchant-api`

Per problemi: admin@payglobe.it

---

## ✅ Checklist Post-Deploy

- [ ] Servizio merchant-api attivo: `sudo systemctl status merchant-api`
- [ ] Health check OK: `curl http://localhost:8986/actuator/health`
- [ ] Database connection OK: `curl http://localhost:8986/actuator/health/db`
- [ ] Apache proxy funziona: `curl http://ricevute.payglobe.it/api/v2/auth/health`
- [ ] PHP ancora funziona: `curl http://ricevute.payglobe.it/merchant/index.php`
- [ ] JWT_SECRET cambiato in produzione
- [ ] Log rotation configurato
- [ ] Health check cron attivo
- [ ] Backup configurato

**Deploy completato!** 🎉
