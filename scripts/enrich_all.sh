#!/bin/bash

# 🎯 SCRIPT COMPLET D'ENRICHISSEMENT - CIVICDASH
# Exécute tous les imports dans le bon ordre
# À lancer depuis la racine du projet

set -e  # Arrêter en cas d'erreur

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🚀 ENRICHISSEMENT COMPLET CIVICDASH"
echo "========================================="
echo ""
echo "📊 Ce script va importer :"
echo "   1. Organes parlementaires (~4 min)"
echo "   2. Votes/Interventions/Questions (~32 min)"
echo "   3. Amendements (~32 min)"
echo ""
echo "⏱️  Durée totale estimée : ~1h10"
echo ""

read -p "❓ Continuer ? (y/n): " confirm
if [[ ! "$confirm" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "❌ Annulé"
    exit 0
fi

echo ""
echo "========================================="
echo "📊 ÉTAT INITIAL"
echo "========================================="

docker-compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Organes' as type, COUNT(*) as total FROM organes_parlementaires
UNION ALL
SELECT 'Membres organes', COUNT(*) FROM membres_organes
UNION ALL
SELECT 'Votes', COUNT(*) FROM votes_deputes
UNION ALL
SELECT 'Interventions', COUNT(*) FROM interventions_parlementaires
UNION ALL
SELECT 'Questions', COUNT(*) FROM questions_gouvernement
UNION ALL
SELECT 'Amendements', COUNT(*) FROM amendements_parlementaires;
"

echo ""
echo "========================================="
echo "🏛️  ÉTAPE 1/3 : Organes parlementaires"
echo "========================================="
echo ""

docker-compose exec app php artisan import:organes-parlementaires --source=both

echo ""
echo "✅ Étape 1/3 terminée !"
echo ""

echo "========================================="
echo "📝 ÉTAPE 2/3 : Votes/Interventions/Questions"
echo "========================================="
echo ""

# Députés
echo "📥 Import députés..."
docker-compose exec app php artisan enrich:deputes-votes

echo ""
echo "📥 Import sénateurs..."
docker-compose exec app php artisan enrich:senateurs-votes

echo ""
echo "✅ Étape 2/3 terminée !"
echo ""

echo "========================================="
echo "📋 ÉTAPE 3/3 : Amendements"
echo "========================================="
echo ""

docker-compose exec app php artisan enrich:amendements --source=both

echo ""
echo "✅ Étape 3/3 terminée !"
echo ""

echo "========================================="
echo "📊 ÉTAT FINAL"
echo "========================================="

docker-compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Organes' as type, 
    COUNT(*) as total,
    '~60 attendus' as estimation
FROM organes_parlementaires
UNION ALL
SELECT 
    'Membres organes', 
    COUNT(*),
    '~1000 attendus'
FROM membres_organes
UNION ALL
SELECT 
    'Votes', 
    COUNT(*),
    '~200k attendus'
FROM votes_deputes
UNION ALL
SELECT 
    'Interventions', 
    COUNT(*),
    '~60k attendus'
FROM interventions_parlementaires
UNION ALL
SELECT 
    'Questions', 
    COUNT(*),
    '~25k attendus'
FROM questions_gouvernement
UNION ALL
SELECT 
    'Amendements', 
    COUNT(*),
    '~150k attendus'
FROM amendements_parlementaires;
"

echo ""
echo "========================================="
echo "✅ ENRICHISSEMENT COMPLET TERMINÉ ! 🎉"
echo "========================================="
echo ""
echo "📈 Statistiques avancées disponibles :"
echo "   - Profils complets de tous les députés/sénateurs"
echo "   - Analyse par organe parlementaire"
echo "   - Réseaux de co-signatures"
echo "   - Taux d'adoption par commission"
echo ""
echo "📚 Documentation :"
echo "   - ROADMAP_ENRICHISSEMENT.md"
echo "   - SESSION_8_NOV_FINAL.md"
echo "   - CHANGELOG.md"
echo ""
echo "========================================="

