#!/bin/bash

# Script d'enrichissement des députés via API NosDéputés.fr
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🏛️  ENRICHISSEMENT DÉPUTÉS"
echo "========================================="
echo ""

echo "📊 État actuel des députés..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN groupe_politique IS NOT NULL THEN 1 END) as avec_groupe,
    COUNT(CASE WHEN photo_url IS NOT NULL THEN 1 END) as avec_photo,
    COUNT(CASE WHEN nb_propositions > 0 THEN 1 END) as avec_stats
FROM deputes_senateurs 
WHERE source = 'assemblee';
"

echo ""
echo "🚀 Options d'enrichissement :"
echo "  1) Enrichissement COMPLET (tous les députés sans données)"
echo "  2) Enrichissement TEST (10 députés)"
echo "  3) Enrichissement FORCE (tous les députés, même déjà enrichis)"
echo "  4) Annuler"
echo ""
read -p "Votre choix (1/2/3/4): " choice

case $choice in
    1)
        echo ""
        echo "📥 Lancement de l'enrichissement COMPLET..."
        echo "⏱️  Cela peut prendre 2-3 minutes (pause entre chaque appel API)"
        echo ""
        docker compose exec app php artisan enrich:deputes
        ;;
    2)
        echo ""
        echo "📥 Lancement de l'enrichissement TEST (10 députés)..."
        docker compose exec app php artisan enrich:deputes --limit=10
        ;;
    3)
        echo ""
        echo "📥 Lancement de l'enrichissement FORCE (tous les députés)..."
        echo "⏱️  Cela peut prendre 5-10 minutes"
        echo ""
        docker compose exec app php artisan enrich:deputes --force
        ;;
    4)
        echo "❌ Annulé."
        exit 0
        ;;
    *)
        echo "❌ Choix invalide. Annulé."
        exit 1
        ;;
esac

echo ""
echo "========================================="
echo "📊 RÉSULTAT DE L'ENRICHISSEMENT"
echo "========================================="
echo ""

docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    COUNT(*) as total_deputes,
    COUNT(CASE WHEN groupe_politique IS NOT NULL THEN 1 END) as avec_groupe,
    COUNT(CASE WHEN photo_url IS NOT NULL THEN 1 END) as avec_photo,
    COUNT(CASE WHEN nb_propositions > 0 THEN 1 END) as avec_propositions,
    ROUND(AVG(nb_propositions), 1) as avg_propositions,
    ROUND(AVG(nb_amendements), 1) as avg_amendements
FROM deputes_senateurs 
WHERE source = 'assemblee';
"

echo ""
echo "📋 Top 5 députés les plus actifs (propositions):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT nom_complet, groupe_politique, nb_propositions, nb_amendements
FROM deputes_senateurs 
WHERE source = 'assemblee' AND nb_propositions > 0
ORDER BY nb_propositions DESC
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Enrichissement terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "   1. Vérifier sur: https://demo.objectif2027.fr/representants/deputes"
echo "   2. Les photos devraient être visibles"
echo "   3. Les groupes politiques devraient être renseignés"
echo "========================================="

