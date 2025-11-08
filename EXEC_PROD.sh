#!/bin/bash

echo "========================================"
echo "🚀 DÉPLOIEMENT FINAL - Toutes fonctionnalités"
echo "========================================"
echo ""
echo "✅ Fonctionnalités ajoutées :"
echo "   1. 🏛️  Hémicycles : Comparaison temporelle (2012-2024)"
echo "   2. 👥 Hémicycles : Liens vers fiches députés par groupe"
echo "   3. 🗺️  Carte France : Filtres par région (13 régions)"
echo "   4. 💰 Budget : Correction des vrais montants 2024"
echo "   5. 📊 Budget : 1501,6 Mds€ recettes / 1670,2 Mds€ dépenses"
echo ""
echo "========================================"

# Reseed budget avec les nouvelles données
echo ""
echo "📊 1/4 - Re-seed données budget..."
docker compose exec app php artisan db:seed --class=FranceStatisticsSeeder --force

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors du seeding"
    exit 1
fi

# Build frontend
echo ""
echo "📦 2/4 - Build frontend..."
docker compose exec -u root app npm run build

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors du build"
    exit 1
fi

# Clear caches
echo ""
echo "🧹 3/4 - Clear caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear

# Restart
echo ""
echo "🔄 4/4 - Restart services..."
docker compose restart app nginx

echo ""
echo "========================================"
echo "✅ DÉPLOIEMENT TERMINÉ !"
echo "========================================"
echo ""
echo "📝 Pages à tester :"
echo ""
echo "   🏛️  Hémicycles avec évolution temporelle :"
echo "   https://demo.objectif2027.fr/representants"
echo "   → Sélectionner différentes législatures (2012-2024)"
echo "   → Cliquer sur un groupe → voir les députés"
echo ""
echo "   🗺️  Carte interactive avec filtres :"
echo "   https://demo.objectif2027.fr/statistiques/france"
echo "   → Onglet 'Régions'"
echo "   → Filtrer par région (Bretagne, PACA, etc.)"
echo ""
echo "   💰 Budget France (montants corrigés) :"
echo "   https://demo.objectif2027.fr/statistiques/france"
echo "   → Onglet 'Budget'"
echo "   → Recettes : 1 501,6 Mds€"
echo "   → Dépenses : 1 670,2 Mds€"
echo ""
