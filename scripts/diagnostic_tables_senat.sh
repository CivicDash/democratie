#!/bin/bash

# Script de diagnostic des tables SQL Sénat
# Affiche la structure de toutes les tables importantes

echo "🔍 DIAGNOSTIC DES TABLES SQL SÉNAT"
echo "=================================="
echo ""

# Fonction pour afficher les colonnes d'une table
show_table() {
    local table_name=$1
    echo "📋 Table: $table_name"
    echo "---"
    docker compose exec app php artisan tinker --execute="
\$columns = DB::select(\"SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table_name' ORDER BY ordinal_position LIMIT 30\");
if (count(\$columns) > 0) {
    foreach (\$columns as \$col) {
        echo '  - ' . \$col->column_name . ' (' . \$col->data_type . ')' . PHP_EOL;
    }
} else {
    echo '  ❌ Table non trouvée' . PHP_EOL;
}
" 2>/dev/null
    echo ""
}

# Tables principales
echo "🏛️ SÉNATEURS"
show_table "senat_senateurs_sen"

echo "📜 MANDATS"
show_table "senat_senateurs_elusen"

echo "🏢 COMMISSIONS"
show_table "senat_senateurs_memcom"
show_table "senat_senateurs_org"

echo "👥 GROUPES"
show_table "senat_senateurs_memgrpsen"
show_table "senat_senateurs_grpsen"

echo "📊 SCRUTINS & VOTES"
show_table "senat_senateurs_scr"
show_table "senat_senateurs_votes"

echo "📝 AMENDEMENTS"
show_table "senat_ameli_amd"
show_table "senat_ameli_amdsen"

echo "📑 DOSSIERS"
show_table "senat_dosleg_doc"

echo ""
echo "✅ Diagnostic terminé !"
echo "Vérifiez les colonnes ci-dessus pour corriger les migrations."

