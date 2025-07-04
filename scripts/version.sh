#!/bin/bash

# Leiterpruefung Version Management Script
# Semantic Versioning mit automatischer Changelog-Generierung

set -e

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Konfiguration
VERSION_FILE="version.json"
CHANGELOG_FILE="CHANGELOG.md"
DOCKER_REGISTRY="ghcr.io"
DOCKER_IMAGE_NAME="meddatzk/leiterpruefung"

# Funktionen
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Aktuelle Version aus version.json lesen
get_current_version() {
    if [[ -f "$VERSION_FILE" ]]; then
        jq -r '.version' "$VERSION_FILE"
    else
        echo "1.0.0"
    fi
}

# Version in version.json aktualisieren
update_version_file() {
    local new_version=$1
    local commit_hash=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
    local branch=$(git branch --show-current 2>/dev/null || echo "unknown")
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%S.000Z")
    
    # Version aufteilen
    IFS='.' read -ra VERSION_PARTS <<< "$new_version"
    local major=${VERSION_PARTS[0]}
    local minor=${VERSION_PARTS[1]}
    local patch=${VERSION_PARTS[2]}
    
    cat > "$VERSION_FILE" << EOF
{
  "version": "$new_version",
  "major": $major,
  "minor": $minor,
  "patch": $patch,
  "prerelease": null,
  "build": null,
  "lastUpdated": "$timestamp",
  "gitTag": "v$new_version",
  "branch": "$branch",
  "commit": "$commit_hash",
  "releaseNotes": "Version $new_version"
}
EOF
    
    log_success "Version file updated to $new_version"
}

# Nächste Version basierend auf Commit-Messages berechnen
calculate_next_version() {
    local current_version=$1
    local version_type="patch"
    
    # Letzte Commits seit letztem Tag analysieren
    local last_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "")
    local commit_range=""
    
    if [[ -n "$last_tag" ]]; then
        commit_range="$last_tag..HEAD"
    else
        commit_range="HEAD"
    fi
    
    # Commit-Messages analysieren
    local commits=$(git log --pretty=format:"%s" $commit_range 2>/dev/null || echo "")
    
    if echo "$commits" | grep -q "^feat\|^feature"; then
        version_type="minor"
    fi
    
    if echo "$commits" | grep -q "BREAKING CHANGE\|^feat!\|^fix!\|^refactor!"; then
        version_type="major"
    fi
    
    # Version erhöhen
    IFS='.' read -ra VERSION_PARTS <<< "$current_version"
    local major=${VERSION_PARTS[0]}
    local minor=${VERSION_PARTS[1]}
    local patch=${VERSION_PARTS[2]}
    
    case $version_type in
        "major")
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        "minor")
            minor=$((minor + 1))
            patch=0
            ;;
        "patch")
            patch=$((patch + 1))
            ;;
    esac
    
    echo "$major.$minor.$patch"
}

# Changelog generieren
generate_changelog() {
    local version=$1
    local date=$(date +"%Y-%m-%d")
    
    log_info "Generating changelog for version $version..."
    
    # Backup des aktuellen Changelog
    if [[ -f "$CHANGELOG_FILE" ]]; then
        cp "$CHANGELOG_FILE" "${CHANGELOG_FILE}.bak"
    fi
    
    # Neue Changelog-Einträge generieren
    local temp_changelog=$(mktemp)
    
    cat > "$temp_changelog" << EOF
# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [${version}] - ${date}

EOF
    
    # Commits seit letztem Tag analysieren
    local last_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "")
    local commit_range=""
    
    if [[ -n "$last_tag" ]]; then
        commit_range="$last_tag..HEAD"
    else
        commit_range="HEAD"
    fi
    
    # Features
    local features=$(git log --pretty=format:"- %s" --grep="^feat" $commit_range 2>/dev/null || echo "")
    if [[ -n "$features" ]]; then
        echo "### Hinzugefügt" >> "$temp_changelog"
        echo "$features" >> "$temp_changelog"
        echo "" >> "$temp_changelog"
    fi
    
    # Fixes
    local fixes=$(git log --pretty=format:"- %s" --grep="^fix" $commit_range 2>/dev/null || echo "")
    if [[ -n "$fixes" ]]; then
        echo "### Behoben" >> "$temp_changelog"
        echo "$fixes" >> "$temp_changelog"
        echo "" >> "$temp_changelog"
    fi
    
    # Breaking Changes
    local breaking=$(git log --pretty=format:"- %s" --grep="BREAKING CHANGE\|!" $commit_range 2>/dev/null || echo "")
    if [[ -n "$breaking" ]]; then
        echo "### Breaking Changes" >> "$temp_changelog"
        echo "$breaking" >> "$temp_changelog"
        echo "" >> "$temp_changelog"
    fi
    
    # Alten Changelog anhängen (falls vorhanden)
    if [[ -f "${CHANGELOG_FILE}.bak" ]]; then
        # Alten Inhalt ab der zweiten Zeile anhängen (Header überspringen)
        tail -n +2 "${CHANGELOG_FILE}.bak" >> "$temp_changelog"
        rm "${CHANGELOG_FILE}.bak"
    fi
    
    mv "$temp_changelog" "$CHANGELOG_FILE"
    log_success "Changelog updated"
}

