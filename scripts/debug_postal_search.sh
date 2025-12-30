#!/bin/bash

# Diagnostic complet pour la recherche de codes postaux
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🔍 DIAGNOSTIC COMPLET - Codes Postaux"
echo "========================================="
echo ""

echo "📊 1/5 - Vérification table..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total, 
       COUNT(DISTINCT postal_code) as codes_uniques,
       COUNT(DISTINCT city_name) as villes_uniques
FROM french_postal_codes;
"

echo ""
echo "📋 2/5 - Échantillon de données (5 lignes)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
ORDER BY postal_code 
LIMIT 5;
"

echo ""
echo "🔎 3/5 - Test recherche par code postal (75001)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
WHERE postal_code = '75001';
"

echo ""
echo "🏙️ 4/5 - Test recherche par ville EXACTE (PARIS)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as nb_paris
FROM french_postal_codes 
WHERE city_name = 'PARIS';
"

echo ""
echo "🔤 5/5 - Test recherche ILIKE (par)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
WHERE city_name ILIKE '%par%'
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Diagnostic terminé"
echo ""
echo "💡 Analyse :"
echo "   - Si 'total' = 0 → Lancer import_postal_codes_local.sh"
echo "   - Si recherche ILIKE échoue → Problème de données/encodage"
echo "   - Si tout OK mais API KO → Vérifier routes web.php"
echo "========================================="

