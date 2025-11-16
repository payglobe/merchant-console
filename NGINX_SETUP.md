# 🔧 Configurazione Nginx su pgfe

## 📍 Obiettivo

Configurare nginx su **pgfe** (195.234.46.178) per:
1. Frontend React: `http://ricevute.payglobe.it/merchant-dashboard/` → pgbe2:8987
2. Backend API: `http://ricevute.payglobe.it/merchant-api/` → pgbe2:8986

---

## 🚀 Procedura Rapida

### 1. SSH su pgfe
```bash
ssh user@pgfe
```

### 2. Modifica configurazione nginx
```bash
sudo nano /etc/nginx/sites-available/ricevute.payglobe.it.conf
```

### 3. Aggiungi queste location al server block

```nginx
server {
    listen 80;
    server_name ricevute.payglobe.it;

    # === REACT FRONTEND ===
    location /merchant-dashboard/ {
        proxy_pass http://10.10.10.11:8987/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }

    # === BACKEND API ===
    location /merchant-api/ {
        proxy_pass http://10.10.10.11:8986/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }

    # Resto della configurazione esistente...
}
```

### 4. Test configurazione
```bash
sudo nginx -t
```

Output atteso:
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 5. Reload nginx
```bash
sudo systemctl reload nginx
```

### 6. Verifica
```bash
# Test backend API
curl http://ricevute.payglobe.it/merchant-api/api/v2/auth/health
# Atteso: OK

# Test frontend (dal browser)
# Apri: http://ricevute.payglobe.it/merchant-dashboard/
```

---

## 🔍 Troubleshooting

### Problema: 502 Bad Gateway

**Causa 1:** pgfe non raggiunge pgbe2

```bash
# Su pgfe, testa connessione a pgbe2
ping 10.10.10.11
telnet 10.10.10.11 8987
telnet 10.10.10.11 8986
```

**Causa 2:** Firewall su pgbe2 blocca porte

```bash
# Su pgbe2, verifica porte in ascolto
netstat -tulpn | grep 8987
netstat -tulpn | grep 8986

# Verifica firewall
sudo iptables -L -n | grep 898
```

**Soluzione:** Se firewall blocca, aggiungi regole (SOLO se necessario):
```bash
# Su pgbe2
sudo iptables -I INPUT -p tcp --dport 8987 -s 10.10.10.0/24 -j ACCEPT
sudo iptables -I INPUT -p tcp --dport 8986 -s 10.10.10.0/24 -j ACCEPT
```

### Problema: 404 Not Found

**Causa:** Trailing slash nel proxy_pass

**Soluzione:** Assicurati che proxy_pass abbia il `/` finale:
```nginx
proxy_pass http://10.10.10.11:8987/;   # ✅ CORRETTO
proxy_pass http://10.10.10.11:8987;    # ❌ SBAGLIATO
```

### Problema: Timeout dopo 60 secondi

**Soluzione:** Aumenta timeout in nginx:
```nginx
location /merchant-dashboard/ {
    proxy_pass http://10.10.10.11:8987/;
    proxy_connect_timeout 120s;
    proxy_send_timeout 120s;
    proxy_read_timeout 120s;
}
```

---

## 📊 Test Completi

Dopo aver configurato nginx su pgfe:

### 1. Test Backend Health
```bash
curl http://ricevute.payglobe.it/merchant-api/api/v2/auth/health
```
**Atteso:** `OK`

### 2. Test Backend Stats
```bash
curl http://ricevute.payglobe.it/merchant-api/api/v2/transactions/stats
```
**Atteso:** JSON con statistiche

### 3. Test Frontend Homepage
```bash
curl -I http://ricevute.payglobe.it/merchant-dashboard/
```
**Atteso:** `HTTP/1.1 200 OK`

### 4. Test Login (dal browser)
1. Apri: `http://ricevute.payglobe.it/merchant-dashboard/`
2. Inserisci credenziali
3. Dovrebbe mostrare dashboard

---

## 🌐 URL Finali

Dopo configurazione nginx:

- **Frontend React:** http://ricevute.payglobe.it/merchant-dashboard/
- **Backend API:** http://ricevute.payglobe.it/merchant-api/api/v2/*
- **Login page:** http://ricevute.payglobe.it/merchant-dashboard/login

---

## 🔐 HTTPS (Opzionale ma raccomandato)

Se vuoi abilitare HTTPS:

### 1. Modifica configurazione per HTTPS
```nginx
server {
    listen 443 ssl;
    server_name ricevute.payglobe.it;

    ssl_certificate /etc/ssl/certs/ricevute.payglobe.it.crt;
    ssl_certificate_key /etc/ssl/private/ricevute.payglobe.it.key;

    # Location blocks come sopra...
}

# Redirect HTTP → HTTPS
server {
    listen 80;
    server_name ricevute.payglobe.it;
    return 301 https://$server_name$request_uri;
}
```

### 2. Genera certificato SSL

#### Opzione A: Let's Encrypt (gratis)
```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d ricevute.payglobe.it
```

#### Opzione B: Certificato esistente
Usa certificato già installato su pgfe.

---

## 📝 Log Monitoring

### Nginx error log
```bash
sudo tail -f /var/log/nginx/error.log
```

### Nginx access log
```bash
sudo tail -f /var/log/nginx/access.log | grep merchant
```

### Backend log (su pgbe2)
```bash
ssh pguser@pgbe2 "tail -f /opt/merchant-console/logs/merchant-api.log"
```

### Frontend log (su pgbe2)
```bash
ssh pguser@pgbe2 "tail -f /opt/merchant-console/frontend/frontend.log"
```

---

## ✅ Checklist

- [ ] SSH su pgfe fatto
- [ ] Configurazione nginx modificata
- [ ] `nginx -t` OK
- [ ] nginx reloaded
- [ ] Test backend health OK
- [ ] Test frontend homepage OK
- [ ] Test login funziona
- [ ] Dashboard mostra dati

---

**Se tutto è OK, il sistema è completo!** 🎉
