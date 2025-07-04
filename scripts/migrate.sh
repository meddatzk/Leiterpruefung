#!/bin/bash

# ==============================================
# PRODUCTION MIGRATION SCRIPT
# Leiterprüfung System - Datenbank Migrationen
# ==============================================

set -euo pipefail

# Konfiguration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
MIGRATION_LOG="$PROJECT_DIR/logs/migration.log"
COMPOSE_FILE="$PROJECT_DIR/docker-compose.prod.yml"
ENV_FILE="$PROJECT_DIR/.env.prod"
MIGRATION_DIR="$PROJECT_DIR/database/migrations"
SCHEMA_DIR="$PROJECT_DIR/database/init"

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
    mkdir -p "$(dirname "$MIGRATION_LOG")"
    echo "[$timestamp] [$level] $message" >> "$MIGRATION_LOG"
}

# Fehlerbehandlung
error_exit() {
    log "ERROR" "$1"
    exit 1
}

# Cleanup Funktion
cleanup() {
    log "DEBUG" "Migration Cleanup wird ausgeführt..."
    # Temporäre Dateien löschen
    rm -f /tmp/migration_*.tmp
}

# Trap für Cleanup bei Exit
trap cleanup EXIT

# Hilfe anzeigen
show_help() {
    cat << EOF
Leiterprüfung Production Migration Script

USAGE:
    $0 [OPTIONS] COMMAND

COMMANDS:
    run             Führt alle ausstehenden Migrationen aus
    status          Zeigt Migration Status
    create NAME     Erstellt neue Migration
    rollback        Rollback zur vorherigen Migration
    reset           Setzt Datenbank zurück (VORSICHT!)
    validate        Validiert Migration Dateien
    backup          Erstellt Backup vor Migration

OPTIONS:
    -h, --help      Zeigt diese Hilfe
    -v, --verbose   Verbose Output
    -f, --force     Erzwingt Migration ohne Bestätigung
    --dry-run       Simulation ohne Ausführung
    --target VER    Migriert zu spezifischer Version

EXAMPLES:
    $0 run                      # Alle ausstehenden Migrationen
    $0 status                   # Migration Status anzeigen
    $0 create add_user_table    # Neue Migration erstellen
    $0 rollback                 # Letzte Migration rückgängig

EOF
}

# Environment Variablen laden
load_environment() {
    if [[ -f "$ENV_FILE" ]]; then
        source "$ENV_FILE"
        log "INFO" "Environment Variablen geladen: $ENV_FILE"
    else
        error_exit "Environment Datei nicht gefunden: $ENV_FILE"
    fi
}

# Datenbank Verbindung prüfen
check_database_connection() {
    log "INFO" "Prüfe Datenbank Verbindung..."
    
    # Prüfe ob Datenbank Container läuft
    if ! docker-compose -f "$COMPOSE_FILE" ps database | grep -q "Up"; then
        error_exit "Datenbank Container läuft nicht"
    fi
    
    # Teste Verbindung
    if docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -e "SELECT 1;" > /dev/null 2>&1; then
        log "INFO" "Datenbank Verbindung erfolgreich"
    else
        error_exit "Datenbank Verbindung fehlgeschlagen"
    fi
}

# Migration Tabelle erstellen
create_migration_table() {
    log "INFO" "Erstelle Migration Tabelle falls nicht vorhanden..."
    
    docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -D "${DB_NAME}" << 'EOF'
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(255) NOT NULL UNIQUE,
    filename VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT DEFAULT 0,
    checksum VARCHAR(64),
    INDEX idx_version (version),
    INDEX idx_executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF
    
    if [[ $? -eq 0 ]]; then
        log "INFO" "Migration Tabelle bereit"
    else
        error_exit "Fehler beim Erstellen der Migration Tabelle"
    fi
}

# Ausgeführte Migrationen abrufen
get_executed_migrations() {
    docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -D "${DB_NAME}" \
        -N -e "SELECT version FROM migrations ORDER BY executed_at;" 2>/dev/null || echo ""
}

