#!/bin/bash

# ==============================================
# PRODUCTION DEPLOYMENT SCRIPT
# Leiterprüfung System - Zero Downtime Deployment
# ==============================================

set -euo pipefail

# Konfiguration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_DIR/backups"
DEPLOY_LOG="$PROJECT_DIR/logs/deploy.log"
COMPOSE_FILE="$PROJECT_DIR/docker-compose.prod.yml"
ENV_FILE="$PROJECT_DIR/.env.prod"

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging Funktion
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")  echo -e "${GREEN}[INFO]${NC} $message" ;;
        "WARN")  echo -e "${YELLOW}[WARN]${NC} $message" ;;
        "ERROR") echo -e "${RED}[ERROR]${NC} $message" ;;
        "DEBUG") echo -e "${BLUE}[DEBUG]${NC} $message" ;;
    esac
    
    # Log in Datei
    mkdir -p "$(dirname "$DEPLOY_LOG")"
    echo "[$timestamp] [$level] $message" >> "$DEPLOY_LOG"
}

# Fehlerbehandlung
error_exit() {
    log "ERROR" "$1"
    exit 1
}

# Cleanup Funktion
cleanup() {
    log "INFO" "Cleanup wird ausgeführt..."
    # Temporäre Dateien löschen falls vorhanden
    rm -f /tmp/deploy_*.tmp
}

# Trap für Cleanup bei Exit
trap cleanup EXIT

# Hilfe anzeigen
show_help() {
    cat << EOF
Leiterprüfung Production Deployment Script

USAGE:
    $0 [OPTIONS] COMMAND

COMMANDS:
    deploy          Führt vollständiges Deployment durch
    rollback        Rollback zur vorherigen Version
    status          Zeigt aktuellen Status
    health          Führt Health Check durch
    logs            Zeigt Deployment Logs
    backup          Erstellt manuelles Backup

OPTIONS:
    -h, --help      Zeigt diese Hilfe
    -v, --verbose   Verbose Output
    -f, --force     Erzwingt Deployment ohne Bestätigung
    --no-backup     Überspringt Backup (nicht empfohlen)
    --tag TAG       Spezifische Git Tag/Branch für Deployment

EXAMPLES:
    $0 deploy                    # Standard Deployment
    $0 deploy --tag v1.2.0       # Deployment einer spezifischen Version
    $0 rollback                  # Rollback zur vorherigen Version
    $0 status                    # Status Check

EOF
}

# Voraussetzungen prüfen
check_prerequisites() {
    log "INFO" "Prüfe Voraussetzungen..."
    
    # Docker prüfen
    if ! command -v docker &> /dev/null; then
        error_exit "Docker ist nicht installiert oder nicht im PATH"
    fi
    
    # Docker Compose prüfen
    if ! command -v docker-compose &> /dev/null; then
        error_exit "Docker Compose ist nicht installiert oder nicht im PATH"
    fi
    
    # Git prüfen
    if ! command -v git &> /dev/null; then
        error_exit "Git ist nicht installiert oder nicht im PATH"
    fi
    
    # Environment File prüfen
    if [[ ! -f "$ENV_FILE" ]]; then
        error_exit "Environment File nicht gefunden: $ENV_FILE"
    fi
    
    # Docker Compose File prüfen
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        error_exit "Docker Compose File nicht gefunden: $COMPOSE_FILE"
    fi
    
    log "INFO" "Alle Voraussetzungen erfüllt"
}

# Git Repository Status prüfen
check_git_status() {
    log "INFO" "Prüfe Git Repository Status..."
    
    cd "$PROJECT_DIR"
    
    # Prüfe ob wir in einem Git Repository sind
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        error_exit "Nicht in einem Git Repository"
    fi
    
    # Prüfe auf uncommitted changes
    if [[ -n $(git status --porcelain) ]]; then
        log "WARN" "Uncommitted changes gefunden:"
        git status --short
        
        if [[ "${FORCE:-false}" != "true" ]]; then
            read -p "Trotzdem fortfahren? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                error_exit "Deployment abgebrochen"
            fi
        fi
    fi
}

