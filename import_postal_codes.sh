#!/bin/bash
# Import des codes postaux français

echo "📮 Import des codes postaux français..."
echo ""

cd /opt/civicdash

# Lancer l'import en arrière-plan
echo "🚀 Lancement de l'import (en arrière-plan)..."
docker compose exec -d app php artisan app:import-french-postal-codes --fresh

echo ""
echo "✅ Import lancé en arrière-plan !"
echo ""
echo "📊 Pour suivre la progression :"
echo "   docker compose logs -f app | grep -i postal"
echo ""
echo "🔍 Pour vérifier le nombre de codes postaux :"
echo "   docker compose exec app php artisan tinker --execute=\"use App\\Models\\FrenchPostalCode; echo FrenchPostalCode::count();\""
