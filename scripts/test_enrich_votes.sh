#!/bin/bash

# Script de TEST rapide pour l'enrichissement complet
# Teste avec 3 députés et 2 sénateurs seulement

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🧪 TEST Enrichissement Complet"
echo "    3 députés + 2 sénateurs"
echo "========================================="
echo ""

echo "📊 État initial..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    (SELECT COUNT(*) FROM votes_deputes) as total_votes,
    (SELECT COUNT(*) FROM interventions_parlementaires) as total_interventions,
    (SELECT COUNT(*) FROM questions_gouvernement) as total_questions;
"

echo ""
echo "========================================="
echo "🧪 Test 1/2 - Enrichissement 3 DÉPUTÉS"
echo "========================================="
echo ""

docker compose exec app php artisan enrich:deputes-votes --limit=3

echo ""
echo "========================================="
echo "🧪 Test 2/2 - Enrichissement 2 SÉNATEURS"
echo "========================================="
echo ""

docker compose exec app php artisan enrich:senateurs-votes --limit=2

echo ""
echo "========================================="
echo "📊 Résultat du test"
echo "========================================="
echo ""

docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    (SELECT COUNT(*) FROM votes_deputes) as total_votes,
    (SELECT COUNT(*) FROM interventions_parlementaires) as total_interventions,
    (SELECT COUNT(*) FROM questions_gouvernement) as total_questions;
"

echo ""
echo "📋 Exemple de votes importés :"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    ds.nom_complet,
    vd.titre,
    vd.position,
    vd.date_vote
FROM votes_deputes vd
JOIN deputes_senateurs ds ON ds.id = vd.depute_senateur_id
ORDER BY vd.date_vote DESC
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Test terminé !"
echo "========================================="
echo ""
echo "💡 Pour import complet :"
echo "   bash scripts/enrich_complete.sh"
echo "========================================="

