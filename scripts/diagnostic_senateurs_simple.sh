#!/bin/bash

# Script de diagnostic simple via requêtes SQL directes
# À lancer depuis /opt/civicdash/

cd /opt/civicdash || exit 1

echo "🔍 Diagnostic Tables Sénat - Amendements & Votes"
echo "=================================================="
echo ""

echo "📋 1. Structure de senat_ameli_amdsen :"
php artisan tinker --execute="
\$cols = DB::select(\"SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'senat_ameli_amdsen' ORDER BY ordinal_position LIMIT 10\");
foreach (\$cols as \$col) {
    echo \$col->column_name . ' (' . \$col->data_type . ')' . PHP_EOL;
}
"
echo ""

echo "📋 2. Colonnes senid/senmat dans senat_senateurs_sen :"
php artisan tinker --execute="
\$cols = DB::select(\"SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'senat_senateurs_sen' AND (column_name LIKE '%senid%' OR column_name LIKE '%senmat%')\");
if (count(\$cols) > 0) {
    foreach (\$cols as \$col) {
        echo \$col->column_name . ' (' . \$col->data_type . ')' . PHP_EOL;
    }
} else {
    echo 'Aucune colonne trouvée' . PHP_EOL;
}
"
echo ""

echo "📊 3. Exemple senat_senateurs_sen (3 premiers) :"
php artisan tinker --execute="
\$sens = DB::select(\"SELECT senmat, sennomuse, senprenomuse FROM senat_senateurs_sen LIMIT 3\");
foreach (\$sens as \$sen) {
    echo 'Matricule: ' . \$sen->senmat . ' - ' . \$sen->senprenomuse . ' ' . \$sen->sennomuse . PHP_EOL;
}
"
echo ""

echo "📊 4. Exemple senat_ameli_amdsen (3 premiers) :"
php artisan tinker --execute="
\$amds = DB::select(\"SELECT amdid, senid, nomuse, prenomuse FROM senat_ameli_amdsen LIMIT 3\");
foreach (\$amds as \$amd) {
    echo 'AMD ID: ' . \$amd->amdid . ' - SEN ID: ' . \$amd->senid . ' - ' . \$amd->prenomuse . ' ' . \$amd->nomuse . PHP_EOL;
}
"
echo ""

echo "🔗 5. Test jointure senid → senmat (3 premiers) :"
php artisan tinker --execute="
try {
    \$joins = DB::select(\"
        SELECT 
            amdsen.senid,
            sen.senmat,
            amdsen.nomuse
        FROM senat_ameli_amdsen amdsen
        LEFT JOIN senat_senateurs_sen sen ON amdsen.senid = sen.senid
        LIMIT 3
    \");
    foreach (\$joins as \$join) {
        echo 'SEN ID: ' . \$join->senid . ' → Matricule: ' . (\$join->senmat ?? 'NULL') . ' (' . \$join->nomuse . ')' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo 'ERREUR: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "📊 6. Amendements dans la vue actuelle :"
php artisan tinker --execute="
\$count = DB::select(\"SELECT COUNT(*) as total FROM amendements_senat\");
echo 'Total amendements: ' . \$count[0]->total . PHP_EOL;
\$sample = DB::select(\"SELECT id, senateur_matricule, numero, auteur_nom FROM amendements_senat LIMIT 2\");
foreach (\$sample as \$amd) {
    echo '  - AMD ' . \$amd->numero . ' par ' . \$amd->auteur_nom . ' (matricule: ' . \$amd->senateur_matricule . ')' . PHP_EOL;
}
"
echo ""

echo "🗳️  7. Votes dans la vue actuelle :"
php artisan tinker --execute="
\$count = DB::select(\"SELECT COUNT(*) as total FROM senateurs_votes\");
echo 'Total votes: ' . \$count[0]->total . PHP_EOL;
"
echo ""

echo "🔍 8. Test sénateur 19565D :"
php artisan tinker --execute="
\$sen = DB::select(\"SELECT senmat, sennomuse, senprenomuse FROM senat_senateurs_sen WHERE senmat = '19565D'\");
if (count(\$sen) > 0) {
    echo '✅ Trouvé: ' . \$sen[0]->senprenomuse . ' ' . \$sen[0]->sennomuse . PHP_EOL;
    \$amds = DB::select(\"SELECT COUNT(*) as total FROM amendements_senat WHERE senateur_matricule = '19565D'\");
    echo '  Amendements: ' . \$amds[0]->total . PHP_EOL;
    \$votes = DB::select(\"SELECT COUNT(*) as total FROM senateurs_votes WHERE senateur_matricule = '19565D'\");
    echo '  Votes: ' . \$votes[0]->total . PHP_EOL;
} else {
    echo '❌ Sénateur 19565D non trouvé' . PHP_EOL;
}
"
echo ""

echo "✅ Diagnostic terminé !"
echo ""
echo "💡 Analyse :"
echo "  - Si jointure senid fonctionne ET donne des matricules"
echo "  - Alors on peut corriger la vue amendements_senat"
echo "  - Sinon, il faut trouver une autre correspondance"

