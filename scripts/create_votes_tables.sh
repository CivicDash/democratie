#!/bin/bash

# Script rapide pour créer les tables votes/interventions/questions

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🗄️  Création des tables"
echo "    - votes_deputes"
echo "    - interventions_parlementaires"
echo "    - questions_gouvernement"
echo "========================================="
echo ""

echo "🔄 Lancement des migrations..."
docker compose exec app php artisan migrate --force

echo ""
echo "========================================="
echo "✅ Migrations terminées !"
echo "========================================="
echo ""

echo "📊 Vérification des tables..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    table_name,
    (SELECT COUNT(*) FROM information_schema.columns 
     WHERE table_name = t.table_name AND table_schema = 'public') as nb_colonnes
FROM information_schema.tables t
WHERE table_schema = 'public' 
AND table_name IN ('votes_deputes', 'interventions_parlementaires', 'questions_gouvernement')
ORDER BY table_name;
"

echo ""
echo "💡 Prochaine étape :"
echo "   bash scripts/enrich_complete.sh"
echo "========================================="

