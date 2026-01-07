#!/bin/bash
# =============================================================================
# CivicDash - Script de Déploiement Production
# =============================================================================
# Usage :
#   ./deploy.sh                 # Déploiement complet
#   ./deploy.sh --quick         # Redéploiement rapide (sans rebuild)
#   ./deploy.sh --init          # Première installation
#   ./deploy.sh --rollback      # Rollback à la version précédente
# =============================================================================

set -euo pipefail

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../../.." && pwd)"
COMPOSE_FILE="$PROJECT_DIR/docker/production/docker-compose.production.yml"
BACKUP_DIR="/backups/deploys"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Logging
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
    exit 1
}

# =============================================================================
# FONCTIONS
# =============================================================================

check_requirements() {
    log "Vérification des prérequis..."
    
    # Docker
    if ! command -v docker &> /dev/null; then
        error "Docker n'est pas installé"
    fi
    
    # Docker Compose
    if ! docker compose version &> /dev/null; then
        error "Docker Compose n'est pas installé"
    fi
    
    # Fichier .env
    if [ ! -f "$PROJECT_DIR/.env" ]; then
        error "Fichier .env manquant. Copiez env.production.example vers .env"
    fi
    
    # Variables critiques
    source "$PROJECT_DIR/.env"
    
    if [ -z "${DB_PASSWORD:-}" ]; then
        error "DB_PASSWORD non défini dans .env"
    fi
    
    if [ -z "${MEILISEARCH_KEY:-}" ]; then
        error "MEILISEARCH_KEY non défini dans .env"
    fi
    
    success "Prérequis validés"
}

