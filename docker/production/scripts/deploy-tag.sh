#!/bin/bash
# =============================================================================
# CivicDash - Déploiement par Tag (Optimisé Espace Disque)
# =============================================================================
# Usage :
#   ./deploy-tag.sh v1.2.0         # Déployer un tag spécifique
#   ./deploy-tag.sh --latest       # Déployer le dernier tag
#   ./deploy-tag.sh --list         # Lister les tags disponibles
#   ./deploy-tag.sh --rollback     # Revenir au tag précédent
#   ./deploy-tag.sh --status       # Afficher la version actuelle
# =============================================================================
#
# STRATÉGIE ESPACE DISQUE :
# ─────────────────────────
# • UNE seule version sur le serveur
# • Migrations INCRÉMENTALES (pas de réimport BDD)
# • Nettoyage Docker agressif après chaque déploiement
# • Backup minimal local (dernier uniquement)
#
# =============================================================================

set -euo pipefail

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../../.." && pwd)"
COMPOSE_FILE="$PROJECT_DIR/docker/production/docker-compose.production.yml"
VERSION_FILE="$PROJECT_DIR/.deployed-version"
PREVIOUS_VERSION_FILE="$PROJECT_DIR/.previous-version"
BACKUP_DIR="/backups"
MAX_LOCAL_BACKUPS=1  # Garder UN SEUL backup local

