#!/bin/bash
# =============================================================================
# CivicDash - Script de Backup Production
# =============================================================================
# Usage :
#   ./backup.sh                 # Backup complet
#   ./backup.sh --db-only       # Backup PostgreSQL uniquement
#   ./backup.sh --files-only    # Backup fichiers uniquement
#   ./backup.sh --restore       # Restauration interactive
# =============================================================================

set -euo pipefail

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../../.." && pwd)"
BACKUP_BASE_DIR="${BACKUP_DIR:-/backups}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DATE_DIR=$(date +%Y-%m-%d)

# Rétention
KEEP_DAILY=7
KEEP_WEEKLY=4
KEEP_MONTHLY=6

# Logging
log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
success() { echo -e "${GREEN}[✓]${NC} $1"; }
warning() { echo -e "${YELLOW}[⚠]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# =============================================================================
# FONCTIONS
# =============================================================================

setup_backup_dirs() {
    mkdir -p "$BACKUP_BASE_DIR/postgresql"
    mkdir -p "$BACKUP_BASE_DIR/redis"
    mkdir -p "$BACKUP_BASE_DIR/files"
    mkdir -p "$BACKUP_BASE_DIR/meilisearch"
}

backup_postgresql() {
    log "Backup PostgreSQL..."
    
    local backup_file="$BACKUP_BASE_DIR/postgresql/civicdash_${TIMESTAMP}.dump"
    local backup_sql="$BACKUP_BASE_DIR/postgresql/civicdash_${TIMESTAMP}.sql.gz"
    
    # Vérifier que PostgreSQL est accessible
    if ! docker exec civicdash_db_primary pg_isready -U civicdash &> /dev/null; then
        error "PostgreSQL n'est pas accessible"
    fi
    
    # Backup format custom (plus rapide pour restore)
    docker exec civicdash_db_primary pg_dump \
        -U civicdash \
        -Fc \
        --no-owner \
        --no-acl \
        civicdash > "$backup_file"
    
    # Backup SQL compressé (pour lecture humaine)
    docker exec civicdash_db_primary pg_dump \
        -U civicdash \
        --no-owner \
        --no-acl \
        civicdash | gzip > "$backup_sql"
    
    # Statistiques
    local size_dump=$(du -h "$backup_file" | cut -f1)
    local size_sql=$(du -h "$backup_sql" | cut -f1)
    
    success "PostgreSQL backup créé : $size_dump (dump) + $size_sql (sql.gz)"
    
    # Lien symbolique vers le dernier backup
    ln -sf "$backup_file" "$BACKUP_BASE_DIR/postgresql/latest.dump"
}

backup_redis() {
    log "Backup Redis..."
    
    local backup_file="$BACKUP_BASE_DIR/redis/dump_${TIMESTAMP}.rdb"
    
    # Déclencher un BGSAVE
    docker exec civicdash_redis redis-cli BGSAVE
    
    # Attendre la fin du save
    sleep 5
    
    # Copier le fichier RDB
    docker cp civicdash_redis:/data/dump.rdb "$backup_file" 2>/dev/null || true
    
    if [ -f "$backup_file" ]; then
        local size=$(du -h "$backup_file" | cut -f1)
        success "Redis backup créé : $size"
    else
        warning "Redis backup non disponible (cache vide ?)"
    fi
}

backup_files() {
    log "Backup des fichiers..."
    
    local backup_file="$BACKUP_BASE_DIR/files/storage_${TIMESTAMP}.tar.gz"
    
    # Backup du storage (uploads, logs, etc.)
    tar -czf "$backup_file" \
        -C "$PROJECT_DIR" \
        storage/app/public \
        storage/logs \
        2>/dev/null || true
    
    if [ -f "$backup_file" ]; then
        local size=$(du -h "$backup_file" | cut -f1)
        success "Fichiers backup créés : $size"
    fi
}

backup_meilisearch() {
    log "Backup Meilisearch..."
    
    local backup_file="$BACKUP_BASE_DIR/meilisearch/data_${TIMESTAMP}.tar.gz"
    
    # Créer un snapshot
    docker exec civicdash_search curl -s -X POST \
        "http://localhost:7700/snapshots" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY:-}" || true
    
    sleep 5
    
    # Copier les données
    docker cp civicdash_search:/meili_data "$BACKUP_BASE_DIR/meilisearch/temp_data" 2>/dev/null || true
    
    if [ -d "$BACKUP_BASE_DIR/meilisearch/temp_data" ]; then
        tar -czf "$backup_file" -C "$BACKUP_BASE_DIR/meilisearch" temp_data
        rm -rf "$BACKUP_BASE_DIR/meilisearch/temp_data"
        local size=$(du -h "$backup_file" | cut -f1)
        success "Meilisearch backup créé : $size"
    else
        warning "Meilisearch backup non disponible"
    fi
}

