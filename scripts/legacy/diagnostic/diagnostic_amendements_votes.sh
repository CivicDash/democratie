#!/bin/bash

# Script de diagnostic complet pour les amendements et votes
# Teste les corrections apportées aux modèles et vues

cd /home/kevin/www/demoscratos || exit 1

echo "🔍 DIAGNOSTIC COMPLET - AMENDEMENTS & VOTES"
echo "============================================"
echo ""

# ============================================================================
# 1. TEST AMENDEMENTS DÉPUTÉS (AN)
# ============================================================================
echo "📊 1. AMENDEMENTS DÉPUTÉS (AN)"
echo "------------------------------"

php artisan tinker --execute="
try {
    // Total amendements
    \$total = \App\Models\AmendementAN::count();
    echo '📝 Total amendements AN : ' . \$total . PHP_EOL;
    
    if (\$total > 0) {
        // Codes sort distincts
        \$codes = DB::select('SELECT DISTINCT sort_code, sort_libelle, COUNT(*) as nb FROM amendements_an GROUP BY sort_code, sort_libelle ORDER BY nb DESC LIMIT 10');
        echo PHP_EOL . 'Codes sort distincts :' . PHP_EOL;
        foreach (\$codes as \$c) {
            echo '  - ' . (\$c->sort_code ?? 'NULL') . ' (' . (\$c->sort_libelle ?? 'NULL') . ') : ' . \$c->nb . PHP_EOL;
        }
        
        // Test scopes
        \$adoptes = \App\Models\AmendementAN::adoptes()->count();
        \$rejetes = \App\Models\AmendementAN::rejetes()->count();
        \$retires = \App\Models\AmendementAN::retires()->count();
        
        echo PHP_EOL . 'Scopes :' . PHP_EOL;
        echo '  - Adoptés (scope) : ' . \$adoptes . PHP_EOL;
        echo '  - Rejetés (scope) : ' . \$rejetes . PHP_EOL;
        echo '  - Retirés (scope) : ' . \$retires . PHP_EOL;
        
        // Test pour un député
        \$depute = \App\Models\ActeurAN::first();
        if (\$depute) {
            \$nbAmds = \App\Models\AmendementAN::where('auteur_acteur_ref', \$depute->uid)->count();
            echo PHP_EOL . 'Test député ' . \$depute->nom . ' : ' . \$nbAmds . ' amendements' . PHP_EOL;
        }
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# ============================================================================
# 2. TEST AMENDEMENTS SÉNATEURS
# ============================================================================
echo "📊 2. AMENDEMENTS SÉNATEURS"
echo "---------------------------"

php artisan tinker --execute="
try {
    // Total amendements vue
    \$total = DB::select('SELECT COUNT(*) as total FROM amendements_senat');
    echo '📝 Total amendements Sénat (vue) : ' . \$total[0]->total . PHP_EOL;
    
    if (\$total[0]->total > 0) {
        // Codes sort distincts
        \$codes = DB::select('SELECT DISTINCT sort_code, sort_libelle, COUNT(*) as nb FROM amendements_senat GROUP BY sort_code, sort_libelle ORDER BY nb DESC LIMIT 10');
        echo PHP_EOL . 'Codes sort distincts :' . PHP_EOL;
        foreach (\$codes as \$c) {
            echo '  - ' . (\$c->sort_code ?? 'NULL') . ' (' . (\$c->sort_libelle ?? 'NULL') . ') : ' . \$c->nb . PHP_EOL;
        }
        
        // Test liaison sénateur via matricule
        \$liaisons = DB::select('
            SELECT 
                a.senateur_matricule,
                s.nom_usuel,
                COUNT(*) as nb_amendements
            FROM amendements_senat a
            LEFT JOIN senateurs s ON a.senateur_matricule = s.matricule
            WHERE s.matricule IS NOT NULL
            GROUP BY a.senateur_matricule, s.nom_usuel
            ORDER BY nb_amendements DESC
            LIMIT 5
        ');
        
        echo PHP_EOL . 'Top 5 sénateurs avec amendements :' . PHP_EOL;
        foreach (\$liaisons as \$l) {
            echo '  - ' . \$l->nom_usuel . ' (' . \$l->senateur_matricule . ') : ' . \$l->nb_amendements . ' amendements' . PHP_EOL;
        }
        
        // Test via modèle Eloquent
        \$senateur = \App\Models\Senateur::where('etat', 'ACTIF')->first();
        if (\$senateur) {
            \$nbAmds = \$senateur->amendementsSenat()->count();
            echo PHP_EOL . 'Test sénateur ' . \$senateur->nom_usuel . ' (via relation) : ' . \$nbAmds . ' amendements' . PHP_EOL;
        }
    } else {
        echo '⚠️  Vue amendements_senat vide - vérifier import AMELI' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# ============================================================================
# 3. TEST VOTES SÉNATEURS
# ============================================================================
echo "📊 3. VOTES SÉNATEURS"
echo "---------------------"

php artisan tinker --execute="
try {
    // Total votes vue
    \$total = DB::select('SELECT COUNT(*) as total FROM senateurs_votes');
    echo '🗳️  Total votes Sénat (vue) : ' . \$total[0]->total . PHP_EOL;
    
    if (\$total[0]->total > 0) {
        // Positions distinctes
        \$positions = DB::select('SELECT DISTINCT position, COUNT(*) as nb FROM senateurs_votes GROUP BY position ORDER BY nb DESC');
        echo PHP_EOL . 'Positions distinctes :' . PHP_EOL;
        foreach (\$positions as \$p) {
            echo '  - ' . (\$p->position ?? 'NULL') . ' : ' . \$p->nb . PHP_EOL;
        }
        
        // Résultats scrutins
        \$resultats = DB::select('SELECT DISTINCT resultat_scrutin, COUNT(*) as nb FROM senateurs_votes GROUP BY resultat_scrutin ORDER BY nb DESC');
        echo PHP_EOL . 'Résultats scrutins :' . PHP_EOL;
        foreach (\$resultats as \$r) {
            echo '  - ' . (\$r->resultat_scrutin ?? 'NULL') . ' : ' . \$r->nb . PHP_EOL;
        }
        
        // Test via modèle Eloquent
        \$senateur = \App\Models\Senateur::where('etat', 'ACTIF')->first();
        if (\$senateur) {
            \$nbVotes = \$senateur->votesSenat()->count();
            echo PHP_EOL . 'Test sénateur ' . \$senateur->nom_usuel . ' (via relation) : ' . \$nbVotes . ' votes' . PHP_EOL;
        }
    } else {
        echo '⚠️  Vue senateurs_votes vide - vérifier import sénateurs' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# ============================================================================
# 4. TEST SCRUTINS SÉNAT
# ============================================================================
echo "📊 4. SCRUTINS SÉNAT"
echo "--------------------"

php artisan tinker --execute="
try {
    // Total scrutins vue
    \$total = DB::select('SELECT COUNT(*) as total FROM senateurs_scrutins');
    echo '📋 Total scrutins Sénat (vue) : ' . \$total[0]->total . PHP_EOL;
    
    if (\$total[0]->total > 0) {
        // Résultats distincts
        \$resultats = DB::select('SELECT DISTINCT resultat, COUNT(*) as nb FROM senateurs_scrutins GROUP BY resultat ORDER BY nb DESC');
        echo PHP_EOL . 'Résultats distincts :' . PHP_EOL;
        foreach (\$resultats as \$r) {
            echo '  - ' . (\$r->resultat ?? 'NULL') . ' : ' . \$r->nb . PHP_EOL;
        }
        
        // Exemple scrutin avec stats
        \$exemple = DB::select('SELECT id, intitule, pour, contre, votants, resultat FROM senateurs_scrutins WHERE pour > 0 LIMIT 1');
        if (count(\$exemple) > 0) {
            \$s = \$exemple[0];
            echo PHP_EOL . 'Exemple scrutin :' . PHP_EOL;
            echo '  - ID: ' . \$s->id . PHP_EOL;
            echo '  - Intitulé: ' . substr(\$s->intitule ?? '', 0, 60) . '...' . PHP_EOL;
            echo '  - Pour: ' . \$s->pour . PHP_EOL;
            echo '  - Contre: ' . \$s->contre . PHP_EOL;
            echo '  - Votants: ' . \$s->votants . PHP_EOL;
            echo '  - Résultat: ' . \$s->resultat . PHP_EOL;
        }
    } else {
        echo '⚠️  Vue senateurs_scrutins vide - vérifier import sénateurs' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""

# ============================================================================
# 5. RÉSUMÉ
# ============================================================================
echo "📊 5. RÉSUMÉ"
echo "------------"

php artisan tinker --execute="
echo '📋 RÉSUMÉ DES DONNÉES :' . PHP_EOL;
echo '' . PHP_EOL;

try {
    // AN
    \$amdAN = \App\Models\AmendementAN::count();
    \$amdAN_ado = \App\Models\AmendementAN::adoptes()->count();
    echo '🏛️  Amendements AN : ' . \$amdAN . ' (dont ' . \$amdAN_ado . ' adoptés)' . PHP_EOL;
} catch (Exception \$e) {
    echo '🏛️  Amendements AN : ERREUR - ' . \$e->getMessage() . PHP_EOL;
}

try {
    // Sénat amendements
    \$amdSenat = DB::select('SELECT COUNT(*) as total FROM amendements_senat');
    echo '🏰 Amendements Sénat : ' . \$amdSenat[0]->total . PHP_EOL;
} catch (Exception \$e) {
    echo '🏰 Amendements Sénat : ERREUR - ' . \$e->getMessage() . PHP_EOL;
}

try {
    // Sénat votes
    \$votesSenat = DB::select('SELECT COUNT(*) as total FROM senateurs_votes');
    echo '🗳️  Votes Sénat : ' . \$votesSenat[0]->total . PHP_EOL;
} catch (Exception \$e) {
    echo '🗳️  Votes Sénat : ERREUR - ' . \$e->getMessage() . PHP_EOL;
}

try {
    // Sénat scrutins
    \$scrutinsSenat = DB::select('SELECT COUNT(*) as total FROM senateurs_scrutins');
    echo '📋 Scrutins Sénat : ' . \$scrutinsSenat[0]->total . PHP_EOL;
} catch (Exception \$e) {
    echo '📋 Scrutins Sénat : ERREUR - ' . \$e->getMessage() . PHP_EOL;
}

try {
    // Sénateurs actifs
    \$senateurs = \App\Models\Senateur::where('etat', 'ACTIF')->count();
    echo '👥 Sénateurs actifs : ' . \$senateurs . PHP_EOL;
} catch (Exception \$e) {
    echo '👥 Sénateurs actifs : ERREUR - ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo ""
echo "✅ DIAGNOSTIC TERMINÉ !"
echo ""
echo "💡 Si des données sont à 0 ou en erreur :"
echo "  - Vérifier que les imports ont été effectués"
echo "  - Vérifier que les migrations ont créé les vues SQL"
echo "  - Lancer : php artisan migrate --force"