# Git Tag erstellen
create_git_tag() {
    local version=$1
    local tag="v$version"
    
    log_info "Creating git tag $tag..."
    
    # Tag erstellen
    git tag -a "$tag" -m "Release $version"
    
    log_success "Git tag $tag created"
}

# Docker Images bauen und taggen
build_docker_images() {
    local version=$1
    
    log_info "Building Docker images for version $version..."
    
    # Production Image bauen
    docker build -f docker/php/Dockerfile.prod -t "${DOCKER_IMAGE_NAME}:${version}" .
    docker tag "${DOCKER_IMAGE_NAME}:${version}" "${DOCKER_IMAGE_NAME}:latest"
    
    # Registry Tags
    docker tag "${DOCKER_IMAGE_NAME}:${version}" "${DOCKER_REGISTRY}/${DOCKER_IMAGE_NAME}:${version}"
    docker tag "${DOCKER_IMAGE_NAME}:${version}" "${DOCKER_REGISTRY}/${DOCKER_IMAGE_NAME}:latest"
    
    log_success "Docker images built and tagged"
}

# Docker Images pushen
push_docker_images() {
    local version=$1
    
    log_info "Pushing Docker images for version $version..."
    
    # Images pushen
    docker push "${DOCKER_REGISTRY}/${DOCKER_IMAGE_NAME}:${version}"
    docker push "${DOCKER_REGISTRY}/${DOCKER_IMAGE_NAME}:latest"
    
    log_success "Docker images pushed to registry"
}

# Hilfe anzeigen
show_help() {
    cat << EOF
Leiterpruefung Version Management Script

USAGE:
    $0 [COMMAND] [OPTIONS]

COMMANDS:
    auto        Automatische Versionierung basierend auf Commits
    major       Major Version erhöhen (Breaking Changes)
    minor       Minor Version erhöhen (neue Features)
    patch       Patch Version erhöhen (Bugfixes)
    current     Aktuelle Version anzeigen
    changelog   Changelog generieren
    tag         Git Tag für aktuelle Version erstellen
    docker      Docker Images bauen
    push        Docker Images pushen
    release     Vollständiger Release-Prozess
    help        Diese Hilfe anzeigen

OPTIONS:
    --dry-run   Nur anzeigen, was gemacht würde
    --no-docker Keine Docker Images bauen
    --no-push   Keine Docker Images pushen

EXAMPLES:
    $0 auto                 # Automatische Versionierung
    $0 minor                # Minor Version erhöhen
    $0 release              # Vollständiger Release
    $0 current              # Aktuelle Version anzeigen
    $0 release --dry-run    # Release simulieren

EOF
}