create_backup() {
    log "Création du backup pré-déploiement..."
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup de la base de données
    if docker ps -q -f name=civicdash_db_primary &> /dev/null; then
        docker exec civicdash_db_primary pg_dump -U civicdash -Fc civicdash \
            > "$BACKUP_DIR/civicdash_$TIMESTAMP.dump" 2>/dev/null || true
        success "Backup PostgreSQL créé"
    fi
    
    # Tag de l'image actuelle pour rollback
    if docker images civicdash_app:latest &> /dev/null; then
        docker tag civicdash_app:latest "civicdash_app:backup_$TIMESTAMP" 2>/dev/null || true
        success "Image Docker taguée pour rollback"
    fi
    
    # Garder seulement les 5 derniers backups
    ls -t "$BACKUP_DIR"/*.dump 2>/dev/null | tail -n +6 | xargs -r rm -f
    
    success "Backup terminé"
}

pull_latest() {
    log "Récupération du dernier code..."
    
    cd "$PROJECT_DIR"
    
    # Stash des modifications locales si nécessaire
    if ! git diff --quiet; then
        warning "Modifications locales détectées, stash en cours..."
        git stash
    fi
    
    git fetch origin main
    git checkout main
    git pull origin main
    
    success "Code mis à jour"
}

build_images() {
    log "Construction des images Docker..."
    
    cd "$PROJECT_DIR"
    
    docker compose -f "$COMPOSE_FILE" build --no-cache app
    
    success "Images construites"
}

generate_pgbouncer_auth() {
    log "Génération de l'authentification PgBouncer..."
    
    # Attendre que PostgreSQL soit prêt
    local retries=30
    while [ $retries -gt 0 ]; do
        if docker exec civicdash_db_primary pg_isready -U civicdash &> /dev/null; then
            break
        fi
        retries=$((retries - 1))
        sleep 2
    done
    
    if [ $retries -eq 0 ]; then
        warning "PostgreSQL non prêt, skip génération PgBouncer auth"
        return
    fi
    
    # Récupérer le hash du mot de passe
    local password_hash
    password_hash=$(docker exec civicdash_db_primary psql -U civicdash -t -c \
        "SELECT rolpassword FROM pg_authid WHERE rolname='civicdash';" 2>/dev/null | tr -d ' ')
    
    if [ -n "$password_hash" ]; then
        echo "\"civicdash\" \"$password_hash\"" > "$PROJECT_DIR/docker/production/pgbouncer/userlist.txt"
        success "Authentification PgBouncer générée"
    else
        warning "Impossible de récupérer le hash, utilisation du fichier existant"
    fi
}

deploy_services() {
    log "Déploiement des services..."
    
    cd "$PROJECT_DIR"
    
    # Arrêter les anciens conteneurs
    docker compose -f "$COMPOSE_FILE" down --remove-orphans
    
    # Démarrer les nouveaux
    docker compose -f "$COMPOSE_FILE" up -d
    
    success "Services déployés"
}

deploy_quick() {
    log "Redéploiement rapide (sans rebuild)..."
    
    cd "$PROJECT_DIR"
    
    # Redéployer uniquement l'application
    docker compose -f "$COMPOSE_FILE" up -d --no-deps --force-recreate app horizon scheduler
    
    success "Application redéployée"
}

run_migrations() {
    log "Exécution des migrations..."
    
    # Attendre que l'app soit prête
    local retries=30
    while [ $retries -gt 0 ]; do
        if docker exec civicdash_app curl -sf http://localhost/up &> /dev/null; then
            break
        fi
        retries=$((retries - 1))
        sleep 2
    done
    
    if [ $retries -eq 0 ]; then
        error "Application non prête après 60 secondes"
    fi
    
    # Migrations
    docker exec civicdash_app php artisan migrate --force
    
    success "Migrations exécutées"
}

optimize_app() {
    log "Optimisation de l'application..."
    
    docker exec civicdash_app php artisan optimize:clear
    docker exec civicdash_app php artisan optimize
    docker exec civicdash_app php artisan config:cache
    docker exec civicdash_app php artisan route:cache
    docker exec civicdash_app php artisan view:cache
    docker exec civicdash_app php artisan event:cache
    
    success "Application optimisée"
}

health_check() {
    log "Vérification de la santé des services..."
    
    local all_healthy=true
    
    # Check App
    if docker exec civicdash_app curl -sf http://localhost/up &> /dev/null; then
        success "Application : OK"
    else
        error "Application : ERREUR"
        all_healthy=false
    fi
    
    # Check PostgreSQL
    if docker exec civicdash_db_primary pg_isready -U civicdash &> /dev/null; then
        success "PostgreSQL : OK"
    else
        warning "PostgreSQL : ERREUR"
        all_healthy=false
    fi
    
    # Check PgBouncer
    if docker exec civicdash_pgbouncer pg_isready -h localhost -p 6432 -U civicdash &> /dev/null; then
        success "PgBouncer : OK"
    else
        warning "PgBouncer : ERREUR (normal au premier démarrage)"
    fi
    
    # Check Redis
    if docker exec civicdash_redis redis-cli ping &> /dev/null; then
        success "Redis : OK"
    else
        warning "Redis : ERREUR"
        all_healthy=false
    fi
    
    # Check Meilisearch
    if docker exec civicdash_search curl -sf http://localhost:7700/health &> /dev/null; then
        success "Meilisearch : OK"
    else
        warning "Meilisearch : ERREUR"
        all_healthy=false
    fi
    
    # Check Horizon
    if docker exec civicdash_horizon php artisan horizon:status 2>/dev/null | grep -q "running"; then
        success "Horizon : OK"
    else
        warning "Horizon : ERREUR ou en démarrage"
    fi
    
    if [ "$all_healthy" = true ]; then
        success "Tous les services sont opérationnels ✓"
    else
        warning "Certains services nécessitent une attention"
    fi
}

rollback() {
    log "Rollback vers la version précédente..."
    
    # Trouver le dernier backup
    local latest_backup
    latest_backup=$(ls -t "$BACKUP_DIR"/*.dump 2>/dev/null | head -1)
    
    if [ -z "$latest_backup" ]; then
        error "Aucun backup trouvé pour le rollback"
    fi
    
    # Trouver la dernière image taguée
    local latest_image
    latest_image=$(docker images --format "{{.Tag}}" civicdash_app | grep "backup_" | head -1)
    
    if [ -n "$latest_image" ]; then
        log "Restauration de l'image : civicdash_app:$latest_image"
        docker tag "civicdash_app:$latest_image" civicdash_app:latest
    fi
    
    # Restaurer la base de données
    log "Restauration de la base de données depuis : $latest_backup"
    docker exec -i civicdash_db_primary pg_restore -U civicdash -d civicdash -c < "$latest_backup"
    
    # Redéployer
    deploy_quick
    
    success "Rollback terminé"
}

init_deployment() {
    log "Initialisation du premier déploiement..."
    
    check_requirements
    
    cd "$PROJECT_DIR"
    
    # Construire les images
    build_images
    
    # Démarrer les services de base (DB, Redis, Meilisearch)
    docker compose -f "$COMPOSE_FILE" up -d postgres-primary redis meilisearch
    
    # Attendre PostgreSQL
    log "Attente de PostgreSQL..."
    sleep 10
    
    # Générer l'auth PgBouncer
    generate_pgbouncer_auth
    
    # Démarrer PgBouncer et l'app
    docker compose -f "$COMPOSE_FILE" up -d
    
    # Attendre l'app
    log "Attente de l'application..."
    sleep 15
    
    # Migrations initiales
    docker exec civicdash_app php artisan migrate --force
    
    # Seeders
    log "Exécution des seeders..."
    docker exec civicdash_app php artisan db:seed --force
    
    # Indexation Meilisearch
    log "Indexation Meilisearch..."
    docker exec civicdash_app php artisan scout:import "App\Models\Topic" || true
    docker exec civicdash_app php artisan scout:import "App\Models\ActeurAN" || true
    docker exec civicdash_app php artisan scout:import "App\Models\Senateur" || true
    
    # Optimisation
    optimize_app
    
    # Vérification
    health_check
    
    success "Première installation terminée !"
    echo ""
    echo -e "${GREEN}Accès :${NC}"
    echo "  • Application : https://\${DOMAIN}"
    echo "  • Horizon     : https://\${DOMAIN}/horizon"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║           CivicDash - Déploiement Production               ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    case "${1:-}" in
        --init)
            init_deployment
            ;;
        --quick)
            check_requirements
            deploy_quick
            optimize_app
            health_check
            ;;
        --rollback)
            rollback
            ;;
        --health)
            health_check
            ;;
        *)
            # Déploiement standard
            check_requirements
            create_backup
            pull_latest
            build_images
            generate_pgbouncer_auth
            deploy_services
            run_migrations
            optimize_app
            health_check
            ;;
    esac
    
    echo ""
    success "Déploiement terminé avec succès !"
    echo ""
}

main "$@"