# Logging
log() { echo -e "${BLUE}[$(date +'%H:%M:%S')]${NC} $1"; }
success() { echo -e "${GREEN}[✓]${NC} $1"; }
warning() { echo -e "${YELLOW}[⚠]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# =============================================================================
# FONCTIONS UTILITAIRES
# =============================================================================

get_current_version() {
    if [ -f "$VERSION_FILE" ]; then
        cat "$VERSION_FILE"
    else
        echo "unknown"
    fi
}

get_latest_tag() {
    git fetch --tags --quiet
    git describe --tags --abbrev=0 2>/dev/null || echo ""
}

list_tags() {
    echo ""
    echo -e "${BLUE}Tags disponibles :${NC}"
    echo ""
    git fetch --tags --quiet
    git tag -l "v*" --sort=-version:refname | head -20 | while read tag; do
        local date=$(git log -1 --format="%ci" "$tag" 2>/dev/null | cut -d' ' -f1)
        local msg=$(git tag -l --format='%(contents:subject)' "$tag" 2>/dev/null)
        
        if [ "$tag" = "$(get_current_version)" ]; then
            echo -e "  ${GREEN}► $tag${NC} ($date) - $msg ${GREEN}[DÉPLOYÉ]${NC}"
        else
            echo "    $tag ($date) - $msg"
        fi
    done
    echo ""
}

check_disk_space() {
    local available=$(df -BG "$PROJECT_DIR" | awk 'NR==2 {print $4}' | tr -d 'G')
    local threshold=5  # Minimum 5 GB requis
    
    if [ "$available" -lt "$threshold" ]; then
        warning "Espace disque faible : ${available}GB disponible"
        log "Nettoyage Docker en cours..."
        docker system prune -af --volumes 2>/dev/null || true
        
        available=$(df -BG "$PROJECT_DIR" | awk 'NR==2 {print $4}' | tr -d 'G')
        if [ "$available" -lt "$threshold" ]; then
            error "Espace disque insuffisant après nettoyage : ${available}GB"
        fi
    fi
    
    success "Espace disque OK : ${available}GB disponible"
}

# =============================================================================
# BACKUP MINIMAL (Un seul backup local)
# =============================================================================

create_minimal_backup() {
    log "Création backup minimal (pré-déploiement)..."
    
    mkdir -p "$BACKUP_DIR"
    
    local current_version=$(get_current_version)
    local backup_file="$BACKUP_DIR/pre_deploy_${current_version}.dump"
    
    # Supprimer les anciens backups (garder seulement le dernier)
    find "$BACKUP_DIR" -name "pre_deploy_*.dump" -type f | sort -r | tail -n +$((MAX_LOCAL_BACKUPS + 1)) | xargs -r rm -f
    
    # Backup PostgreSQL (format custom, compressé)
    if docker ps -q -f name=civicdash_db_primary &> /dev/null; then
        docker exec civicdash_db_primary pg_dump -U civicdash -Fc civicdash > "$backup_file" 2>/dev/null || true
        
        if [ -f "$backup_file" ]; then
            local size=$(du -h "$backup_file" | cut -f1)
            success "Backup créé : $backup_file ($size)"
        fi
    fi
}

# =============================================================================
# DÉPLOIEMENT
# =============================================================================

deploy_tag() {
    local target_tag=$1
    local current_version=$(get_current_version)
    
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║       CivicDash - Déploiement Tag : $target_tag              ${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    # Vérifications
    log "Vérification du tag..."
    git fetch --tags --quiet
    
    if ! git rev-parse "$target_tag" &> /dev/null; then
        error "Tag '$target_tag' introuvable"
    fi
    
    if [ "$target_tag" = "$current_version" ]; then
        warning "Le tag $target_tag est déjà déployé"
        read -p "Redéployer quand même ? (o/n) " confirm
        [ "$confirm" != "o" ] && exit 0
    fi
    
    success "Tag $target_tag trouvé"
    
    # Espace disque
    check_disk_space
    
    # Backup minimal
    create_minimal_backup
    
    # Sauvegarder la version actuelle pour rollback
    if [ "$current_version" != "unknown" ]; then
        echo "$current_version" > "$PREVIOUS_VERSION_FILE"
    fi
    
    # Checkout du tag
    log "Checkout du tag $target_tag..."
    cd "$PROJECT_DIR"
    
    # Stash des modifications locales si nécessaire
    if ! git diff --quiet 2>/dev/null; then
        warning "Modifications locales détectées"
        git stash --quiet
    fi
    
    git checkout "$target_tag" --quiet
    success "Code mis à jour vers $target_tag"
    
    # Rebuild de l'image
    log "Construction de l'image Docker..."
    docker compose -f "$COMPOSE_FILE" build --no-cache app 2>&1 | tail -5
    success "Image construite"
    
    # Arrêt des anciens conteneurs
    log "Redémarrage des services..."
    docker compose -f "$COMPOSE_FILE" down --remove-orphans 2>/dev/null || true
    docker compose -f "$COMPOSE_FILE" up -d
    success "Services redémarrés"
    
    # Attendre que l'app soit prête
    log "Attente de l'application..."
    local retries=30
    while [ $retries -gt 0 ]; do
        if docker exec civicdash_app curl -sf http://localhost/up &> /dev/null; then
            break
        fi
        retries=$((retries - 1))
        sleep 2
    done
    
    if [ $retries -eq 0 ]; then
        error "L'application n'a pas démarré correctement"
    fi
    success "Application prête"
    
    # Migrations INCRÉMENTALES (clé pour ne pas réimporter la BDD)
    log "Exécution des migrations incrémentales..."
    docker exec civicdash_app php artisan migrate --force 2>&1 | grep -E "(Migrating|Migrated|Nothing)" || true
    success "Migrations appliquées"
    
    # Optimisation
    log "Optimisation de l'application..."
    docker exec civicdash_app php artisan optimize:clear --quiet
    docker exec civicdash_app php artisan optimize --quiet
    docker exec civicdash_app php artisan config:cache --quiet
    docker exec civicdash_app php artisan route:cache --quiet
    docker exec civicdash_app php artisan view:cache --quiet
    success "Application optimisée"
    
    # Sauvegarder la version déployée
    echo "$target_tag" > "$VERSION_FILE"
    
    # NETTOYAGE AGRESSIF (crucial pour l'espace disque)
    log "Nettoyage Docker agressif..."
    cleanup_docker
    
    # Afficher le résumé
    show_deploy_summary "$target_tag" "$current_version"
}

# =============================================================================
# NETTOYAGE DOCKER
# =============================================================================

cleanup_docker() {
    # Supprimer les images non utilisées
    docker image prune -af 2>/dev/null || true
    
    # Supprimer les conteneurs arrêtés
    docker container prune -f 2>/dev/null || true
    
    # Supprimer les volumes orphelins
    docker volume prune -f 2>/dev/null || true
    
    # Supprimer les réseaux inutilisés
    docker network prune -f 2>/dev/null || true
    
    # Supprimer le cache de build
    docker builder prune -af 2>/dev/null || true
    
    local freed=$(docker system df | awk '/Total/ {print $4}' 2>/dev/null || echo "N/A")
    success "Nettoyage terminé (espace récupérable : ~$freed)"
}

# =============================================================================
# ROLLBACK
# =============================================================================

rollback() {
    if [ ! -f "$PREVIOUS_VERSION_FILE" ]; then
        error "Aucune version précédente trouvée pour le rollback"
    fi
    
    local previous_version=$(cat "$PREVIOUS_VERSION_FILE")
    local current_version=$(get_current_version)
    
    echo ""
    warning "ROLLBACK : $current_version → $previous_version"
    echo ""
    read -p "Confirmer le rollback ? (oui/non) " confirm
    
    if [ "$confirm" != "oui" ]; then
        log "Rollback annulé"
        exit 0
    fi
    
    # Rollback des migrations si possible
    log "Tentative de rollback des migrations..."
    docker exec civicdash_app php artisan migrate:rollback --force 2>&1 | head -10 || true
    
    # Déployer la version précédente
    deploy_tag "$previous_version"
}

# =============================================================================
# AFFICHAGE
# =============================================================================

show_status() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║                CivicDash - Status                           ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    local current=$(get_current_version)
    local latest=$(get_latest_tag)
    local disk_used=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $3}')
    local disk_avail=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $4}')
    local disk_pct=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $5}')
    
    echo -e "  ${GREEN}Version déployée :${NC} $current"
    echo -e "  ${GREEN}Dernier tag      :${NC} $latest"
    
    if [ "$current" != "$latest" ] && [ -n "$latest" ]; then
        echo -e "  ${YELLOW}⚠ Une nouvelle version est disponible !${NC}"
    fi
    
    echo ""
    echo -e "  ${GREEN}Espace disque :${NC}"
    echo "    Utilisé     : $disk_used"
    echo "    Disponible  : $disk_avail"
    echo "    Utilisation : $disk_pct"
    echo ""
    
    # Docker
    echo -e "  ${GREEN}Docker :${NC}"
    docker system df 2>/dev/null | head -5 | sed 's/^/    /'
    echo ""
}

