#!/bin/bash

# ==============================================
# PRODUCTION BACKUP SCRIPT
# Leiterprüfung System - Automatisierte Backups
# ==============================================

set -euo pipefail

# Konfiguration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_BASE_DIR="$PROJECT_DIR/backups"
BACKUP_LOG="$PROJECT_DIR/logs/backup.log"
COMPOSE_FILE="$PROJECT_DIR/docker-compose.prod.yml"
ENV_FILE="$PROJECT_DIR/.env.prod"

# Backup Konfiguration
DEFAULT_RETENTION_DAYS=30
DEFAULT_COMPRESSION_LEVEL=6
ENCRYPTION_KEY_FILE="$PROJECT_DIR/.backup_key"

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
    mkdir -p "$(dirname "$BACKUP_LOG")"
    echo "[$timestamp] [$level] $message" >> "$BACKUP_LOG"
}

# Fehlerbehandlung
error_exit() {
    log "ERROR" "$1"
    exit 1
}

# Cleanup Funktion
cleanup() {
    log "DEBUG" "Cleanup wird ausgeführt..."
    # Temporäre Dateien löschen
    rm -f /tmp/backup_*.tmp
    rm -f /tmp/restore_*.tmp
}

# Trap für Cleanup bei Exit
trap cleanup EXIT

# Hilfe anzeigen
show_help() {
    cat << EOF
Leiterprüfung Production Backup Script

USAGE:
    $0 [OPTIONS] COMMAND

COMMANDS:
    create          Erstellt vollständiges Backup
    restore         Stellt Backup wieder her
    list            Listet verfügbare Backups
    cleanup         Löscht alte Backups (Retention Policy)
    verify          Verifiziert Backup-Integrität
    encrypt         Verschlüsselt existierendes Backup
    decrypt         Entschlüsselt Backup

OPTIONS:
    -h, --help              Zeigt diese Hilfe
    -v, --verbose           Verbose Output
    -o, --output-dir DIR    Backup Ausgabeverzeichnis
    -r, --retention DAYS    Retention Policy in Tagen (default: $DEFAULT_RETENTION_DAYS)
    -c, --compress LEVEL    Komprimierungslevel 1-9 (default: $DEFAULT_COMPRESSION_LEVEL)
    -e, --encrypt           Backup verschlüsseln
    -k, --key-file FILE     Verschlüsselungsschlüssel-Datei
    --no-database           Datenbank-Backup überspringen
    --no-files              Datei-Backup überspringen
    --restore FILE          Backup-Datei für Wiederherstellung

EXAMPLES:
    $0 create                           # Standard Backup
    $0 create --encrypt                 # Verschlüsseltes Backup
    $0 restore --restore backup.tar.gz  # Backup wiederherstellen
    $0 cleanup --retention 7            # Alte Backups löschen (7 Tage)
    $0 list                             # Verfügbare Backups anzeigen

EOF
}

# Voraussetzungen prüfen
check_prerequisites() {
    log "INFO" "Prüfe Backup-Voraussetzungen..."
    
    # Docker prüfen
    if ! command -v docker &> /dev/null; then
        error_exit "Docker ist nicht installiert oder nicht im PATH"
    fi
    
    # Docker Compose prüfen
    if ! command -v docker-compose &> /dev/null; then
        error_exit "Docker Compose ist nicht installiert oder nicht im PATH"
    fi
    
    # Komprimierung Tools prüfen
    if ! command -v tar &> /dev/null; then
        error_exit "tar ist nicht installiert"
    fi
    
    if ! command -v gzip &> /dev/null; then
        error_exit "gzip ist nicht installiert"
    fi
    
    # Verschlüsselung prüfen (falls benötigt)
    if [[ "${ENCRYPT:-false}" == "true" ]] && ! command -v openssl &> /dev/null; then
        error_exit "openssl ist nicht installiert (für Verschlüsselung benötigt)"
    fi
    
    log "INFO" "Alle Voraussetzungen erfüllt"
}

