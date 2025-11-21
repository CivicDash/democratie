#!/bin/bash

# Script de diagnostic pour comprendre la structure des tables Sénat
# et trouver la correspondance senid ↔ senmat

echo "🔍 Diagnostic Tables Sénat - Amendements & Votes"
echo "=================================================="
echo ""

# Vérifier structure senat_ameli_amdsen
echo "📋 Structure de senat_ameli_amdsen (amendements auteurs) :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns 
WHERE table_name = 'senat_ameli_amdsen' 
ORDER BY ordinal_position;
"
echo ""

# Vérifier structure senat_senateurs_sen
echo "📋 Colonnes senid/senmat dans senat_senateurs_sen :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns 
WHERE table_name = 'senat_senateurs_sen' 
AND (column_name LIKE '%senid%' OR column_name LIKE '%senmat%')
ORDER BY ordinal_position;
"
echo ""

# Tenter de voir la correspondance
echo "🔗 Exemple de données senat_senateurs_sen (5 premiers) :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT senmat, COALESCE(senid::text, 'NULL') AS senid_si_existe
FROM senat_senateurs_sen 
LIMIT 5;
" 2>/dev/null || echo "❌ Colonne senid n'existe pas dans senat_senateurs_sen"
echo ""

# Vérifier données amendements
echo "📊 Exemple amendements_senat (vue actuelle) :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT id, senateur_matricule, numero, auteur_nom, sort_libelle 
FROM amendements_senat 
LIMIT 5;
"
echo ""

# Compter amendements
echo "🔢 Total amendements dans la vue :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT COUNT(*) AS total_amendements FROM amendements_senat;
"
echo ""

# Vérifier votes
echo "📊 Exemple senateurs_votes (vue actuelle) :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT id, senateur_matricule, scrutin_id, position 
FROM senateurs_votes 
LIMIT 5;
"
echo ""

# Compter votes
echo "🔢 Total votes dans la vue :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT COUNT(*) AS total_votes FROM senateurs_votes;
"
echo ""

# Chercher dans senat_ameli_amdsen un senid pour test
echo "🔍 Tester la correspondance senid → senmat :"
docker compose exec postgres psql -U laravel -d laravel -c "
SELECT 
    amdsen.senid,
    sen.senmat,
    sen.sennomuse AS nom
FROM senat_ameli_amdsen amdsen
LEFT JOIN senat_senateurs_sen sen ON amdsen.senid = sen.senid
LIMIT 5;
" 2>/dev/null || echo "❌ Jointure impossible : senid n'existe pas dans senat_senateurs_sen"
echo ""

echo "✅ Diagnostic terminé !"
echo ""
echo "💡 Prochaines étapes :"
echo "  1. Analyser les résultats ci-dessus"
echo "  2. Identifier la bonne jointure senid → senmat"
echo "  3. Corriger la vue amendements_senat"
echo "  4. Tester avec: docker compose exec app php artisan tinker"

