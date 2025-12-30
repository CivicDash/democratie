#!/bin/bash

# Script de diagnostic pour PRODUCTION (sans Docker)
# À lancer depuis /opt/civicdash/scripts/

echo "🔍 Diagnostic Tables Sénat - Amendements & Votes (PROD)"
echo "========================================================"
echo ""

# Variables de connexion PostgreSQL (à adapter si nécessaire)
PGUSER="${PGUSER:-postgres}"
PGDATABASE="${PGDATABASE:-demoscratos_prod}"

# Vérifier structure senat_ameli_amdsen
echo "📋 Structure de senat_ameli_amdsen (amendements auteurs) :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns 
WHERE table_name = 'senat_ameli_amdsen' 
ORDER BY ordinal_position;
"
echo ""

# Vérifier structure senat_senateurs_sen
echo "📋 Colonnes dans senat_senateurs_sen :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns 
WHERE table_name = 'senat_senateurs_sen' 
AND (column_name LIKE '%senid%' OR column_name LIKE '%senmat%')
ORDER BY ordinal_position;
"
echo ""

# Vérifier si senid existe dans senat_senateurs_sen
echo "🔍 Vérifier si senid existe dans senat_senateurs_sen :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT column_name
FROM information_schema.columns 
WHERE table_name = 'senat_senateurs_sen' 
AND column_name = 'senid';
"
echo ""

# Exemple de données senat_senateurs_sen
echo "📊 Exemple de données senat_senateurs_sen (5 premiers) :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT senmat, sennomuse, senprenomuse
FROM senat_senateurs_sen 
LIMIT 5;
"
echo ""

# Exemple de données senat_ameli_amdsen
echo "📊 Exemple de données senat_ameli_amdsen (5 premiers) :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT amdid, senid, nomuse, prenomuse, rng
FROM senat_ameli_amdsen 
LIMIT 5;
"
echo ""

# Vérifier données amendements
echo "📊 Exemple amendements_senat (vue actuelle) :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT id, senateur_matricule, numero, auteur_nom, sort_libelle 
FROM amendements_senat 
LIMIT 5;
"
echo ""

# Compter amendements
echo "🔢 Total amendements dans la vue :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT COUNT(*) AS total_amendements FROM amendements_senat;
"
echo ""

# Vérifier votes
echo "📊 Exemple senateurs_votes (vue actuelle) :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT id, senateur_matricule, scrutin_id, position 
FROM senateurs_votes 
LIMIT 5;
"
echo ""

# Compter votes
echo "🔢 Total votes dans la vue :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT COUNT(*) AS total_votes FROM senateurs_votes;
"
echo ""

# Tenter la jointure senid → senmat
echo "🔗 Tester la jointure senid → senmat :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT 
    amdsen.senid,
    sen.senmat,
    sen.sennomuse AS nom
FROM senat_ameli_amdsen amdsen
LEFT JOIN senat_senateurs_sen sen ON amdsen.senid = sen.senid
LIMIT 5;
" 2>&1 | grep -v "ERROR" || echo "❌ Jointure impossible : colonne senid manquante"
echo ""

# Chercher toutes les tables avec senid
echo "🔍 Chercher toutes les tables contenant 'senid' :"
sudo -u postgres psql -d "$PGDATABASE" -c "
SELECT DISTINCT table_name
FROM information_schema.columns 
WHERE column_name LIKE '%senid%'
ORDER BY table_name;
"
echo ""

echo "✅ Diagnostic terminé !"
echo ""
echo "💡 Prochaines étapes :"
echo "  1. Analyser les résultats ci-dessus"
echo "  2. Identifier la bonne jointure senid → senmat"
echo "  3. Corriger la vue amendements_senat"
echo "  4. Tester avec: php artisan tinker"