# Verschlüsselungsschlüssel generieren oder laden
setup_encryption() {
    if [[ "${ENCRYPT:-false}" != "true" ]]; then
        return 0
    fi
    
    log "INFO" "Setup Verschlüsselung..."
    
    local key_file="${KEY_FILE:-$ENCRYPTION_KEY_FILE}"
    
    if [[ ! -f "$key_file" ]]; then
        log "INFO" "Generiere neuen Verschlüsselungsschlüssel..."
        
        # Sicheres Verzeichnis erstellen
        mkdir -p "$(dirname "$key_file")"
        chmod 700 "$(dirname "$key_file")"
        
        # 256-bit Schlüssel generieren
        openssl rand -base64 32 > "$key_file"
        chmod 600 "$key_file"
        
        log "WARN" "Neuer Verschlüsselungsschlüssel erstellt: $key_file"
        log "WARN" "WICHTIG: Schlüssel sicher aufbewahren! Ohne Schlüssel können Backups nicht wiederhergestellt werden!"
    else
        log "INFO" "Verwende existierenden Verschlüsselungsschlüssel: $key_file"
    fi
    
    # Schlüssel validieren
    if [[ ! -s "$key_file" ]]; then
        error_exit "Verschlüsselungsschlüssel ist leer oder nicht lesbar: $key_file"
    fi
    
    export ENCRYPTION_KEY_FILE="$key_file"
}

# Datenbank Backup erstellen
backup_database() {
    if [[ "${NO_DATABASE:-false}" == "true" ]]; then
        log "INFO" "Datenbank-Backup wird übersprungen"
        return 0
    fi
    
    log "INFO" "Erstelle Datenbank Backup..."
    
    local backup_dir="$1"
    local db_backup_file="$backup_dir/database_backup.sql"
    
    # Prüfe ob Datenbank Container läuft
    if ! docker-compose -f "$COMPOSE_FILE" ps database | grep -q "Up"; then
        log "WARN" "Datenbank Container läuft nicht, überspringe Datenbank Backup"
        return 0
    fi
    
    # Environment Variablen laden
    if [[ -f "$ENV_FILE" ]]; then
        source "$ENV_FILE"
    fi
    
    # MySQL Dump erstellen
    log "INFO" "Erstelle MySQL Dump..."
    docker-compose -f "$COMPOSE_FILE" exec -T database mysqldump \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --add-drop-table \
        --create-options \
        --disable-keys \
        --extended-insert \
        --quick \
        --lock-tables=false \
        -u root -p"${DB_ROOT_PASSWORD}" \
        --databases "${DB_NAME}" > "$db_backup_file"
    
    if [[ $? -eq 0 ]]; then
        log "INFO" "Datenbank Backup erfolgreich: $(du -h "$db_backup_file" | cut -f1)"
        
        # Backup verifizieren
        if [[ -s "$db_backup_file" ]]; then
            local line_count=$(wc -l < "$db_backup_file")
            log "INFO" "Datenbank Backup Zeilen: $line_count"
        else
            error_exit "Datenbank Backup ist leer"
        fi
    else
        error_exit "Datenbank Backup fehlgeschlagen"
    fi
    
    # Schema-Info speichern
    log "INFO" "Speichere Datenbank Schema-Informationen..."
    docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
        -u root -p"${DB_ROOT_PASSWORD}" \
        -e "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}'" \
        > "$backup_dir/database_schema_info.txt" 2>/dev/null || true
}

