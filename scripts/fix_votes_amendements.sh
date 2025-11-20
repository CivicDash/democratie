#!/bin/bash

# Script rapide pour fixer les imports manquants
# À lancer sur le serveur production

PROJECT_ROOT="/opt/civicdash"
cd "$PROJECT_ROOT"

echo "======================================"
echo "🔧 FIX IMPORT VOTES & AMENDEMENTS"
echo "======================================"
echo ""

# 1. Migration pour resultat_code nullable
echo "[1/6] Migration resultat_code nullable..."
docker compose exec -T app php artisan migrate --force

# 2. Ré-import scrutins (complet cette fois)
echo ""
echo "[2/6] Import scrutins AN (L17)..."
docker compose exec -T app php artisan import:scrutins-an --legislature=17 --fresh

# 3. Extraction votes individuels
echo ""
echo "[3/6] Extraction votes individuels..."
docker compose exec -T app php artisan extract:votes-individuels-an --legislature=17 --fresh

# 4. Import amendements
echo ""
echo "[4/6] Import amendements AN..."
docker compose exec -T app php artisan import:amendements-an --fresh

# 5. Import dossiers & textes
echo ""
echo "[5/6] Import dossiers & textes législatifs..."
docker compose exec -T app php artisan import:dossiers-textes-an

# 6. Vérification finale
echo ""
echo "[6/6] Vérification finale..."
echo ""
docker compose exec -T app php artisan tinker --execute="
echo '📊 VÉRIFICATION FINALE';
echo '';
echo '✓ Scrutins : ' . \App\Models\ScrutinAN::count();
echo '✓ Votes individuels : ' . \App\Models\VoteIndividuelAN::count();
echo '✓ Amendements : ' . \App\Models\AmendementAN::count();
echo '✓ Dossiers : ' . \App\Models\DossierLegislatifAN::count();
echo '✓ Textes : ' . \App\Models\TexteLegislatifAN::count();
echo '';
echo '👤 Votes par député (sample) :';
\App\Models\ActeurAN::deputes()->take(3)->get()->each(function(\$a) {
    echo '  - ' . \$a->nom_complet . ' : ' . \$a->votesIndividuels()->count() . ' votes';
});
"

echo ""
echo "======================================"
echo "✅ IMPORT TERMINÉ !"
echo "======================================"

