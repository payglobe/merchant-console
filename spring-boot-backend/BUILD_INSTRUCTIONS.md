# Istruzioni Build JAR

## Prerequisiti

### Installazione Maven su Windows

**Opzione 1: Chocolatey** (raccomandato)
```bash
# Installa Chocolatey se non hai già (da PowerShell Admin)
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Installa Maven
choco install maven -y

# Verifica
mvn --version
```

**Opzione 2: Download Manuale**
1. Scarica Apache Maven da: https://maven.apache.org/download.cgi
2. Estrai in `C:\Program Files\Apache\maven`
3. Aggiungi a PATH: `C:\Program Files\Apache\maven\bin`
4. Verifica: `mvn --version`

### Verifica Java 17

```bash
java -version
# Output atteso: openjdk version "17.0.x" o superiore
```

Se Java 17 non installato:
```bash
# Con Chocolatey
choco install openjdk17 -y
```

## Build JAR

```bash
# Dalla directory spring-boot-backend
cd C:\Users\hellrock\Desktop\merchant\spring-boot-backend

# Build completo con tests
mvn clean package

# Oppure skip tests (più veloce)
mvn clean package -DskipTests

# Output: target/merchant-api.jar (~40-50 MB)
```

## Verifica JAR

```bash
# Controlla dimensione
ls -lh target/merchant-api.jar

# Test locale (opzionale)
java -jar target/merchant-api.jar

# Dovrebbe avviarsi su porta 8986
# Ctrl+C per terminare
```

## Deploy su pgbe2

Una volta che hai il JAR, procedi con il deploy:

```bash
# Rendi eseguibili gli script
cd deploy
chmod +x *.sh

# Opzione 1: Deploy manuale
scp ../target/merchant-api.jar pguser@pgbe2:/opt/merchant-console/
ssh pguser@pgbe2 "./stop.sh && ./start.sh"

# Opzione 2: Deploy automatico (TODO: finire script)
./deploy.sh
```

## Troubleshooting

### Errore "JAVA_HOME not found"
```bash
# Windows
set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%
```

### Errore "mvn not found"
Verifica che Maven sia nel PATH:
```bash
echo %PATH%
# Deve contenere: C:\Program Files\Apache\maven\bin
```

### Build lento
```bash
# Usa cache locale e skip tests
mvn clean package -DskipTests -o
```
