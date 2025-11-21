#!/bin/bash

# Script d'analyse complète des données Sénat importées
# À lancer après l'import des données

cd /home/kevin/www/demoscratos || exit 1

echo "🔍 ANALYSE COMPLÈTE DONNÉES SÉNAT"
echo "===================================="
echo ""

# 1. SCRUTINS
echo "📊 1. ANALYSE SCRUTINS (table scr)"
echo "-----------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_scr');
    echo 'Total scrutins : ' . \$count[0]->total . PHP_EOL;
    
    // Exemple de scrutin
    \$scrutin = DB::select('SELECT * FROM senat_senateurs_scr LIMIT 1');
    if (count(\$scrutin) > 0) {
        \$s = \$scrutin[0];
        echo PHP_EOL . '📋 Exemple scrutin :' . PHP_EOL;
        echo '  - ID: ' . \$s->scrid . PHP_EOL;
        echo '  - Numéro: ' . (\$s->scrnum ?? 'NULL') . PHP_EOL;
        echo '  - Date: ' . (\$s->scrdat ?? 'NULL') . PHP_EOL;
        echo '  - Type: ' . (\$s->typscrcod ?? 'NULL') . PHP_EOL;
        echo '  - Pour: ' . (\$s->scrpou ?? 'NULL') . PHP_EOL;
        echo '  - Contre: ' . (\$s->scrcon ?? 'NULL') . PHP_EOL;
        echo '  - Votants: ' . (\$s->scrvot ?? 'NULL') . PHP_EOL;
        echo '  - Suffrages: ' . (\$s->scrsuf ?? 'NULL') . PHP_EOL;
        echo '  - Majorité: ' . (\$s->scrmaj ?? 'NULL') . PHP_EOL;
        echo '  - Résultat code: ' . (\$s->scrrecsea ?? 'NULL') . PHP_EOL;
        echo '  - Résultat libellé: ' . (\$s->scrcptsea ?? 'NULL') . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 2. VOTES INDIVIDUELS
echo "📊 2. ANALYSE VOTES (table votes)"
echo "----------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_votes');
    echo 'Total votes : ' . \$count[0]->total . PHP_EOL;
    
    // Répartition positions
    \$positions = DB::select('
        SELECT posvotcod, COUNT(*) as total 
        FROM senat_senateurs_votes 
        GROUP BY posvotcod 
        ORDER BY total DESC
    ');
    
    echo PHP_EOL . '📊 Répartition positions :' . PHP_EOL;
    foreach (\$positions as \$p) {
        echo '  - ' . (\$p->posvotcod ?? 'NULL') . ' : ' . \$p->total . ' votes' . PHP_EOL;
    }
    
    // Exemple vote
    \$vote = DB::select('SELECT * FROM senat_senateurs_votes LIMIT 1');
    if (count(\$vote) > 0) {
        \$v = \$vote[0];
        echo PHP_EOL . '📋 Exemple vote :' . PHP_EOL;
        echo '  - ID: ' . \$v->votesid . PHP_EOL;
        echo '  - Scrutin: ' . \$v->scrid . PHP_EOL;
        echo '  - Sénateur: ' . \$v->senmat . PHP_EOL;
        echo '  - Position: ' . (\$v->posvotcod ?? 'NULL') . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 3. SÉNATEURS
echo "📊 3. ANALYSE SÉNATEURS (table sen)"
echo "------------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_sen');
    echo 'Total sénateurs : ' . \$count[0]->total . PHP_EOL;
    
    // Par état
    \$etats = DB::select('
        SELECT etasencod, COUNT(*) as total 
        FROM senat_senateurs_sen 
        GROUP BY etasencod
    ');
    
    echo PHP_EOL . '📊 Par état :' . PHP_EOL;
    foreach (\$etats as \$e) {
        echo '  - ' . \$e->etasencod . ' : ' . \$e->total . PHP_EOL;
    }
    
    // Exemple sénateur
    \$sen = DB::select('SELECT * FROM senat_senateurs_sen WHERE senmat = \\'19954N\\' LIMIT 1');
    if (count(\$sen) > 0) {
        \$s = \$sen[0];
        echo PHP_EOL . '📋 Exemple sénateur (19954N) :' . PHP_EOL;
        echo '  - Matricule: ' . \$s->senmat . PHP_EOL;
        echo '  - Nom: ' . \$s->senprenomuse . ' ' . \$s->sennomuse . PHP_EOL;
        echo '  - Groupe code: ' . (\$s->sengrppolcodcou ?? 'NULL') . PHP_EOL;
        echo '  - Groupe libellé: ' . (\$s->sengrppolliccou ?? 'NULL') . PHP_EOL;
        echo '  - Commission code: ' . (\$s->sencomcodcou ?? 'NULL') . PHP_EOL;
        echo '  - Commission libellé: ' . (\$s->sencomliccou ?? 'NULL') . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 4. GROUPES POLITIQUES
echo "📊 4. ANALYSE GROUPES POLITIQUES (table grppol)"
echo "-----------------------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_grppol');
    echo 'Total groupes : ' . \$count[0]->total . PHP_EOL;
    
    // Liste groupes actifs
    \$groupes = DB::select('
        SELECT grppolcod, grppollib 
        FROM senat_senateurs_grppol 
        WHERE grppoldatfin IS NULL OR grppoldatfin > NOW()
        ORDER BY grppollib
        LIMIT 10
    ');
    
    echo PHP_EOL . '📊 Groupes actifs :' . PHP_EOL;
    foreach (\$groupes as \$g) {
        echo '  - ' . \$g->grppolcod . ' : ' . \$g->grppollib . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 5. ORGANES/COMMISSIONS
echo "📊 5. ANALYSE ORGANES/COMMISSIONS (table org)"
echo "----------------------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_org');
    echo 'Total organes : ' . \$count[0]->total . PHP_EOL;
    
    // Par type
    \$types = DB::select('
        SELECT typorgcod, COUNT(*) as total 
        FROM senat_senateurs_org 
        GROUP BY typorgcod
    ');
    
    echo PHP_EOL . '📊 Par type :' . PHP_EOL;
    foreach (\$types as \$t) {
        echo '  - ' . (\$t->typorgcod ?? 'NULL') . ' : ' . \$t->total . PHP_EOL;
    }
    
    // Commissions permanentes
    \$coms = DB::select('
        SELECT orgcod, evelib 
        FROM senat_senateurs_org 
        WHERE typorgcod = \\'COMPER\\'
        ORDER BY evelib
        LIMIT 10
    ');
    
    echo PHP_EOL . '📊 Commissions permanentes :' . PHP_EOL;
    foreach (\$coms as \$c) {
        echo '  - ' . \$c->orgcod . ' : ' . \$c->evelib . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 6. VÉRIFIER VUE SENATEURS
echo "📊 6. VÉRIFICATION VUE senateurs"
echo "---------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senateurs');
    echo 'Total dans vue senateurs : ' . \$count[0]->total . PHP_EOL;
    
    // Vérifier les colonnes remplies
    \$sample = DB::select('
        SELECT 
            id, 
            nom_usuel, 
            prenom_usuel, 
            etat, 
            groupe_politique, 
            commission_permanente 
        FROM senateurs 
        WHERE id = \\'19954N\\'
    ');
    
    if (count(\$sample) > 0) {
        \$s = \$sample[0];
        echo PHP_EOL . '📋 Sénateur 19954N dans la vue :' . PHP_EOL;
        echo '  - Nom: ' . \$s->prenom_usuel . ' ' . \$s->nom_usuel . PHP_EOL;
        echo '  - État: ' . \$s->etat . PHP_EOL;
        echo '  - Groupe: ' . (\$s->groupe_politique ?? 'NULL') . PHP_EOL;
        echo '  - Commission: ' . (\$s->commission_permanente ?? 'NULL') . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 7. VÉRIFIER VUE SENATEURS_VOTES
echo "📊 7. VÉRIFICATION VUE senateurs_votes"
echo "---------------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senateurs_votes');
    echo 'Total votes dans vue : ' . \$count[0]->total . PHP_EOL;
    
    // Votes pour 19954N
    \$votes = DB::select('
        SELECT COUNT(*) as total 
        FROM senateurs_votes 
        WHERE senateur_matricule = \\'19954N\\'
    ');
    echo 'Votes pour 19954N : ' . \$votes[0]->total . PHP_EOL;
    
    // Exemple
    \$sample = DB::select('
        SELECT * 
        FROM senateurs_votes 
        WHERE senateur_matricule = \\'19954N\\'
        LIMIT 1
    ');
    
    if (count(\$sample) > 0) {
        \$v = \$sample[0];
        echo PHP_EOL . '📋 Exemple vote 19954N :' . PHP_EOL;
        echo '  - Scrutin: ' . \$v->scrutin_id . PHP_EOL;
        echo '  - Position: ' . \$v->position . PHP_EOL;
        echo '  - Date: ' . \$v->date_vote . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 8. VÉRIFIER VUE SENATEURS_SCRUTINS
echo "📊 8. VÉRIFICATION VUE senateurs_scrutins"
echo "------------------------------------------"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senateurs_scrutins');
    echo 'Total scrutins dans vue : ' . \$count[0]->total . PHP_EOL;
    
    // Exemple
    \$sample = DB::select('SELECT * FROM senateurs_scrutins LIMIT 1');
    
    if (count(\$sample) > 0) {
        \$s = \$sample[0];
        echo PHP_EOL . '📋 Exemple scrutin :' . PHP_EOL;
        echo '  - Numéro: ' . \$s->numero . PHP_EOL;
        echo '  - Date: ' . \$s->date_scrutin . PHP_EOL;
        echo '  - Pour: ' . \$s->pour . PHP_EOL;
        echo '  - Contre: ' . \$s->contre . PHP_EOL;
        echo '  - Résultat: ' . \$s->resultat_code . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "✅ ANALYSE TERMINÉE !"
echo ""
echo "💡 Prochaines étapes :"
echo "  1. Vérifier que les libellés groupes/commissions sont corrects"
echo "  2. Corriger les vues si nécessaire"
echo "  3. Tester l'affichage dans l'application"

