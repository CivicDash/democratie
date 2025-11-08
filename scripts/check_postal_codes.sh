#!/bin/bash

# Script de test du système des codes postaux
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🔍 Diagnostic des codes postaux"
echo "========================================="
echo ""

echo "📊 1/3 - Comptage en base de données..."
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT COUNT(*) as total FROM french_postal_codes;"

echo ""
echo "📋 2/3 - Échantillon de données (10 premières lignes)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT postal_code, city_name, department_name FROM french_postal_codes ORDER BY postal_code LIMIT 10;"

echo ""
echo "🔎 3/3 - Test de recherche (75001)..."
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT postal_code, city_name, department_name, circonscription FROM french_postal_codes WHERE postal_code = '75001';"

echo ""
echo "========================================="
echo "✅ Diagnostic terminé"
echo ""
echo "💡 Pour importer le fichier local:"
echo "   bash scripts/import_postal_codes_local.sh"
echo "========================================="