# Verfügbare Migration Dateien finden
find_migration_files() {
    if [[ ! -d "$MIGRATION_DIR" ]]; then
        mkdir -p "$MIGRATION_DIR"
        log "INFO" "Migration Verzeichnis erstellt: $MIGRATION_DIR"
    fi
    
    find "$MIGRATION_DIR" -name "*.sql" -type f | sort
}

# Migration Datei validieren
validate_migration_file() {
    local migration_file="$1"
    
    if [[ ! -f "$migration_file" ]]; then
        log "ERROR" "Migration Datei nicht gefunden: $migration_file"
        return 1
    fi
    
    # Prüfe auf SQL Syntax Fehler (basic check)
    if ! grep -q ";" "$migration_file"; then
        log "WARN" "Migration enthält möglicherweise keine SQL Statements: $(basename "$migration_file")"
    fi
    
    # Prüfe auf gefährliche Operationen
    local dangerous_patterns=(
        "DROP DATABASE"
        "DROP SCHEMA"
        "TRUNCATE"
        "DELETE FROM.*WHERE.*1.*=.*1"
    )
    
    for pattern in "${dangerous_patterns[@]}"; do
        if grep -qi "$pattern" "$migration_file"; then
            log "WARN" "Gefährliche Operation gefunden in $(basename "$migration_file"): $pattern"
        fi
    done
    
    return 0
}

# Migration ausführen
execute_migration() {
    local migration_file="$1"
    local migration_name=$(basename "$migration_file" .sql)
    local version=$(echo "$migration_name" | grep -o '^[0-9]\{14\}' || echo "$migration_name")
    
    log "INFO" "Führe Migration aus: $migration_name"
    
    # Validierung
    if ! validate_migration_file "$migration_file"; then
        error_exit "Migration Validierung fehlgeschlagen: $migration_name"
    fi
    
    # Checksum berechnen
    local checksum=$(sha256sum "$migration_file" | cut -d' ' -f1)
    
    # Dry Run Modus
    if [[ "${DRY_RUN:-false}" == "true" ]]; then
        log "INFO" "[DRY RUN] Würde Migration ausführen: $migration_name"
        return 0
    fi
    
    # Backup vor Migration (falls gewünscht)
    if [[ "${BACKUP_BEFORE_MIGRATION:-true}" == "true" ]]; then
        log "INFO" "Erstelle Backup vor Migration..."
        "$SCRIPT_DIR/backup.sh" create --output-dir "$PROJECT_DIR/backups/pre-migration" || {
            log "WARN" "Backup vor Migration fehlgeschlagen, fortfahren? (y/N)"
            if [[ "${FORCE:-false}" != "true" ]]; then
                read -p "" -n 1 -r
                echo
                if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                    error_exit "Migration abgebrochen"
                fi
            fi
        }
    fi
    
    # Migration ausführen
    local start_time=$(date +%s%3N)
    
    if docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -D "${DB_NAME}" < "$migration_file"; then
        
        local end_time=$(date +%s%3N)
        local execution_time=$((end_time - start_time))
        
        # Migration als ausgeführt markieren
        docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
            -u root -p"${DB_ROOT_PASSWORD}" \
            -D "${DB_NAME}" \
            -e "INSERT INTO migrations (version, filename, execution_time_ms, checksum) VALUES ('$version', '$migration_name', $execution_time, '$checksum');"
        
        log "INFO" "Migration erfolgreich: $migration_name (${execution_time}ms)"
    else
        error_exit "Migration fehlgeschlagen: $migration_name"
    fi
}

