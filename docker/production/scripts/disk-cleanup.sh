#!/bin/bash
# =============================================================================
# CivicDash - Nettoyage Espace Disque
# =============================================================================
# Exécuter régulièrement (cron) pour maintenir l'espace disque
#
# Crontab recommandé :
#   0 3 * * * /opt/civicdash/docker/production/scripts/disk-cleanup.sh --auto
#
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
LOG_DIR="/var/log/civicdash"
BACKUP_DIR="/backups"

# Seuils
DISK_WARNING_PERCENT=80
DISK_CRITICAL_PERCENT=90

# Logging
log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
success() { echo -e "${GREEN}[✓]${NC} $1"; }
warning() { echo -e "${YELLOW}[⚠]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }

# =============================================================================
# ANALYSE
# =============================================================================

get_disk_usage() {
    df -h "$PROJECT_DIR" | awk 'NR==2 {print $5}' | tr -d '%'
}

get_disk_available() {
    df -h "$PROJECT_DIR" | awk 'NR==2 {print $4}'
}

analyze_disk() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║              Analyse Espace Disque                          ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    echo -e "${GREEN}Système de fichiers :${NC}"
    df -h "$PROJECT_DIR" | head -2
    echo ""
    
    echo -e "${GREEN}Répartition par dossier :${NC}"
    echo ""
    
    # Docker
    local docker_size=$(docker system df --format "{{.Size}}" 2>/dev/null | head -1 || echo "N/A")
    echo "  Docker (total)    : $docker_size"
    
    # Images Docker
    local images_size=$(docker system df --format "{{.Size}}" 2>/dev/null | sed -n '1p' || echo "N/A")
    echo "    - Images        : $images_size"
    
    # Volumes Docker
    local volumes_size=$(docker system df --format "{{.Size}}" 2>/dev/null | sed -n '3p' || echo "N/A")
    echo "    - Volumes       : $volumes_size"
    
    # Build cache
    local cache_size=$(docker system df --format "{{.Size}}" 2>/dev/null | sed -n '4p' || echo "N/A")
    echo "    - Build cache   : $cache_size"
    echo ""
    
    # PostgreSQL
    if docker ps -q -f name=civicdash_db &> /dev/null; then
        local pg_size=$(docker exec civicdash_db psql -U civicdash -t -c \
            "SELECT pg_size_pretty(pg_database_size('civicdash'));" 2>/dev/null | tr -d ' ' || echo "N/A")
        echo "  PostgreSQL        : $pg_size"
    fi
    
    # Redis
    if docker ps -q -f name=civicdash_redis &> /dev/null; then
        local redis_size=$(docker exec civicdash_redis redis-cli info memory 2>/dev/null | \
            grep used_memory_human | cut -d: -f2 | tr -d '\r' || echo "N/A")
        echo "  Redis             : $redis_size"
    fi
    
    # Meilisearch
    local meili_dir="/var/lib/docker/volumes/civicdash_meilisearch_data/_data"
    if [ -d "$meili_dir" ]; then
        local meili_size=$(du -sh "$meili_dir" 2>/dev/null | cut -f1 || echo "N/A")
        echo "  Meilisearch       : $meili_size"
    fi
    
    # Backups
    if [ -d "$BACKUP_DIR" ]; then
        local backup_size=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1 || echo "N/A")
        echo "  Backups           : $backup_size"
    fi
    
    # Logs Laravel
    local laravel_logs="$PROJECT_DIR/storage/logs"
    if [ -d "$laravel_logs" ]; then
        local logs_size=$(du -sh "$laravel_logs" 2>/dev/null | cut -f1 || echo "N/A")
        echo "  Logs Laravel      : $logs_size"
    fi
    
    echo ""
    
    # Alertes
    local usage=$(get_disk_usage)
    if [ "$usage" -ge "$DISK_CRITICAL_PERCENT" ]; then
        error "CRITIQUE : Utilisation disque à ${usage}%"
    elif [ "$usage" -ge "$DISK_WARNING_PERCENT" ]; then
        warning "Utilisation disque élevée : ${usage}%"
    else
        success "Utilisation disque OK : ${usage}%"
    fi
    echo ""
}

# =============================================================================
# NETTOYAGES
# =============================================================================

cleanup_docker() {
    log "Nettoyage Docker..."
    
    local before=$(docker system df --format "{{.TotalCount}}" 2>/dev/null | paste -sd+ | bc 2>/dev/null || echo 0)
    
    # Images non utilisées
    docker image prune -af 2>/dev/null || true
    
    # Conteneurs arrêtés
    docker container prune -f 2>/dev/null || true
    
    # Volumes orphelins
    docker volume prune -f 2>/dev/null || true
    
    # Réseaux inutilisés
    docker network prune -f 2>/dev/null || true
    
    # Build cache
    docker builder prune -af 2>/dev/null || true
    
    success "Docker nettoyé"
}

cleanup_logs() {
    log "Nettoyage des logs..."
    
    # Logs Laravel (garder 7 jours)
    find "$PROJECT_DIR/storage/logs" -name "*.log" -type f -mtime +7 -delete 2>/dev/null || true
    
    # Logs Docker (rotation)
    # Les logs Docker sont gérés par la config logging dans docker-compose
    
    # Logs système CivicDash
    if [ -d "$LOG_DIR" ]; then
        find "$LOG_DIR" -name "*.log" -type f -mtime +7 -delete 2>/dev/null || true
    fi
    
    success "Logs nettoyés"
}

