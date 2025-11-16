# Piano di Migrazione Conservativo
## Spring Boot su Infrastruttura Esistente

---

## 📋 Executive Summary

Questo documento descrive un approccio **conservativo e graduale** per migrare da PHP a Spring Boot **mantenendo l'infrastruttura esistente**:

- ✅ **Stesso database MySQL** (10.10.10.13)
- ✅ **Stesse tabelle** (zero breaking changes)
- ✅ **Server esistente** pgbe2 (Ubuntu 18.04, Apache2)
- ✅ **Migrazione graduale** (PHP + Spring Boot coesistono)
- ✅ **Zero downtime** durante la migrazione
- ✅ **Rollback facile** in caso di problemi

**Tempo stimato**: 2-3 mesi
**Budget**: €50.000-70.000 (vs €140.000 della soluzione cloud-native)

---

## 🖥️ Infrastruttura Attuale

### Server pgbe2
```
OS: Ubuntu 18.04.6 LTS
RAM: 15 GB (11 GB disponibili)
Disco: 20 GB (5.5 GB disponibili) ⚠️ LIMITATO
CPU: Non specificata (da verificare)
Web Server: Apache2
Path: /var/www/html/merchant/
```

### Database
```
Host: 10.10.10.13 (server separato)
DBMS: MySQL 5.7+ (probabile)
Database: payglobe
User: PGDBUSER
Charset: utf8mb4
```

### Stack Attuale
- PHP 7.4+
- Apache2 con mod_php
- MySQL connector (mysqli)
- Session-based authentication

---

## 🎯 Architettura Proposta (Coesistenza)

```
┌─────────────────────────────────────────────────────────────┐
│                    pgbe2 Server (Apache2)                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────┐      ┌────────────────────────┐  │
│  │   PHP Application    │      │  Spring Boot App       │  │
│  │   (Esistente)        │      │  (Nuovo)               │  │
│  │                      │      │                        │  │
│  │  /merchant/*.php     │      │  Port 8080             │  │
│  │                      │      │  /api/*                │  │
│  └──────────┬───────────┘      └────────┬───────────────┘  │
│             │                           │                   │
│             │                           │                   │
│  ┌──────────▼───────────────────────────▼───────────────┐  │
│  │           Apache2 Reverse Proxy                      │  │
│  │                                                       │  │
│  │  Route 1: /merchant/*.php      → PHP (mod_php)       │  │
│  │  Route 2: /api/*               → Spring Boot :8080   │  │
│  │  Route 3: /merchant/dashboard  → React SPA           │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
└──────────────────────────┬───────────────────────────────────┘
                           │
                           ▼
              ┌────────────────────────┐
              │   MySQL Database       │
              │   10.10.10.13          │
              │                        │
              │   - transactions       │
              │   - stores             │
              │   - users              │
              │   - activation_codes   │
              │   - terminal_config    │
              │   (tutte esistenti)    │
              └────────────────────────┘
```

### URL Mapping

| URL | Backend | Note |
|-----|---------|------|
| `/merchant/index.php` | PHP | Dashboard esistente (mantieni attivo) |
| `/merchant/stores.php` | PHP | Lista stores esistente |
| `/merchant/login.php` | PHP | Login esistente |
| **`/api/v2/transactions`** | **Spring Boot** | Nuove API REST |
| **`/api/v2/stores`** | **Spring Boot** | Nuove API REST |
| **`/api/v2/auth/login`** | **Spring Boot** | Autenticazione JWT |
| **`/merchant/dashboard`** | **React SPA** | Nuova dashboard moderna |

### Vantaggi Coesistenza

✅ **Zero Breaking Changes**: PHP continua a funzionare
✅ **Migrazione Graduale**: Modulo per modulo
✅ **Rollback Facile**: Basta disattivare proxy
✅ **Testing in Produzione**: A/B testing possibile
✅ **Formazione Utenti Graduale**: Non tutti subito sul nuovo

---

## 📦 Setup Infrastruttura

### 1. Installazione Java 17 su pgbe2