# Alle ausstehenden Migrationen ausführen
run_migrations() {
    log "INFO" "=== STARTE DATENBANK MIGRATIONEN ==="
    
    local start_time=$(date +%s)
    
    # Voraussetzungen prüfen
    load_environment
    check_database_connection
    create_migration_table
    
    # Ausgeführte Migrationen abrufen
    local executed_migrations=($(get_executed_migrations))
    log "INFO" "Bereits ausgeführte Migrationen: ${#executed_migrations[@]}"
    
    # Verfügbare Migration Dateien finden
    local migration_files=($(find_migration_files))
    log "INFO" "Verfügbare Migration Dateien: ${#migration_files[@]}"
    
    if [[ ${#migration_files[@]} -eq 0 ]]; then
        log "INFO" "Keine Migration Dateien gefunden"
        return 0
    fi
    
    # Ausstehende Migrationen identifizieren
    local pending_migrations=()
    
    for migration_file in "${migration_files[@]}"; do
        local migration_name=$(basename "$migration_file" .sql)
        local version=$(echo "$migration_name" | grep -o '^[0-9]\{14\}' || echo "$migration_name")
        
        # Prüfe ob Migration bereits ausgeführt wurde
        local already_executed=false
        for executed in "${executed_migrations[@]}"; do
            if [[ "$executed" == "$version" ]]; then
                already_executed=true
                break
            fi
        done
        
        if [[ "$already_executed" == "false" ]]; then
            pending_migrations+=("$migration_file")
        fi
    done
    
    log "INFO" "Ausstehende Migrationen: ${#pending_migrations[@]}"
    
    if [[ ${#pending_migrations[@]} -eq 0 ]]; then
        log "INFO" "Alle Migrationen sind bereits ausgeführt"
        return 0
    fi
    
    # Ausstehende Migrationen anzeigen
    log "INFO" "Folgende Migrationen werden ausgeführt:"
    for migration in "${pending_migrations[@]}"; do
        log "INFO" "  - $(basename "$migration")"
    done
    
    # Bestätigung einholen (außer bei --force)
    if [[ "${FORCE:-false}" != "true" ]] && [[ "${DRY_RUN:-false}" != "true" ]]; then
        echo -e "\n${YELLOW}WARNUNG: ${#pending_migrations[@]} Migration(s) werden ausgeführt!${NC}"
        read -p "Fortfahren? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "INFO" "Migration abgebrochen"
            exit 0
        fi
    fi
    
    # Migrationen ausführen
    local executed_count=0
    local failed_count=0
    
    for migration_file in "${pending_migrations[@]}"; do
        if execute_migration "$migration_file"; then
            ((executed_count++))
        else
            ((failed_count++))
            if [[ "${CONTINUE_ON_ERROR:-false}" != "true" ]]; then
                break
            fi
        fi
    done
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    log "INFO" "=== MIGRATIONEN ABGESCHLOSSEN ==="
    log "INFO" "Erfolgreich: $executed_count"
    log "INFO" "Fehlgeschlagen: $failed_count"
    log "INFO" "Dauer: ${duration} Sekunden"
    
    if [[ $failed_count -gt 0 ]]; then
        error_exit "Einige Migrationen sind fehlgeschlagen"
    fi
}

# Migration Status anzeigen
show_migration_status() {
    log "INFO" "=== MIGRATION STATUS ==="
    
    load_environment
    check_database_connection
    create_migration_table
    
    # Ausgeführte Migrationen
    local executed_migrations=($(get_executed_migrations))
    
    # Verfügbare Migration Dateien
    local migration_files=($(find_migration_files))
    
    echo -e "\n${BLUE}Datenbank: ${DB_NAME}${NC}"
    echo -e "${BLUE}Migration Verzeichnis: $MIGRATION_DIR${NC}\n"
    
    if [[ ${#migration_files[@]} -eq 0 ]]; then
        echo "Keine Migration Dateien gefunden"
        return 0
    fi
    
    printf "%-40s %-15s %-20s %s\n" "MIGRATION" "STATUS" "AUSGEFÜHRT AM" "DAUER"
    printf "%-40s %-15s %-20s %s\n" "$(printf '%*s' 40 | tr ' ' '-')" "$(printf '%*s' 15 | tr ' ' '-')" "$(printf '%*s' 20 | tr ' ' '-')" "$(printf '%*s' 10 | tr ' ' '-')"
    
    for migration_file in "${migration_files[@]}"; do
        local migration_name=$(basename "$migration_file" .sql)
        local version=$(echo "$migration_name" | grep -o '^[0-9]\{14\}' || echo "$migration_name")
        local status="AUSSTEHEND"
        local executed_at=""
        local duration=""
        
        # Prüfe ob Migration ausgeführt wurde
        for executed in "${executed_migrations[@]}"; do
            if [[ "$executed" == "$version" ]]; then
                status="AUSGEFÜHRT"
                
                # Details aus Datenbank abrufen
                local migration_info=$(docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
                    -u root -p"${DB_ROOT_PASSWORD}" \
                    -D "${DB_NAME}" \
                    -N -e "SELECT executed_at, execution_time_ms FROM migrations WHERE version='$version';" 2>/dev/null)
                
                if [[ -n "$migration_info" ]]; then
                    executed_at=$(echo "$migration_info" | cut -f1)
                    local duration_ms=$(echo "$migration_info" | cut -f2)
                    duration="${duration_ms}ms"
                fi
                break
            fi
        done
        
        # Status färben
        local colored_status=""
        case $status in
            "AUSGEFÜHRT") colored_status="${GREEN}$status${NC}" ;;
            "AUSSTEHEND") colored_status="${YELLOW}$status${NC}" ;;
            *) colored_status="$status" ;;
        esac
        
        printf "%-40s %-25s %-20s %s\n" "$migration_name" "$colored_status" "$executed_at" "$duration"
    done
    
    # Zusammenfassung
    local total_migrations=${#migration_files[@]}
    local executed_count=${#executed_migrations[@]}
    local pending_count=$((total_migrations - executed_count))
    
    echo -e "\n${GREEN}Gesamt: $total_migrations${NC}"
    echo -e "${GREEN}Ausgeführt: $executed_count${NC}"
    echo -e "${YELLOW}Ausstehend: $pending_count${NC}"
}

# Neue Migration erstellen
create_migration() {
    local migration_name="$1"
    
    if [[ -z "$migration_name" ]]; then
        error_exit "Migration Name ist erforderlich"
    fi
    
    # Timestamp für Version
    local timestamp=$(date '+%Y%m%d%H%M%S')
    local filename="${timestamp}_${migration_name}.sql"
    local filepath="$MIGRATION_DIR/$filename"
    
    # Migration Verzeichnis erstellen
    mkdir -p "$MIGRATION_DIR"
    
    # Migration Template erstellen
    cat > "$filepath" << EOF
-- ==============================================
-- MIGRATION: $migration_name
-- VERSION: $timestamp
-- CREATED: $(date '+%Y-%m-%d %H:%M:%S')
-- ==============================================

-- Beschreibung:
-- TODO: Beschreibe was diese Migration macht

-- UP Migration (Änderungen anwenden)
-- TODO: SQL Statements hier einfügen

-- Beispiel:
-- CREATE TABLE example_table (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ALTER TABLE existing_table ADD COLUMN new_column VARCHAR(100);

-- INSERT INTO settings (key, value) VALUES ('new_setting', 'default_value');

-- WICHTIG: 
-- - Verwende Transaktionen für komplexe Änderungen
-- - Teste Migrationen in Entwicklungsumgebung
-- - Erstelle Backups vor kritischen Änderungen
-- - Verwende IF NOT EXISTS für CREATE Statements
-- - Verwende IF EXISTS für DROP Statements

EOF
    
    log "INFO" "Migration erstellt: $filepath"
    log "INFO" "Bearbeite die Datei und füge deine SQL Statements hinzu"
    
    # Öffne Editor falls verfügbar
    if command -v "${EDITOR:-nano}" &> /dev/null; then
        log "INFO" "Öffne Migration in Editor..."
        "${EDITOR:-nano}" "$filepath"
    fi
}

# Migration Rollback
rollback_migration() {
    log "INFO" "=== MIGRATION ROLLBACK ==="
    
    load_environment
    check_database_connection
    
    # Letzte Migration finden
    local last_migration=$(docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -D "${DB_NAME}" \
        -N -e "SELECT version FROM migrations ORDER BY executed_at DESC LIMIT 1;" 2>/dev/null)
    
    if [[ -z "$last_migration" ]]; then
        log "INFO" "Keine Migrationen zum Rollback gefunden"
        return 0
    fi
    
    log "WARN" "WARNUNG: Rollback der Migration: $last_migration"
    log "WARN" "Dies kann zu Datenverlust führen!"
    
    if [[ "${FORCE:-false}" != "true" ]]; then
        read -p "Rollback durchführen? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "INFO" "Rollback abgebrochen"
            exit 0
        fi
    fi
    
    # Backup vor Rollback
    log "INFO" "Erstelle Backup vor Rollback..."
    "$SCRIPT_DIR/backup.sh" create --output-dir "$PROJECT_DIR/backups/pre-rollback"
    
    # Migration aus Tabelle entfernen
    docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -D "${DB_NAME}" \
        -e "DELETE FROM migrations WHERE version='$last_migration';"
    
    log "INFO" "Rollback abgeschlossen: $last_migration"
    log "WARN" "WICHTIG: Manuelle Datenbankänderungen müssen ggf. rückgängig gemacht werden"
}

# Datenbank Reset (VORSICHT!)
reset_database() {
    log "WARN" "=== DATENBANK RESET ==="
    log "WARN" "WARNUNG: Dies löscht ALLE Daten und Migrationen!"
    
    if [[ "${FORCE:-false}" != "true" ]]; then
        echo -e "\n${RED}GEFAHR: Alle Daten werden gelöscht!${NC}"
        read -p "Wirklich fortfahren? Tippe 'RESET' um zu bestätigen: " -r
        if [[ "$REPLY" != "RESET" ]]; then
            log "INFO" "Reset abgebrochen"
            exit 0
        fi
    fi
    
    load_environment
    check_database_connection
    
    # Backup vor Reset
    log "INFO" "Erstelle Backup vor Reset..."
    "$SCRIPT_DIR/backup.sh" create --output-dir "$PROJECT_DIR/backups/pre-reset"
    
    # Datenbank neu erstellen
    docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -e "DROP DATABASE IF EXISTS ${DB_NAME}; CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # Schema neu laden
    if [[ -f "$SCHEMA_DIR/01_schema.sql" ]]; then
        log "INFO" "Lade Schema neu..."
        docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
            -u root -p"${DB_ROOT_PASSWORD}" \
            -D "${DB_NAME}" < "$SCHEMA_DIR/01_schema.sql"
    fi
    
    # Weitere Init Scripts ausführen
    for init_file in "$SCHEMA_DIR"/*.sql; do
        if [[ -f "$init_file" ]] && [[ "$(basename "$init_file")" != "01_schema.sql" ]]; then
            log "INFO" "Führe aus: $(basename "$init_file")"
            docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
                -u root -p"${DB_ROOT_PASSWORD}" \
                -D "${DB_NAME}" < "$init_file"
        fi
    done
    
    log "INFO" "Datenbank Reset abgeschlossen"
}

# Hauptprogramm
main() {
    # Standardwerte
    VERBOSE=false
    FORCE=false
    DRY_RUN=false
    TARGET_VERSION=""
    BACKUP_BEFORE_MIGRATION=true
    CONTINUE_ON_ERROR=false
    
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
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --target)
                TARGET_VERSION="$2"
                shift 2
                ;;
            run)
                COMMAND="run"
                shift
                ;;
            status)
                COMMAND="status"
                shift
                ;;
            create)
                COMMAND="create"
                MIGRATION_NAME="$2"
                shift 2
                ;;
            rollback)
                COMMAND="rollback"
                shift
                ;;
            reset)
                COMMAND="reset"
                shift
                ;;
            validate)
                COMMAND="validate"
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
        run)
            run_migrations
            ;;
        status)
            show_migration_status
            ;;
        create)
            create_migration "${MIGRATION_NAME:-}"
            ;;
        rollback)
            rollback_migration
            ;;
        reset)
            reset_database
            ;;
        validate)
            log "INFO" "Validiere Migration Dateien..."
            local migration_files=($(find_migration_files))
            local valid_count=0
            local invalid_count=0
            
            for migration_file in "${migration_files[@]}"; do
                if validate_migration_file "$migration_file"; then
                    ((valid_count++))
                else
                    ((invalid_count++))
                fi
            done
            
            log "INFO" "Validierung abgeschlossen: $valid_count gültig, $invalid_count ungültig"
            ;;
        backup)
            "$SCRIPT_DIR/backup.sh" create
            ;;
        *)
            show_help
            exit 1
            ;;
    esac
}

# Script ausführen
main "$@"
