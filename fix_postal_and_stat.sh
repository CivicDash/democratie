#!/bin/bash

echo "=========================================="
echo "🔍 DIAGNOSTIC & FIX - CivicDash Production"
echo "=========================================="
echo ""

# Couleurs pour le terminal
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ========================================
# 1. CODES POSTAUX
# ========================================
echo -e "${BLUE}📮 1/3 - CODES POSTAUX${NC}"
echo "=========================================="

echo -e "${YELLOW}📊 État actuel:${NC}"
POSTAL_COUNT=$(docker compose exec -T postgres psql -U civicdash -d civicdash -t -c "SELECT COUNT(*) FROM french_postal_codes;" 2>/dev/null | tr -d ' ')
echo "   Codes postaux en base: ${POSTAL_COUNT}"

if [ "$POSTAL_COUNT" -lt 1000 ]; then
    echo -e "${YELLOW}⚠️  Base vide ou incomplète, lancement de l'import...${NC}"
    echo ""
    
    # Vérifier quelle commande existe
    echo "   🔍 Vérification de la commande disponible..."
    COMMAND_EXISTS=$(docker compose exec -T app php artisan list | grep -c "postal-codes:import" || echo "0")
    
    if [ "$COMMAND_EXISTS" -gt 0 ]; then
        echo -e "${GREEN}   ✓ Commande 'postal-codes:import' trouvée${NC}"
        echo "   🚀 Lancement de l'import (5-10 minutes)..."
        docker compose exec -d app php artisan postal-codes:import --fresh
        echo -e "${GREEN}   ✓ Import lancé en arrière-plan${NC}"
        echo "   📝 Pour suivre la progression:"
        echo "      docker compose logs -f app | grep postal"
    else
        echo -e "${RED}   ✗ Commande 'postal-codes:import' introuvable${NC}"
        echo "   📝 Commandes disponibles:"
        docker compose exec -T app php artisan list | grep -i postal || echo "      (aucune commande postal trouvée)"
    fi
else
    echo -e "${GREEN}✅ Codes postaux OK (${POSTAL_COUNT} entrées)${NC}"
fi

echo ""
echo ""

# ========================================
# 2. STATISTIQUES FRANCE
# ========================================
echo -e "${BLUE}📊 2/3 - STATISTIQUES FRANCE${NC}"
echo "=========================================="

echo -e "${YELLOW}📊 État actuel des tables:${NC}"

# Vérifier chaque table
tables=(
    "france_demographics"
    "france_economy"
    "france_migration"
    "france_budget_revenue"
    "france_budget_spending"
    "france_lost_revenue"
    "france_quality_of_life"
    "france_education"
    "france_health"
    "france_housing"
    "france_environment"
    "france_security"
    "france_employment_detailed"
)

NEED_SEED=0

for table in "${tables[@]}"; do
    COUNT=$(docker compose exec -T postgres psql -U civicdash -d civicdash -t -c "SELECT COUNT(*) FROM ${table};" 2>/dev/null | tr -d ' ')
    if [ -z "$COUNT" ] || [ "$COUNT" -eq 0 ]; then
        echo -e "   ${RED}✗ ${table}: ${COUNT:-0} lignes${NC}"
        NEED_SEED=1
    else
        echo -e "   ${GREEN}✓ ${table}: ${COUNT} lignes${NC}"
    fi
done

echo ""

if [ "$NEED_SEED" -eq 1 ]; then
    echo -e "${YELLOW}⚠️  Tables vides ou incomplètes détectées${NC}"
    echo ""
    echo "   🌱 Lancement du seeding principal..."
    docker compose exec app php artisan db:seed --class=FranceStatisticsSeeder --force
    
    echo ""
    echo "   🌱 Lancement du seeding des indicateurs sociaux..."
    docker compose exec app php artisan db:seed --class=FranceSocialIndicatorsSeeder --force
    
    echo ""
    echo -e "${YELLOW}📊 Vérification post-seeding:${NC}"
    
    # Re-vérifier quelques tables clés
    for table in "france_demographics" "france_education" "france_security" "france_quality_of_life"; do
        COUNT=$(docker compose exec -T postgres psql -U civicdash -d civicdash -t -c "SELECT COUNT(*) FROM ${table};" 2>/dev/null | tr -d ' ')
        echo "   ${table}: ${COUNT} lignes"
    done
    
    # Afficher quelques données de test
    echo ""
    echo -e "${YELLOW}📊 Échantillon de données:${NC}"
    echo ""
    echo "   🎓 Éducation - Taux d'illettrisme:"
    docker compose exec -T postgres psql -U civicdash -d civicdash -c "SELECT year, illiteracy_rate FROM france_education ORDER BY year;" 2>/dev/null
    
    echo ""
    echo "   💜 Sécurité - Féminicides:"
    docker compose exec -T postgres psql -U civicdash -d civicdash -c "SELECT year, feminicides FROM france_security ORDER BY year;" 2>/dev/null
    
    echo ""
    echo "   ✨ Qualité de vie - IDH:"
    docker compose exec -T postgres psql -U civicdash -d civicdash -c "SELECT year, hdi_score, happiness_score FROM france_quality_of_life ORDER BY year;" 2>/dev/null
    
else
    echo -e "${GREEN}✅ Toutes les tables de statistiques sont remplies${NC}"
fi

echo ""
echo ""

# ========================================
# 3. CLEAR CACHES & RESTART
# ========================================
echo -e "${BLUE}🧹 3/3 - CLEAR CACHES & RESTART${NC}"
echo "=========================================="

echo "   🧹 Clear config cache..."
docker compose exec app php artisan config:clear

echo "   🧹 Clear route cache..."
docker compose exec app php artisan route:clear

echo "   🧹 Clear view cache..."
docker compose exec app php artisan view:clear

echo "   🧹 Clear application cache..."
docker compose exec app php artisan cache:clear

echo ""
echo "   🔄 Redémarrage des services..."
docker compose restart app nginx queue

echo ""
echo -e "${GREEN}✅ Caches vidés et services redémarrés${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}✅ DIAGNOSTIC & FIX TERMINÉ !${NC}"
echo "=========================================="
echo ""
echo "📝 Prochaines étapes:"
echo ""
echo "   1. Tester l'application:"
echo "      https://demo.objectif2027.fr/statistiques/france"
echo ""
echo "   2. Si codes postaux toujours vides (après 10 min):"
echo "      docker compose logs app | grep postal"
echo ""
echo "   3. Vérifier les données dans la base:"
echo "      docker compose exec postgres psql -U civicdash -d civicdash"
echo "      SELECT COUNT(*) FROM french_postal_codes;"
echo "      SELECT * FROM france_education;"
echo ""