cleanup_old_backups() {
    log "Nettoyage des anciens backups..."
    
    # Fonction de nettoyage par répertoire
    cleanup_dir() {
        local dir=$1
        local pattern=$2
        
        # Garder les daily (7 jours)
        find "$dir" -name "$pattern" -type f -mtime +$KEEP_DAILY \
            ! -name "*_0[17]_*" | xargs -r rm -f
        
        # Garder les weekly (4 semaines) - dimanche (01 ou 07)
        find "$dir" -name "$pattern" -type f -mtime +$((KEEP_DAILY + KEEP_WEEKLY * 7)) \
            ! -name "*_01_*" | xargs -r rm -f
        
        # Garder les monthly (6 mois) - premier du mois
        find "$dir" -name "$pattern" -type f -mtime +$((KEEP_DAILY + KEEP_WEEKLY * 7 + KEEP_MONTHLY * 30)) \
            | xargs -r rm -f
    }
    
    cleanup_dir "$BACKUP_BASE_DIR/postgresql" "civicdash_*.dump"
    cleanup_dir "$BACKUP_BASE_DIR/postgresql" "civicdash_*.sql.gz"
    cleanup_dir "$BACKUP_BASE_DIR/redis" "dump_*.rdb"
    cleanup_dir "$BACKUP_BASE_DIR/files" "storage_*.tar.gz"
    cleanup_dir "$BACKUP_BASE_DIR/meilisearch" "data_*.tar.gz"
    
    success "Nettoyage terminé"
}

upload_to_s3() {
    log "Upload vers S3..."
    
    # Vérifier si restic ou aws-cli est disponible
    if command -v restic &> /dev/null; then
        if [ -n "${RESTIC_REPOSITORY:-}" ]; then
            restic backup "$BACKUP_BASE_DIR" \
                --tag "daily" \
                --tag "$(date +%A)" \
                2>/dev/null || true
            
            # Nettoyage restic
            restic forget \
                --keep-daily $KEEP_DAILY \
                --keep-weekly $KEEP_WEEKLY \
                --keep-monthly $KEEP_MONTHLY \
                --prune 2>/dev/null || true
            
            success "Upload S3 (restic) terminé"
        else
            warning "RESTIC_REPOSITORY non défini, skip upload S3"
        fi
    elif command -v aws &> /dev/null; then
        if [ -n "${S3_BUCKET:-}" ]; then
            aws s3 sync "$BACKUP_BASE_DIR" "s3://$S3_BUCKET/backups/" \
                --exclude "*" \
                --include "*/civicdash_${DATE_DIR}*" \
                2>/dev/null || true
            success "Upload S3 (aws-cli) terminé"
        else
            warning "S3_BUCKET non défini, skip upload S3"
        fi
    else
        warning "Ni restic ni aws-cli installé, skip upload S3"
    fi
}

restore_postgresql() {
    log "Restauration PostgreSQL..."
    
    # Lister les backups disponibles
    echo ""
    echo "Backups disponibles :"
    ls -lh "$BACKUP_BASE_DIR/postgresql/"*.dump 2>/dev/null | tail -10
    echo ""
    
    read -p "Entrez le nom du fichier à restaurer (ou 'latest') : " backup_name
    
    local backup_file
    if [ "$backup_name" = "latest" ]; then
        backup_file="$BACKUP_BASE_DIR/postgresql/latest.dump"
    else
        backup_file="$BACKUP_BASE_DIR/postgresql/$backup_name"
    fi
    
    if [ ! -f "$backup_file" ]; then
        error "Fichier non trouvé : $backup_file"
    fi
    
    echo ""
    warning "ATTENTION : Cette action va remplacer toutes les données actuelles !"
    read -p "Êtes-vous sûr ? (oui/non) : " confirm
    
    if [ "$confirm" != "oui" ]; then
        log "Restauration annulée"
        exit 0
    fi
    
    # Restauration
    log "Restauration en cours..."
    
    docker exec -i civicdash_db_primary pg_restore \
        -U civicdash \
        -d civicdash \
        --clean \
        --if-exists \
        --no-owner \
        --no-acl \
        < "$backup_file"
    
    success "Restauration PostgreSQL terminée"
    
    # Réindexer Meilisearch
    log "Réindexation Meilisearch..."
    docker exec civicdash_app php artisan scout:flush "App\Models\Topic" || true
    docker exec civicdash_app php artisan scout:import "App\Models\Topic" || true
    
    success "Restauration complète !"
}

show_backup_status() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                    Status des Backups                       ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    echo -e "${GREEN}PostgreSQL :${NC}"
    ls -lh "$BACKUP_BASE_DIR/postgresql/"*.dump 2>/dev/null | tail -5 || echo "  Aucun backup"
    echo ""
    
    echo -e "${GREEN}Redis :${NC}"
    ls -lh "$BACKUP_BASE_DIR/redis/"*.rdb 2>/dev/null | tail -5 || echo "  Aucun backup"
    echo ""
    
    echo -e "${GREEN}Fichiers :${NC}"
    ls -lh "$BACKUP_BASE_DIR/files/"*.tar.gz 2>/dev/null | tail -5 || echo "  Aucun backup"
    echo ""
    
    echo -e "${GREEN}Meilisearch :${NC}"
    ls -lh "$BACKUP_BASE_DIR/meilisearch/"*.tar.gz 2>/dev/null | tail -5 || echo "  Aucun backup"
    echo ""
    
    # Espace utilisé
    echo -e "${GREEN}Espace utilisé :${NC}"
    du -sh "$BACKUP_BASE_DIR"/* 2>/dev/null || echo "  N/A"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║              CivicDash - Backup Production                  ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    setup_backup_dirs
    
    case "${1:-}" in
        --db-only)
            backup_postgresql
            ;;
        --files-only)
            backup_files
            ;;
        --restore)
            restore_postgresql
            ;;
        --status)
            show_backup_status
            ;;
        --cleanup)
            cleanup_old_backups
            ;;
        *)
            # Backup complet
            backup_postgresql
            backup_redis
            backup_files
            backup_meilisearch
            cleanup_old_backups
            upload_to_s3
            show_backup_status
            ;;
    esac
    
    success "Opération terminée !"
    echo ""
}

main "$@"
