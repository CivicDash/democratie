#!/bin/bash

# Script d'import des codes postaux depuis le fichier local CSV

cd /home/kevin/www/demoscratos

echo "========================================="
echo "🇫🇷 Import des codes postaux (fichier local)"
echo "========================================="
echo ""

# Vérifier que le fichier existe
if [ ! -f "public/data/019HexaSmal.csv" ]; then
    echo "❌ Erreur: fichier public/data/019HexaSmal.csv introuvable"
    exit 1
fi

echo "📂 Fichier trouvé: public/data/019HexaSmal.csv"
echo ""

# Import via Docker
echo "🚀 Lancement de l'import..."
docker compose exec app php artisan postal-codes:import-local --fresh

# Vérification
echo ""
echo "📊 Vérification du résultat..."
docker compose exec postgres psql -U civicdash -d civicdash -c "SELECT COUNT(*) as total_codes_postaux FROM french_postal_codes;"

echo ""
echo "✅ Import terminé!"
echo "========================================="

