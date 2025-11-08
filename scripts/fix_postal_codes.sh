#!/bin/bash

# Script de diagnostic et correction complète - Codes Postaux
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🔍 DIAGNOSTIC & FIX - Codes Postaux"
echo "========================================="
echo ""

echo "📊 1/4 - Vérification de la table..."
echo ""
docker compose exec postgres psql -U civicdash -d civicdash -c "\d french_postal_codes" 2>&1 | head -20

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Table 'french_postal_codes' n'existe pas !"
    echo "🔧 Exécution de la migration..."
    echo ""
    docker compose exec app php artisan migrate --force
    echo ""
    echo "✅ Migration terminée. Nouvelle tentative..."
    echo ""
    docker compose exec postgres psql -U civicdash -d civicdash -c "\d french_postal_codes" 2>&1 | head -20
fi

echo ""
echo "📊 2/4 - Comptage actuel..."
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT COUNT(*) as total FROM french_postal_codes;"

echo ""
echo "📂 3/4 - Vérification du fichier CSV..."
if [ -f "public/data/019HexaSmal.csv" ]; then
    echo "✅ Fichier trouvé: public/data/019HexaSmal.csv"
    LINE_COUNT=$(wc -l < public/data/019HexaSmal.csv)
    echo "📊 $LINE_COUNT lignes dans le CSV"
    echo ""
    echo "📋 Premières lignes du CSV:"
    head -3 public/data/019HexaSmal.csv
else
    echo "❌ Fichier CSV introuvable: public/data/019HexaSmal.csv"
    exit 1
fi

echo ""
echo "🚀 4/4 - Test d'import..."
echo "Voulez-vous lancer l'import ? (y/n)"
read -r response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo ""
    echo "🔄 Lancement de l'import avec logs détaillés..."
    docker compose exec app php artisan postal-codes:import-local --fresh -vvv
    
    echo ""
    echo "📊 Vérification post-import..."
    docker compose exec postgres psql -U civicdash -d civicdash -c "
    SELECT COUNT(*) as total,
           COUNT(DISTINCT postal_code) as codes_uniques,
           COUNT(DISTINCT city_name) as villes_uniques
    FROM french_postal_codes;
    "
    
    echo ""
    echo "📋 Échantillon (5 premières lignes):"
    docker compose exec postgres psql -U civicdash -d civicdash -c "
    SELECT postal_code, city_name, department_name 
    FROM french_postal_codes 
    ORDER BY postal_code 
    LIMIT 5;
    "
fi

echo ""
echo "========================================="
echo "✅ Diagnostic terminé"
echo "========================================="

