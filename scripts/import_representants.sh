#!/bin/bash

# Script d'import des députés et sénateurs depuis CSV
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🏛️  IMPORT DÉPUTÉS & SÉNATEURS"
echo "========================================="
echo ""

# Vérifier les fichiers CSV
echo "📂 1/4 - Vérification des fichiers CSV..."
echo ""

DEPUTES_CSV="public/data/elus-deputes-dep.csv"
SENATEURS_CSV="public/data/elus-senateurs-sen.csv"

if [ ! -f "$DEPUTES_CSV" ]; then
    echo "❌ Fichier députés introuvable: $DEPUTES_CSV"
    exit 1
fi

if [ ! -f "$SENATEURS_CSV" ]; then
    echo "❌ Fichier sénateurs introuvable: $SENATEURS_CSV"
    exit 1
fi

DEPUTES_LINES=$(wc -l < "$DEPUTES_CSV")
SENATEURS_LINES=$(wc -l < "$SENATEURS_CSV")

echo "✅ Fichier députés trouvé: $DEPUTES_LINES lignes"
echo "✅ Fichier sénateurs trouvé: $SENATEURS_LINES lignes"
echo ""

# État actuel
echo "📊 2/4 - État actuel de la base de données..."
echo ""
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    source,
    COUNT(*) as total,
    COUNT(CASE WHEN en_exercice = true THEN 1 END) as en_exercice
FROM deputes_senateurs 
GROUP BY source
ORDER BY source;
"

echo ""
echo "🚀 3/4 - Import des données..."
echo ""
echo "⚠️  Attention : Les données de démo existantes seront SUPPRIMÉES."
echo "Voulez-vous continuer ? (y/n)"
read -r response

if [[ ! "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "❌ Import annulé."
    exit 0
fi

echo ""
echo "📥 Import des députés..."
docker compose exec app php artisan import:deputes --fresh

echo ""
echo "📥 Import des sénateurs..."
docker compose exec app php artisan import:senateurs --fresh

# Vérification finale
echo ""
echo "📊 4/4 - Vérification post-import..."
echo ""
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    source,
    COUNT(*) as total,
    COUNT(CASE WHEN en_exercice = true THEN 1 END) as en_exercice,
    COUNT(DISTINCT circonscription) as circonscriptions
FROM deputes_senateurs 
GROUP BY source
ORDER BY source;
"

echo ""
echo "📋 Échantillon (5 députés):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT nom_complet, circonscription, profession, debut_mandat
FROM deputes_senateurs 
WHERE source = 'assemblee'
ORDER BY nom
LIMIT 5;
"

echo ""
echo "📋 Échantillon (5 sénateurs):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT nom_complet, circonscription, profession, debut_mandat
FROM deputes_senateurs 
WHERE source = 'senat'
ORDER BY nom
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Import terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "   1. Tester sur: https://demo.objectif2027.fr/representants/deputes"
echo "   2. Tester sur: https://demo.objectif2027.fr/representants/senateurs"
echo "   3. Compléter les groupes politiques via API si nécessaire"
echo "========================================="

