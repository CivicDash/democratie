#!/bin/bash

echo "========================================="
echo "🏛️  IMPORT COMPLET - AN Législature 17"
echo "========================================="
echo ""
echo "Ce script va importer :"
echo "  - Acteurs (députés)"
echo "  - Organes (groupes, commissions)"
echo "  - Mandats"
echo "  - Scrutins"
echo "  - Votes individuels"
echo "  - Dossiers & textes législatifs"
echo "  - Amendements (~68 000)"
echo ""
echo "⏱️  Durée estimée : 2-3 heures"
echo ""
read -p "Continuer ? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Import annulé"
    exit 1
fi

echo "========================================="
echo "📦 1/8 - Import des acteurs..."
echo "========================================="
docker compose exec app php artisan import:acteurs-an

echo ""
echo "========================================="
echo "📦 2/8 - Import des organes (L17)..."
echo "========================================="
docker compose exec app php artisan import:organes-an --legislature=17

echo ""
echo "========================================="
echo "📦 3/8 - Import des mandats (L17)..."
echo "========================================="
docker compose exec app php artisan import:mandats-an --legislature=17

echo ""
echo "========================================="
echo "📦 4/8 - Import des scrutins (L17)..."
echo "========================================="
docker compose exec app php artisan import:scrutins-an --legislature=17

echo ""
echo "========================================="
echo "📦 5/8 - Extraction des votes individuels (L17)..."
echo "========================================="
docker compose exec app php artisan extract:votes-individuels-an --legislature=17

echo ""
echo "========================================="
echo "📦 6/8 - Import dossiers & textes (L17)..."
echo "========================================="
docker compose exec app php artisan import:dossiers-textes-an --legislature=17

echo ""
echo "========================================="
echo "📦 7/8 - Import amendements (L17 - LONG !)..."
echo "========================================="
docker compose exec app php artisan import:amendements-an --legislature=17

echo ""
echo "========================================="
echo "📊 8/8 - Statistiques finales"
echo "========================================="
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Acteurs' as table_name, 
    COUNT(*) as total 
FROM acteurs_an
UNION ALL
SELECT 'Organes (L17)', COUNT(*) FROM organes_an WHERE legislature = 17
UNION ALL
SELECT 'Mandats (L17)', COUNT(*) FROM mandats_an WHERE legislature = 17
UNION ALL
SELECT 'Scrutins (L17)', COUNT(*) FROM scrutins_an WHERE legislature = 17
UNION ALL
SELECT 'Votes individuels', COUNT(*) FROM votes_individuels_an
UNION ALL
SELECT 'Dossiers (L17)', COUNT(*) FROM dossiers_legislatifs_an WHERE legislature = 17
UNION ALL
SELECT 'Textes (L17)', COUNT(*) FROM textes_legislatifs_an WHERE legislature = 17
UNION ALL
SELECT 'Amendements (L17)', COUNT(*) FROM amendements_an WHERE legislature = 17
ORDER BY table_name;
"

echo ""
echo "========================================="
echo "✅ Import AN Législature 17 terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "  1. Tester les données : bash scripts/test_donnees_an.sh"
echo "  2. Lancer import Sénat : bash scripts/import_senateurs_complet.sh"
echo "  3. Créer les API endpoints"
echo ""