```bash
# SSH su pgbe2
ssh pguser@pgbe2

# Aggiungi repository OpenJDK
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

### 2. Configurazione Apache2 Reverse Proxy

```apache
# /etc/apache2/sites-available/merchant.conf

<VirtualHost *:80>
    ServerName ricevute.payglobe.it
    DocumentRoot /var/www/html

    # Abilita moduli necessari
    # sudo a2enmod proxy proxy_http headers rewrite

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/merchant-error.log
    CustomLog ${APACHE_LOG_DIR}/merchant-access.log combined

    # ========================================
    # ROUTE 1: API Spring Boot (priorità massima)
    # ========================================
    ProxyPreserveHost On

    # API REST Spring Boot (porta 8080)
    <Location /api/v2>
        ProxyPass http://localhost:8080/api/v2
        ProxyPassReverse http://localhost:8080/api/v2

        # Headers per forwarding
        RequestHeader set X-Forwarded-Proto "http"
        RequestHeader set X-Forwarded-Port "80"

        # CORS (se necessario)
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
        Header always set Access-Control-Allow-Headers "Content-Type, Authorization"

        # OPTIONS preflight
        RewriteEngine On
        RewriteCond %{REQUEST_METHOD} OPTIONS
        RewriteRule ^(.*)$ $1 [R=204,L]
    </Location>

    # ========================================
    # ROUTE 2: Nuova Dashboard React SPA
    # ========================================
    <Location /merchant/dashboard>
        ProxyPass http://localhost:8080/dashboard
        ProxyPassReverse http://localhost:8080/dashboard
    </Location>

    # ========================================
    # ROUTE 3: PHP Esistente (default)
    # ========================================
    <Directory /var/www/html/merchant>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # PHP handler
        <FilesMatch \.php$>
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>

    # ========================================
    # ROUTE 4: API Terminal Config (mantieni PHP pubblico)
    # ========================================
    Alias /api/terminal /var/www/html/merchant/api/terminal
    <Directory /var/www/html/merchant/api/terminal>
        Options -Indexes
        AllowOverride None
        Require all granted
    </Directory>

</VirtualHost>

# HTTPS Configuration (se certificato disponibile)
<VirtualHost *:443>
    ServerName ricevute.payglobe.it

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/payglobe.crt
    SSLCertificateKeyFile /etc/ssl/private/payglobe.key

    # Stessa configurazione di sopra...
    Include /etc/apache2/sites-available/merchant.conf
</VirtualHost>
```

### 3. Systemd Service per Spring Boot

```ini
# /etc/systemd/system/merchant-api.service

[Unit]
Description=PayGlobe Merchant API (Spring Boot)
After=network.target mysql.service

[Service]
Type=simple
User=pguser
Group=pguser
WorkingDirectory=/opt/merchant-api

# Java options
Environment="JAVA_HOME=/usr/lib/jvm/java-17-openjdk-amd64"
Environment="JAVA_OPTS=-Xmx1024m -Xms512m -XX:+UseG1GC -XX:MaxGCPauseMillis=200"

# Spring profiles
Environment="SPRING_PROFILES_ACTIVE=production"

# Esegui JAR
ExecStart=/usr/bin/java $JAVA_OPTS -jar /opt/merchant-api/merchant-api.jar

# Restart policy
Restart=on-failure
RestartSec=10
StandardOutput=journal
StandardError=journal

# Limiti risorse
LimitNOFILE=65536
MemoryLimit=1.5G

[Install]
WantedBy=multi-user.target
```

```bash
# Abilita e avvia servizio
sudo systemctl daemon-reload
sudo systemctl enable merchant-api
sudo systemctl start merchant-api
sudo systemctl status merchant-api

# Verifica logs
sudo journalctl -u merchant-api -f
```

### 4. Configurazione Spring Boot

```yaml
# /opt/merchant-api/application-production.yml

server:
  port: 8080
  servlet:
    context-path: /
  compression:
    enabled: true
  tomcat:
    threads:
      max: 50
      min-spare: 10
    max-connections: 500

