#!/bin/bash

# Test de la recherche de codes postaux

cd /home/kevin/www/demoscratos

echo "========================================="
echo "🔍 Test recherche codes postaux"
echo "========================================="
echo ""

echo "1️⃣ Test recherche par code postal (75001):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
WHERE postal_code LIKE '75001%' 
LIMIT 5;
"

echo ""
echo "2️⃣ Test recherche par ville (Paris):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
WHERE city_name ILIKE '%Paris%' 
LIMIT 5;
"

echo ""
echo "3️⃣ Test recherche par ville (Lyon):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
WHERE city_name ILIKE '%Lyon%' 
LIMIT 5;
"

echo ""
echo "4️⃣ Échantillon des 10 premières villes en base:"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT postal_code, city_name, department_name 
FROM french_postal_codes 
ORDER BY postal_code 
LIMIT 10;
"

echo ""
echo "========================================="
echo "✅ Tests terminés"
echo ""
echo "💡 Si les recherches par ville ne fonctionnent pas:"
echo "   1. Vérifier que city_name n'est pas NULL"
echo "   2. Relancer l'import avec: bash import_postal_codes_local.sh"
echo "========================================="