# Dateien Backup erstellen
backup_files() {
    if [[ "${NO_FILES:-false}" == "true" ]]; then
        log "INFO" "Datei-Backup wird übersprungen"
        return 0
    fi
    
    log "INFO" "Erstelle Datei Backup..."
    
    local backup_dir="$1"
    local files_backup_file="$backup_dir/files_backup.tar"
    
    cd "$PROJECT_DIR"
    
    # Wichtige Dateien und Verzeichnisse für Backup
    local backup_paths=(
        "web/public"
        "web/src"
        "web/templates"
        "database/init"
        "docker"
        "scripts"
        ".env.prod"
        "docker-compose.prod.yml"
        "composer.json"
        "composer.lock"
    )
    
    # Ausschlüsse definieren
    local exclude_patterns=(
        "*.log"
        "*.tmp"
        "*/cache/*"
        "*/tmp/*"
        "*/.git/*"
        "*/node_modules/*"
        "*/vendor/*"
        "*/tests/*"
    )
    
    # Exclude-Datei erstellen
    local exclude_file="/tmp/backup_exclude.tmp"
    printf '%s\n' "${exclude_patterns[@]}" > "$exclude_file"
    
    # Tar Archive erstellen
    log "INFO" "Erstelle Datei-Archive..."
    tar --exclude-from="$exclude_file" \
        -cf "$files_backup_file" \
        "${backup_paths[@]}" 2>/dev/null || {
        log "WARN" "Einige Dateien konnten nicht gesichert werden (möglicherweise nicht vorhanden)"
    }
    
    if [[ -f "$files_backup_file" ]]; then
        log "INFO" "Datei Backup erfolgreich: $(du -h "$files_backup_file" | cut -f1)"
    else
        error_exit "Datei Backup fehlgeschlagen"
    fi
    
    # Cleanup
    rm -f "$exclude_file"
}

# Docker Volumes Backup
backup_volumes() {
    log "INFO" "Erstelle Docker Volumes Backup..."
    
    local backup_dir="$1"
    local volumes_backup_dir="$backup_dir/volumes"
    
    mkdir -p "$volumes_backup_dir"
    
    # MySQL Volume Backup
    if docker volume ls | grep -q "leiter_mysql_data"; then
        log "INFO" "Sichere MySQL Volume..."
        docker run --rm \
            -v leiter_mysql_data:/source:ro \
            -v "$volumes_backup_dir":/backup \
            alpine:latest \
            tar -czf /backup/mysql_volume.tar.gz -C /source .
    fi
    
    # Redis Volume Backup (falls vorhanden)
    if docker volume ls | grep -q "leiter_redis_data"; then
        log "INFO" "Sichere Redis Volume..."
        docker run --rm \
            -v leiter_redis_data:/source:ro \
            -v "$volumes_backup_dir":/backup \
            alpine:latest \
            tar -czf /backup/redis_volume.tar.gz -C /source .
    fi
}