spring:
  application:
    name: merchant-api

  # ========================================
  # Database MySQL Esistente
  # ========================================
  datasource:
    url: jdbc:mysql://10.10.10.13:3306/payglobe?useUnicode=true&characterEncoding=utf8mb4&serverTimezone=Europe/Rome&useLegacyDatetimeCode=false
    username: PGDBUSER
    password: PNeNkar{K1.%D~V
    driver-class-name: com.mysql.cj.jdbc.Driver

    # HikariCP connection pool
    hikari:
      maximum-pool-size: 10  # Limitato per non sovraccaricare DB condiviso con PHP
      minimum-idle: 2
      connection-timeout: 30000
      idle-timeout: 600000
      max-lifetime: 1800000
      pool-name: MerchantHikariPool

  # ========================================
  # JPA/Hibernate
  # ========================================
  jpa:
    database-platform: org.hibernate.dialect.MySQL8Dialect
    hibernate:
      ddl-auto: validate  # IMPORTANTE: solo validate, nessuna modifica schema!
      naming:
        physical-strategy: org.hibernate.boot.model.naming.PhysicalNamingStrategyStandardImpl
        # Usa nomi tabelle esatti come nel DB (users, stores, transactions, ecc.)
    properties:
      hibernate:
        show_sql: false
        format_sql: false
        jdbc:
          batch_size: 20
          fetch_size: 50
        order_inserts: true
        order_updates: true
        generate_statistics: false
        # NO L2 cache per evitare disallineamenti con PHP
        cache:
          use_second_level_cache: false
          use_query_cache: false

  # ========================================
  # Session Management (Dual: JWT + Session per compatibilità)
  # ========================================
  session:
    store-type: none  # Stateless per JWT

# ========================================
# Security
# ========================================
jwt:
  secret: ${JWT_SECRET:your-very-long-secret-key-here-change-in-production}
  expiration: 900000  # 15 minuti
  refresh-expiration: 604800000  # 7 giorni

# ========================================
# Logging
# ========================================
logging:
  level:
    root: INFO
    com.payglobe.merchant: DEBUG
    org.springframework.web: INFO
    org.hibernate.SQL: DEBUG
    org.hibernate.type.descriptor.sql.BasicBinder: TRACE
  file:
    name: /var/log/merchant-api/application.log
    max-size: 10MB
    max-history: 30

# ========================================
# Actuator (Health checks per Apache)
# ========================================
management:
  endpoints:
    web:
      exposure:
        include: health,info,metrics
      base-path: /actuator
  endpoint:
    health:
      show-details: when-authorized
  health:
    db:
      enabled: true

# ========================================
# CORS (se necessario per sviluppo)
# ========================================
cors:
  allowed-origins: http://localhost:3000,https://ricevute.payglobe.it
  allowed-methods: GET,POST,PUT,DELETE,OPTIONS
  allowed-headers: "*"
  allow-credentials: true
```

---

## 🗄️ Database: ZERO Modifiche Iniziali

### Strategia Conservativa

**IMPORTANTE**: Spring Boot userà **esattamente le stesse tabelle** del PHP, senza modifiche allo schema.

### Mapping JPA Entities su Tabelle Esistenti

```java
// User.java - Mappa tabella "users" esistente
@Entity
@Table(name = "users")  // Nome esatto tabella PHP
public class User {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "email", unique = true, nullable = false)
    private String email;

    @Column(name = "password", nullable = false)
    private String password;  // BCrypt hash (PHP usa PASSWORD_DEFAULT = bcrypt)

    @Column(name = "bu", nullable = false)
    private String bu;

    @Column(name = "ragione_sociale")
    private String ragioneSociale;

    @Column(name = "active")
    private Boolean active = true;

    @Column(name = "force_password_change")
    private Boolean forcePasswordChange = false;

    @Column(name = "password_last_changed")
    private LocalDateTime passwordLastChanged;

    @Column(name = "last_login")
    private LocalDateTime lastLogin;

    @Column(name = "created_at", updatable = false)
    @CreationTimestamp
    private LocalDateTime createdAt;

    // Getters/Setters
}
```

```java
// Transaction.java - Mappa tabella "transactions" esistente
@Entity
@Table(name = "transactions", indexes = {
    @Index(name = "idx_transaction_date", columnList = "transaction_date"),
    @Index(name = "idx_posid", columnList = "posid")
})
public class Transaction {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "posid", nullable = false)
    private String posid;

    @Column(name = "transaction_date", nullable = false)
    private LocalDateTime transactionDate;

    @Column(name = "transaction_type", nullable = false)
    private String transactionType;

    @Column(name = "amount", nullable = false, precision = 10, scale = 2)
    private BigDecimal amount;

    @Column(name = "pan")
    private String pan;

    @Column(name = "card_brand")
    private String cardBrand;

    @Column(name = "settlement_flag")
    private String settlementFlag;  // '1' = OK, altro = NO

    @Column(name = "response_code")
    private String responseCode;

    @Column(name = "ib_response_code")
    private String ibResponseCode;

    @Column(name = "authorization_code")
    private String authorizationCode;

    @Column(name = "rrn")
    private String rrn;

    @Column(name = "stan")
    private String stan;

    @Column(name = "created_at", updatable = false)
    @CreationTimestamp
    private LocalDateTime createdAt;

    // Relazione con Store (lazy loading)
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "posid", referencedColumnName = "terminal_id",
                insertable = false, updatable = false)
    private Store store;

    // Getters/Setters
}
```

```java
// Store.java - Mappa tabella "stores" esistente
@Entity
@Table(name = "stores")
public class Store {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "TerminalID", unique = true, nullable = false)
    private String terminalId;

    @Column(name = "bu", nullable = false)
    private String bu;

    @Column(name = "Insegna")
    private String insegna;

    @Column(name = "Ragione_Sociale")
    private String ragioneSociale;

    @Column(name = "indirizzo")
    private String indirizzo;

    @Column(name = "citta")
    private String citta;

    @Column(name = "cap")
    private String cap;

    @Column(name = "prov")
    private String prov;

    @Column(name = "country")
    private String country;

    @Column(name = "Modello_pos")
    private String modelloPos;

    // Getters/Setters
}
```

### Naming Strategy

**IMPORTANTE**: Spring Boot di default converte i nomi (camelCase → snake_case). Per usare i nomi esatti delle tabelle PHP:

```java
@Configuration
public class JpaConfig {

