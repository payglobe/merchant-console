# MC5 v3.0 - Sistema di Autenticazione

## 📋 Panoramica

Sistema completo di autenticazione per PayGlobe MC5 v3.0 con design moderno, sicurezza avanzata e integrazione email SMTP.

---

## 🆕 File Creati

### 1. **login.php**
Pagina di login moderna con:
- Design glassmorphism con gradiente animato
- Validazione real-time
- Toggle mostra/nascondi password
- Gestione sessione (24 ore)
- Controllo scadenza password (45 giorni)
- Supporto per cambio password forzato
- Messaggi di feedback per logout/session expired

**Path**: `/merchant/frontend/mc5/login.php`

### 2. **forgot_password.php**
Pagina recupero password con:
- Invio email automatico tramite SMTP PayGlobe
- Token sicuro con scadenza 2 ore
- Template email HTML moderno
- Protezione contro enumerazione account
- Design responsive e user-friendly

**Path**: `/merchant/frontend/mc5/forgot_password.php`

### 3. **reset_password.php**
Pagina reset password con:
- Validazione password avanzata (8+ caratteri, maiuscola, numero, carattere speciale)
- Barra di forza password in tempo reale
- Indicatori requisiti con check animati
- Gestione token email e cambio password forzato
- Design moderno coerente con MC5

**Path**: `/merchant/frontend/mc5/reset_password.php`

### 4. **authentication.php**
Sistema di autenticazione centrale con:
- Gestione sessione sicura
- Timeout sessione (24 ore)
- Rigenerazione ID sessione periodica (ogni ora)
- Funzioni helper: `requireAuth()`, `requireAdmin()`, `isAuthenticated()`
- Protezione automatica delle pagine
- Flash messages per feedback utente

**Path**: `/merchant/frontend/mc5/authentication.php`

### 5. **logout.php**
Handler logout con:
- Distruzione sessione completa
- Pulizia cookie
- Redirect a login con messaggio

**Path**: `/merchant/frontend/mc5/logout.php`

---

## 🔧 Configurazione SMTP

Le email vengono inviate usando PHPMailer con le credenziali PayGlobe:

```php
Host: email.payglobe.it
Username: info
Password: md-pu08ca80tOb6IJIEQGmLzg
Port: 587 (STARTTLS)
From: info@payglobe.it
```

**Mittente visualizzato**: PayGlobe MC5
**Email di supporto**: support@payglobe.it

---

## 🎨 Design e Stile

Tutte le pagine seguono il design system MC5 v3.0:

- **Font**: Inter (Google Fonts)
- **Gradiente principale**: `linear-gradient(135deg, #667eea 0%, #764ba2 100%)`
- **Effetti**: Glassmorphism, backdrop-filter blur
- **Animazioni**: Slide up, fade in, floating background
- **Responsive**: Ottimizzato per mobile e desktop
- **Icons**: Font Awesome 6.5

---

## 🔐 Funzionalità di Sicurezza

