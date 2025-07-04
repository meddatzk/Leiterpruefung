# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Hinzugefügt
- Versionsverwaltung mit Semantic Versioning
- GitHub Actions für CI/CD
- Automatische Changelog-Generierung
- Docker Image Tagging mit Versionen

### Geändert
- Projektstruktur für bessere Versionsverwaltung

### Behoben
- Initiale Einrichtung der Versionsverwaltung

## [1.0.0] - 2025-01-04

### Hinzugefügt
- Initiale Version des Leiterpruefung Systems
- Benutzerauthentifizierung mit LDAP
- Leiterverwaltung (CRUD-Operationen)
- Inspektionssystem mit Templates
- Dashboard mit Übersichten und Statistiken
- Berichtsgenerierung (PDF/Excel)
- Sicherheitsfeatures (CSRF-Schutz, Input-Validierung)
- Docker-basierte Deployment-Umgebung
- Umfassende Test-Suite (Unit, Integration, Functional)
- Audit-Logging für alle wichtigen Aktionen
- Responsive Web-Interface
- MySQL-Datenbankintegration
- Session-Management
- Template-Engine für Views
- Rate Limiting für API-Schutz
- Verschlüsselungshelfer für sensible Daten

### Technische Details
- PHP 8.1+ Backend
- MySQL 8.0 Datenbank
- Apache Webserver
- Docker & Docker Compose
- PHPUnit für Tests
- Bootstrap CSS Framework
- JavaScript für Frontend-Interaktivität

### Sicherheit
- LDAP-Authentifizierung
- CSRF-Token-Schutz
- Input-Sanitization
- SQL-Injection-Schutz
- XSS-Schutz
- Rate Limiting
- Sichere Session-Verwaltung
- Audit-Logging

### Deployment
- Docker-basierte Containerisierung
- Produktions- und Entwicklungsumgebungen
- Automatisierte Backup-Scripts
- Migrations-System
- Umgebungsspezifische Konfiguration

---

## Versionsschema

Dieses Projekt verwendet [Semantic Versioning](https://semver.org/spec/v2.0.0.html):

- **MAJOR**: Inkompatible API-Änderungen
- **MINOR**: Neue Funktionalität (rückwärtskompatibel)
- **PATCH**: Bugfixes (rückwärtskompatibel)

## Conventional Commits

Commit-Messages folgen dem [Conventional Commits](https://www.conventionalcommits.org/) Standard:

- `feat:` Neue Features (MINOR Version)
- `fix:` Bugfixes (PATCH Version)
- `docs:` Dokumentationsänderungen
- `style:` Code-Formatierung
- `refactor:` Code-Refactoring
- `test:` Test-Änderungen
- `chore:` Build-Prozess, Tools, etc.
- `BREAKING CHANGE:` Breaking Changes (MAJOR Version)

## Release-Prozess

1. Commits mit Conventional Commits Standard
2. Automatische Versionierung basierend auf Commit-Types
3. Automatische Changelog-Generierung
4. Git-Tag-Erstellung
5. Docker-Image-Build und -Push
6. GitHub Release mit Release Notes

## Links

- [Repository](https://github.com/meddatzk/Leiterpruefung)
- [Issues](https://github.com/meddatzk/Leiterpruefung/issues)
- [Releases](https://github.com/meddatzk/Leiterpruefung/releases)
- [Docker Images](https://ghcr.io/meddatzk/leiterpruefung)