# Hauptfunktion
main() {
    local command=${1:-"help"}
    local dry_run=false
    local no_docker=false
    local no_push=false
    
    # Parameter parsen
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                dry_run=true
                shift
                ;;
            --no-docker)
                no_docker=true
                shift
                ;;
            --no-push)
                no_push=true
                shift
                ;;
            *)
                if [[ -z "$command" || "$command" == "help" ]]; then
                    command=$1
                fi
                shift
                ;;
        esac
    done
    
    # Git Repository prüfen
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        log_error "Not in a git repository"
        exit 1
    fi
    
    # jq prüfen
    if ! command -v jq &> /dev/null; then
        log_error "jq is required but not installed"
        exit 1
    fi
    
    case $command in
        "current")
            echo $(get_current_version)
            ;;
        "auto")
            local current_version=$(get_current_version)
            local next_version=$(calculate_next_version "$current_version")
            
            if [[ "$current_version" == "$next_version" ]]; then
                log_info "No version change needed (current: $current_version)"
                exit 0
            fi
            
            log_info "Version change: $current_version -> $next_version"
            
            if [[ "$dry_run" == "true" ]]; then
                log_info "DRY RUN - Would update version to $next_version"
                exit 0
            fi
            
            update_version_file "$next_version"
            generate_changelog "$next_version"
            ;;
        "major"|"minor"|"patch")
            local current_version=$(get_current_version)
            IFS='.' read -ra VERSION_PARTS <<< "$current_version"
            local major=${VERSION_PARTS[0]}
            local minor=${VERSION_PARTS[1]}
            local patch=${VERSION_PARTS[2]}
            
            case $command in
                "major")
                    major=$((major + 1))
                    minor=0
                    patch=0
                    ;;
                "minor")
                    minor=$((minor + 1))
                    patch=0
                    ;;
                "patch")
                    patch=$((patch + 1))
                    ;;
            esac
            
            local new_version="$major.$minor.$patch"
            log_info "Version change: $current_version -> $new_version"
            
            if [[ "$dry_run" == "true" ]]; then
                log_info "DRY RUN - Would update version to $new_version"
                exit 0
            fi
            
            update_version_file "$new_version"
            generate_changelog "$new_version"
            ;;
        "changelog")
            local current_version=$(get_current_version)
            generate_changelog "$current_version"
            ;;
        "tag")
            local current_version=$(get_current_version)
            if [[ "$dry_run" == "true" ]]; then
                log_info "DRY RUN - Would create tag v$current_version"
                exit 0
            fi
            create_git_tag "$current_version"
            ;;
        "docker")
            local current_version=$(get_current_version)
            if [[ "$dry_run" == "true" ]]; then
                log_info "DRY RUN - Would build Docker images for $current_version"
                exit 0
            fi
            build_docker_images "$current_version"
            ;;
        "push")
            local current_version=$(get_current_version)
            if [[ "$dry_run" == "true" ]]; then
                log_info "DRY RUN - Would push Docker images for $current_version"
                exit 0
            fi
            push_docker_images "$current_version"
            ;;
        "release")
            local current_version=$(get_current_version)
            local next_version=$(calculate_next_version "$current_version")
            
            if [[ "$current_version" == "$next_version" ]]; then
                log_info "No version change needed for release (current: $current_version)"
                next_version=$current_version
            fi
            
            log_info "Starting release process for version $next_version"
            
            if [[ "$dry_run" == "true" ]]; then
                log_info "DRY RUN - Release process for $next_version:"
                log_info "  1. Update version file"
                log_info "  2. Generate changelog"
                log_info "  3. Create git tag"
                if [[ "$no_docker" != "true" ]]; then
                    log_info "  4. Build Docker images"
                fi
                if [[ "$no_push" != "true" && "$no_docker" != "true" ]]; then
                    log_info "  5. Push Docker images"
                fi
                exit 0
            fi
            
            # Release-Prozess
            if [[ "$current_version" != "$next_version" ]]; then
                update_version_file "$next_version"
                generate_changelog "$next_version"
            fi
            
            create_git_tag "$next_version"
            
            if [[ "$no_docker" != "true" ]]; then
                build_docker_images "$next_version"
                
                if [[ "$no_push" != "true" ]]; then
                    push_docker_images "$next_version"
                fi
            fi
            
            log_success "Release $next_version completed successfully!"
            ;;
        "help"|*)
            show_help
            ;;
    esac
}

# Script ausführen
main "$@"