    @Bean
    public PhysicalNamingStrategy physicalNamingStrategy() {
        // Usa nomi ESATTI come nel database
        return new PhysicalNamingStrategyStandardImpl();
    }
}
```

### Flyway: Solo Validazione Iniziale

```yaml
# application.yml
spring:
  flyway:
    enabled: false  # Disabilitato inizialmente, nessuna migrazione automatica

  jpa:
    hibernate:
      ddl-auto: validate  # Solo validazione, NO creazione/modifica tabelle
```

### Future: Tabelle Aggiuntive (Opzionale)

Se in futuro vorrai aggiungere funzionalità che richiedono nuove tabelle:

```sql
-- V1__add_user_sessions.sql (Flyway migration futura)
CREATE TABLE user_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,

    CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V2__add_api_audit_log.sql
CREATE TABLE api_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    endpoint VARCHAR(255) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    status_code INT,
    request_body TEXT,
    response_body TEXT,
    execution_time_ms INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Vantaggi**:
- PHP continua a usare le vecchie tabelle
- Spring Boot può aggiungere tabelle nuove senza impatti
- Migrazione graduale funzionalità

---

## 🔄 Migrazione Graduale (Modulo per Modulo)

### Fase 1: Setup Infrastruttura (1 settimana)

**Attività**:
1. ✅ Installa Java 17 su pgbe2
2. ✅ Configura Apache2 reverse proxy
3. ✅ Crea systemd service per Spring Boot
4. ✅ Deploy skeleton Spring Boot (solo health check)
5. ✅ Test connessione database

