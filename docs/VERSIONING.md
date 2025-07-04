# Versionsverwaltung - Leiterpruefung

Dieses Dokument beschreibt die Versionsverwaltungsstrategie für das Leiterpruefung-Projekt.

## Übersicht

Das Projekt verwendet **Semantic Versioning (SemVer)** in Kombination mit **Conventional Commits** für eine automatisierte und konsistente Versionsverwaltung.

## Semantic Versioning

Versionen folgen dem Format: `MAJOR.MINOR.PATCH`

### Version-Typen

- **MAJOR** (X.0.0): Inkompatible API-Änderungen oder Breaking Changes
- **MINOR** (0.X.0): Neue Funktionalität, die rückwärtskompatibel ist
- **PATCH** (0.0.X): Bugfixes, die rückwärtskompatibel sind

### Beispiele

```
1.0.0 → 1.0.1  (Bugfix)
1.0.1 → 1.1.0  (Neues Feature)
1.1.0 → 2.0.0  (Breaking Change)
```

## Conventional Commits

Commit-Messages folgen dem [Conventional Commits](https://www.conventionalcommits.org/) Standard:

### Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Commit-Types

| Type | Beschreibung | Version Impact |
|------|-------------|----------------|
| `feat` | Neue Funktionalität | MINOR |
| `fix` | Bugfix | PATCH |
| `docs` | Dokumentationsänderungen | - |
| `style` | Code-Formatierung, Whitespace | - |
| `refactor` | Code-Refactoring ohne Funktionsänderung | - |
| `test` | Tests hinzufügen oder ändern | - |
| `chore` | Build-Prozess, Tools, Dependencies | - |
| `perf` | Performance-Verbesserungen | PATCH |
| `ci` | CI/CD-Konfiguration | - |
| `build` | Build-System-Änderungen | - |
| `revert` | Commit rückgängig machen | - |

### Breaking Changes

Breaking Changes werden durch `!` nach dem Type oder `BREAKING CHANGE:` im Footer markiert:

```bash
feat!: neue API-Struktur für Inspektionen
# oder
feat: neue API-Struktur für Inspektionen

BREAKING CHANGE: Die alte API-Struktur wird nicht mehr unterstützt
```

### Beispiele

```bash
# Feature (MINOR)
feat(ladders): neue Suchfunktion für Leitern

# Bugfix (PATCH)
fix(auth): LDAP-Verbindungsfehler behoben

# Breaking Change (MAJOR)
feat!: neue Datenbankstruktur für Inspektionen

# Dokumentation
docs: API-Dokumentation aktualisiert

# Refactoring
refactor(security): InputValidator überarbeitet
```

## Automatisierte Versionierung

### GitHub Actions

Das Projekt verwendet GitHub Actions für automatisierte Versionierung:

- **CI-Pipeline**: Läuft bei jedem Push/PR
- **Release-Pipeline**: Läuft bei Push auf main-Branch

### Workflow

1. **Commit** mit Conventional Commits Standard
2. **Push** auf main-Branch
3. **Automatische Analyse** der Commit-Messages
4. **Versionierung** basierend auf Commit-Types
5. **Changelog-Generierung**
6. **Git-Tag-Erstellung**
7. **Docker-Image-Build** und -Push
8. **GitHub-Release** mit Release Notes

## Manuelle Versionierung

### Version Script

Das `scripts/version.sh` Script ermöglicht manuelle Versionierung:

```bash
# Aktuelle Version anzeigen
./scripts/version.sh current

# Automatische Versionierung
./scripts/version.sh auto

# Manuelle Versionierung
./scripts/version.sh major    # 1.0.0 → 2.0.0
./scripts/version.sh minor    # 1.0.0 → 1.1.0
./scripts/version.sh patch    # 1.0.0 → 1.0.1

# Vollständiger Release
./scripts/version.sh release

# Dry-Run (Simulation)
./scripts/version.sh release --dry-run
```

### Script-Optionen

```bash
# Ohne Docker-Build
./scripts/version.sh release --no-docker

# Ohne Docker-Push
./scripts/version.sh release --no-push

# Nur Changelog generieren
./scripts/version.sh changelog

# Nur Git-Tag erstellen
./scripts/version.sh tag
```

## Dateien

### version.json

Zentrale Versionsdatei mit Metadaten:

```json
{
  "version": "1.0.0",
  "major": 1,
  "minor": 0,
  "patch": 0,
  "prerelease": null,
  "build": null,
  "lastUpdated": "2025-01-04T16:47:00.000Z",
  "gitTag": "v1.0.0",
  "branch": "main",
  "commit": "abc123...",
  "releaseNotes": "Version 1.0.0"
}
```

### package.json

NPM-Konfiguration für Semantic Release:

```json
{
  "version": "1.0.0",
  "scripts": {
    "release": "semantic-release",
    "version": "./scripts/version.sh"
  },
  "devDependencies": {
    "@semantic-release/changelog": "^6.0.3",
    "@semantic-release/git": "^10.0.1",
    "semantic-release": "^22.0.12"
  }
}
```

## Docker Image Tagging

Docker Images werden automatisch mit Versionen getaggt:

```bash
# Version-spezifische Tags
ghcr.io/meddatzk/leiterpruefung:1.0.0
ghcr.io/meddatzk/leiterpruefung:1.0
ghcr.io/meddatzk/leiterpruefung:1

# Latest Tag
ghcr.io/meddatzk/leiterpruefung:latest
```

## Branch-Strategie

### Main Branch

- **Produktionsreif**: Nur stabile, getestete Änderungen
- **Automatische Releases**: Jeder Push triggert Release-Pipeline
- **Protected**: Requires Pull Request Reviews

### Feature Branches

```bash
# Feature-Branch erstellen
git checkout -b feature/neue-suchfunktion

# Commits mit Conventional Commits
git commit -m "feat(search): erweiterte Suchfilter hinzugefügt"

# Pull Request erstellen
# Nach Merge: Automatisches Release
```

### Hotfix Branches

```bash
# Hotfix-Branch erstellen
git checkout -b hotfix/kritischer-bug

# Bugfix-Commit
git commit -m "fix(auth): kritischer Sicherheitsfehler behoben"

# Direkt auf main mergen für schnelles Release
```

## Release-Prozess

### Automatischer Release

1. **Feature entwickeln** in Feature-Branch
2. **Pull Request** erstellen
3. **Code Review** und Tests
4. **Merge** in main-Branch
5. **Automatisches Release** durch GitHub Actions

### Manueller Release

```bash
# 1. Änderungen committen
git add .
git commit -m "feat: neue Funktionalität"

# 2. Version aktualisieren
./scripts/version.sh auto

# 3. Änderungen pushen
git add version.json CHANGELOG.md
git commit -m "chore: version bump"
git push

# 4. Release erstellen
./scripts/version.sh release
```

## Changelog

Das `CHANGELOG.md` wird automatisch generiert und enthält:

- **Neue Features** (feat)
- **Bugfixes** (fix)
- **Breaking Changes** (BREAKING CHANGE)
- **Performance-Verbesserungen** (perf)

### Format

```markdown
## [1.1.0] - 2025-01-04

### Hinzugefügt
- feat: neue Suchfunktion für Leitern
- feat: erweiterte Berichtsfunktionen

### Behoben
- fix: LDAP-Verbindungsfehler
- fix: Datenbankperformance verbessert

### Breaking Changes
- feat!: neue API-Struktur
```

## Best Practices

### Commit-Messages

```bash
# ✅ Gut
feat(ladders): neue Filterfunktion hinzugefügt
fix(auth): Session-Timeout-Problem behoben
docs: API-Dokumentation aktualisiert

# ❌ Schlecht
added new feature
bug fix
update
```

### Versionierung

- **Kleine Änderungen**: Patch-Version (fix)
- **Neue Features**: Minor-Version (feat)
- **Breaking Changes**: Major-Version (feat! oder BREAKING CHANGE)

### Release-Timing

- **Patch-Releases**: Sofort bei kritischen Bugfixes
- **Minor-Releases**: Wöchentlich oder bei Feature-Completion
- **Major-Releases**: Geplant, mit Migration-Guide

## Troubleshooting

### Häufige Probleme

#### Version Script Fehler

```bash
# jq nicht installiert
sudo apt-get install jq  # Ubuntu/Debian
brew install jq          # macOS

# Git Repository nicht gefunden
git init
git remote add origin <repository-url>
```

#### Docker Build Fehler

```bash
# Docker nicht gestartet
sudo systemctl start docker

# Registry-Login erforderlich
docker login ghcr.io
```

#### Semantic Release Fehler

```bash
# NPM Dependencies installieren
npm install

# GitHub Token setzen
export GITHUB_TOKEN=<your-token>
```

### Debug-Modus

```bash
# Verbose Output
./scripts/version.sh release --dry-run

# Git-Status prüfen
git status
git log --oneline -10

# Version-Datei prüfen
cat version.json | jq .
```

## Weiterführende Links

- [Semantic Versioning](https://semver.org/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [GitHub Actions](https://docs.github.com/en/actions)
- [Semantic Release](https://semantic-release.gitbook.io/)