show_deploy_summary() {
    local new_version=$1
    local old_version=$2
    
    local disk_avail=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $4}')
    
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║              ✓ Déploiement Réussi !                         ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  Version précédente : $old_version"
    echo -e "  Nouvelle version   : ${GREEN}$new_version${NC}"
    echo -e "  Espace disponible  : $disk_avail"
    echo ""
    echo -e "  ${CYAN}Accès :${NC}"
    echo "    • Application : https://\${DOMAIN}"
    echo "    • Horizon     : https://\${DOMAIN}/horizon"
    echo ""
}

# =============================================================================
# AIDE
# =============================================================================

show_help() {
    echo ""
    echo -e "${BLUE}CivicDash - Déploiement par Tag${NC}"
    echo ""
    echo "Usage :"
    echo "  $0 <tag>           Déployer un tag spécifique (ex: v1.2.0)"
    echo "  $0 --latest        Déployer le dernier tag"
    echo "  $0 --list          Lister les tags disponibles"
    echo "  $0 --status        Afficher la version actuelle"
    echo "  $0 --rollback      Revenir au tag précédent"
    echo "  $0 --cleanup       Nettoyage Docker uniquement"
    echo "  $0 --help          Afficher cette aide"
    echo ""
    echo "Exemples :"
    echo "  $0 v1.0.0          # Déployer v1.0.0"
    echo "  $0 v1.2.0          # Déployer v1.2.0"
    echo "  $0 --latest        # Déployer le dernier tag"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    cd "$PROJECT_DIR"
    
    case "${1:-}" in
        --latest)
            local latest=$(get_latest_tag)
            if [ -z "$latest" ]; then
                error "Aucun tag trouvé"
            fi
            deploy_tag "$latest"
            ;;
        --list)
            list_tags
            ;;
        --status)
            show_status
            ;;
        --rollback)
            rollback
            ;;
        --cleanup)
            log "Nettoyage Docker..."
            cleanup_docker
            show_status
            ;;
        --help|-h|"")
            show_help
            ;;
        v*)
            deploy_tag "$1"
            ;;
        *)
            error "Option inconnue : $1"
            ;;
    esac
}

main "$@"
