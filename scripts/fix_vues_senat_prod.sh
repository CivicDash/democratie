#!/bin/bash

# Script de correction pour les vues Sénat sur le serveur de production

echo "🔧 Script de correction des vues SQL Sénat"
echo ""

cd /opt/civicdash

echo "1️⃣ Re-création de la vue amendements_senat avec cast TEXT..."
docker compose exec app php artisan db:statement "
DROP VIEW IF EXISTS amendements_senat CASCADE;

CREATE OR REPLACE VIEW amendements_senat AS
SELECT 
    amd.id AS id,
    amdsen.senid::text AS senateur_matricule,
    amd.num AS numero,
    amd.typ AS type_amendement,
    amd.dis AS dispositif,
    amd.obj AS expose,
    amd.datdep::date AS date_depot,
    sor.lib AS sort_libelle,
    sor.cod AS sort_code,
    amdsen.nomuse AS auteur_nom,
    amdsen.prenomuse AS auteur_prenom,
    amdsen.grpid AS auteur_groupe_id,
    NOW() AS created_at,
    NOW() AS updated_at
FROM senat_ameli_amd amd
LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
LEFT JOIN senat_ameli_sor sor ON amd.sorid = sor.id
WHERE amdsen.senid IS NOT NULL
ORDER BY amd.datdep DESC NULLS LAST;
"

if [ $? -eq 0 ]; then
    echo "✅ Vue amendements_senat recréée avec succès"
else
    echo "❌ Erreur lors de la recréation de la vue amendements_senat"
    exit 1
fi

echo ""
echo "2️⃣ Diagnostic des données scrutins Sénat..."
docker compose exec app php artisan tinker --execute="
\$total = DB::table('senat_senateurs_scr')->count();
\$avecPour = DB::table('senat_senateurs_scr')->whereNotNull('scrpou')->count();
\$avecContre = DB::table('senat_senateurs_scr')->whereNotNull('scrcon')->count();
echo \"Total scrutins: \$total\\n\";
echo \"Avec 'pour': \$avecPour\\n\";
echo \"Avec 'contre': \$avecContre\\n\";
\$sample = DB::table('senat_senateurs_scr')->limit(5)->get(['scrid', 'scrnum', 'scrpou', 'scrcon', 'scrint']);
echo \"Échantillon:\\n\";
print_r(\$sample->toArray());
"

echo ""
echo "3️⃣ Re-création de la vue senateurs_votes avec cast TEXT..."
docker compose exec app php artisan db:statement "
DROP VIEW IF EXISTS votes_senat CASCADE;
DROP VIEW IF EXISTS senateurs_votes CASCADE;

CREATE OR REPLACE VIEW senateurs_votes AS
SELECT 
    v.votesid AS id,
    v.senmat::text AS senateur_matricule,
    v.scrid AS scrutin_id,
    scr.scrdat::date AS date_vote,
    scr.scrint AS intitule,
    scr.scrintext AS intitule_complet,
    CASE 
        WHEN v.posvotcod = 'P' THEN 'pour'
        WHEN v.posvotcod = 'C' THEN 'contre'
        WHEN v.posvotcod = 'A' THEN 'abstention'
        WHEN v.posvotcod = 'NV' THEN 'non_votant'
        ELSE v.posvotcod
    END AS position,
    CASE 
        WHEN scr.scrpou > scr.scrcon THEN 'Adopté'
        WHEN scr.scrcon > scr.scrpou THEN 'Rejeté'
        ELSE 'Égalité'
    END AS resultat_scrutin,
    NOW() AS created_at,
    NOW() AS updated_at
FROM senat_senateurs_votes v
LEFT JOIN senat_senateurs_scr scr ON v.scrid = scr.scrid
WHERE v.senmat IS NOT NULL
ORDER BY scr.scrdat DESC NULLS LAST;

CREATE OR REPLACE VIEW votes_senat AS SELECT * FROM senateurs_votes;
"

if [ $? -eq 0 ]; then
    echo "✅ Vues senateurs_votes et votes_senat recréées avec succès"
else
    echo "❌ Erreur lors de la recréation des vues votes"
fi

echo ""
echo "4️⃣ Vérification finale..."
docker compose exec app php artisan tinker --execute="
echo \"Amendements: \" . \App\Models\AmendementSenat::count() . \"\\n\";
echo \"Votes: \" . \App\Models\VoteSenat::count() . \"\\n\";
echo \"Scrutins: \" . \App\Models\ScrutinSenat::count() . \"\\n\";
\$scrutin = \App\Models\ScrutinSenat::first();
if (\$scrutin) {
    echo \"Premier scrutin: \" . \$scrutin->intitule . \"\\n\";
    echo \"Pour: \" . \$scrutin->pour . \" | Contre: \" . \$scrutin->contre . \"\\n\";
    echo \"Resultat: \" . \$scrutin->resultat . \"\\n\";
}
"

echo ""
echo "5️⃣ Clear cache..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan opcache:clear

echo ""
echo "✅ Script terminé !"
echo ""
echo "🔍 Testez maintenant :"
echo "  - Page sénateur > Amendements"
echo "  - Page sénateur > Votes"
echo "  - Page sénateur > Activité"

