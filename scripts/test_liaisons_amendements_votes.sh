#!/bin/bash

# Script pour tester les liaisons amendements/sénateurs et votes/scrutins
# Test avec Catherine Belrhiti (19954N)

cd /home/kevin/www/demoscratos || exit 1

echo "🔍 TEST LIAISONS AMENDEMENTS & VOTES"
echo "======================================"
echo ""

MATRICULE="19954N"
echo "🎯 Sénateur test : Catherine Belrhiti ($MATRICULE)"
echo ""

# ============================================================================
# 1. VÉRIFIER LA LIAISON AMENDEMENTS → SÉNATEUR
# ============================================================================
echo "📊 1. LIAISON AMENDEMENTS → SÉNATEUR"
echo "-------------------------------------"

echo "🔹 Étape 1 : Trouver senid pour matricule $MATRICULE"
php artisan tinker --execute="
\$sen = DB::select('SELECT senid, senmat, senprenomuse, sennomuse FROM sen_ameli WHERE mat = \\'$MATRICULE\\'');
if (count(\$sen) > 0) {
    echo '✅ Trouvé dans sen_ameli :' . PHP_EOL;
    echo '  senid: ' . \$sen[0]->senid . PHP_EOL;
    echo '  matricule: ' . \$sen[0]->senmat . PHP_EOL;
    echo '  nom: ' . \$sen[0]->senprenomuse . ' ' . \$sen[0]->sennomuse . PHP_EOL;
} else {
    echo '❌ PAS trouvé dans sen_ameli' . PHP_EOL;
}
"

echo ""
echo "🔹 Étape 2 : Chercher amendements avec ce senid"
php artisan tinker --execute="
// Récupérer senid
\$sen = DB::select('SELECT senid FROM sen_ameli WHERE mat = \\'$MATRICULE\\'');
if (count(\$sen) > 0) {
    \$senid = \$sen[0]->senid;
    echo 'Recherche amendements pour senid: ' . \$senid . PHP_EOL;
    
    // Chercher dans senat_ameli_amdsen
    try {
        \$amds = DB::select('SELECT COUNT(*) as total FROM senat_ameli_amdsen WHERE senid = ?', [\$senid]);
        echo '✅ Amendements trouvés (senat_ameli_amdsen) : ' . \$amds[0]->total . PHP_EOL;
        
        if (\$amds[0]->total > 0) {
            // Exemple
            \$sample = DB::select('SELECT amdid, senid, nomuse, prenomuse FROM senat_ameli_amdsen WHERE senid = ? LIMIT 1', [\$senid]);
            echo '  Exemple AMD ID: ' . \$sample[0]->amdid . ' - Auteur: ' . \$sample[0]->prenomuse . ' ' . \$sample[0]->nomuse . PHP_EOL;
        }
    } catch (Exception \$e) {
        echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
    }
}
"

