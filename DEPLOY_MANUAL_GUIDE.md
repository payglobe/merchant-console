# Guida Deploy Manuale - PayGlobe Merchant API

## ✅ Stato Attuale

- [x] Progetto Spring Boot creato
- [x] JAR buildato con successo (52 MB)
- [x] Configurazioni pronte
- [x] Script stop/start/restart creati
- [x] Porta: **8986**
- [x] Path deploy: **/opt/merchant-console**

## 📁 File Pronti per Deploy

```
C:\Users\hellrock\Desktop\merchant\spring-boot-backend\
├── target/
│   └── merchant-api.jar                    (52 MB) - Applicazione
├── deploy/
│   ├── merchant-api.service                Systemd service
│   ├── apache-merchant.conf                Apache reverse proxy
│   ├── start.sh                            Script start
│   ├── stop.sh                             Script stop
│   └── restart.sh                          Script restart
```

---

## 🚀 Procedura Deploy Completa

### Step 1: Copia File su Server

**Opzione A: SCP (raccomandato)**

```bash
# Dal tuo PC

# 1. Copia JAR (può richiedere tempo, 52MB)
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\target\merchant-api.jar pguser@pgbe2:~/

# 2. Copia configurazioni
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\deploy\merchant-api.service pguser@pgbe2:~/
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\deploy\apache-merchant.conf pguser@pgbe2:~/

# 3. Copia script
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\deploy\start.sh pguser@pgbe2:~/
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\deploy\stop.sh pguser@pgbe2:~/
scp C:\Users\hellrock\Desktop\merchant\spring-boot-backend\deploy\restart.sh pguser@pgbe2:~/
```

**Opzione B: SFTP (se SCP fallisce)**

```bash
# Usa FileZilla, WinSCP o SFTP client
# Host: pgbe2
# User: pguser
# Protocol: SFTP
# Port: 22

# Copia nella home di pguser:
# - merchant-api.jar
# - merchant-api.service
# - apache-merchant.conf
# - start.sh, stop.sh, restart.sh
```

**Opzione C: Git (alternativa)**

```bash
# Su pguser@pgbe2
git clone <repository-url>
cd merchant/spring-boot-backend
# Copia i file dalla repo
```

---

### Step 2: Setup Directory su Server

```bash
# SSH su pgbe2
ssh pguser@pgbe2

# Crea directory applicazione
sudo mkdir -p /opt/merchant-console
sudo chown pguser:pguser /opt/merchant-console

# Crea directory logs
sudo mkdir -p /var/log/merchant-api
sudo chown pguser:pguser /var/log/merchant-api

# Sposta JAR
mv ~/merchant-api.jar /opt/merchant-console/

# Verifica
ls -lh /opt/merchant-console/merchant-api.jar
# Output atteso: 52M merchant-api.jar
```

---

### Step 3: Installazione Java 17 (se non già installato)

```bash
# Su pgbe2

# Verifica se Java 17 è installato
java -version

# Se versione < 17 o non trovato:
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
```

---

### Step 4: Configurazione Systemd Service

```bash
# Su pgbe2

# Installa service file
sudo mv ~/merchant-api.service /etc/systemd/system/

# IMPORTANTE: Modifica JWT_SECRET per produzione!
sudo nano /etc/systemd/system/merchant-api.service
# Cambia la riga:
# Environment="JWT_SECRET=..."
# con un valore random sicuro (es: genera con `openssl rand -hex 32`)

# Reload systemd
sudo systemctl daemon-reload

# Abilita servizio (start automatico al boot)
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
   Active: active (running) since Tue 2025-11-12 20:50:00 CET; 5s ago
 Main PID: 12345 (java)
```

---

### Step 5: Verifica Logs e Health Check

```bash
# Su pgbe2

# Visualizza logs in tempo reale
sudo journalctl -u merchant-api -f

# Dovresti vedere:
# "Started MerchantApiApplication in X seconds"
# "Tomcat started on port(s): 8986 (http)"

# In un altro terminale, verifica health
curl http://localhost:8986/actuator/health
# Output atteso: {"status":"UP"}

# Verifica connessione database
curl http://localhost:8986/actuator/health/db
# Output atteso: {"status":"UP"}

# Se errori, controlla logs:
sudo journalctl -u merchant-api -n 100 --no-pager
```