# Backup erstellen
create_backup() {
    if [[ "${NO_BACKUP:-false}" == "true" ]]; then
        log "WARN" "Backup wird übersprungen (--no-backup)"
        return 0
    fi
    
    log "INFO" "Erstelle Backup..."
    
    local backup_timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_name="deploy_backup_${backup_timestamp}"
    local backup_path="$BACKUP_DIR/$backup_name"
    
    mkdir -p "$backup_path"
    
    # Git Commit Hash speichern
    git rev-parse HEAD > "$backup_path/git_commit.txt"
    
    # Docker Images speichern
    log "INFO" "Speichere aktuelle Docker Images..."
    docker images --format "table {{.Repository}}:{{.Tag}}\t{{.ID}}\t{{.CreatedAt}}" | grep leiter > "$backup_path/docker_images.txt" || true
    
    # Datenbank Backup
    if docker-compose -f "$COMPOSE_FILE" ps database | grep -q "Up"; then
        log "INFO" "Erstelle Datenbank Backup..."
        "$SCRIPT_DIR/backup.sh" --output-dir "$backup_path"
    fi
    
    # Konfigurationsdateien sichern
    cp "$ENV_FILE" "$backup_path/"
    cp "$COMPOSE_FILE" "$backup_path/"
    
    # Backup Metadaten
    cat > "$backup_path/backup_info.json" << EOF
{
    "timestamp": "$backup_timestamp",
    "git_commit": "$(git rev-parse HEAD)",
    "git_branch": "$(git branch --show-current)",
    "backup_type": "deployment",
    "created_by": "$(whoami)",
    "hostname": "$(hostname)"
}
EOF
    
    # Symlink auf letztes Backup
    ln -sfn "$backup_name" "$BACKUP_DIR/latest"
    
    log "INFO" "Backup erstellt: $backup_path"
    echo "$backup_path" > /tmp/deploy_backup_path.tmp
}

# Git Pull durchführen
git_pull() {
    log "INFO" "Aktualisiere Code von Git Repository..."
    
    cd "$PROJECT_DIR"
    
    local current_commit=$(git rev-parse HEAD)
    local current_branch=$(git branch --show-current)
    
    if [[ -n "${TAG:-}" ]]; then
        log "INFO" "Checkout Tag/Branch: $TAG"
        git fetch --all
        git checkout "$TAG"
    else
        log "INFO" "Pull aktueller Branch: $current_branch"
        git pull origin "$current_branch"
    fi
    
    local new_commit=$(git rev-parse HEAD)
    
    if [[ "$current_commit" == "$new_commit" ]]; then
        log "INFO" "Keine neuen Commits gefunden"
    else
        log "INFO" "Code aktualisiert: $current_commit -> $new_commit"
        
        # Zeige Änderungen
        log "INFO" "Änderungen seit letztem Deployment:"
        git log --oneline "$current_commit..$new_commit" || true
    fi
}

# Docker Images bauen
build_images() {
    log "INFO" "Baue Docker Images..."
    
    cd "$PROJECT_DIR"
    
    # Build Arguments setzen
    local build_date=$(date -u +'%Y-%m-%dT%H:%M:%SZ')
    local vcs_ref=$(git rev-parse HEAD)
    local version=$(git describe --tags --always --dirty)
    
    # Images bauen
    docker-compose -f "$COMPOSE_FILE" build \
        --build-arg BUILD_DATE="$build_date" \
        --build-arg VCS_REF="$vcs_ref" \
        --build-arg VERSION="$version" \
        --no-cache
    
    log "INFO" "Docker Images erfolgreich gebaut"
}

# Health Check durchführen
health_check() {
    log "INFO" "Führe Health Check durch..."
    
    local max_attempts=30
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s http://localhost/health.php > /dev/null 2>&1; then
            log "INFO" "Health Check erfolgreich"
            return 0
        fi
        
        log "DEBUG" "Health Check Versuch $attempt/$max_attempts fehlgeschlagen"
        sleep 10
        ((attempt++))
    done
    
    error_exit "Health Check fehlgeschlagen nach $max_attempts Versuchen"
}

