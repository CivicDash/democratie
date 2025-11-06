#!/bin/bash
# Script complet de vérification et fix

echo "🔍 Vérification complète CivicDash"
echo "===================================="
echo ""

cd /opt/civicdash

# 1. Rebuild frontend (FIX VITE MANIFEST)
echo "🎨 1/5 - Rebuild frontend..."
docker compose exec -u root app rm -rf /var/www/public/build
docker compose exec -u root app rm -f /var/www/public/hot
docker compose exec -u root app npm run build
echo "✅ Frontend rebuilt"
echo ""

# 2. Vérifier codes postaux
echo "📮 2/5 - Vérification codes postaux..."
POSTAL_COUNT=$(docker compose exec app php artisan tinker --execute="use App\Models\FrenchPostalCode; echo FrenchPostalCode::count();" 2>/dev/null | tail -1 | tr -d '\r\n' || echo "0")
echo "   Total codes postaux : $POSTAL_COUNT"
if [ "$POSTAL_COUNT" -lt "1000" ]; then
    echo "   ⚠️  Import incomplet ou en cours"
    echo "   Vérifier : docker compose logs app | grep -i postal"
else
    echo "   ✅ Import OK"
fi
echo ""

# 3. Vérifier affectation propositions de loi aux thématiques
echo "🏛️ 3/5 - Vérification thématiques..."
docker compose exec app php artisan tinker --execute="
use App\Models\PropositionLoi;
use App\Models\ThematiqueLegislation;
\$totalProps = PropositionLoi::count();
\$propsWithTheme = PropositionLoi::has('thematiques')->count();
\$totalThemes = ThematiqueLegislation::count();
echo 'Propositions de loi : ' . \$totalProps . '\n';
echo 'Avec thématiques : ' . \$propsWithTheme . '\n';
echo 'Thématiques disponibles : ' . \$totalThemes . '\n';
if (\$propsWithTheme == 0 && \$totalProps > 0) {
    echo '⚠️  Aucune proposition n\'a de thématique assignée\n';
} else {
    echo '✅ Affectation OK\n';
}
" 2>&1 | grep -v "UnexpectedValueException" | grep -v "telescope"
echo ""

# 4. Vérifier scrutins et votes
echo "🗳️ 4/5 - Vérification scrutins..."
docker compose exec app php artisan tinker --execute="
use App\Models\Ballot;
use App\Models\BallotVote;
\$totalBallots = Ballot::count();
\$openBallots = Ballot::where('status', 'open')->count();
\$totalVotes = BallotVote::count();
echo 'Scrutins totaux : ' . \$totalBallots . '\n';
echo 'Scrutins ouverts : ' . \$openBallots . '\n';
echo 'Votes citoyens : ' . \$totalVotes . '\n';
if (\$totalBallots > 0 && \$totalVotes == 0) {
    echo '⚠️  Aucun vote citoyen enregistré\n';
} else {
    echo '✅ Votes OK\n';
}
" 2>&1 | grep -v "UnexpectedValueException" | grep -v "telescope"
echo ""

# 5. Clear caches et redémarrer
echo "🧹 5/5 - Clear caches et redémarrage..."
docker compose exec app php artisan config:clear 2>&1 | grep "INFO" || true
docker compose exec app php artisan view:clear 2>&1 | grep "INFO" || true
docker compose restart app nginx queue
echo "✅ Services redémarrés"
echo ""

echo "===================================="
echo "✅ Vérification terminée !"
echo ""
echo "📝 Prochaines étapes :"
echo "   1. Tester l'application sur demo.objectif2027.fr"
echo "   2. Vérifier que Topics/Show.vue fonctionne"
echo "   3. Si codes postaux < 1000, relancer l'import"
echo "   4. Si thématiques manquantes, lancer le seeder"
