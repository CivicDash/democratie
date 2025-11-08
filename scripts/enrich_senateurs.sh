#!/bin/bash

# Script d'enrichissement des sénateurs via API NosSénateurs.fr
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🏛️  ENRICHISSEMENT SÉNATEURS"
echo "========================================="
echo ""

echo "📊 État actuel des sénateurs..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN groupe_politique IS NOT NULL THEN 1 END) as avec_groupe,
    COUNT(CASE WHEN photo_url IS NOT NULL THEN 1 END) as avec_photo,
    COUNT(CASE WHEN nb_propositions > 0 THEN 1 END) as avec_stats
FROM deputes_senateurs 
WHERE source = 'senat';
"

echo ""
echo "🚀 Options d'enrichissement :"
echo "  1) Enrichissement COMPLET (tous les sénateurs sans données)"
echo "  2) Enrichissement TEST (10 sénateurs)"
echo "  3) Enrichissement FORCE (tous les sénateurs, même déjà enrichis)"
echo "  4) Annuler"
echo ""
read -p "Votre choix (1/2/3/4): " choice

case $choice in
    1)
        echo ""
        echo "📥 Lancement de l'enrichissement COMPLET..."
        echo "⏱️  Cela peut prendre 1-2 minutes (pause entre chaque appel API)"
        echo ""
        docker compose exec app php artisan enrich:senateurs
        ;;
    2)
        echo ""
        echo "📥 Lancement de l'enrichissement TEST (10 sénateurs)..."
        docker compose exec app php artisan enrich:senateurs --limit=10
        ;;
    3)
        echo ""
        echo "📥 Lancement de l'enrichissement FORCE (tous les sénateurs)..."
        echo "⏱️  Cela peut prendre 3-5 minutes"
        echo ""
        docker compose exec app php artisan enrich:senateurs --force
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
    COUNT(*) as total_senateurs,
    COUNT(CASE WHEN groupe_politique IS NOT NULL THEN 1 END) as avec_groupe,
    COUNT(CASE WHEN photo_url IS NOT NULL THEN 1 END) as avec_photo,
    COUNT(CASE WHEN nb_propositions > 0 THEN 1 END) as avec_propositions,
    ROUND(AVG(nb_propositions), 1) as avg_propositions,
    ROUND(AVG(nb_amendements), 1) as avg_amendements
FROM deputes_senateurs 
WHERE source = 'senat';
"

echo ""
echo "📋 Top 5 sénateurs les plus actifs (propositions):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT nom_complet, groupe_politique, nb_propositions, nb_amendements
FROM deputes_senateurs 
WHERE source = 'senat' AND nb_propositions > 0
ORDER BY nb_propositions DESC
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Enrichissement terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "   1. Vérifier sur: https://demo.objectif2027.fr/representants/senateurs"
echo "   2. Les photos devraient être visibles"
echo "   3. Les groupes politiques devraient être renseignés"
echo "========================================="