# Container aktualisieren (Zero Downtime)
update_containers() {
    log "INFO" "Starte Zero-Downtime Container Update..."
    
    cd "$PROJECT_DIR"
    
    # Aktuelle Container Status speichern
    docker-compose -f "$COMPOSE_FILE" ps > /tmp/deploy_container_status.tmp
    
    # Rolling Update für Web Container
    log "INFO" "Aktualisiere Web Container..."
    
    # Neuen Container starten
    docker-compose -f "$COMPOSE_FILE" up -d --no-deps --scale web=2 web
    
    # Warten bis neuer Container bereit ist
    sleep 30
    
    # Health Check für neuen Container
    health_check
    
    # Alten Container stoppen
    docker-compose -f "$COMPOSE_FILE" up -d --no-deps --scale web=1 web
    
    # Andere Services aktualisieren
    log "INFO" "Aktualisiere andere Services..."
    docker-compose -f "$COMPOSE_FILE" up -d --no-deps database redis
    
    log "INFO" "Container Update abgeschlossen"
}

# Datenbank Migration
run_migrations() {
    log "INFO" "Führe Datenbank Migrationen durch..."
    
    cd "$PROJECT_DIR"
    
    # Migration Script ausführen
    if [[ -f "$SCRIPT_DIR/migrate.sh" ]]; then
        "$SCRIPT_DIR/migrate.sh"
    else
        log "WARN" "Migrations Script nicht gefunden, überspringe Migrationen"
    fi
}

# Cache leeren
clear_cache() {
    log "INFO" "Leere Application Cache..."
    
    cd "$PROJECT_DIR"
    
    # OPcache Reset
    docker-compose -f "$COMPOSE_FILE" exec -T web php -r "if (function_exists('opcache_reset')) opcache_reset();"
    
    # Redis Cache leeren
    if docker-compose -f "$COMPOSE_FILE" ps redis | grep -q "Up"; then
        docker-compose -f "$COMPOSE_FILE" exec -T redis redis-cli FLUSHALL
    fi
    
    # Session Cache leeren
    docker-compose -f "$COMPOSE_FILE" exec -T web find /tmp -name "sess_*" -delete 2>/dev/null || true
    
    log "INFO" "Cache erfolgreich geleert"
}

# Deployment Status anzeigen
show_status() {
    log "INFO" "=== DEPLOYMENT STATUS ==="
    
    cd "$PROJECT_DIR"
    
    # Git Status
    echo -e "\n${BLUE}Git Information:${NC}"
    echo "Branch: $(git branch --show-current)"
    echo "Commit: $(git rev-parse HEAD)"
    echo "Last Commit: $(git log -1 --pretty=format:'%h - %s (%cr) <%an>')"
    
    # Docker Status
    echo -e "\n${BLUE}Docker Container Status:${NC}"
    docker-compose -f "$COMPOSE_FILE" ps
    
    # Health Status
    echo -e "\n${BLUE}Health Status:${NC}"
    if curl -f -s http://localhost/health.php | jq . 2>/dev/null; then
        echo "✅ Application ist erreichbar"
    else
        echo "❌ Application ist nicht erreichbar"
    fi
    
    # Disk Usage
    echo -e "\n${BLUE}Disk Usage:${NC}"
    df -h "$PROJECT_DIR" | tail -1
    
    # Recent Logs
    echo -e "\n${BLUE}Recent Deployment Logs:${NC}"
    if [[ -f "$DEPLOY_LOG" ]]; then
        tail -10 "$DEPLOY_LOG"
    else
        echo "Keine Deployment Logs gefunden"
    fi
}

