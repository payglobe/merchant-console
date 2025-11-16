# 🖥️ Configurazione Server pgbe2

## 📋 Info Connessione

```bash
Host: pgbe2
User: pguser

# Connessione SSH
ssh pguser@pgbe2
```

---

## 📁 Path Deployment

### Frontend React
```
Path: /opt/merchant-console/frontend/

Struttura:
├── src/
│   ├── components/
│   │   ├── Login.jsx
│   │   ├── Dashboard.jsx
│   │   ├── Layout.jsx
│   │   ├── ActivationCodes.jsx
│   │   ├── UsersManagement.jsx
│   │   └── BinTableUpload.jsx
│   ├── services/
│   │   └── api.js
│   └── App.jsx
├── dist/                  # Build output
├── node_modules/
├── package.json
├── react-start.sh         # Script avvio
├── react-restart.sh       # Script riavvio
└── react-stop.sh          # Script stop
```

**URL pubblico**: https://ricevute.payglobe.it/merchant-dashboard

### Backend Spring Boot
```
Path: /opt/merchant-console/

File principale: merchant-api.jar
Servizio systemd: merchant-api.service
Porta: 8080 (localhost)
```

**Endpoint API**: https://ricevute.payglobe.it/merchant-api/api/v2/

---

## 🚀 Comandi Rapidi

### Frontend
```bash
# Naviga al progetto
cd /opt/merchant-console/frontend

# Rebuild
npm run build

# Riavvia
./react-restart.sh

# Verifica log
tail -f frontend.log
```

### Backend
```bash
# Naviga al progetto
cd /opt/merchant-console

# Riavvia servizio
sudo systemctl restart merchant-api.service

# Verifica stato
sudo systemctl status merchant-api.service

# Segui log
sudo journalctl -u merchant-api.service -f

# Health check
curl http://localhost:8080/merchant-api/api/v2/auth/health
```

---

## 🗄️ Database

```
Host: localhost (su pgbe2)
Database: payglobe
User: root
Password: PNeNkar{K1.%D~V

# Connessione
mysql -u root -p payglobe
```

**Tabelle principali**:
- `users` - Utenti sistema
- `stores` - Negozi/Terminal
- `transactions` - Transazioni
- `activation_codes` - Codici attivazione PAX
- `bin_table` - Database BIN banche

---

## 📝 Note Deployment

1. **Frontend è SORGENTE**: Non solo dist, ma progetto completo con build su server
2. **Backup automatici**: Usa date +%Y%m%d-%H%M%S per timestamp
3. **Scripts React**: Sempre usare react-restart.sh invece di kill manuale
4. **Sudo**: Alcuni comandi richiedono sudo (systemctl, chown)
5. **Log**: frontend.log per React, journalctl per Spring Boot
6. **⚠️ NON USARE /tmp**: Deploy JAR e file direttamente in /opt/merchant-console, mai usare /tmp come staging!
