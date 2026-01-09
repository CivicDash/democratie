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
    log_step "1/6 - Fresh database migrations..."
    docker compose exec app php artisan migrate:fresh --seed --force
    log_success "Database refreshed"
else
    log_step "1/6 - Running pending migrations..."
    docker compose exec app php artisan migrate --force
    log_success "Migrations executed"
fi

# 2. Build Frontend
log_step "2/6 - Building frontend assets..."
if docker compose exec -u root app npm run build; then
    log_success "Frontend built successfully"
else
    log_error "Frontend build failed"
    exit 1
fi

# 3. Clear All Caches
log_step "3/6 - Clearing Laravel caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan event:clear 2>/dev/null || true
log_success "Caches cleared"

# 4. Reload Octane Workers (important pour recharger le manifeste Vite)
log_step "4/6 - Reloading Octane workers..."
docker compose exec app php artisan octane:reload 2>/dev/null || docker compose restart app
log_success "Octane workers reloaded"

# 5. Optimize (optionnel en prod)
if [ "$1" == "--optimize" ] || [ "$2" == "--optimize" ]; then
    log_step "5/6 - Optimizing application..."
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    docker compose exec app php artisan view:cache
    log_success "Application optimized"
else
    log_step "5/6 - Skipping optimization (use --optimize flag)"
fi

# 6. Restart remaining services
log_step "6/6 - Restarting remaining services..."
docker compose restart nginx queue 2>/dev/null || true
log_success "Services restarted"

echo ""
echo "========================================"
log_success "Déploiement terminé avec succès!"
echo "========================================"
echo ""
echo "🌐 Application disponible sur:"
echo "   https://demo.objectif2027.fr"
echo ""

# 7. Vérification des imports nocturnes (optionnel)
if [ "$1" == "--check-imports" ] || [ "$2" == "--check-imports" ] || [ "$3" == "--check-imports" ]; then
    echo ""
    echo "========================================"
    log_step "Vérification des imports nocturnes..."
    echo "========================================"
    echo ""
    
    # Date d'aujourd'hui et hier pour les logs
    TODAY=$(date +%Y-%m-%d)
    YESTERDAY=$(date -d "yesterday" +%Y-%m-%d 2>/dev/null || date -v-1d +%Y-%m-%d)
    
    echo -e "${BLUE}📅 Recherche des imports du $YESTERDAY et $TODAY${NC}"
    echo ""
    
    # Vérifier les logs Laravel pour les commandes d'import
    log_step "Derniers imports exécutés:"
    docker compose exec -T app grep -E "(import:|sync:|enrich:)" storage/logs/laravel.log 2>/dev/null | tail -30 || echo "Pas de logs d'import récents"
    echo ""
    
    # Vérifier le scheduler
    log_step "État du scheduler:"
    docker compose exec -T app php artisan schedule:list 2>/dev/null | head -25
    echo ""
    
    # Statistiques rapides des données
    log_step "Statistiques des données importées:"
    docker compose exec -T app php artisan tinker --execute="
        echo '📊 Statistiques actuelles:' . PHP_EOL;
        echo '   Députés: ' . \App\Models\Depute::count() . PHP_EOL;
        echo '   Sénateurs: ' . \App\Models\Senateur::count() . PHP_EOL;
        echo '   Dossiers législatifs: ' . \App\Models\DossierLegislatif::count() . PHP_EOL;
        echo '   Amendements AN: ' . \App\Models\AmendementAN::count() . PHP_EOL;
        echo '   Scrutins AN: ' . \App\Models\ScrutinAN::count() . PHP_EOL;
        echo '   Questions: ' . \App\Models\QuestionAN::count() . PHP_EOL;
        echo '   Événements calendrier: ' . \App\Models\EvenementLegislatif::count() . PHP_EOL;
        echo '   Débats Sénat: ' . \App\Models\SenatDebat::count() . PHP_EOL;
        echo '   Budgets communes: ' . \App\Models\CommuneBudget::count() . PHP_EOL;
    " 2>/dev/null || echo "Erreur lors de la récupération des stats"
    echo ""
    
    # Vérifier les erreurs récentes
    log_step "Erreurs récentes (dernières 24h):"
    docker compose exec -T app grep -E "(ERROR|EMERGENCY|CRITICAL)" storage/logs/laravel.log 2>/dev/null | tail -10 || echo "Aucune erreur critique"
    echo ""
    
    log_success "Vérification terminée"
fi

echo "📝 Options disponibles:"
echo "   --fresh-db       : Réinitialise la base de données"
echo "   --optimize       : Active les caches de production"
echo "   --check-imports  : Vérifie les imports nocturnes"
echo ""
echo "📊 Logs en temps réel:"
echo "   docker compose logs -f app"
echo ""