# Backup Metadaten erstellen
create_backup_metadata() {
    local backup_dir="$1"
    local backup_type="${2:-manual}"
    
    log "INFO" "Erstelle Backup Metadaten..."
    
    local metadata_file="$backup_dir/backup_metadata.json"
    local timestamp=$(date -u '+%Y-%m-%dT%H:%M:%SZ')
    
    # Git Informationen (falls verfügbar)
    local git_commit=""
    local git_branch=""
    if git rev-parse --git-dir > /dev/null 2>&1; then
        git_commit=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
        git_branch=$(git branch --show-current 2>/dev/null || echo "unknown")
    fi
    
    # System Informationen
    local hostname=$(hostname)
    local username=$(whoami)
    local os_info=$(uname -a)
    
    # Docker Informationen
    local docker_version=$(docker --version 2>/dev/null || echo "unknown")
    local compose_version=$(docker-compose --version 2>/dev/null || echo "unknown")
    
    # Backup Größe berechnen
    local backup_size=$(du -sb "$backup_dir" | cut -f1)
    local backup_size_human=$(du -sh "$backup_dir" | cut -f1)
    
    # JSON Metadaten erstellen
    cat > "$metadata_file" << EOF
{
    "backup_info": {
        "timestamp": "$timestamp",
        "type": "$backup_type",
        "version": "1.0",
        "size_bytes": $backup_size,
        "size_human": "$backup_size_human"
    },
    "system_info": {
        "hostname": "$hostname",
        "username": "$username",
        "os": "$os_info",
        "docker_version": "$docker_version",
        "compose_version": "$compose_version"
    },
    "git_info": {
        "commit": "$git_commit",
        "branch": "$git_branch"
    },
    "backup_contents": {
        "database": $([ -f "$backup_dir/database_backup.sql" ] && echo "true" || echo "false"),
        "files": $([ -f "$backup_dir/files_backup.tar" ] && echo "true" || echo "false"),
        "volumes": $([ -d "$backup_dir/volumes" ] && echo "true" || echo "false"),
        "encrypted": $([ "${ENCRYPT:-false}" == "true" ] && echo "true" || echo "false")
    },
    "checksums": {
EOF
    
    # Checksums für alle Backup-Dateien
    local first=true
    for file in "$backup_dir"/*.sql "$backup_dir"/*.tar "$backup_dir"/volumes/*.tar.gz; do
        if [[ -f "$file" ]]; then
            if [[ "$first" == "true" ]]; then
                first=false
            else
                echo "," >> "$metadata_file"
            fi
            local filename=$(basename "$file")
            local checksum=$(sha256sum "$file" | cut -d' ' -f1)
            echo "        \"$filename\": \"$checksum\"" >> "$metadata_file"
        fi
    done
    
    cat >> "$metadata_file" << EOF
    }
}
EOF
    
    log "INFO" "Backup Metadaten erstellt: $metadata_file"
}

# Backup komprimieren
compress_backup() {
    local backup_dir="$1"
    local output_file="$2"
    local compression_level="${COMPRESSION_LEVEL:-$DEFAULT_COMPRESSION_LEVEL}"
    
    log "INFO" "Komprimiere Backup (Level: $compression_level)..."
    
    cd "$(dirname "$backup_dir")"
    local backup_name=$(basename "$backup_dir")
    
    # Komprimierung mit Progress
    tar -cf - "$backup_name" | pv -s $(du -sb "$backup_name" | cut -f1) | gzip -"$compression_level" > "$output_file"
    
    if [[ $? -eq 0 ]]; then
        log "INFO" "Backup komprimiert: $(du -h "$output_file" | cut -f1)"
        
        # Original Verzeichnis löschen
        rm -rf "$backup_dir"
    else
        error_exit "Backup Komprimierung fehlgeschlagen"
    fi
}

# Backup verschlüsseln
encrypt_backup() {
    local input_file="$1"
    local output_file="$2"
    
    log "INFO" "Verschlüssele Backup..."
    
    if [[ ! -f "$ENCRYPTION_KEY_FILE" ]]; then
        error_exit "Verschlüsselungsschlüssel nicht gefunden: $ENCRYPTION_KEY_FILE"
    fi
    
    # AES-256-CBC Verschlüsselung
    openssl enc -aes-256-cbc -salt -pbkdf2 -iter 100000 \
        -pass file:"$ENCRYPTION_KEY_FILE" \
        -in "$input_file" \
        -out "$output_file"
    
    if [[ $? -eq 0 ]]; then
        log "INFO" "Backup verschlüsselt: $(du -h "$output_file" | cut -f1)"
        
        # Original Datei löschen
        rm -f "$input_file"
    else
        error_exit "Backup Verschlüsselung fehlgeschlagen"
    fi
}

# Backup entschlüsseln
decrypt_backup() {
    local input_file="$1"
    local output_file="$2"
    
    log "INFO" "Entschlüssele Backup..."
    
    if [[ ! -f "$ENCRYPTION_KEY_FILE" ]]; then
        error_exit "Verschlüsselungsschlüssel nicht gefunden: $ENCRYPTION_KEY_FILE"
    fi
    
    # AES-256-CBC Entschlüsselung
    openssl enc -aes-256-cbc -d -pbkdf2 -iter 100000 \
        -pass file:"$ENCRYPTION_KEY_FILE" \
        -in "$input_file" \
        -out "$output_file"
    
    if [[ $? -eq 0 ]]; then
        log "INFO" "Backup entschlüsselt: $(du -h "$output_file" | cut -f1)"
    else
        error_exit "Backup Entschlüsselung fehlgeschlagen"
    fi
}

# Vollständiges Backup erstellen
create_backup() {
    log "INFO" "=== STARTE BACKUP ERSTELLUNG ==="
    
    local start_time=$(date +%s)
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_name="backup_${timestamp}"
    local backup_dir="${OUTPUT_DIR:-$BACKUP_BASE_DIR}/$backup_name"
    
    # Backup Verzeichnis erstellen
    mkdir -p "$backup_dir"
    
    # Verschlüsselung setup
    setup_encryption
    
    # Backup Komponenten erstellen
    backup_database "$backup_dir"
    backup_files "$backup_dir"
    backup_volumes "$backup_dir"
    
    # Metadaten erstellen
    create_backup_metadata "$backup_dir" "manual"
    
    # Komprimierung
    local compressed_file="${backup_dir}.tar.gz"
    compress_backup "$backup_dir" "$compressed_file"
    
    # Verschlüsselung (falls aktiviert)
    if [[ "${ENCRYPT:-false}" == "true" ]]; then
        local encrypted_file="${compressed_file}.enc"
        encrypt_backup "$compressed_file" "$encrypted_file"
        compressed_file="$encrypted_file"
    fi
    
    # Symlink auf letztes Backup
    local backup_base_dir=$(dirname "$compressed_file")
    ln -sfn "$(basename "$compressed_file")" "$backup_base_dir/latest_backup"
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    log "INFO" "=== BACKUP ERFOLGREICH ERSTELLT ==="
    log "INFO" "Backup Datei: $compressed_file"
    log "INFO" "Backup Größe: $(du -h "$compressed_file" | cut -f1)"
    log "INFO" "Dauer: ${duration} Sekunden"
    
    # Backup verifizieren
    verify_backup "$compressed_file"
}

# Backup verifizieren
verify_backup() {
    local backup_file="$1"
    
    log "INFO" "Verifiziere Backup: $(basename "$backup_file")"
    
    if [[ ! -f "$backup_file" ]]; then
        error_exit "Backup Datei nicht gefunden: $backup_file"
    fi
    
    # Dateigröße prüfen
    local file_size=$(stat -f%z "$backup_file" 2>/dev/null || stat -c%s "$backup_file" 2>/dev/null)
    if [[ $file_size -lt 1024 ]]; then
        error_exit "Backup Datei ist zu klein (< 1KB): $file_size bytes"
    fi
    
    # Verschlüsselung prüfen
    if [[ "$backup_file" == *.enc ]]; then
        log "INFO" "Prüfe verschlüsseltes Backup..."
        
        local temp_file="/tmp/verify_backup.tmp"
        decrypt_backup "$backup_file" "$temp_file"
        
        # Komprimierung prüfen
        if gzip -t "$temp_file" 2>/dev/null; then
            log "INFO" "✅ Backup Verschlüsselung und Komprimierung OK"
        else
            error_exit "❌ Backup Komprimierung defekt"
        fi
        
        rm -f "$temp_file"
    else
        # Komprimierung prüfen
        if gzip -t "$backup_file" 2>/dev/null; then
            log "INFO" "✅ Backup Komprimierung OK"
        else
            error_exit "❌ Backup Komprimierung defekt"
        fi
    fi
    
    log "INFO" "✅ Backup Verifikation erfolgreich"
}

# Backup wiederherstellen
restore_backup() {
    local backup_file="${RESTORE_FILE:-}"
    
    if [[ -z "$backup_file" ]]; then
        error_exit "Keine Backup-Datei für Wiederherstellung angegeben (--restore)"
    fi
    
    if [[ ! -f "$backup_file" ]]; then
        error_exit "Backup Datei nicht gefunden: $backup_file"
    fi
    
    log "INFO" "=== STARTE BACKUP WIEDERHERSTELLUNG ==="
    log "WARN" "WARNUNG: Wiederherstellung überschreibt aktuelle Daten!"
    
    if [[ "${FORCE:-false}" != "true" ]]; then
        read -p "Fortfahren? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "INFO" "Wiederherstellung abgebrochen"
            exit 0
        fi
    fi
    
    local start_time=$(date +%s)
    local restore_dir="/tmp/restore_$(date +%s)"
    
    mkdir -p "$restore_dir"
    
    # Backup entschlüsseln (falls verschlüsselt)
    local working_file="$backup_file"
    if [[ "$backup_file" == *.enc ]]; then
        log "INFO" "Entschlüssele Backup..."
        setup_encryption
        local decrypted_file="$restore_dir/backup_decrypted.tar.gz"
        decrypt_backup "$backup_file" "$decrypted_file"
        working_file="$decrypted_file"
    fi
    
    # Backup extrahieren
    log "INFO" "Extrahiere Backup..."
    cd "$restore_dir"
    tar -xzf "$working_file"
    
    # Backup Verzeichnis finden
    local backup_content_dir=$(find "$restore_dir" -name "backup_*" -type d | head -1)
    if [[ -z "$backup_content_dir" ]]; then
        error_exit "Backup Inhalt nicht gefunden"
    fi
    
    log "INFO" "Backup Inhalt gefunden: $backup_content_dir"
    
    # Metadaten lesen
    if [[ -f "$backup_content_dir/backup_metadata.json" ]]; then
        log "INFO" "Backup Metadaten:"
        cat "$backup_content_dir/backup_metadata.json" | jq . 2>/dev/null || cat "$backup_content_dir/backup_metadata.json"
    fi
    
    # Container stoppen
    log "INFO" "Stoppe Container für Wiederherstellung..."
    cd "$PROJECT_DIR"
    docker-compose -f "$COMPOSE_FILE" down
    
    # Datenbank wiederherstellen
    if [[ -f "$backup_content_dir/database_backup.sql" ]]; then
        log "INFO" "Stelle Datenbank wieder her..."
        
        # Datenbank Container starten
        docker-compose -f "$COMPOSE_FILE" up -d database
        sleep 30
        
        # Environment Variablen laden
        if [[ -f "$ENV_FILE" ]]; then
            source "$ENV_FILE"
        fi
        
        # Datenbank wiederherstellen
        docker-compose -f "$COMPOSE_FILE" exec -T database mysql \
            -u root -p"${DB_ROOT_PASSWORD}" \
            < "$backup_content_dir/database_backup.sql"
        
        log "INFO" "Datenbank Wiederherstellung abgeschlossen"
    fi
    
    # Dateien wiederherstellen
    if [[ -f "$backup_content_dir/files_backup.tar" ]]; then
        log "INFO" "Stelle Dateien wieder her..."
        
        cd "$PROJECT_DIR"
        tar -xf "$backup_content_dir/files_backup.tar"
        
        log "INFO" "Dateien Wiederherstellung abgeschlossen"
    fi
    
    # Docker Volumes wiederherstellen
    if [[ -d "$backup_content_dir/volumes" ]]; then
        log "INFO" "Stelle Docker Volumes wieder her..."
        
        # MySQL Volume
        if [[ -f "$backup_content_dir/volumes/mysql_volume.tar.gz" ]]; then
            docker volume rm leiter_mysql_data 2>/dev/null || true
            docker volume create leiter_mysql_data
            docker run --rm \
                -v leiter_mysql_data:/target \
                -v "$backup_content_dir/volumes":/backup \
                alpine:latest \
                tar -xzf /backup/mysql_volume.tar.gz -C /target
        fi
        
        # Redis Volume
        if [[ -f "$backup_content_dir/volumes/redis_volume.tar.gz" ]]; then
            docker volume rm leiter_redis_data 2>/dev/null || true
            docker volume create leiter_redis_data
            docker run --rm \
                -v leiter_redis_data:/target \
                -v "$backup_content_dir/volumes":/backup \
                alpine:latest \
                tar -xzf /backup/redis_volume.tar.gz -C /target
        fi
        
        log "INFO" "Docker Volumes Wiederherstellung abgeschlossen"
    fi
    
    # Container neu starten
    log "INFO" "Starte Container neu..."
    docker-compose -f "$COMPOSE_FILE" up -d
    
    # Cleanup
    rm -rf "$restore_dir"
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    log "INFO" "=== WIEDERHERSTELLUNG ERFOLGREICH ABGESCHLOSSEN ==="
    log "INFO" "Dauer: ${duration} Sekunden"
}

# Verfügbare Backups auflisten
list_backups() {
    log "INFO" "=== VERFÜGBARE BACKUPS ==="
    
    local backup_dir="${OUTPUT_DIR:-$BACKUP_BASE_DIR}"
    
    if [[ ! -d "$backup_dir" ]]; then
        log "INFO" "Keine Backups gefunden (Verzeichnis existiert nicht): $backup_dir"
        return 0
    fi
    
    echo -e "\n${BLUE}Backup Verzeichnis: $backup_dir${NC}\n"
    
    # Backup Dateien finden und sortieren
    local backup_files=($(find "$backup_dir" -name "backup_*.tar.gz*" -type f | sort -r))
    
    if [[ ${#backup_files[@]} -eq 0 ]]; then
        log "INFO" "Keine Backup-Dateien gefunden"
        return 0
    fi
    
    printf "%-30s %-15s %-10s %-20s %s\n" "BACKUP" "DATUM" "GRÖSSE" "TYP" "PFAD"
    printf "%-30s %-15s %-10s %-20s %s\n" "$(printf '%*s' 30 | tr ' ' '-')" "$(printf '%*s' 15 | tr ' ' '-')" "$(printf '%*s' 10 | tr ' ' '-')" "$(printf '%*s' 20 | tr ' ' '-')" "$(printf '%*s' 20 | tr ' ' '-')"
    
    for backup_file in "${backup_files[@]}"; do
        local filename=$(basename "$backup_file")
        local filesize=$(du -h "$backup_file" | cut -f1)
        local filetype="Standard"
        
        if [[ "$filename" == *.enc ]]; then
            filetype="Verschlüsselt"
        fi
        
        # Datum aus Dateiname extrahieren
        local date_part=$(echo "$filename" | grep -o '[0-9]\{8\}_[0-9]\{6\}' | head -1)
        local formatted_date=""
        if [[ -n "$date_part" ]]; then
            local year=${date_part:0:4}
            local month=${date_part:4:2}
            local day=${date_part:6:2}
            local hour=${date_part:9:2}
            local minute=${date_part:11:2}
            formatted_date="$day.$month.$year $hour:$minute"
        else
            formatted_date=$(stat -f%Sm -t"%d.%m.%Y %H:%M" "$backup_file" 2>/dev/null || stat -c%y "$backup_file" | cut -d' ' -f1-2)
        fi
        
        printf "%-30s %-15s %-10s %-20s %s\n" "$filename" "$formatted_date" "$filesize" "$filetype" "$backup_file"
    done
    
    echo -e "\n${GREEN}Gesamt: ${#backup_files[@]} Backup(s) gefunden${NC}"
}

# Alte Backups löschen (Retention Policy)
cleanup_backups() {
    local retention_days="${RETENTION_DAYS:-$DEFAULT_RETENTION_DAYS}"
    local backup_dir="${OUTPUT_DIR:-$BACKUP_BASE_DIR}"
    
    log "INFO" "=== BACKUP CLEANUP (Retention: $retention_days Tage) ==="
    
    if [[ ! -d "$backup_dir" ]]; then
        log "INFO" "Backup Verzeichnis existiert nicht: $backup_dir"
        return 0
    fi
    
    # Finde alte Backup-Dateien
    local old_backups=($(find "$backup_dir" -name "backup_*.tar.gz*" -type f -mtime +$retention_days))
    
    if [[ ${#old_backups[@]} -eq 0 ]]; then
        log "INFO" "Keine alten Backups zum Löschen gefunden"
        return 0
    fi
    
    log "INFO" "Gefundene alte Backups (älter als $retention_days Tage):"
    for backup in "${old_backups[@]}"; do
        local backup_age=$(find "$backup" -mtime +$retention_days -printf "%TY-%Tm-%Td %TH:%TM\n" 2>/dev/null || stat -f%Sm -t"%Y-%m-%d %H:%M" "$backup" 2>/dev/null)
        log "INFO" "  - $(basename "$backup") ($backup_age)"
    done
    
    if [[ "${FORCE:-false}" != "true" ]]; then
        read -p "Diese ${#old_backups[@]} Backup(s) löschen? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "INFO" "Cleanup abgebrochen"
            return 0
        fi
    fi
    
    # Backups löschen
    local deleted_count=0
    local freed_space=0
    
    for backup in "${old_backups[@]}"; do
        local backup_size=$(stat -f%z "$backup" 2>/dev/null || stat -c%s "$backup" 2>/dev/null)
        
        if rm -f "$backup"; then
            log "INFO" "Gelöscht: $(basename "$backup")"
            ((deleted_count++))
            ((freed_space += backup_size))
        else
            log "ERROR" "Fehler beim Löschen: $(basename "$backup")"
        fi
    done
    
    local freed_space_human=$(numfmt --to=iec-i --suffix=B $freed_space 2>/dev/null || echo "${freed_space} bytes")
    
    log "INFO" "Cleanup abgeschlossen: $deleted_count Backup(s) gelöscht, $freed_space_human freigegeben"
}

# Hauptprogramm
main() {
    # Standardwerte
    VERBOSE=false
    FORCE=false
    ENCRYPT=false
    NO_DATABASE=false
    NO_FILES=false
    OUTPUT_DIR=""
    RETENTION_DAYS=""
    COMPRESSION_LEVEL=""
    KEY_FILE=""
    RESTORE_FILE=""
    
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
            -e|--encrypt)
                ENCRYPT=true
                shift
                ;;
            -o|--output-dir)
                OUTPUT_DIR="$2"
                shift 2
                ;;
            -r|--retention)
                RETENTION_DAYS="$2"
                shift 2
                ;;
            -c|--compress)
                COMPRESSION_LEVEL="$2"
                shift 2
                ;;
            -k|--key-file)
                KEY_FILE="$2"
                shift 2
                ;;
            --no-database)
                NO_DATABASE=true
                shift
                ;;
            --no-files)
                NO_FILES=true
                shift
                ;;
            --restore)
                RESTORE_FILE="$2"
                shift 2
                ;;
            create)
                COMMAND="create"
                shift
                ;;
            restore)
                COMMAND="restore"
                shift
                ;;
            list)
                COMMAND="list"
                shift
                ;;
            cleanup)
                COMMAND="cleanup"
                shift
                ;;
            verify)
                COMMAND="verify"
                shift
                ;;
            encrypt)
                COMMAND="encrypt"
                shift
                ;;
            decrypt)
                COMMAND="decrypt"
                shift
                ;;
            *)
                error_exit "Unbekannter Parameter: $1"
                ;;
        esac
    done
    
    # Voraussetzungen prüfen
    check_prerequisites
    
    # Command ausführen
    case "${COMMAND:-}" in
        create)
            create_backup
            ;;
        restore)
            restore_backup
            ;;
        list)
            list_backups
            ;;
        cleanup)
            cleanup_backups
            ;;
        verify)
            if [[ -n "${RESTORE_FILE:-}" ]]; then
                verify_backup "$RESTORE_FILE"
            else
                error_exit "Keine Backup-Datei für Verifikation angegeben (--restore)"
            fi
            ;;
        encrypt)
            if [[ -n "${RESTORE_FILE:-}" ]]; then
                setup_encryption
                local encrypted_file="${RESTORE_FILE}.enc"
                encrypt_backup "$RESTORE_FILE" "$encrypted_file"
                log "INFO" "Backup verschlüsselt: $encrypted_file"
            else
                error_exit "Keine Backup-Datei für Verschlüsselung angegeben (--restore)"
            fi
            ;;
        decrypt)
            if [[ -n "${RESTORE_FILE:-}" ]]; then
                setup_encryption
                local decrypted_file="${RESTORE_FILE%.enc}"
                decrypt_backup "$RESTORE_FILE" "$decrypted_file"
                log "INFO" "Backup entschlüsselt: $decrypted_file"
            else
                error_exit "Keine Backup-Datei für Entschlüsselung angegeben (--restore)"
            fi
            ;;
        *)
            show_help
            exit 1
            ;;
    esac
}

# Script ausführen
main "$@"