**Verifiche**:
```bash
# Test health endpoint
curl http://localhost:8080/actuator/health
# Output: {"status":"UP"}

# Test database connection
curl http://localhost:8080/actuator/health/db
# Output: {"status":"UP"}

# Test proxy Apache
curl http://ricevute.payglobe.it/api/v2/health
# Output: {"status":"UP"}
```

---

### Fase 2: API Transactions (2 settimane)

**Migra**: Dashboard transazioni (index.php) → API REST

**Endpoints Nuovi**:
- `GET /api/v2/transactions` → Lista con filtri
- `GET /api/v2/transactions/stats` → KPI dashboard
- `GET /api/v2/transactions/export` → Export CSV

**PHP Mantiene**:
- Login (login.php)
- Layout (header.php, footer.php)
- Altre pagine

**Test Parallelo**:
```javascript
// Frontend può chiamare ENTRAMBI per confronto
const phpData = await fetch('/merchant/index.php?format=json');
const springData = await fetch('/api/v2/transactions');

// Confronta risultati per validare
console.assert(phpData.total === springData.total);
```

---

### Fase 3: API Stores (1 settimana)

**Migra**: Gestione stores (stores.php) → API REST

**Endpoints Nuovi**:
- `GET /api/v2/stores` → Lista con filtri
- `GET /api/v2/stores/{id}` → Dettaglio
- `GET /api/v2/stores/{id}/transactions` → Transazioni store

**PHP Mantiene**:
- Tutto il resto

---

### Fase 4: Autenticazione JWT (1 settimana)

**Migra**: Sistema autenticazione

**Endpoints Nuovi**:
- `POST /api/v2/auth/login` → Login con JWT
- `POST /api/v2/auth/refresh` → Refresh token
- `POST /api/v2/auth/logout` → Logout

**Dual Authentication**:
- PHP usa SESSION (esistente)
- Spring Boot usa JWT (nuovo)
- Utente può scegliere quale usare

**Compatibilità**:
```java
// Spring Boot può validare anche sessioni PHP (opzionale)
@Component
public class PhpSessionValidator {

    public boolean validatePhpSession(String phpSessionId) {
        // Leggi sessione da file system PHP (/var/lib/php/sessions/)
        // o da Redis se PHP usa Redis sessions
        File sessionFile = new File("/var/lib/php/sessions/sess_" + phpSessionId);
        if (sessionFile.exists()) {
            String sessionData = new String(Files.readAllBytes(sessionFile.toPath()));
            return sessionData.contains("username");
        }
        return false;
    }
}
```

---

### Fase 5: Nuova Dashboard React (2 settimane)

**Deploy**: React SPA su `/merchant/dashboard`

**Caratteristiche**:
- Chiama API Spring Boot (`/api/v2/*`)
- Design moderno Material-UI
- Coesiste con dashboard PHP (`/merchant/index.php`)

**URL Struttura**:
- **Vecchia**: `https://ricevute.payglobe.it/merchant/index.php`
- **Nuova**: `https://ricevute.payglobe.it/merchant/dashboard`

**Utenti possono scegliere**!

---

### Fase 6: Admin & Altre Funzionalità (2 settimane)

**Migra**:
- Admin users management
- Activation codes
- Terminal config
- Statistics avanzate

---

### Fase 7: Decommissioning PHP Graduale (1 settimana)

**Solo quando tutto è validato**:
1. Redirect automatici da PHP a Spring Boot
2. Disabilita pagine PHP una per una
3. Mantieni API terminal config.php (PAX devices)

---

## 💾 Gestione Spazio Disco (CRITICO)

⚠️ **ATTENZIONE**: Server ha solo **5.5 GB disponibili**!

### Soluzioni Immediate

#### 1. Cleanup Logs Vecchi

```bash
# Pulisci logs Apache vecchi
sudo find /var/log/apache2 -name "*.log.*" -mtime +7 -delete

# Pulisci journal systemd vecchio
sudo journalctl --vacuum-time=7d

# Pulisci cache apt
sudo apt clean
sudo apt autoclean
```

#### 2. Log Rotation Aggressivo

