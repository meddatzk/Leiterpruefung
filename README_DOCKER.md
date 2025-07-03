# Leiterprüfung - Docker Setup

## Übersicht

Dieses Docker-Setup stellt eine vollständige Entwicklungsumgebung für die PHP-Webapplikation zur Leiterprüfung bereit.

## Services

- **nginx**: Webserver (Port 80, 443)
- **php**: PHP 8.2-FPM mit PDO MySQL und LDAP-Extension
- **mysql**: MySQL 8.0 Datenbank (Port 3306)
- **phpmyadmin**: Datenbankadministration (Port 8080)

## Ordnerstruktur

```
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf
│   │   └── default.conf
│   └── php/
│       ├── Dockerfile
│       └── php.ini
├── web/
│   ├── public/          # Öffentlich zugängliche Dateien
│   ├── src/             # PHP-Quellcode
│   └── config/          # Konfigurationsdateien
├── database/
│   └── init/            # SQL-Initialisierungsskripte
├── docker-compose.yml
└── .env.example
```

## Installation und Start

1. **Umgebungsvariablen konfigurieren:**
   ```bash
   cp .env.example .env
   ```
   Bearbeiten Sie die `.env`-Datei nach Ihren Anforderungen.

2. **Docker Container starten:**
   ```bash
   docker-compose up -d
   ```

3. **Container Status prüfen:**
   ```bash
   docker-compose ps
   ```

4. **Logs anzeigen:**
   ```bash
   docker-compose logs -f
   ```

## Zugriff

- **Webapplikation**: http://localhost
- **phpMyAdmin**: http://localhost:8080
- **MySQL**: localhost:3306

## Development

### Hot-Reload
Alle Änderungen in den `web/`-Verzeichnissen werden automatisch übernommen.

### PHP-Container betreten
```bash
docker-compose exec php sh
```

### MySQL-Container betreten
```bash
docker-compose exec mysql mysql -u root -p
```

## Stoppen und Aufräumen

```bash
# Container stoppen
docker-compose down

# Container und Volumes löschen
docker-compose down -v

# Alle Images entfernen
docker-compose down --rmi all
```

## Troubleshooting

### Container startet nicht
```bash
docker-compose logs [service-name]
```

### Datenbankverbindung fehlgeschlagen
- Prüfen Sie die Umgebungsvariablen in der `.env`-Datei
- Warten Sie, bis der MySQL-Container vollständig gestartet ist

### LDAP-Verbindung
- Konfigurieren Sie die LDAP-Parameter in der `.env`-Datei
- Testen Sie die Verbindung zu Ihrem LDAP-Server

## Konfiguration

### PHP-Konfiguration
Bearbeiten Sie `docker/php/php.ini` und starten Sie die Container neu:
```bash
docker-compose restart php
```

### Nginx-Konfiguration
Bearbeiten Sie `docker/nginx/default.conf` und starten Sie die Container neu:
```bash
docker-compose restart nginx
```

### Datenbank-Initialisierung
SQL-Dateien in `database/init/` werden automatisch beim ersten Start ausgeführt.