cleanup_backups() {
    log "Nettoyage des backups locaux..."
    
    if [ -d "$BACKUP_DIR" ]; then
        # Garder uniquement le dernier backup de chaque type
        
        # PostgreSQL
        ls -t "$BACKUP_DIR"/*.dump 2>/dev/null | tail -n +2 | xargs -r rm -f
        ls -t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +2 | xargs -r rm -f
        
        # Autres backups
        ls -t "$BACKUP_DIR"/*.tar.gz 2>/dev/null | tail -n +2 | xargs -r rm -f
        
        # Backups pré-déploiement
        ls -t "$BACKUP_DIR"/pre_deploy_*.dump 2>/dev/null | tail -n +2 | xargs -r rm -f
    fi
    
    success "Backups locaux nettoyés (1 seul conservé)"
}

cleanup_temp() {
    log "Nettoyage fichiers temporaires..."
    
    # Cache Laravel
    docker exec civicdash_app php artisan cache:clear 2>/dev/null || true
    
    # Views compilées
    docker exec civicdash_app php artisan view:clear 2>/dev/null || true
    
    # Sessions expirées
    find "$PROJECT_DIR/storage/framework/sessions" -type f -mtime +1 -delete 2>/dev/null || true
    
    # Cache framework
    find "$PROJECT_DIR/storage/framework/cache" -type f -mtime +7 -delete 2>/dev/null || true
    
    success "Fichiers temporaires nettoyés"
}

vacuum_postgres() {
    log "Vacuum PostgreSQL..."
    
    if docker ps -q -f name=civicdash_db &> /dev/null; then
        # VACUUM ANALYZE pour récupérer l'espace et mettre à jour les stats
        docker exec civicdash_db psql -U civicdash -c "VACUUM ANALYZE;" 2>/dev/null || true
        
        success "PostgreSQL vacuum terminé"
    else
        warning "PostgreSQL non disponible"
    fi
}

# =============================================================================
# MODE AUTO (CRON)
# =============================================================================

auto_cleanup() {
    local usage=$(get_disk_usage)
    local cleaned=false
    
    log "Vérification automatique de l'espace disque..."
    log "Utilisation actuelle : ${usage}%"
    
    # Toujours nettoyer Docker (faible impact)
    docker image prune -af --filter "until=24h" 2>/dev/null || true
    docker container prune -f 2>/dev/null || true
    
    # Si usage > 70%, nettoyage modéré
    if [ "$usage" -ge 70 ]; then
        log "Nettoyage modéré (usage > 70%)..."
        cleanup_logs
        cleanup_temp
        cleaned=true
    fi
    
    # Si usage > 80%, nettoyage plus agressif
    if [ "$usage" -ge "$DISK_WARNING_PERCENT" ]; then
        warning "Nettoyage agressif (usage > 80%)..."
        cleanup_docker
        cleanup_backups
        cleaned=true
    fi
    
    # Si usage > 90%, nettoyage critique
    if [ "$usage" -ge "$DISK_CRITICAL_PERCENT" ]; then
        error "Nettoyage CRITIQUE (usage > 90%)..."
        vacuum_postgres
        
        # Supprimer TOUS les backups sauf le plus récent
        if [ -d "$BACKUP_DIR" ]; then
            find "$BACKUP_DIR" -type f -mtime +1 -delete 2>/dev/null || true
        fi
        cleaned=true
    fi
    
    # Afficher le résultat
    local new_usage=$(get_disk_usage)
    local available=$(get_disk_available)
    
    if [ "$cleaned" = true ]; then
        success "Nettoyage terminé : ${usage}% → ${new_usage}% (${available} disponible)"
    else
        success "Espace disque OK : ${new_usage}% (${available} disponible)"
    fi
}

# =============================================================================
# NETTOYAGE COMPLET
# =============================================================================

full_cleanup() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║              Nettoyage Complet                               ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    local before=$(get_disk_usage)
    log "Utilisation avant : ${before}%"
    echo ""
    
    cleanup_docker
    cleanup_logs
    cleanup_backups
    cleanup_temp
    vacuum_postgres
    
    echo ""
    local after=$(get_disk_usage)
    local available=$(get_disk_available)
    
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    success "Nettoyage terminé !"
    echo ""
    echo "  Avant     : ${before}%"
    echo "  Après     : ${after}%"
    echo "  Disponible: ${available}"
    echo ""
}

# =============================================================================
# AIDE
# =============================================================================

show_help() {
    echo ""
    echo -e "${BLUE}CivicDash - Nettoyage Espace Disque${NC}"
    echo ""
    echo "Usage :"
    echo "  $0                 Analyse de l'espace disque"
    echo "  $0 --clean         Nettoyage complet"
    echo "  $0 --auto          Mode automatique (pour cron)"
    echo "  $0 --docker        Nettoyage Docker uniquement"
    echo "  $0 --logs          Nettoyage logs uniquement"
    echo "  $0 --backups       Nettoyage backups uniquement"
    echo "  $0 --vacuum        Vacuum PostgreSQL"
    echo "  $0 --help          Afficher cette aide"
    echo ""
    echo "Crontab recommandé :"
    echo "  0 3 * * * $0 --auto >> /var/log/civicdash/cleanup.log 2>&1"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    mkdir -p "$LOG_DIR" 2>/dev/null || true
    
    case "${1:-}" in
        --clean)
            full_cleanup
            ;;
        --auto)
            auto_cleanup
            ;;
        --docker)
            cleanup_docker
            ;;
        --logs)
            cleanup_logs
            ;;
        --backups)
            cleanup_backups
            ;;
        --vacuum)
            vacuum_postgres
            ;;
        --help|-h)
            show_help
            ;;
        *)
            analyze_disk
            ;;
    esac
}

main "$@"
