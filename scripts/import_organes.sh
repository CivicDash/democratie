#!/bin/bash

# Script d'import des organes parlementaires
# À exécuter depuis la racine du projet

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🏛️  IMPORT DES ORGANES PARLEMENTAIRES"
echo "========================================="
echo ""

echo "💡 Ce qui sera importé :"
echo "   - Groupes politiques"
echo "   - Commissions permanentes"
echo "   - Délégations"
echo "   - Missions d'information"
echo "   - Membres de chaque organe"
echo ""

echo "📊 Options disponibles :"
echo "   1) Assemblée Nationale uniquement"
echo "   2) Sénat uniquement"
echo "   3) TOUT (Assemblée + Sénat)"
echo ""

read -p "Choix (1-3): " choice

case $choice in
    1)
        echo ""
        echo "📥 Import ASSEMBLÉE (~2 min)"
        docker compose exec app php artisan import:organes-parlementaires --source=assemblee
        ;;
    2)
        echo ""
        echo "📥 Import SÉNAT (~2 min)"
        docker compose exec app php artisan import:organes-parlementaires --source=senat
        ;;
    3)
        echo ""
        echo "📥 Import COMPLET (~4 min)"
        docker compose exec app php artisan import:organes-parlementaires --source=both
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

echo "📊 Statistiques des organes :"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    type,
    source,
    COUNT(*) as nb_organes,
    SUM(nombre_membres) as total_membres
FROM organes_parlementaires
GROUP BY type, source
ORDER BY type, source;
"

echo ""
echo "📋 Top 5 commissions les plus importantes (par nombre de membres) :"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    nom,
    source,
    nombre_membres
FROM organes_parlementaires
WHERE type = 'commission'
ORDER BY nombre_membres DESC
LIMIT 5;
"

echo ""
echo "👥 Députés/Sénateurs avec le plus de mandats :"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    ds.nom_complet,
    ds.source,
    COUNT(mo.id) as nb_organes,
    STRING_AGG(DISTINCT op.nom, ' | ') as organes
FROM deputes_senateurs ds
JOIN membres_organes mo ON mo.depute_senateur_id = ds.id
JOIN organes_parlementaires op ON op.id = mo.organe_id
WHERE mo.actif = true
GROUP BY ds.id, ds.nom_complet, ds.source
ORDER BY nb_organes DESC
LIMIT 5;
"

echo ""
echo "========================================="

