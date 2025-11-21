#!/bin/bash

# Script pour diagnostiquer les problèmes d'affichage groupes/commissions
# Compare les données brutes vs les vues

cd /home/kevin/www/demoscratos || exit 1

echo "🔍 DIAGNOSTIC GROUPES & COMMISSIONS"
echo "====================================="
echo ""

# 1. Test Catherine Belrhiti (19954N)
echo "📊 1. DONNÉES POUR CATHERINE BELRHITI (19954N)"
echo "----------------------------------------------"

echo "🔹 Données RAW (senat_senateurs_sen) :"
php artisan tinker --execute="
\$sen = DB::select('SELECT senmat, sennomuse, senprenomuse, sengrppolcodcou, sengrppolliccou, sencomcodcou, sencomliccou FROM senat_senateurs_sen WHERE senmat = \\'19954N\\'');
if (count(\$sen) > 0) {
    \$s = \$sen[0];
    echo '  Nom: ' . \$s->senprenomuse . ' ' . \$s->sennomuse . PHP_EOL;
    echo '  Groupe CODE: ' . (\$s->sengrppolcodcou ?? 'NULL') . PHP_EOL;
    echo '  Groupe LIBELLÉ: ' . (\$s->sengrppolliccou ?? 'NULL') . PHP_EOL;
    echo '  Commission CODE: ' . (\$s->sencomcodcou ?? 'NULL') . PHP_EOL;
    echo '  Commission LIBELLÉ: ' . (\$s->sencomliccou ?? 'NULL') . PHP_EOL;
}
"

echo ""
echo "🔹 Données VUE (senateurs) :"
php artisan tinker --execute="
try {
    \$sen = DB::select('SELECT id, nom_usuel, prenom_usuel, groupe_politique, groupe_sigle, commission_permanente FROM senateurs WHERE id = \\'19954N\\'');
    if (count(\$sen) > 0) {
        \$s = \$sen[0];
        echo '  Nom: ' . \$s->prenom_usuel . ' ' . \$s->nom_usuel . PHP_EOL;
        echo '  Groupe sigle: ' . (\$s->groupe_sigle ?? 'NULL') . PHP_EOL;
        echo '  Groupe libellé: ' . (\$s->groupe_politique ?? 'NULL') . PHP_EOL;
        echo '  Commission: ' . (\$s->commission_permanente ?? 'NULL') . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔹 Données MODEL (Senateur) :"
php artisan tinker --execute="
try {
    \$sen = \\App\\Models\\Senateur::find('19954N');
    if (\$sen) {
        echo '  Nom: ' . \$sen->prenom_usuel . ' ' . \$sen->nom_usuel . PHP_EOL;
        echo '  Groupe sigle: ' . (\$sen->groupe_sigle ?? 'NULL') . PHP_EOL;
        echo '  Groupe libellé: ' . (\$sen->groupe_politique ?? 'NULL') . PHP_EOL;
        echo '  Commission: ' . (\$sen->commission_permanente ?? 'NULL') . PHP_EOL;
        
        if (\$sen->groupe) {
            echo '  Groupe relation: ' . (\$sen->groupe->nom ?? 'NULL') . PHP_EOL;
        }
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# 2. Vérifier table de référence groupes
echo "📊 2. TABLE RÉFÉRENCE GROUPES (grppol)"
echo "---------------------------------------"

echo "🔹 Chercher le groupe de Catherine Belrhiti :"
php artisan tinker --execute="
\$groupe = DB::select('SELECT sengrppolcodcou FROM senat_senateurs_sen WHERE senmat = \\'19954N\\'');
if (count(\$groupe) > 0 && \$groupe[0]->sengrppolcodcou) {
    \$code = \$groupe[0]->sengrppolcodcou;
    echo 'Code groupe: ' . \$code . PHP_EOL;
    
    // Chercher dans grppol
    \$grp = DB::select('SELECT * FROM senat_senateurs_grppol WHERE grppolcod = ?', [\$code]);
    if (count(\$grp) > 0) {
        echo '✅ Trouvé dans grppol :' . PHP_EOL;
        echo '  Code: ' . \$grp[0]->grppolcod . PHP_EOL;
        echo '  Libellé: ' . \$grp[0]->grppollib . PHP_EOL;
        echo '  Sigle: ' . (\$grp[0]->grppolsig ?? 'NULL') . PHP_EOL;
    } else {
        echo '❌ PAS trouvé dans grppol' . PHP_EOL;
    }
}
"

echo ""
echo ""

# 3. Vérifier table de référence organes/commissions
echo "📊 3. TABLE RÉFÉRENCE COMMISSIONS (org)"
echo "----------------------------------------"

echo "🔹 Chercher la commission de Catherine Belrhiti :"
php artisan tinker --execute="
\$com = DB::select('SELECT sencomcodcou FROM senat_senateurs_sen WHERE senmat = \\'19954N\\'');
if (count(\$com) > 0 && \$com[0]->sencomcodcou) {
    \$code = \$com[0]->sencomcodcou;
    echo 'Code commission: ' . \$code . PHP_EOL;
    
    // Chercher dans org
    \$org = DB::select('SELECT * FROM senat_senateurs_org WHERE orgcod = ?', [\$code]);
    if (count(\$org) > 0) {
        echo '✅ Trouvé dans org :' . PHP_EOL;
        echo '  Code: ' . \$org[0]->orgcod . PHP_EOL;
        echo '  Libellé: ' . (\$org[0]->evelib ?? 'NULL') . PHP_EOL;
        echo '  Type: ' . (\$org[0]->typorgcod ?? 'NULL') . PHP_EOL;
    } else {
        echo '❌ PAS trouvé dans org' . PHP_EOL;
    }
}
"

echo ""
echo ""

# 4. Lister tous les groupes actifs
echo "📊 4. LISTE TOUS LES GROUPES ACTIFS"
echo "------------------------------------"
php artisan tinker --execute="
\$groupes = DB::select('
    SELECT DISTINCT sengrppolcodcou, sengrppolliccou 
    FROM senat_senateurs_sen 
    WHERE etasencod = \\'ACTIF\\' 
    AND sengrppolcodcou IS NOT NULL
    ORDER BY sengrppolliccou
');

echo 'Groupes des sénateurs actifs :' . PHP_EOL;
foreach (\$groupes as \$g) {
    echo '  - [' . \$g->sengrppolcodcou . '] ' . \$g->sengrppolliccou . PHP_EOL;
}
"

echo ""
echo ""

# 5. Lister toutes les commissions actives
echo "📊 5. LISTE TOUTES LES COMMISSIONS ACTIVES"
echo "-------------------------------------------"
php artisan tinker --execute="
\$coms = DB::select('
    SELECT DISTINCT sencomcodcou, sencomliccou 
    FROM senat_senateurs_sen 
    WHERE etasencod = \\'ACTIF\\' 
    AND sencomcodcou IS NOT NULL
    ORDER BY sencomliccou
');

echo 'Commissions des sénateurs actifs :' . PHP_EOL;
foreach (\$coms as \$c) {
    echo '  - [' . \$c->sencomcodcou . '] ' . \$c->sencomliccou . PHP_EOL;
}
"

echo ""
echo ""

echo "✅ DIAGNOSTIC TERMINÉ !"
echo ""
echo "💡 Conclusions attendues :"
echo "  1. Si libellés OK dans table raw → Vue senateurs est correcte"
echo "  2. Si codes seulement → Besoin de jointure avec grppol/org"
echo "  3. Si NULL → Problème de mapping des colonnes"

