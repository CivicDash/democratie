#!/bin/bash

# Script de debug pour tester l'API NosDéputés.fr

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🐛 DEBUG - API NosDéputés.fr"
echo "========================================="
echo ""

echo "📊 1/4 - Test avec un député connu (Éric Ciotti)"
echo "========================================="
curl -s "https://www.nosdeputes.fr/eric-ciotti/json" | jq '.depute | {nom, prenom, slug, votes: (.votes | length), interventions: (.interventions | length), questions: (.questions | length)}'

echo ""
echo ""
echo "📊 2/4 - Récupération d'un député de la base"
echo "========================================="
DEPUTE_INFO=$(docker-compose exec postgres psql -U civicdash -d civicdash -t -c "SELECT nom, prenom FROM deputes_senateurs WHERE source = 'assemblee' AND en_exercice = true LIMIT 1;")
DEPUTE_NOM=$(echo "$DEPUTE_INFO" | awk '{print $1}' | tr -d ' ')
DEPUTE_PRENOM=$(echo "$DEPUTE_INFO" | awk '{print $2}' | tr -d ' ')

echo "Député trouvé : $DEPUTE_PRENOM $DEPUTE_NOM"
echo ""

# Construire le slug
SLUG=$(echo "$DEPUTE_PRENOM-$DEPUTE_NOM" | tr '[:upper:]' '[:lower:]' | sed 's/é/e/g;s/è/e/g;s/ê/e/g;s/à/a/g;s/ù/u/g;s/ô/o/g;s/î/i/g;s/ç/c/g;s/[^a-z-]/-/g;s/--*/-/g;s/^-//;s/-$//')

echo "Slug construit : $SLUG"
echo ""

echo "📊 3/4 - Test API avec ce député"
echo "========================================="
API_RESPONSE=$(curl -s "https://www.nosdeputes.fr/${SLUG}/json")
echo "$API_RESPONSE" | jq '.depute | {nom, prenom, slug, groupe_sigle, votes: (.votes | length), interventions: (.interventions | length), questions: (.questions | length)}' 2>/dev/null

if [ $? -ne 0 ]; then
    echo "❌ Erreur API ou député non trouvé"
    echo ""
    echo "Réponse brute :"
    echo "$API_RESPONSE"
fi

echo ""
echo ""
echo "📊 4/4 - Test de la commande Laravel"
echo "========================================="
docker-compose exec app php artisan tinker --execute="
\$depute = App\Models\DeputeSenateur::where('source', 'assemblee')->where('en_exercice', true)->first();
echo 'Député : ' . \$depute->prenom . ' ' . \$depute->nom . PHP_EOL;
echo 'UID : ' . \$depute->uid . PHP_EOL;
"

echo ""
echo "========================================="
echo "✅ Debug terminé"
echo "========================================="
echo ""
echo "💡 Analyse :"
echo "   - Si l'API retourne des votes/interventions → OK"
echo "   - Si l'API retourne 0 ou erreur → Problème de slug ou API"
echo "   - Vérifier que le slug construit correspond à l'URL NosDéputés"
echo "========================================="

