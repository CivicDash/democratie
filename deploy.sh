#!/bin/bash

# ========================================
# Script de déploiement générique CivicDash
# ========================================

set -e  # Arrêt si erreur

echo "========================================"
echo "🚀 Déploiement CivicDash Production"
echo "========================================"
echo ""

# Couleurs pour les logs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Fonction de log
log_step() {
    echo -e "${BLUE}📦 $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Vérifier si on est dans le bon répertoire
if [ ! -f "composer.json" ]; then
    log_error "Erreur: composer.json introuvable. Êtes-vous dans le bon répertoire?"
    exit 1
fi

# 1. Database Migrations & Seeds (optionnel)
if [ "$1" == "--fresh-db" ]; then
    log_step "1/5 - Fresh database migrations..."
    docker compose exec app php artisan migrate:fresh --seed --force
    log_success "Database refreshed"
else
    log_step "1/5 - Running pending migrations..."
    docker compose exec app php artisan migrate --force
    log_success "Migrations executed"
fi

# 2. Build Frontend
log_step "2/5 - Building frontend assets..."
if docker compose exec -u root app npm run build; then
    log_success "Frontend built successfully"
else
    log_error "Frontend build failed"
    exit 1
fi

# 3. Clear All Caches
log_step "3/5 - Clearing Laravel caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
log_success "Caches cleared"

# 4. Optimize (optionnel en prod)
if [ "$1" == "--optimize" ] || [ "$2" == "--optimize" ]; then
    log_step "4/5 - Optimizing application..."
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    docker compose exec app php artisan view:cache
    log_success "Application optimized"
else
    log_step "4/5 - Skipping optimization (use --optimize flag)"
fi

# 5. Restart Services
log_step "5/5 - Restarting Docker services..."
docker compose restart app nginx queue
log_success "Services restarted"

echo ""
echo "========================================"
log_success "Déploiement terminé avec succès!"
echo "========================================"
echo ""
echo "🌐 Application disponible sur:"
echo "   https://demo.objectif2027.fr"
echo ""
echo "📝 Options disponibles:"
echo "   --fresh-db   : Réinitialise la base de données"
echo "   --optimize   : Active les caches de production"
echo ""
echo "📊 Logs en temps réel:"
echo "   docker compose logs -f app"
echo ""