---

### Step 6: Installazione Script Start/Stop/Restart

```bash
# Su pgbe2

# Copia script nella home
chmod +x ~/start.sh ~/stop.sh ~/restart.sh

# Verifica funzionamento
./stop.sh    # Stop servizio
./start.sh   # Start servizio
./restart.sh # Restart servizio

# (Opzionale) Sposta in /usr/local/bin per uso globale
sudo mv ~/start.sh /usr/local/bin/merchant-start
sudo mv ~/stop.sh /usr/local/bin/merchant-stop
sudo mv ~/restart.sh /usr/local/bin/merchant-restart

# Ora puoi usare ovunque:
merchant-start
merchant-stop
merchant-restart
```

---

### Step 7: Configurazione Apache Reverse Proxy

```bash
# Su pgbe2

# Installa configurazione Apache
sudo mv ~/apache-merchant.conf /etc/apache2/sites-available/

# Abilita moduli necessari
sudo a2enmod proxy proxy_http headers rewrite

# Abilita sito
sudo a2ensite apache-merchant

# Test configurazione
sudo apache2ctl configtest
# Output atteso: Syntax OK

# Reload Apache
sudo systemctl reload apache2

# Verifica Apache status
sudo systemctl status apache2
```

---

### Step 8: Test Completo

```bash
# Su pgbe2 o dal tuo PC

# Test 1: Health check diretto
curl http://localhost:8986/actuator/health
# Output: {"status":"UP"}

# Test 2: Health check via Apache proxy
curl http://ricevute.payglobe.it/api/v2/auth/health
# Output: "OK"

# Test 3: Login endpoint (senza credenziali, deve dare 401)
curl -X POST http://ricevute.payglobe.it/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"wrong"}'
# Output atteso: 401 Unauthorized

# Test 4: Statistiche dashboard (deve funzionare)
curl http://ricevute.payglobe.it/api/v2/transactions/stats
# Output: JSON con statistiche

# Test 5: PHP ancora funziona!
curl http://ricevute.payglobe.it/merchant/index.php
# Output: HTML pagina PHP

# ✅ Se tutti i test passano, deploy completato!
```

---

### Step 9: Verifica Coesistenza PHP + Spring Boot

```bash
# Su pgbe2

# Verifica porte in ascolto
sudo netstat -tulpn | grep -E "(:80|:8986)"
# Output atteso:
# tcp6  0  0 :::80      :::*  LISTEN  <pid>/apache2
# tcp6  0  0 :::8986    :::*  LISTEN  <pid>/java

# Verifica processi
ps aux | grep -E "(apache2|java)" | grep -v grep
# Dovresti vedere sia apache2 che java in esecuzione

# Verifica routing Apache
sudo tail -20 /var/log/apache2/merchant-access.log
# Controlla che richieste a /api/v2/* vadano su Spring Boot
```

---

## 📊 Monitoring Post-Deploy

### Health Check Automatico (Cron)

```bash
# Su pgbe2
sudo nano /usr/local/bin/merchant-health-check.sh
```

Incolla:
```bash
#!/bin/bash
HEALTH_URL="http://localhost:8986/actuator/health"
MAX_RETRIES=3

for i in $(seq 1 $MAX_RETRIES); do
    if curl -f -s $HEALTH_URL > /dev/null; then
        echo "$(date) - Health check OK"
        exit 0
    fi
    sleep 2
done

echo "$(date) - Health check FAILED - Restarting service"
sudo systemctl restart merchant-api
```

```bash
# Rendi eseguibile
sudo chmod +x /usr/local/bin/merchant-health-check.sh

# Aggiungi a cron (ogni 5 minuti)
sudo crontab -e
# Aggiungi:
*/5 * * * * /usr/local/bin/merchant-health-check.sh >> /var/log/merchant-api/health-check.log 2>&1
```

