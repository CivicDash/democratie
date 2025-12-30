#!/bin/bash

# Script d'import des maires depuis CSV
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🏛️  IMPORT MAIRES"
echo "========================================="
echo ""

# Vérifier le fichier CSV
echo "📂 1/4 - Vérification du fichier CSV..."
echo ""

MAIRES_CSV="public/data/elus-maires-mai.csv"

if [ ! -f "$MAIRES_CSV" ]; then
    echo "❌ Fichier maires introuvable: $MAIRES_CSV"
    exit 1
fi

MAIRES_LINES=$(wc -l < "$MAIRES_CSV")

echo "✅ Fichier maires trouvé: $MAIRES_LINES lignes"
echo ""

# Vérifier la migration
echo "📊 2/4 - Vérification de la table maires..."
echo ""
docker compose exec postgres psql -U civicdash -d civicdash -c "\d maires" 2>&1 | head -10

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Table 'maires' n'existe pas !"
    echo "🔧 Exécution de la migration..."
    echo ""
    docker compose exec app php artisan migrate --force
fi

# État actuel
echo ""
echo "📊 3/4 - État actuel de la base de données..."
echo ""
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT COUNT(*) as total, COUNT(CASE WHEN en_exercice = true THEN 1 END) as en_exercice
FROM maires;
" 2>/dev/null || echo "Table vide ou inexistante"

echo ""
echo "🚀 4/4 - Import des données..."
echo ""
echo "⚠️  ATTENTION : 34,867 maires à importer (cela peut prendre 5-10 minutes)"
echo ""
echo "Options d'import :"
echo "  1) Import COMPLET (~35k maires, ~10 min)"
echo "  2) Import TEST (100 maires, rapide)"
echo "  3) Annuler"
echo ""
read -p "Votre choix (1/2/3): " choice

case $choice in
    1)
        echo ""
        echo "📥 Lancement de l'import COMPLET..."
        docker compose exec app php artisan import:maires --fresh
        ;;
    2)
        echo ""
        echo "📥 Lancement de l'import TEST (100 maires)..."
        docker compose exec app php artisan import:maires --fresh --limit=100
        ;;
    3)
        echo "❌ Import annulé."
        exit 0
        ;;
    *)
        echo "❌ Choix invalide. Import annulé."
        exit 1
        ;;
esac

# Vérification finale
echo ""
echo "========================================="
echo "📊 RÉSULTAT DE L'IMPORT"
echo "========================================="
echo ""

docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    COUNT(*) as total_maires,
    COUNT(CASE WHEN en_exercice = true THEN 1 END) as en_exercice,
    COUNT(DISTINCT code_departement) as nb_departements,
    COUNT(DISTINCT code_commune) as nb_communes
FROM maires;
"

echo ""
echo "📋 Top 10 communes (par ordre alphabétique):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT nom_complet, nom_commune, code_departement, debut_fonction
FROM maires 
WHERE en_exercice = true
ORDER BY nom_commune
LIMIT 10;
"

echo ""
echo "========================================="
echo "✅ Import terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "   1. Importer les codes postaux : bash scripts/fix_postal_codes.sh"
echo "   2. Importer les députés/sénateurs : bash scripts/import_representants.sh"
echo "   3. Tester l'API de recherche :"
echo "      curl 'http://demo.objectif2027.fr/api/representants/search?q=75001'"
echo "========================================="

