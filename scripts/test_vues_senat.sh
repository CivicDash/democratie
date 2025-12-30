#!/bin/bash

# Script pour tester les colonnes des tables DOSLEG avant migration

echo "🔍 Diagnostic des colonnes DOSLEG..."

docker compose exec -T app php artisan tinker --execute="
echo '=== senat_dosleg_doc ===\n';
\$columns = DB::select(\"SELECT column_name FROM information_schema.columns WHERE table_name = 'senat_dosleg_doc' ORDER BY ordinal_position LIMIT 30\");
foreach (\$columns as \$col) {
    echo '  - ' . \$col->column_name . '\n';
}

echo '\n=== senat_dosleg_typdoc ===\n';
\$columns = DB::select(\"SELECT column_name FROM information_schema.columns WHERE table_name = 'senat_dosleg_typdoc' ORDER BY ordinal_position LIMIT 20\");
foreach (\$columns as \$col) {
    echo '  - ' . \$col->column_name . '\n';
}

echo '\n=== senat_dosleg_lec ===\n';
\$columns = DB::select(\"SELECT column_name FROM information_schema.columns WHERE table_name = 'senat_dosleg_lec' ORDER BY ordinal_position LIMIT 20\");
foreach (\$columns as \$col) {
    echo '  - ' . \$col->column_name . '\n';
}
"

