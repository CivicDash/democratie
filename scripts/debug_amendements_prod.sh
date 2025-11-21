#!/bin/bash

# Script pour vérifier si la migration a bien été appliquée en prod
cd /opt/civicdash || exit 1

echo "🔍 Vérification vue amendements_senat en prod"
echo "=============================================="
echo ""

echo "📋 1. Vérifier si la migration a été exécutée :"
php artisan tinker --execute="
\$mig = DB::table('migrations')->where('migration', 'LIKE', '%fix_amendements_senat%')->first();
if (\$mig) {
    echo '✅ Migration trouvée : ' . \$mig->migration . ' (batch: ' . \$mig->batch . ')' . PHP_EOL;
} else {
    echo '❌ Migration NON trouvée dans la table migrations' . PHP_EOL;
}
"
echo ""

echo "📊 2. Tester la vue amendements_senat :"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM amendements_senat');
    echo 'Total amendements : ' . \$count[0]->total . PHP_EOL;
    
    \$sample = DB::select('SELECT id, senateur_matricule, numero, auteur_nom FROM amendements_senat LIMIT 3');
    foreach (\$sample as \$amd) {
        echo '  - AMD ' . \$amd->numero . ' par ' . \$amd->auteur_nom . ' (matricule: ' . \$amd->senateur_matricule . ')' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "🔍 3. Tester pour Catherine Belrhiti (19954N) :"
php artisan tinker --execute="
\$amds = DB::select(\"SELECT COUNT(*) as total FROM amendements_senat WHERE senateur_matricule = '19954N'\");
echo 'Amendements pour 19954N : ' . \$amds[0]->total . PHP_EOL;
"
echo ""

echo "🔍 4. Vérifier les tables sources :"
php artisan tinker --execute="
try {
    \$amd_count = DB::select('SELECT COUNT(*) as total FROM senat_ameli_amd');
    echo 'Total dans senat_ameli_amd : ' . \$amd_count[0]->total . PHP_EOL;
    
    \$amdsen_count = DB::select('SELECT COUNT(*) as total FROM senat_ameli_amdsen');
    echo 'Total dans senat_ameli_amdsen : ' . \$amdsen_count[0]->total . PHP_EOL;
    
    \$sen_count = DB::select('SELECT COUNT(*) as total FROM sen_ameli');
    echo 'Total dans sen_ameli : ' . \$sen_count[0]->total . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "🔍 5. Tester la jointure manuellement :"
php artisan tinker --execute="
try {
    \$test = DB::select(\"
        SELECT 
            amd.id,
            amdsen.senid,
            sen.mat AS matricule,
            amdsen.nomuse
        FROM senat_ameli_amd amd
        LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
        LEFT JOIN sen_ameli sen ON amdsen.senid = sen.entid
        WHERE sen.mat = '19954N'
        LIMIT 3
    \");
    
    if (count(\$test) > 0) {
        echo '✅ Jointure fonctionne :' . PHP_EOL;
        foreach (\$test as \$t) {
            echo '  - AMD ID: ' . \$t->id . ' - SEN ID: ' . \$t->senid . ' - Matricule: ' . \$t->matricule . ' - Nom: ' . \$t->nomuse . PHP_EOL;
        }
    } else {
        echo '❌ Jointure ne retourne rien pour 19954N' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur jointure : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "✅ Diagnostic terminé !"
echo ""
echo "💡 Actions selon les résultats :"
echo "  - Si migration non trouvée → Relancer: php artisan migrate --force"
echo "  - Si tables sources vides → Réimporter données AMELI"
echo "  - Si jointure ne fonctionne pas → Vérifier structure tables"

