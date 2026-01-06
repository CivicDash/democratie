#!/bin/bash
# Import complet des budgets communaux depuis OFGL
# Usage: ./scripts/import-ofgl-complet.sh [annee]

ANNEE=${1:-2024}
LOGFILE="/opt/civicdash/storage/logs/ofgl-import-${ANNEE}.log"

echo "🚀 Import OFGL complet - Année ${ANNEE}" | tee -a "$LOGFILE"
echo "📋 Log: $LOGFILE"
echo "⏰ Début: $(date)" | tee -a "$LOGFILE"

# Liste des 101 départements français
DEPARTEMENTS=(
    01 02 03 04 05 06 07 08 09 10
    11 12 13 14 15 16 17 18 19 2A 2B
    21 22 23 24 25 26 27 28 29 30
    31 32 33 34 35 36 37 38 39 40
    41 42 43 44 45 46 47 48 49 50
    51 52 53 54 55 56 57 58 59 60
    61 62 63 64 65 66 67 68 69 70
    71 72 73 74 75 76 77 78 79 80
    81 82 83 84 85 86 87 88 89 90
    91 92 93 94 95
    971 972 973 974 976
)

TOTAL=${#DEPARTEMENTS[@]}
CURRENT=0

for DEPT in "${DEPARTEMENTS[@]}"; do
    CURRENT=$((CURRENT + 1))
    echo "" | tee -a "$LOGFILE"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" | tee -a "$LOGFILE"
    echo "📍 Département ${DEPT} (${CURRENT}/${TOTAL})" | tee -a "$LOGFILE"
    echo "⏰ $(date '+%H:%M:%S')" | tee -a "$LOGFILE"
    
    cd /opt/civicdash && docker compose exec -T app php artisan import:ofgl-budgets \
        --departement="${DEPT}" \
        --annee="${ANNEE}" \
        --force 2>&1 | tee -a "$LOGFILE"
    
    # Petite pause entre les départements
    sleep 2
done

echo "" | tee -a "$LOGFILE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" | tee -a "$LOGFILE"
echo "✅ Import terminé !" | tee -a "$LOGFILE"
echo "⏰ Fin: $(date)" | tee -a "$LOGFILE"

# Statistiques finales
echo "" | tee -a "$LOGFILE"
docker compose exec -T app php artisan tinker --execute="
\$count = \App\Models\CommuneBudget::where('annee', ${ANNEE})->count();
\$size = \Illuminate\Support\Facades\DB::selectOne(\"SELECT pg_size_pretty(pg_total_relation_size('commune_budgets')) as taille\");
echo \"📊 Total budgets ${ANNEE}: \" . number_format(\$count) . \"\n\";
echo \"💾 Taille table: \" . \$size->taille . \"\n\";
" 2>&1 | tee -a "$LOGFILE"
