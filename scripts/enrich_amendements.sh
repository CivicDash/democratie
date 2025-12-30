#!/bin/bash

# Script d'enrichissement des amendements parlementaires
# À exécuter depuis la racine du projet

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "📝 ENRICHISSEMENT DES AMENDEMENTS"
echo "========================================="
echo ""

echo "💡 Source des données :"
echo "   - NosDéputés.fr : https://www.nosdeputes.fr"
echo "   - NosSénateurs.fr : https://www.nossenateurs.fr"
echo ""

echo "📊 Options disponibles :"
echo "   1) Test (10 parlementaires)"
echo "   2) Députés uniquement"
echo "   3) Sénateurs uniquement"
echo "   4) TOUS (députés + sénateurs)"
echo ""

read -p "Choix (1-4): " choice

case $choice in
    1)
        echo ""
        echo "🧪 Mode TEST : 10 parlementaires"
        docker compose exec app php artisan enrich:amendements --limit=10
        ;;
    2)
        echo ""
        echo "📥 Import DEPUTÉS (~20 min)"
        read -p "Continuer ? (y/n): " confirm
        if [[ "$confirm" =~ ^([yY][eE][sS]|[yY])$ ]]; then
            docker compose exec app php artisan enrich:amendements --source=assemblee
        else
            echo "❌ Annulé"
        fi
        ;;
    3)
        echo ""
        echo "📥 Import SÉNATEURS (~12 min)"
        read -p "Continuer ? (y/n): " confirm
        if [[ "$confirm" =~ ^([yY][eE][sS]|[yY])$ ]]; then
            docker compose exec app php artisan enrich:amendements --source=senat
        else
            echo "❌ Annulé"
        fi
        ;;
    4)
        echo ""
        echo "📥 Import COMPLET (~32 min)"
        echo "   - ~566 députés"
        echo "   - ~336 sénateurs"
        echo "   - Estimation : 100-150k amendements"
        echo ""
        read -p "Continuer ? (y/n): " confirm
        if [[ "$confirm" =~ ^([yY][eE][sS]|[yY])$ ]]; then
            docker compose exec app php artisan enrich:amendements --source=both
        else
            echo "❌ Annulé"
        fi
        ;;
    *)
        echo "❌ Choix invalide"
        exit 1
        ;;
esac

echo ""
echo "========================================="
echo "✅ Terminé !"
echo "========================================="
echo ""

echo "📊 Statistiques :"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    COUNT(*) as total_amendements,
    COUNT(*) FILTER (WHERE sort = 'adopte') as adoptes,
    COUNT(*) FILTER (WHERE sort = 'rejete') as rejetes,
    COUNT(*) FILTER (WHERE sort = 'retire') as retires,
    COUNT(*) FILTER (WHERE nombre_cosignataires > 0) as cosignes,
    ROUND(AVG(nombre_cosignataires), 2) as moyenne_cosignataires
FROM amendements_parlementaires;
"

echo ""
echo "📋 Top 5 parlementaires les plus actifs (amendements) :"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    ds.nom_complet,
    ds.groupe_politique,
    COUNT(ap.id) as nb_amendements,
    COUNT(*) FILTER (WHERE ap.sort = 'adopte') as adoptes
FROM deputes_senateurs ds
JOIN amendements_parlementaires ap ON ap.depute_senateur_id = ds.id
GROUP BY ds.id, ds.nom_complet, ds.groupe_politique
ORDER BY nb_amendements DESC
LIMIT 5;
"

echo ""
echo "========================================="