```bash
# /etc/logrotate.d/merchant-api
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

#### 3. Deploy JAR Compresso

```bash
# Build Spring Boot con compressione
mvn clean package -DskipTests
# Output: merchant-api.jar (~40-50 MB)

# NO Docker images (troppo spazio)
```

#### 4. Monitoraggio Spazio

```bash
# Cron job per alert spazio disco
# /etc/cron.daily/disk-space-alert
#!/bin/bash
THRESHOLD=90
USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $USAGE -gt $THRESHOLD ]; then
    echo "ALERT: Disk usage at ${USAGE}%" | mail -s "Disk Space Alert" admin@payglobe.it
fi
```

---

## 📊 Limiti Risorse & Tuning

### Allocazione Memoria

**Totale Disponibile**: 11 GB

```
┌────────────────────────────────────┐
│  Apache2 + PHP:    ~2 GB           │
│  MySQL Client:     ~500 MB         │
│  Spring Boot:      ~1-1.5 GB       │
│  Sistema:          ~1 GB           │
│  Buffer:           ~6 GB           │
└────────────────────────────────────┘
```

### Spring Boot JVM Options

```bash
# /etc/systemd/system/merchant-api.service
Environment="JAVA_OPTS=-Xmx1024m -Xms512m -XX:+UseG1GC -XX:MaxGCPauseMillis=200 -XX:+UseStringDeduplication"
```

### Hikari Connection Pool

```yaml
spring:
  datasource:
    hikari:
      maximum-pool-size: 10  # Limitato perché DB condiviso con PHP
      minimum-idle: 2
```

### Apache Tuning

```apache
# /etc/apache2/mods-available/mpm_prefork.conf
<IfModule mpm_prefork_module>
    StartServers          2
    MinSpareServers       2
    MaxSpareServers       5
    MaxRequestWorkers     50   # Ridotto da default 150
    MaxConnectionsPerChild 1000
</IfModule>
```

---

## 🧪 Testing & Validazione

### 1. Test Database Connection

```bash
# Test da Spring Boot
curl http://localhost:8080/actuator/health/db
```

### 2. Test API Parity (PHP vs Spring Boot)

```bash
# Script di confronto
#!/bin/bash

# Test 1: Dashboard stats
php_result=$(curl -s 'http://ricevute.payglobe.it/merchant/index.php?format=json' | jq '.total')
spring_result=$(curl -s 'http://ricevute.payglobe.it/api/v2/transactions/stats' | jq '.total')

if [ "$php_result" == "$spring_result" ]; then
    echo "✅ Stats match"
else
    echo "❌ Stats mismatch: PHP=$php_result, Spring=$spring_result"
fi

# Test 2: Transaction list
php_count=$(curl -s 'http://ricevute.payglobe.it/merchant/index.php?format=json' | jq '.transactions | length')
spring_count=$(curl -s 'http://ricevute.payglobe.it/api/v2/transactions?page=0&size=25' | jq '.content | length')

if [ "$php_count" == "$spring_count" ]; then
    echo "✅ Transaction count match"
else
    echo "❌ Count mismatch: PHP=$php_count, Spring=$spring_count"
fi
```

### 3. Load Testing

```bash
# Apache Bench (già installato)
ab -n 1000 -c 10 http://localhost:8080/api/v2/transactions

# Risultati attesi:
# - Requests per second: >100
# - Mean response time: <100ms
# - Failed requests: 0
```

---

## 🔄 Rollback Plan

### Scenario: Spring Boot ha problemi

**Rollback in 5 minuti**:

```bash
# 1. Stop Spring Boot
sudo systemctl stop merchant-api

# 2. Disabilita proxy Apache per /api/v2
sudo a2disconf merchant-spring-proxy
sudo systemctl reload apache2

