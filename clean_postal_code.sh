#!/bin/bash

# Script de nettoyage complet de french_postal_codes

echo "========================================="
echo "🧹 NETTOYAGE COMPLET - french_postal_codes"
echo "========================================="
echo ""

echo "🗑️  Suppression de TOUS les index et contraintes..."
docker compose exec postgres psql -U civicdash -d civicdash << 'EOF'
-- Supprimer tous les index liés à french_postal_codes
DROP INDEX IF EXISTS idx_postal_city CASCADE;
DROP INDEX IF EXISTS idx_dept_city CASCADE;
DROP INDEX IF EXISTS idx_circonscription CASCADE;
DROP INDEX IF EXISTS unique_postal_city CASCADE;
DROP INDEX IF EXISTS unique_postal_city_insee CASCADE;
DROP INDEX IF EXISTS french_postal_codes_postal_code_index CASCADE;
DROP INDEX IF EXISTS french_postal_codes_city_name_index CASCADE;
DROP INDEX IF EXISTS french_postal_codes_department_code_index CASCADE;
DROP INDEX IF EXISTS french_postal_codes_region_code_index CASCADE;
DROP INDEX IF EXISTS french_postal_codes_circonscription_index CASCADE;
DROP INDEX IF EXISTS french_postal_codes_insee_code_index CASCADE;

-- Supprimer la table
DROP TABLE IF EXISTS french_postal_codes CASCADE;
EOF

echo ""
echo "🗑️  Nettoyage de la table migrations..."
docker compose exec postgres psql -U civicdash -d civicdash -c "DELETE FROM migrations WHERE migration LIKE '%french_postal_codes%' OR migration LIKE '%postal_codes%';"

echo ""
echo "📦 Relance de la migration..."
docker compose exec app php artisan migrate --force --path=database/migrations/2025_11_06_134921_create_french_postal_codes_table.php

echo ""
echo "✅ Vérification de la structure..."
docker compose exec postgres psql -U civicdash -d civicdash -c "\d french_postal_codes" | head -40

echo ""
echo "📊 Comptage des lignes..."
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT COUNT(*) FROM french_postal_codes;"

echo ""
echo "========================================="
echo "✅ Nettoyage terminé !"
echo "========================================="
echo ""
echo "💡 Prochaine étape : Importer les codes postaux"
echo "   bash scripts/fix_postal_codes.sh"
echo "   OU"
echo "   docker compose exec app php artisan postal-codes:import-local --fresh"
echo "========================================="

