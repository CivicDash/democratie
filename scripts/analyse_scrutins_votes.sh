#!/bin/bash

# Script pour analyser les données scrutins et votes Sénat
# À lancer après avoir importé les données en local

echo "🔍 Analyse Scrutins & Votes Sénat"
echo "=================================="
echo ""

echo "📊 1. Structure table scrutins Sénat :"
php artisan tinker --execute="
\$cols = DB::select(\"SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'senat_senateurs_scr' ORDER BY ordinal_position\");
echo 'Colonnes de senat_senateurs_scr :' . PHP_EOL;
foreach (\$cols as \$col) {
    echo '  - ' . \$col->column_name . ' (' . \$col->data_type . ')' . PHP_EOL;
}
"
echo ""

echo "📊 2. Exemple scrutins (3 premiers) :"
php artisan tinker --execute="
\$scrutins = DB::select(\"SELECT * FROM senat_senateurs_scr LIMIT 3\");
foreach (\$scrutins as \$s) {
    echo 'Scrutin ID: ' . \$s->scrid . PHP_EOL;
    echo '  Numéro: ' . (\$s->scrnum ?? 'NULL') . PHP_EOL;
    echo '  Date: ' . (\$s->scrdat ?? 'NULL') . PHP_EOL;
    echo '  Intitulé: ' . substr(\$s->scrint ?? 'NULL', 0, 60) . '...' . PHP_EOL;
    echo '  Pour: ' . (\$s->scrpou ?? 'NULL') . ' | Contre: ' . (\$s->scrcon ?? 'NULL') . ' | Votants: ' . (\$s->scrvot ?? 'NULL') . PHP_EOL;
    echo '  Résultat code: ' . (\$s->scrrecsea ?? 'NULL') . ' | Résultat libellé: ' . (\$s->scrcptsea ?? 'NULL') . PHP_EOL;
    echo PHP_EOL;
}
"
echo ""

echo "📊 3. Structure table votes individuels :"
php artisan tinker --execute="
\$cols = DB::select(\"SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'senat_senateurs_votes' ORDER BY ordinal_position\");
echo 'Colonnes de senat_senateurs_votes :' . PHP_EOL;
foreach (\$cols as \$col) {
    echo '  - ' . \$col->column_name . ' (' . \$col->data_type . ')' . PHP_EOL;
}
"
echo ""

echo "📊 4. Exemple votes individuels (5 premiers) :"
php artisan tinker --execute="
\$votes = DB::select(\"SELECT * FROM senat_senateurs_votes LIMIT 5\");
foreach (\$votes as \$v) {
    echo 'Vote ID: ' . \$v->votesid . ' | Scrutin: ' . \$v->scrid . ' | Sénateur: ' . \$v->senmat . ' | Position: ' . (\$v->posvotcod ?? 'NULL') . PHP_EOL;
}
"
echo ""

echo "📊 5. Vérifier la vue senateurs_votes :"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senateurs_votes');
    echo 'Total votes dans la vue : ' . \$count[0]->total . PHP_EOL;
    
    \$sample = DB::select('SELECT id, senateur_matricule, scrutin_id, position FROM senateurs_votes LIMIT 3');
    foreach (\$sample as \$v) {
        echo '  - ID: ' . \$v->id . ' | Matricule: ' . \$v->senateur_matricule . ' | Scrutin: ' . \$v->scrutin_id . ' | Position: ' . \$v->position . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur vue senateurs_votes : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "📊 6. Vérifier la vue senateurs_scrutins :"
php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as total FROM senateurs_scrutins');
    echo 'Total scrutins dans la vue : ' . \$count[0]->total . PHP_EOL;
    
    \$sample = DB::select('SELECT id, numero, date_scrutin, intitule, pour, contre, resultat_code FROM senateurs_scrutins LIMIT 3');
    foreach (\$sample as \$s) {
        echo '  - Scrutin ' . \$s->numero . ' du ' . \$s->date_scrutin . PHP_EOL;
        echo '    Pour: ' . \$s->pour . ' | Contre: ' . \$s->contre . ' | Résultat: ' . \$s->resultat_code . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur vue senateurs_scrutins : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "🔍 7. Tester pour un sénateur spécifique (19954N) :"
php artisan tinker --execute="
\$votes = DB::select(\"SELECT COUNT(*) as total FROM senateurs_votes WHERE senateur_matricule = '19954N'\");
echo 'Votes pour Catherine Belrhiti (19954N) : ' . \$votes[0]->total . PHP_EOL;

if (\$votes[0]->total > 0) {
    \$sample = DB::select(\"SELECT * FROM senateurs_votes WHERE senateur_matricule = '19954N' LIMIT 3\");
    foreach (\$sample as \$v) {
        echo '  - Scrutin ' . \$v->scrutin_id . ' : ' . \$v->position . ' (' . \$v->date_vote . ')' . PHP_EOL;
    }
}
"
echo ""

echo "🔍 8. Statistiques générales :"
php artisan tinker --execute="
try {
    \$stats = DB::select(\"
        SELECT 
            posvotcod AS position,
            COUNT(*) AS total
        FROM senat_senateurs_votes
        GROUP BY posvotcod
        ORDER BY total DESC
    \");
    
    echo 'Répartition des positions de vote :' . PHP_EOL;
    foreach (\$stats as \$s) {
        echo '  - ' . (\$s->position ?? 'NULL') . ' : ' . \$s->total . ' votes' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Erreur : ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "✅ Analyse terminée !"
echo ""
echo "💡 Vérifications à faire :"
echo "  1. Les colonnes pour/contre/votants sont bien remplies ?"
echo "  2. Les positions de vote sont cohérentes (pour/contre/abstention) ?"
echo "  3. La vue senateurs_votes mappe bien senmat → matricule ?"
echo "  4. Les scrutins ont un résultat_code exploitable ?"