# 3. PHP continua a funzionare normalmente
curl http://ricevute.payglobe.it/merchant/index.php
# ✅ Funziona
```

**Utenti non vedono downtime!**

---

## 💰 Costi Ridotti

### Team Minimo

| Ruolo | Settimane | Costo/Settimana | Totale |
|-------|-----------|-----------------|--------|
| Senior Backend (Java/Spring) | 10 | €2.500 | €25.000 |
| Mid Frontend (React) | 6 | €2.000 | €12.000 |
| DevOps (part-time) | 4 | €2.500 | €10.000 |
| QA (part-time) | 3 | €1.800 | €5.400 |
| **TOTALE** | | | **€52.400** |

### Infrastruttura

| Risorsa | Costo |
|---------|-------|
| Server pgbe2 | **€0** (esistente) |
| Database MySQL | **€0** (esistente) |
| Java 17 | **€0** (open source) |
| Apache2 | **€0** (già installato) |
| Spring Boot | **€0** (open source) |
| **TOTALE** | **€0** |

### **TOTALE PROGETTO: ~€52.000**

**(vs €140.000 della soluzione cloud-native = 63% di risparmio!)**

---

## 🎯 Vantaggi Approccio Conservativo

### ✅ Pro

1. **Zero Costi Infrastruttura**: Usa server esistente
2. **Zero Breaking Changes**: Database e tabelle invariati
3. **Migrazione Graduale**: Modulo per modulo, testabile
4. **Rollback Facile**: In caso di problemi, torna a PHP in 5 minuti
5. **Coesistenza**: PHP e Spring Boot attivi insieme
6. **Formazione Graduale**: Utenti si abituano piano piano
7. **Budget Ridotto**: ~€52K vs ~€140K (-63%)
8. **Tempo Ridotto**: 2-3 mesi vs 4 mesi

### ⚠️ Contro & Mitigazioni

| Problema | Mitigazione |
|----------|-------------|
| **Spazio disco limitato (5.5 GB)** | Cleanup logs, rotation aggressivo, no Docker |
| **RAM condivisa (PHP + Spring)** | Tuning JVM (-Xmx1024m), connection pool limitato |
| **DB condiviso** | Connection pool ridotto (max 10 conn), query ottimizzate |
| **Apache single-point-of-failure** | Health checks, systemd auto-restart |
| **Ubuntu 18.04 EOL (2023)** | Pianifica upgrade a 22.04 LTS (separatamente) |

---

## 🚀 Prossimi Passi

### Immediate (Questa settimana)

1. ✅ **Approvazione stakeholder** per approccio conservativo
2. ✅ **Backup completo database** (mysqldump)
3. ✅ **Test spazio disco**: Cleanup e liberazione minimo 2-3 GB
4. ✅ **Verifica versione MySQL** su 10.10.10.13
5. ✅ **Documentazione credenziali** complete

### Setup Iniziale (Settimana 1)

1. Installa Java 17 su pgbe2
2. Configura Apache2 reverse proxy
3. Deploy skeleton Spring Boot
4. Test health checks

### Sviluppo (Settimane 2-10)

Segui fasi di migrazione graduale descritte sopra.

---

## 📞 Supporto & Monitoraggio

### Logs Centrali

```bash
# Spring Boot
sudo journalctl -u merchant-api -f

# Apache2
sudo tail -f /var/log/apache2/merchant-error.log

# MySQL slow queries (da configurare su DB server)
# /etc/mysql/my.cnf
# slow_query_log = 1
# long_query_time = 2
```

### Health Checks

```bash
# Cron job ogni 5 minuti
*/5 * * * * curl -f http://localhost:8080/actuator/health || systemctl restart merchant-api
```

---

## ✅ Conclusioni

Questo approccio **conservativo** permette di:

- ✅ Modernizzare gradualmente l'applicazione
- ✅ Mantenere l'infrastruttura esistente
- ✅ Zero breaking changes per database e utenti
- ✅ Ridurre costi del 63% (~€52K vs €140K)
- ✅ Ridurre rischi con coesistenza PHP + Spring Boot
- ✅ Rollback facile in caso di problemi

È l'approccio **ideale** per una migrazione **low-risk**, **budget-friendly**, mantenendo alta qualità e benefici della modernizzazione.

---

**Prossimo Step**: Approva piano e iniziamo con setup Java 17 + Apache proxy! 🚀