echo ""
echo "🔹 Étape 3 : Tester la vue amendements_senat"
php artisan tinker --execute="
try {
    \$amds = DB::select('SELECT COUNT(*) as total FROM amendements_senat WHERE senateur_matricule = \\'$MATRICULE\\'');
    echo 'Vue amendements_senat pour $MATRICULE : ' . \$amds[0]->total . PHP_EOL;
    
    if (\$amds[0]->total > 0) {
        \$sample = DB::select('SELECT id, senateur_matricule, numero, auteur_nom, sort_libelle FROM amendements_senat WHERE senateur_matricule = \\'$MATRICULE\\' LIMIT 1');
        echo '  Exemple : AMD ' . \$sample[0]->numero . ' - ' . \$sample[0]->auteur_nom . ' - Sort: ' . \$sample[0]->sort_libelle . PHP_EOL;
    } else {
        echo '⚠️  Aucun amendement dans la vue' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur vue : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔹 Étape 4 : Tester la jointure complète"
php artisan tinker --execute="
try {
    \$test = DB::select(\"
        SELECT 
            amd.id AS amd_id,
            amdsen.senid,
            sen.mat AS matricule,
            amdsen.nomuse AS auteur,
            amd.num AS numero
        FROM senat_ameli_amd amd
        LEFT JOIN senat_ameli_amdsen amdsen ON amd.id = amdsen.amdid AND amdsen.rng = 1
        LEFT JOIN sen_ameli sen ON amdsen.senid = sen.entid
        WHERE sen.mat = '$MATRICULE'
        LIMIT 3
    \");
    
    if (count(\$test) > 0) {
        echo '✅ Jointure fonctionne ! Exemples :' . PHP_EOL;
        foreach (\$test as \$t) {
            echo '  - AMD ' . \$t->numero . ' (ID: ' . \$t->amd_id . ') - senid: ' . \$t->senid . ' → matricule: ' . \$t->matricule . PHP_EOL;
        }
    } else {
        echo '❌ Jointure ne retourne rien' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur jointure : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# ============================================================================
# 2. VÉRIFIER LA LIAISON VOTES → SCRUTIN
# ============================================================================
echo "📊 2. LIAISON VOTES → SCRUTIN"
echo "------------------------------"

echo "🔹 Étape 1 : Chercher votes pour matricule $MATRICULE"
php artisan tinker --execute="
try {
    \$votes = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_votes WHERE senmat = \\'$MATRICULE\\'');
    echo 'Votes dans senat_senateurs_votes : ' . \$votes[0]->total . PHP_EOL;
    
    if (\$votes[0]->total > 0) {
        // Exemple vote
        \$sample = DB::select('SELECT votesid, scrid, senmat, posvotcod FROM senat_senateurs_votes WHERE senmat = \\'$MATRICULE\\' LIMIT 1');
        echo '  Exemple : Vote ID ' . \$sample[0]->votesid . ' - Scrutin: ' . \$sample[0]->scrid . ' - Position: ' . \$sample[0]->posvotcod . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔹 Étape 2 : Vérifier détails d'un scrutin"
php artisan tinker --execute="
try {
    // Récupérer un scrutin ID
    \$vote = DB::select('SELECT scrid FROM senat_senateurs_votes WHERE senmat = \\'$MATRICULE\\' LIMIT 1');
    if (count(\$vote) > 0) {
        \$scrid = \$vote[0]->scrid;
        echo 'Test scrutin ID: ' . \$scrid . PHP_EOL;
        
        // Chercher dans senat_senateurs_scr
        \$scrutin = DB::select('SELECT * FROM senat_senateurs_scr WHERE scrid = ?', [\$scrid]);
        if (count(\$scrutin) > 0) {
            \$s = \$scrutin[0];
            echo '✅ Scrutin trouvé :' . PHP_EOL;
            echo '  - Numéro: ' . (\$s->scrnum ?? 'NULL') . PHP_EOL;
            echo '  - Date: ' . (\$s->scrdat ?? 'NULL') . PHP_EOL;
            echo '  - Pour: ' . (\$s->scrpou ?? 'NULL') . PHP_EOL;
            echo '  - Contre: ' . (\$s->scrcon ?? 'NULL') . PHP_EOL;
            echo '  - Abstentions: ' . (\$s->scrabs ?? 'NULL') . PHP_EOL;
            echo '  - Votants: ' . (\$s->scrvot ?? 'NULL') . PHP_EOL;
            echo '  - Résultat code: ' . (\$s->scrrecsea ?? 'NULL') . PHP_EOL;
            echo '  - Résultat libellé: ' . (\$s->scrcptsea ?? 'NULL') . PHP_EOL;
        }
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔹 Étape 3 : Tester la vue senateurs_votes"
php artisan tinker --execute="
try {
    \$votes = DB::select('SELECT COUNT(*) as total FROM senateurs_votes WHERE senateur_matricule = \\'$MATRICULE\\'');
    echo 'Vue senateurs_votes pour $MATRICULE : ' . \$votes[0]->total . PHP_EOL;
    
    if (\$votes[0]->total > 0) {
        \$sample = DB::select('SELECT id, senateur_matricule, scrutin_id, position, date_vote, intitule FROM senateurs_votes WHERE senateur_matricule = \\'$MATRICULE\\' LIMIT 1');
        echo '  Exemple : Scrutin ' . \$sample[0]->scrutin_id . ' - Position: ' . \$sample[0]->position . ' - Date: ' . \$sample[0]->date_vote . PHP_EOL;
        echo '  Intitulé: ' . substr(\$sample[0]->intitule ?? 'NULL', 0, 60) . '...' . PHP_EOL;
    } else {
        echo '⚠️  Aucun vote dans la vue' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur vue : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔹 Étape 4 : Vérifier les positions de vote (codes)"
php artisan tinker --execute="
try {
    \$positions = DB::select('
        SELECT DISTINCT posvotcod, COUNT(*) as total 
        FROM senat_senateurs_votes 
        GROUP BY posvotcod 
        ORDER BY total DESC
        LIMIT 10
    ');
    
    echo 'Positions de vote disponibles :' . PHP_EOL;
    foreach (\$positions as \$p) {
        echo '  - ' . (\$p->posvotcod ?? 'NULL') . ' : ' . \$p->total . ' votes' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# ============================================================================
# 3. RÉSUMÉ & DIAGNOSTIC
# ============================================================================
echo "📊 3. RÉSUMÉ & DIAGNOSTIC"
echo "-------------------------"

php artisan tinker --execute="
echo '🔍 Vérifications finales :' . PHP_EOL;
echo '' . PHP_EOL;

// 1. Amendements
try {
    \$amd_raw = DB::select('SELECT COUNT(*) as total FROM senat_ameli_amd');
    \$amd_vue = DB::select('SELECT COUNT(*) as total FROM amendements_senat');
    \$amd_sen = DB::select('SELECT COUNT(*) as total FROM amendements_senat WHERE senateur_matricule = \\'$MATRICULE\\'');
    
    echo '📝 AMENDEMENTS :' . PHP_EOL;
    echo '  - Total raw (senat_ameli_amd): ' . \$amd_raw[0]->total . PHP_EOL;
    echo '  - Total vue (amendements_senat): ' . \$amd_vue[0]->total . PHP_EOL;
    echo '  - Pour $MATRICULE: ' . \$amd_sen[0]->total . PHP_EOL;
    
    if (\$amd_vue[0]->total == 0) {
        echo '  ❌ PROBLÈME : Vue vide alors que raw a des données' . PHP_EOL;
    } elseif (\$amd_sen[0]->total == 0) {
        echo '  ⚠️  ATTENTION : Pas d\\'amendements pour ce sénateur' . PHP_EOL;
    } else {
        echo '  ✅ OK' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur amendements : ' . \$e->getMessage() . PHP_EOL;
}

echo '' . PHP_EOL;

// 2. Votes
try {
    \$votes_raw = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_votes');
    \$votes_vue = DB::select('SELECT COUNT(*) as total FROM senateurs_votes');
    \$votes_sen = DB::select('SELECT COUNT(*) as total FROM senateurs_votes WHERE senateur_matricule = \\'$MATRICULE\\'');
    
    echo '🗳️  VOTES :' . PHP_EOL;
    echo '  - Total raw (senat_senateurs_votes): ' . \$votes_raw[0]->total . PHP_EOL;
    echo '  - Total vue (senateurs_votes): ' . \$votes_vue[0]->total . PHP_EOL;
    echo '  - Pour $MATRICULE: ' . \$votes_sen[0]->total . PHP_EOL;
    
    if (\$votes_vue[0]->total == 0) {
        echo '  ❌ PROBLÈME : Vue vide alors que raw a des données' . PHP_EOL;
    } elseif (\$votes_sen[0]->total == 0) {
        echo '  ⚠️  ATTENTION : Pas de votes pour ce sénateur' . PHP_EOL;
    } else {
        echo '  ✅ OK' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur votes : ' . \$e->getMessage() . PHP_EOL;
}

echo '' . PHP_EOL;

// 3. Scrutins
try {
    \$scr_raw = DB::select('SELECT COUNT(*) as total FROM senat_senateurs_scr');
    \$scr_vue = DB::select('SELECT COUNT(*) as total FROM senateurs_scrutins');
    
    echo '📊 SCRUTINS :' . PHP_EOL;
    echo '  - Total raw (senat_senateurs_scr): ' . \$scr_raw[0]->total . PHP_EOL;
    echo '  - Total vue (senateurs_scrutins): ' . \$scr_vue[0]->total . PHP_EOL;
    
    if (\$scr_vue[0]->total == 0) {
        echo '  ❌ PROBLÈME : Vue vide alors que raw a des données' . PHP_EOL;
    } else {
        echo '  ✅ OK' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur scrutins : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""
echo "✅ TEST TERMINÉ !"
echo ""
echo "💡 Actions selon résultats :"
echo "  - Si vue vide + raw plein → Problème de migration/vue SQL"
echo "  - Si 0 pour sénateur → Vérifier mapping matricule"
echo "  - Si positions = codes → Vérifier mapping dans controller"

