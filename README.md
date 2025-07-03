# Leiterprüfung - Docker Setup

Docker-Umgebung für die PHP-Webapplikation zur Leiterprüfung.

## Ordnerstruktur

```
project/
├── docker-compose.yml          # Docker Compose Konfiguration
├── .env.example               # Beispiel Umgebungsvariablen
├── .env                       # Lokale Umgebungsvariablen (zu erstellen)
├── README.md                  # Diese Datei
├── docker/                    # Docker Konfigurationsdateien
│   └── php/
│       ├── Dockerfile         # PHP 8.1 + Apache Container
│       └── apache.conf        # Apache Virtual Host Konfiguration
├── web/                       # Webapplikation (zu erstellen)
│   ├── public/               # DocumentRoot - öffentlich zugängliche Dateien
│   │   ├── index.php         # Haupteinstiegspunkt
│   │   ├── .htaccess         # Apache Rewrite Rules
│   │   └── favicon.ico       # Website Icon
│   ├── src/                  # Quellcode der Anwendung
│   │   ├── config/           # Konfigurationsdateien
│   │   │   ├── database.php  # Datenbankverbindung
│   │   │   ├── ldap.php      # LDAP Konfiguration
│   │   │   └── app.php       # Allgemeine App-Konfiguration
│   │   ├── includes/         # PHP Include-Dateien
│   │   │   ├── functions.php # Hilfsfunktionen
│   │   │   ├── auth.php      # Authentifizierung
│   │   │   └── validation.php # Eingabevalidierung
│   │   └── assets/           # Statische Ressourcen
│   │       ├── css/          # Stylesheets
│   │       ├── js/           # JavaScript Dateien
│   │       └── images/       # Bilder und Icons
│   └── templates/            # HTML Templates
│       ├── header.php        # Kopfbereich
│       ├── footer.php        # Fußbereich
│       ├── navigation.php    # Navigation
│       └── forms/            # Formular-Templates
└── database/                 # Datenbank-bezogene Dateien
    └── init/                 # SQL Initialisierungsskripte
        ├── 01_schema.sql     # Datenbankschema
        ├── 02_users.sql      # Benutzer-Tabellen
        └── 03_sample_data.sql # Beispieldaten
```

## Services

### Web Service (PHP + Apache)
- **Image**: PHP 8.1 mit Apache
- **Port**: 80 (direkt gemappt, kein Reverse Proxy)
- **DocumentRoot**: `/web/public/`
- **Extensions**: PDO MySQL, LDAP, ZIP
- **Features**: 
  - .htaccess Support
  - Sicherheitsheader
  - Kompression
  - Logging

### Database Service (MySQL)
- **Image**: MySQL 8.0
- **Port**: 3306 (konfigurierbar über DB_EXTERNAL_PORT)
- **Persistenz**: Docker Volume `mysql_data`
- **Initialisierung**: SQL-Skripte aus `database/init/`

## Setup-Anweisungen

### 1. Umgebungsvariablen konfigurieren
```bash
# Kopiere die Beispiel-Konfiguration
cp .env.example .env

# Bearbeite die .env Datei mit deinen Werten
# Wichtig: Ändere alle Passwörter für Produktionsumgebung!
```

### 2. Ordnerstruktur erstellen
```bash
# Erstelle die benötigten Ordner
mkdir -p web/public web/src/config web/src/includes web/src/assets/css web/src/assets/js web/src/assets/images
mkdir -p web/templates/forms
mkdir -p database/init
```

### 3. Docker Container starten
```bash
# Container bauen und starten
docker-compose up -d

# Logs anzeigen
docker-compose logs -f

# Status prüfen
docker-compose ps
```

### 4. Zugriff testen
- **Webapplikation**: http://localhost
- **MySQL**: localhost:3306 (mit konfigurierten Credentials)

## Wichtige Umgebungsvariablen

### Datenbank
- `DB_HOST`: MySQL Host (Standard: database)
- `DB_NAME`: Datenbankname
- `DB_USER`: Datenbankbenutzer
- `DB_PASSWORD`: Datenbankpasswort

### LDAP
- `LDAP_HOST`: LDAP Server
- `LDAP_BASE_DN`: Base Distinguished Name
- `LDAP_BIND_DN`: Bind Distinguished Name

### PHP/Apache
- `PHP_TIMEZONE`: Zeitzone (Standard: Europe/Berlin)
- `PHP_MEMORY_LIMIT`: Memory Limit (Standard: 256M)
- `APACHE_SERVER_NAME`: Server Name (Standard: localhost)

## Entwicklung

### Container neu bauen
```bash
docker-compose build --no-cache
docker-compose up -d
```

### In Container einloggen
```bash
# Web Container
docker-compose exec web bash

# MySQL Container
docker-compose exec database mysql -u root -p
```

### Logs anzeigen
```bash
# Alle Services
docker-compose logs -f

# Nur Web Service
docker-compose logs -f web

# Nur Database Service
docker-compose logs -f database
```

## Sicherheitshinweise

1. **Produktionsumgebung**: Ändere alle Standard-Passwörter in der `.env` Datei
2. **HTTPS**: Für Produktion SSL/TLS konfigurieren
3. **Firewall**: Nur benötigte Ports öffnen
4. **Updates**: Regelmäßig Docker Images aktualisieren
5. **Backups**: Regelmäßige Datenbank-Backups erstellen

## Troubleshooting

### Port bereits belegt
```bash
# Prüfe welcher Prozess Port 80 verwendet
netstat -tulpn | grep :80

# Ändere Port in docker-compose.yml
ports:
  - "8080:80"  # Verwende Port 8080 statt 80
```

### Berechtigungsprobleme
```bash
# Setze korrekte Berechtigungen
sudo chown -R $USER:$USER web/
chmod -R 755 web/
```

### Container startet nicht
```bash
# Prüfe Logs
docker-compose logs web
docker-compose logs database

# Container neu bauen
docker-compose down
docker-compose build --no-cache
docker-compose up -d
