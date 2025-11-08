#!/bin/bash

# Script de diagnostic des thématiques de législation

cd /home/kevin/www/demoscratos

echo "========================================="
echo "🔍 Diagnostic Thématiques Législation"
echo "========================================="
echo ""

echo "📊 1/5 - Comptage des thématiques..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total_thematiques 
FROM thematiques_legislation;
"

echo ""
echo "📋 2/5 - Liste des thématiques (avec slug)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT id, nom, code as slug, nb_propositions 
FROM thematiques_legislation 
ORDER BY id;
"

echo ""
echo "📊 3/5 - Comptage des propositions de loi..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total_propositions 
FROM propositions_loi;
"

echo ""
echo "🔗 4/5 - Comptage des associations (table pivot)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total_associations 
FROM proposition_loi_thematique;
"

echo ""
echo "📈 5/5 - Détail des associations par thématique..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    t.nom as thematique,
    COUNT(plt.id) as nb_propositions_associees,
    SUM(CASE WHEN plt.est_principal THEN 1 ELSE 0 END) as nb_principal
FROM thematiques_legislation t
LEFT JOIN proposition_loi_thematique plt ON t.id = plt.thematique_legislation_id
GROUP BY t.id, t.nom
ORDER BY nb_propositions_associees DESC;
"

echo ""
echo "========================================="
echo "✅ Diagnostic terminé"
echo ""
echo "💡 Si aucune association n'est trouvée:"
echo "   1. Vérifier que les seeders ont été exécutés"
echo "   2. Re-seed avec: docker compose exec app php artisan db:seed --class=DemoDataSeeder"
echo "========================================="

