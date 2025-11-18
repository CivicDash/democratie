#!/bin/bash

echo "========================================="
echo "🧪 TEST - Import AN Législature 17"
echo "========================================="
echo ""
echo "Mode TEST avec --limit pour valider rapidement"
echo ""

echo "📦 1/7 - Import 10 acteurs..."
docker compose exec app php artisan import:acteurs-an --limit=10

echo ""
echo "📦 2/7 - Import organes (L17)..."
docker compose exec app php artisan import:organes-an --legislature=17 --limit=20

echo ""
echo "📦 3/7 - Import mandats (L17, 10 acteurs)..."
docker compose exec app php artisan import:mandats-an --legislature=17 --limit=10

echo ""
echo "📦 4/7 - Import 20 scrutins (L17)..."
docker compose exec app php artisan import:scrutins-an --legislature=17 --limit=20

echo ""
echo "📦 5/7 - Extraction votes (20 scrutins)..."
docker compose exec app php artisan extract:votes-individuels-an --legislature=17 --limit=20

echo ""
echo "📦 6/7 - Import dossiers & textes (L17)..."
docker compose exec app php artisan import:dossiers-textes-an --legislature=17

echo ""
echo "📦 7/7 - Import 100 amendements (L17)..."
docker compose exec app php artisan import:amendements-an --legislature=17 --limit=100

echo ""
echo "========================================="
echo "📊 Résultats du test"
echo "========================================="
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Acteurs' as table_name, 
    COUNT(*) as total 
FROM acteurs_an
UNION ALL
SELECT 'Organes', COUNT(*) FROM organes_an
UNION ALL
SELECT 'Mandats', COUNT(*) FROM mandats_an
UNION ALL
SELECT 'Scrutins', COUNT(*) FROM scrutins_an
UNION ALL
SELECT 'Votes individuels', COUNT(*) FROM votes_individuels_an
UNION ALL
SELECT 'Dossiers', COUNT(*) FROM dossiers_legislatifs_an
UNION ALL
SELECT 'Textes', COUNT(*) FROM textes_legislatifs_an
UNION ALL
SELECT 'Amendements', COUNT(*) FROM amendements_an
ORDER BY table_name;
"

echo ""
echo "========================================="
echo "✅ Test terminé !"
echo "========================================="
echo ""
echo "💡 Si tout fonctionne, lancer l'import complet :"
echo "  bash scripts/import_donnees_an_l17.sh"
echo ""