### Password Requirements
- Minimo 8 caratteri
- Almeno 1 lettera maiuscola
- Almeno 1 numero
- Almeno 1 carattere speciale (!@#$%^&*)

### Sessione
- Durata: 24 ore
- Rigenerazione ID ogni ora
- Timeout automatico
- Protezione CSRF (session regeneration)

### Password Reset
- Token unico 64 caratteri (hex)
- Scadenza: 2 ore
- Uso singolo (token eliminato dopo reset)
- Link sicuro via HTTPS

### Password Expiry
- Scadenza automatica dopo 45 giorni
- Redirect automatico a reset password
- Cambio password forzato per nuovi utenti

---

## 📂 Struttura File

```
frontend/mc5/
├── login.php                 # Login page
├── forgot_password.php       # Password recovery
├── reset_password.php        # Password reset
├── authentication.php        # Auth system
├── logout.php               # Logout handler
├── menu-v3.php              # Top navigation (aggiornato)
├── index.php                # Dashboard (protected)
├── stores.php               # Stores page (protected)
├── tutte-5.php              # Transactions (protected)
└── assets/
    └── css/
        └── design-modern.css # Design system
```

---

## 🚀 Utilizzo

### Proteggere una Pagina

Per proteggere una pagina MC5, aggiungi all'inizio:

```php
<?php
require_once 'authentication.php';
require_once('menu-v3.php');

// La pagina è ora protetta automaticamente
// Variabili disponibili:
// - $user: array con dati utente
// - $role: stringa ruolo ("Admin" o "Reader")
// - $application: string BU dell'utente
?>
```

### Richiedere Privilegi Admin

```php
<?php
require_once 'authentication.php';
require_once('menu-v3.php');

requireAdmin(); // Blocca se non admin

// Solo admin arrivano qui
?>
```

### Controllare Autenticazione Manualmente

```php
<?php
require_once 'authentication.php';

if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "Benvenuto " . $user['email'];
}

if (isAdmin()) {
    echo "Sei un amministratore";
}
?>
```

---

## 📧 Template Email

L'email di reset password include:

✅ Design moderno con gradiente MC5
✅ Logo PayGlobe embedded
✅ Pulsante CTA grande e visibile
✅ Link testuale come fallback
✅ Box informazioni di sicurezza
✅ Footer con contatti supporto
✅ Versione plain text alternativa

---

## 🧪 Test Flow

### Test Login
1. Vai a: `https://ricevute.payglobe.it/merchant/frontend/mc5/login.php`
2. Inserisci credenziali valide
3. Verifica redirect a dashboard

### Test Forgot Password
1. Vai a: `https://ricevute.payglobe.it/merchant/frontend/mc5/forgot_password.php`
2. Inserisci email registrata
3. Controlla inbox per email reset
4. Clicca link nell'email
5. Imposta nuova password
6. Verifica login con nuova password

### Test Logout
1. Loggati in MC5
2. Clicca "Logout" nel menu utente (top-right)
3. Verifica redirect a login con messaggio successo

### Test Session Expiry
1. Loggati in MC5
2. Attendi 24 ore (o modifica `SESSION_TIMEOUT` per test)
3. Tenta di accedere a una pagina
4. Verifica redirect a login con messaggio "Sessione scaduta"

---

## 🔗 Link Importanti

- **Login MC5**: https://ricevute.payglobe.it/merchant/frontend/mc5/login.php
- **Forgot Password**: https://ricevute.payglobe.it/merchant/frontend/mc5/forgot_password.php
- **Dashboard MC5**: https://ricevute.payglobe.it/merchant/frontend/mc5/index.php

---

## ⚠️ Note Importanti

1. **Separazione da Merchant**: Questi file sono SOLO per MC5 (`frontend/mc5/`). Non toccano il sistema merchant principale.

2. **Database Users Table**: Assicurati che la tabella `users` abbia questi campi:
   - `id` (INT)
   - `email` (VARCHAR)
   - `password` (VARCHAR - hashed)
   - `bu` (VARCHAR - business unit)
   - `active` (TINYINT - 0/1)
   - `password_last_changed` (DATETIME)
   - `force_password_change` (TINYINT - 0/1)
   - `password_reset_token` (VARCHAR - nullable)
   - `password_reset_token_expiry` (DATETIME - nullable)
   - `last_login` (DATETIME - nullable)

3. **PHPMailer**: Assicurati che `composer` abbia installato PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```

4. **HTTPS**: Le email contengono link HTTPS. Assicurati che il certificato SSL sia valido.

5. **Logs**: Gli errori di invio email vengono loggati con `error_log()`. Controlla i log PHP per debug.

---

## 🎯 TODO Future (Opzionale)

- [ ] 2FA (Two-Factor Authentication)
- [ ] Login con QR code
- [ ] Limite tentativi login (rate limiting)
- [ ] Log accessi (audit trail)
- [ ] Notifica email per login da nuovo dispositivo
- [ ] Remember me (cookie persistente)

---

## 👨‍💻 Supporto

Per problemi o domande:
- Email: support@payglobe.it
- Documentazione: Questo file (README_AUTH.md)

---

**Creato per**: PayGlobe MC5 v3.0
**Data**: 2025-01-21
**Autore**: Claude Code con supervisione utente