### Log Rotation

```bash
# Su pgbe2
sudo nano /etc/logrotate.d/merchant-api
```

Incolla:
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

## 🔧 Comandi Utili

```bash
# Visualizza logs
sudo journalctl -u merchant-api -f

# Ultimi 50 log
sudo journalctl -u merchant-api -n 50

# Status servizio
sudo systemctl status merchant-api

# Restart servizio
sudo systemctl restart merchant-api

# Stop servizio
sudo systemctl stop merchant-api

# Start servizio
sudo systemctl start merchant-api

# Verifica porte
sudo netstat -tulpn | grep 8986

# Verifica processi Java
ps aux | grep java

# Verifica spazio disco
df -h /

# Health check
curl http://localhost:8986/actuator/health
```

---

## 🛑 Rollback Emergency

Se qualcosa va storto e vuoi tornare a PHP:

```bash
# Su pgbe2

# 1. Stop Spring Boot
sudo systemctl stop merchant-api

# 2. Disabilita proxy Apache per /api/v2
sudo a2dissite apache-merchant
sudo systemctl reload apache2

# 3. PHP continua a funzionare!
curl http://ricevute.payglobe.it/merchant/index.php
# ✅ Dovrebbe funzionare

# Tempo rollback: ~2 minuti
# Downtime: ZERO (PHP sempre attivo)
```

---

## ❓ Troubleshooting

### Servizio non si avvia

```bash
# Visualizza logs dettagliati
sudo journalctl -u merchant-api -n 100 --no-pager

# Errori comuni:

# 1. "Address already in use" (porta 8986 occupata)
sudo netstat -tulpn | grep 8986
# Soluzione: killare processo o cambiare porta in application.properties

# 2. "Cannot connect to database"
telnet 10.10.10.13 3306
# Soluzione: verificare credenziali DB e firewall

# 3. "Java not found"
which java
java -version
# Soluzione: installare Java 17
```

### Apache proxy non funziona

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
# Se funziona, problema è Apache config
```

### Errore connessione database

```bash
# Test connessione da pgbe2
ping 10.10.10.13

# Test MySQL
mysql -h 10.10.10.13 -u PGDBUSER -p payglobe
# Password: PNeNkar{K1.%D~V

# Se funziona, verifica application-production.properties
```

---

## 📞 Supporto

- **Logs applicazione**: `sudo journalctl -u merchant-api -f`
- **Logs Apache**: `sudo tail -f /var/log/apache2/merchant-error.log`
- **Health check**: `curl http://localhost:8986/actuator/health`
- **Status servizio**: `sudo systemctl status merchant-api`

---

## ✅ Checklist Deploy Completato

- [ ] Java 17 installato
- [ ] Directory `/opt/merchant-console` creata
- [ ] JAR copiato (52 MB)
- [ ] Systemd service configurato
- [ ] JWT_SECRET modificato in produzione
- [ ] Servizio merchant-api attivo
- [ ] Health check OK: `curl http://localhost:8986/actuator/health`
- [ ] Database connection OK: `curl http://localhost:8986/actuator/health/db`
- [ ] Apache reverse proxy configurato
- [ ] Proxy funziona: `curl http://ricevute.payglobe.it/api/v2/auth/health`
- [ ] PHP ancora funziona: `curl http://ricevute.payglobe.it/merchant/index.php`
- [ ] Script start/stop/restart installati
- [ ] Health check cron configurato
- [ ] Log rotation configurato

**Deploy completato!** 🎉

---

## 🎯 Prossimi Step

1. **Testing parallelo**: Testa le API Spring Boot in parallelo con PHP
2. **Frontend React**: Deploy nuova dashboard (opzionale)
3. **Migrazione graduale**: Funzione per funzione da PHP a Spring Boot
4. **Monitoring**: Setup Grafana + Prometheus (opzionale)
5. **Decommissioning PHP**: Quando tutto validato

Per domande: admin@payglobe.it
