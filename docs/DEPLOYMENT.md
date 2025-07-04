# Production Deployment Guide
## Leiterprüfung System

---

## 📋 Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Systemanforderungen](#systemanforderungen)
3. [Vorbereitung](#vorbereitung)
4. [Installation](#installation)
5. [Konfiguration](#konfiguration)
6. [SSL/TLS Setup](#ssltls-setup)
7. [Deployment](#deployment)
8. [Monitoring & Wartung](#monitoring--wartung)
9. [Backup & Recovery](#backup--recovery)
10. [Troubleshooting](#troubleshooting)
11. [Sicherheit](#sicherheit)

---

## 🎯 Überblick

Diese Anleitung beschreibt das Production-Deployment des Leiterprüfung Systems mit Docker Compose. Das System ist für hohe Verfügbarkeit, Sicherheit und Performance optimiert.

### Architektur

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Load Balancer │    │   Web Server    │    │    Database     │
│    (Nginx)      │────│   (Apache/PHP)  │────│    (MySQL)      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                       ┌─────────────────┐
                       │     Redis       │
                       │   (Sessions)    │
                       └─────────────────┘
```

### Features

- ✅ Zero-Downtime Deployment
- ✅ SSL/TLS Termination
- ✅ Automatisierte Backups
- ✅ Health Monitoring
- ✅ Log Aggregation
- ✅ Rolling Updates
- ✅ Rollback-Funktionalität

---

## 🖥️ Systemanforderungen

### Hardware (Minimum)

- **CPU**: 4 Cores (2.0 GHz)
- **RAM**: 8 GB
- **Storage**: 100 GB SSD
- **Network**: 1 Gbps

### Hardware (Empfohlen)

- **CPU**: 8 Cores (2.5 GHz)
- **RAM**: 16 GB
- **Storage**: 500 GB SSD (RAID 1)
- **Network**: 1 Gbps (redundant)

### Software

- **OS**: Ubuntu 20.04 LTS / CentOS 8 / RHEL 8
- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **Git**: 2.25+
- **OpenSSL**: 1.1.1+

### Netzwerk

- **Ports**: 80 (HTTP), 443 (HTTPS), 22 (SSH)
- **Firewall**: UFW / iptables konfiguriert
- **DNS**: A-Record für Domain konfiguriert

---

## 🛠️ Vorbereitung

### 1. Server Setup

```bash
# System aktualisieren
sudo apt update && sudo apt upgrade -y

# Notwendige Pakete installieren
sudo apt install -y curl wget git unzip htop

# Docker installieren
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Docker Compose installieren
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Neustart für Gruppenmitgliedschaft
sudo reboot
```

### 2. Firewall Konfiguration

```bash
# UFW aktivieren
sudo ufw enable

# SSH erlauben
sudo ufw allow 22/tcp

# HTTP/HTTPS erlauben
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Status prüfen
sudo ufw status
```

### 3. Verzeichnisstruktur erstellen

```bash
# Projekt-Verzeichnis erstellen
sudo mkdir -p /opt/leiter
sudo chown $USER:$USER /opt/leiter
cd /opt/leiter

# Notwendige Verzeichnisse
mkdir -p {logs,backups,ssl,data}
```

---

## 📦 Installation

### 1. Repository klonen

```bash
cd /opt/leiter
git clone https://github.com/meddatzk/Leiterpruefung.git .

# Production Branch (falls vorhanden)
git checkout production
```

### 2. Berechtigungen setzen

```bash
# Scripts ausführbar machen
chmod +x scripts/*.sh

# Log-Verzeichnisse erstellen
sudo mkdir -p /var/log/leiter
sudo chown $USER:$USER /var/log/leiter
```

### 3. Environment Konfiguration

```bash
# Production Environment erstellen
cp .env.prod.example .env.prod

# Konfiguration anpassen (siehe Konfiguration Sektion)
nano .env.prod
```

---

## ⚙️ Konfiguration

### 1. Environment Variablen (.env.prod)

**Kritische Einstellungen:**

```bash
# Anwendung
APP_ENV=production
APP_DEBUG=false
APP_SECRET_KEY=GENERIERE_STARKEN_SCHLUESSEL_HIER

# Datenbank
DB_PASSWORD=STARKES_DB_PASSWORT
DB_ROOT_PASSWORD=STARKES_ROOT_PASSWORT

# LDAP
LDAP_HOST=ihr-ldap-server.com
LDAP_BIND_PASSWORD=LDAP_SERVICE_PASSWORT

# SSL
SSL_CERT_PATH=/etc/ssl/certs/leiter/fullchain.pem
SSL_KEY_PATH=/etc/ssl/certs/leiter/privkey.pem
```

**Sichere Passwörter generieren:**

```bash
# App Secret (32 Zeichen)
openssl rand -base64 32

# Datenbank Passwörter (16 Zeichen)
openssl rand -base64 16
```

### 2. Docker Compose Validierung

```bash
# Konfiguration prüfen
docker-compose -f docker-compose.prod.yml config

# Services validieren
docker-compose -f docker-compose.prod.yml ps
```

---

## 🔒 SSL/TLS Setup

### Option 1: Let's Encrypt (Empfohlen)

```bash
# Certbot installieren
sudo apt install -y certbot

# Zertifikat erstellen
sudo certbot certonly --standalone \
  -d leiter.example.com \
  --email admin@example.com \
  --agree-tos \
  --non-interactive

# Zertifikate kopieren
sudo cp /etc/letsencrypt/live/leiter.example.com/fullchain.pem ssl/
sudo cp /etc/letsencrypt/live/leiter.example.com/privkey.pem ssl/
sudo chown $USER:$USER ssl/*.pem

# Auto-Renewal einrichten
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

### Option 2: Kommerzielle Zertifikate

```bash
# Zertifikate in ssl/ Verzeichnis kopieren
cp your-certificate.crt ssl/fullchain.pem
cp your-private-key.key ssl/privkey.pem

# Berechtigungen setzen
chmod 600 ssl/*.pem
```

### SSL Konfiguration testen

```bash
# SSL Labs Test
curl -s "https://api.ssllabs.com/api/v3/analyze?host=leiter.example.com"

# Lokaler Test
openssl s_client -connect leiter.example.com:443 -servername leiter.example.com
```

---

## 🚀 Deployment

### 1. Erstes Deployment

```bash
# Deployment-Script ausführen
./scripts/deploy.sh deploy

# Oder manuell:
docker-compose -f docker-compose.prod.yml up -d
```

### 2. Deployment-Optionen

```bash
# Standard Deployment
./scripts/deploy.sh deploy

# Deployment mit spezifischer Version
./scripts/deploy.sh deploy --tag v1.2.0

# Force Deployment (ohne Bestätigung)
./scripts/deploy.sh deploy --force

# Deployment ohne Backup
./scripts/deploy.sh deploy --no-backup
```

### 3. Deployment Status prüfen

```bash
# Status anzeigen
./scripts/deploy.sh status

# Health Check
./scripts/deploy.sh health

# Container Status
docker-compose -f docker-compose.prod.yml ps

# Logs anzeigen
docker-compose -f docker-compose.prod.yml logs -f
```

### 4. Rollback

```bash
# Rollback zur vorherigen Version
./scripts/deploy.sh rollback

# Backup wiederherstellen
./scripts/backup.sh restore --restore /path/to/backup.tar.gz
```

---

## 📊 Monitoring & Wartung

### 1. Health Checks

```bash
# Application Health
curl -f http://localhost/health.php

# Container Health
docker-compose -f docker-compose.prod.yml ps

# System Resources
htop
df -h
```

### 2. Log Monitoring

```bash
# Application Logs
tail -f logs/deploy.log
tail -f logs/backup.log
tail -f logs/migration.log

# Container Logs
docker-compose -f docker-compose.prod.yml logs -f web
docker-compose -f docker-compose.prod.yml logs -f database
```

### 3. Performance Monitoring

```bash
# Database Performance
docker-compose -f docker-compose.prod.yml exec database mysql \
  -u root -p -e "SHOW PROCESSLIST;"

# Apache Status
curl http://localhost/server-status

# OPcache Status
curl http://localhost/opcache-status.php
```

### 4. Automatisierte Überwachung

**Crontab Einträge:**

```bash
# Health Check alle 5 Minuten
*/5 * * * * /opt/leiter/scripts/deploy.sh health > /dev/null 2>&1

# Tägliche Backups um 2:00 Uhr
0 2 * * * /opt/leiter/scripts/backup.sh create --encrypt

# Wöchentliche Backup-Bereinigung
0 3 * * 0 /opt/leiter/scripts/backup.sh cleanup --retention 30

# Log-Rotation
0 1 * * * /usr/sbin/logrotate /etc/logrotate.d/leiter
```

---

## 💾 Backup & Recovery

### 1. Backup Erstellung

```bash
# Manuelles Backup
./scripts/backup.sh create

# Verschlüsseltes Backup
./scripts/backup.sh create --encrypt

# Backup ohne Datenbank
./scripts/backup.sh create --no-database

# Backup in spezifisches Verzeichnis
./scripts/backup.sh create --output-dir /external/backup/path
```

### 2. Backup Verwaltung

```bash
# Verfügbare Backups anzeigen
./scripts/backup.sh list

# Backup verifizieren
./scripts/backup.sh verify --restore /path/to/backup.tar.gz

# Alte Backups löschen
./scripts/backup.sh cleanup --retention 30
```

### 3. Disaster Recovery

```bash
# Vollständige Wiederherstellung
./scripts/backup.sh restore --restore /path/to/backup.tar.gz.enc

# Nur Datenbank wiederherstellen
./scripts/backup.sh restore --restore /path/to/backup.tar.gz --no-files

# System nach Wiederherstellung neu starten
docker-compose -f docker-compose.prod.yml restart
```

### 4. Backup-Strategie

**3-2-1 Regel:**
- **3** Kopien der Daten
- **2** verschiedene Medien
- **1** Offsite-Backup

**Empfohlene Konfiguration:**
```bash
# Lokale Backups (täglich, 30 Tage)
BACKUP_RETENTION_DAYS=30

# Remote Backups (wöchentlich, 12 Wochen)
# S3, Google Cloud, Azure Blob Storage

# Archiv Backups (monatlich, 12 Monate)
# Tape, Cold Storage
```

---

## 🔧 Troubleshooting

### Häufige Probleme

#### 1. Container startet nicht

```bash
# Logs prüfen
docker-compose -f docker-compose.prod.yml logs web

# Konfiguration validieren
docker-compose -f docker-compose.prod.yml config

# Ports prüfen
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :443
```

#### 2. Datenbank Verbindungsfehler

```bash
# Datenbank Status
docker-compose -f docker-compose.prod.yml exec database mysql \
  -u root -p -e "SELECT 1;"

# Netzwerk prüfen
docker network ls
docker network inspect leiter_leiter_network
```

#### 3. SSL Zertifikat Probleme

```bash
# Zertifikat prüfen
openssl x509 -in ssl/fullchain.pem -text -noout

# Zertifikat Gültigkeit
openssl x509 -in ssl/fullchain.pem -checkend 86400

# Apache SSL Konfiguration testen
docker-compose -f docker-compose.prod.yml exec web apache2ctl -S
```

#### 4. Performance Probleme

```bash
# System Resources
free -h
df -h
iostat 1 5

# Container Resources
docker stats

# Database Performance
docker-compose -f docker-compose.prod.yml exec database mysql \
  -u root -p -e "SHOW ENGINE INNODB STATUS\G"
```

### Debug Modus

```bash
# Temporär Debug aktivieren
docker-compose -f docker-compose.prod.yml exec web \
  sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' /var/www/html/.env.prod

# Container neu starten
docker-compose -f docker-compose.prod.yml restart web

# Debug wieder deaktivieren (WICHTIG!)
docker-compose -f docker-compose.prod.yml exec web \
  sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' /var/www/html/.env.prod
```

---

## 🛡️ Sicherheit

### 1. Sicherheits-Checkliste

- [ ] Starke Passwörter für alle Services
- [ ] SSL/TLS Zertifikate installiert und gültig
- [ ] Firewall konfiguriert (nur 22, 80, 443 offen)
- [ ] SSH Key-basierte Authentifizierung
- [ ] Regelmäßige Sicherheitsupdates
- [ ] Backup-Verschlüsselung aktiviert
- [ ] Log-Monitoring eingerichtet
- [ ] Rate Limiting konfiguriert
- [ ] CSRF Protection aktiviert
- [ ] Security Headers gesetzt

### 2. Sicherheitsupdates

```bash
# System Updates
sudo apt update && sudo apt upgrade -y

# Docker Updates
sudo apt update docker-ce docker-ce-cli containerd.io

# Container Images aktualisieren
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d
```

### 3. Security Monitoring

```bash
# Failed Login Attempts
grep "authentication failure" /var/log/auth.log

# Suspicious Network Activity
sudo netstat -tulpn | grep LISTEN

# File Integrity Monitoring
find /opt/leiter -type f -name "*.php" -exec md5sum {} \; > /tmp/checksums.txt
```

### 4. Incident Response

**Bei Sicherheitsvorfall:**

1. **Sofortmaßnahmen:**
   ```bash
   # System isolieren
   sudo ufw deny in
   
   # Container stoppen
   docker-compose -f docker-compose.prod.yml down
   ```

2. **Analyse:**
   ```bash
   # Logs sichern
   cp -r logs/ /secure/location/incident-$(date +%Y%m%d)/
   
   # System-Logs prüfen
   journalctl -xe
   ```

3. **Recovery:**
   ```bash
   # Clean Backup wiederherstellen
   ./scripts/backup.sh restore --restore /path/to/clean/backup.tar.gz
   
   # Passwörter ändern
   # Zertifikate erneuern
   # System härten
   ```

---

## 📚 Weitere Ressourcen

### Dokumentation

- [Docker Compose Reference](https://docs.docker.com/compose/)
- [Apache HTTP Server Documentation](https://httpd.apache.org/docs/)
- [MySQL 8.0 Reference Manual](https://dev.mysql.com/doc/refman/8.0/en/)
- [PHP Documentation](https://www.php.net/docs.php)

### Tools

- [SSL Labs SSL Test](https://www.ssllabs.com/ssltest/)
- [Security Headers](https://securityheaders.com/)
- [GTmetrix Performance Test](https://gtmetrix.com/)

### Support

- **GitHub Issues**: [Leiterpruefung Issues](https://github.com/meddatzk/Leiterpruefung/issues)
- **Documentation**: [Project Wiki](https://github.com/meddatzk/Leiterpruefung/wiki)

---

## 📝 Changelog

### Version 1.0.0 (2025-01-04)

- Initiales Production-Deployment
- SSL/TLS Integration
- Automatisierte Backups
- Health Monitoring
- Zero-Downtime Deployment

---

**© 2025 Leiterprüfung System - Production Deployment Guide**

*Letzte Aktualisierung: 04.01.2025*