# Rollback durchführen
rollback() {
    log "INFO" "Starte Rollback..."
    
    local backup_path="$BACKUP_DIR/latest"
    
    if [[ ! -d "$backup_path" ]]; then
        error_exit "Kein Backup für Rollback gefunden"
    fi
    
    log "INFO" "Verwende Backup: $backup_path"
    
    # Git Rollback
    if [[ -f "$backup_path/git_commit.txt" ]]; then
        local rollback_commit=$(cat "$backup_path/git_commit.txt")
        log "INFO" "Rollback zu Git Commit: $rollback_commit"
        
        cd "$PROJECT_DIR"
        git checkout "$rollback_commit"
    fi
    
    # Konfiguration wiederherstellen
    if [[ -f "$backup_path/.env.prod" ]]; then
        cp "$backup_path/.env.prod" "$ENV_FILE"
        log "INFO" "Environment Konfiguration wiederhergestellt"
    fi
    
    # Datenbank Rollback
    if [[ -f "$backup_path/database_backup.sql" ]]; then
        log "INFO" "Stelle Datenbank wieder her..."
        "$SCRIPT_DIR/backup.sh" --restore "$backup_path/database_backup.sql"
    fi
    
    # Container neu starten
    log "INFO" "Starte Container neu..."
    docker-compose -f "$COMPOSE_FILE" down
    docker-compose -f "$COMPOSE_FILE" up -d
    
    # Health Check
    health_check
    
    log "INFO" "Rollback erfolgreich abgeschlossen"
}

# Hauptfunktion für Deployment
deploy() {
    log "INFO" "=== STARTE PRODUCTION DEPLOYMENT ==="
    
    local start_time=$(date +%s)
    
    # Voraussetzungen prüfen
    check_prerequisites
    
    # Git Status prüfen
    check_git_status
    
    # Bestätigung einholen (außer bei --force)
    if [[ "${FORCE:-false}" != "true" ]]; then
        echo -e "\n${YELLOW}WARNUNG: Production Deployment wird gestartet!${NC}"
        read -p "Fortfahren? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "INFO" "Deployment abgebrochen"
            exit 0
        fi
    fi
    
    # Backup erstellen
    create_backup
    
    # Code aktualisieren
    git_pull
    
    # Docker Images bauen
    build_images
    
    # Container aktualisieren
    update_containers
    
    # Migrationen ausführen
    run_migrations
    
    # Cache leeren
    clear_cache
    
    # Finaler Health Check
    health_check
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    log "INFO" "=== DEPLOYMENT ERFOLGREICH ABGESCHLOSSEN ==="
    log "INFO" "Dauer: ${duration} Sekunden"
    
    # Status anzeigen
    show_status
}

# Logs anzeigen
show_logs() {
    if [[ -f "$DEPLOY_LOG" ]]; then
        tail -f "$DEPLOY_LOG"
    else
        error_exit "Deployment Log nicht gefunden: $DEPLOY_LOG"
    fi
}

# Hauptprogramm
main() {
    # Standardwerte
    VERBOSE=false
    FORCE=false
    NO_BACKUP=false
    TAG=""
    
    # Parameter parsen
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            -f|--force)
                FORCE=true
                shift
                ;;
            --no-backup)
                NO_BACKUP=true
                shift
                ;;
            --tag)
                TAG="$2"
                shift 2
                ;;
            deploy)
                COMMAND="deploy"
                shift
                ;;
            rollback)
                COMMAND="rollback"
                shift
                ;;
            status)
                COMMAND="status"
                shift
                ;;
            health)
                COMMAND="health"
                shift
                ;;
            logs)
                COMMAND="logs"
                shift
                ;;
            backup)
                COMMAND="backup"
                shift
                ;;
            *)
                error_exit "Unbekannter Parameter: $1"
                ;;
        esac
    done
    
    # Command ausführen
    case "${COMMAND:-}" in
        deploy)
            deploy
            ;;
        rollback)
            rollback
            ;;
        status)
            show_status
            ;;
        health)
            health_check
            ;;
        logs)
            show_logs
            ;;
        backup)
            create_backup
            ;;
        *)
            show_help
            exit 1
            ;;
    esac
}

# Script ausführen
main "$@"
