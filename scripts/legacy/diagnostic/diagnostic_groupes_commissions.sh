#!/bin/bash

# Script pour trouver les tables de groupes et commissions Sénat
cd /opt/civicdash || exit 1

echo "🔍 Recherche tables groupes parlementaires et commissions"
echo "=========================================================="
echo ""

echo "📋 1. Tables contenant 'grp' (groupes) :"
php artisan tinker --execute="
\$tables = DB::select(\"SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%grp%' ORDER BY table_name\");
foreach (\$tables as \$t) {
    echo \$t->table_name . PHP_EOL;
}
"
echo ""

echo "📋 2. Tables contenant 'org' (organes/commissions) :"
php artisan tinker --execute="
\$tables = DB::select(\"SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%org%' ORDER BY table_name\");
foreach (\$tables as \$t) {
    echo \$t->table_name . PHP_EOL;
}
"
echo ""

echo "📋 3. Tables contenant 'com' (commissions) :"
php artisan tinker --execute="
\$tables = DB::select(\"SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%com%' ORDER BY table_name\");
foreach (\$tables as \$t) {
    echo \$t->table_name . PHP_EOL;
}
"
echo ""

echo "📊 4. Exemple de groupe parlementaire d'un sénateur :"
php artisan tinker --execute="
\$sen = DB::select(\"SELECT senmat, sennomuse, sengrppolcodcou, sengrppolliccou FROM senat_senateurs_sen WHERE senmat = '19954N' LIMIT 1\");
if (count(\$sen) > 0) {
    echo 'Matricule: ' . \$sen[0]->senmat . PHP_EOL;
    echo 'Nom: ' . \$sen[0]->sennomuse . PHP_EOL;
    echo 'Groupe CODE: ' . (\$sen[0]->sengrppolcodcou ?? 'NULL') . PHP_EOL;
    echo 'Groupe LIBELLE: ' . (\$sen[0]->sengrppolliccou ?? 'NULL') . PHP_EOL;
}
"
echo ""

echo "📊 5. Exemple de commission d'un sénateur :"
php artisan tinker --execute="
\$sen = DB::select(\"SELECT senmat, sennomuse, sencomcodcou, sencomliccou FROM senat_senateurs_sen WHERE senmat = '19954N' LIMIT 1\");
if (count(\$sen) > 0) {
    echo 'Matricule: ' . \$sen[0]->senmat . PHP_EOL;
    echo 'Nom: ' . \$sen[0]->sennomuse . PHP_EOL;
    echo 'Commission CODE: ' . (\$sen[0]->sencomcodcou ?? 'NULL') . PHP_EOL;
    echo 'Commission LIBELLE: ' . (\$sen[0]->sencomliccou ?? 'NULL') . PHP_EOL;
}
"
echo ""

echo "📊 6. Chercher table de référence groupes (si existe) :"
php artisan tinker --execute="
try {
    \$grps = DB::select(\"SELECT * FROM senat_senateurs_grppol LIMIT 3\");
    echo 'Table senat_senateurs_grppol trouvée :' . PHP_EOL;
    foreach (\$grps as \$g) {
        echo '  - ' . print_r(\$g, true) . PHP_EOL;
    }
} catch (Exception \$e) {
    echo 'Table senat_senateurs_grppol non trouvée' . PHP_EOL;
}
"
echo ""

echo "📊 7. Chercher table de référence organes (si existe) :"
php artisan tinker --execute="
try {
    \$orgs = DB::select(\"SELECT * FROM senat_senateurs_org LIMIT 3\");
    echo 'Table senat_senateurs_org trouvée :' . PHP_EOL;
    foreach (\$orgs as \$o) {
        echo '  - Code: ' . (\$o->orgcod ?? 'NULL') . ' - Libellé: ' . (\$o->evelib ?? \$o->orglib ?? 'NULL') . PHP_EOL;
    }
} catch (Exception \$e) {
    echo 'Table senat_senateurs_org non trouvée' . PHP_EOL;
}
"
echo ""

echo "✅ Diagnostic terminé !"

