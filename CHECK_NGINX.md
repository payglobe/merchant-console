# 🔍 Troubleshooting Nginx 502 Error

## Il Problema

```
curl http://ricevute.payglobe.it/merchant-api/api/v2/auth/health
Risposta: HTTP 502 - Connection timed out
```

Nginx su pgfe (195.234.46.178) **non riesce a raggiungere** pgbe2:8986

---

## ✅ Step 1: Verifica Backend su pgbe2

SSH su pgbe2 e verifica:

```bash
ssh pguser@pgbe2

# 1. Check processo Java
ps aux | grep merchant-api.jar | grep -v grep
# Dovrebbe mostrare: java ... merchant-api.jar

# 2. Check porta 8986
netstat -tulpn | grep 8986
# Dovrebbe mostrare: tcp6 ... :::8986 ... LISTEN

# 3. Test health locale
curl http://localhost:8986/api/v2/auth/health
# Dovrebbe rispondere: OK

# 4. Check con IP interno
curl http://10.10.10.11:8986/api/v2/auth/health
# Dovrebbe rispondere: OK
```

---

## ✅ Step 2: Verifica Nginx Config su pgfe

SSH su pgfe e verifica:

```bash
ssh user@pgfe

# 1. Check configurazione nginx
cat /etc/nginx/sites-available/ricevute.payglobe.it.conf | grep -A20 "location /merchant-api"

# Dovrebbe contenere:
# location /merchant-api/ {
#     proxy_pass http://pgbe2:8986/;
#     # oppure: proxy_pass http://10.10.10.11:8986/;
#     ...
# }

# 2. Verifica che pgfe raggiunga pgbe2
ping pgbe2
# oppure: ping 10.10.10.11

# 3. Test diretto da pgfe a pgbe2:8986
curl -v http://pgbe2:8986/api/v2/auth/health
# oppure: curl -v http://10.10.10.11:8986/api/v2/auth/health
# Dovrebbe rispondere: OK

# 4. Check nginx error log
tail -50 /var/log/nginx/error.log | grep merchant-api
```

---

## 🔧 Fix Configurazione Nginx

Se il test da pgfe fallisce, la configurazione nginx è errata.

### Fix upstream

Modifica `/etc/nginx/sites-available/ricevute.payglobe.it.conf`:

```nginx
# Aggiungi upstream all'inizio del file
upstream merchant_api_backend {
    server 10.10.10.11:8986;  # Usa IP invece di hostname
    keepalive 32;
}

# Nel server block
server {
    listen 80;
    server_name ricevute.payglobe.it;

    # Location merchant API
    location /merchant-api/ {
        # Proxy verso Spring Boot su pgbe2:8986
        proxy_pass http://merchant_api_backend/;

        # Headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;

        # HTTP/1.1 per keepalive
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }

    # Resto della configurazione...
}
```

### Applica fix

```bash
# Test configurazione
sudo nginx -t

# Se OK, reload
sudo systemctl reload nginx

# Verifica
curl http://localhost/merchant-api/api/v2/auth/health
# Dovrebbe rispondere: OK
```

---

## 🔥 Fix Firewall (se necessario)

Se pgfe non raggiunge pgbe2:8986, potrebbe essere un firewall:

```bash
# Su pgbe2: verifica se porta 8986 è aperta
sudo iptables -L -n | grep 8986

# Se non è aperta, aggiungi regola (ATTENZIONE: chiedi prima!)
# sudo iptables -I INPUT -p tcp --dport 8986 -s 10.10.10.0/24 -j ACCEPT
```

---

## ✅ Step 3: Test Finale

Dopo il fix:

```bash
# Dal tuo PC
curl http://ricevute.payglobe.it/merchant-api/api/v2/auth/health
# Dovrebbe rispondere: OK

# Test stats
curl http://ricevute.payglobe.it/merchant-api/api/v2/transactions/stats
# Dovrebbe rispondere: {"total":...,"volume":...}
```

---

## 🚨 Quick Check Comandi

Esegui questi comandi in ordine:

### Su pgbe2
```bash
curl http://localhost:8986/api/v2/auth/health
```
**Atteso**: `OK`

### Su pgfe
```bash
curl http://10.10.10.11:8986/api/v2/auth/health
```
**Atteso**: `OK`

### Su pgfe (dopo nginx reload)
```bash
curl http://localhost/merchant-api/api/v2/auth/health
```
**Atteso**: `OK`

### Dal tuo PC
```bash
curl http://ricevute.payglobe.it/merchant-api/api/v2/auth/health
```
**Atteso**: `OK`

---

## 📝 Note

- Il backend Spring Boot è su **pgbe2:8986**
- Nginx frontend è su **pgfe**
- Il path `/merchant-api/` deve essere proxato a `http://pgbe2:8986/`
- Il trailing slash `/` è importante nel proxy_pass!

---

**Se tutti i test passano**, il frontend React funzionerà! 🎉
