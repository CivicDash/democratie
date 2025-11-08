#!/bin/bash

# Script d'enrichissement COMPLET des députés et sénateurs
# Importe : votes, interventions, questions
# À exécuter depuis la racine du projet ou depuis /scripts/

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

echo "========================================="
echo "🏛️  ENRICHISSEMENT COMPLET"
echo "    Votes + Interventions + Questions"
echo "========================================="
echo ""

echo "⚠️  ATTENTION : Import de TOUTES les données"
echo "   - Votes détaillés"
echo "   - Interventions en séance"
echo "   - Questions au gouvernement"
echo ""
echo "⏱️  Durée estimée :"
echo "   - Députés (575) : ~20 minutes"
echo "   - Sénateurs (348) : ~12 minutes"
echo "   TOTAL : ~32 minutes"
echo ""

read -p "Continuer ? (y/n): " response

if [[ ! "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "❌ Annulé."
    exit 0
fi

echo ""
echo "========================================="
echo "📊 1/5 - Vérification des migrations"
echo "========================================="
echo ""

echo "🔍 Vérification des tables nécessaires..."
TABLES_EXIST=$(docker compose exec postgres psql -U civicdash -d civicdash -t -c "
SELECT COUNT(*) 
FROM information_schema.tables 
WHERE table_schema = 'public' 
AND table_name IN ('votes_deputes', 'interventions_parlementaires', 'questions_gouvernement');
" | tr -d ' ')

if [ "$TABLES_EXIST" != "3" ]; then
    echo "⚠️  Tables manquantes. Lancement des migrations..."
    docker compose exec app php artisan migrate --force
    echo "✅ Migrations terminées"
else
    echo "✅ Tables déjà présentes"
fi

echo ""
echo "========================================="
echo "📊 2/5 - État initial"
echo "========================================="

docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Députés' as type,
    COUNT(*) as elus,
    (SELECT COUNT(*) FROM votes_deputes vd 
     JOIN deputes_senateurs ds ON ds.id = vd.depute_senateur_id 
     WHERE ds.source = 'assemblee') as votes,
    (SELECT COUNT(*) FROM interventions_parlementaires ip 
     JOIN deputes_senateurs ds ON ds.id = ip.depute_senateur_id 
     WHERE ds.source = 'assemblee') as interventions
FROM deputes_senateurs 
WHERE source = 'assemblee'
UNION ALL
SELECT 
    'Sénateurs' as type,
    COUNT(*) as elus,
    (SELECT COUNT(*) FROM votes_deputes vd 
     JOIN deputes_senateurs ds ON ds.id = vd.depute_senateur_id 
     WHERE ds.source = 'senat') as votes,
    (SELECT COUNT(*) FROM interventions_parlementaires ip 
     JOIN deputes_senateurs ds ON ds.id = ip.depute_senateur_id 
     WHERE ds.source = 'senat') as interventions
FROM deputes_senateurs 
WHERE source = 'senat';
"

echo ""
echo "========================================="
echo "📥 3/5 - Enrichissement DÉPUTÉS"
echo "========================================="
echo ""
echo "🔄 Lancement... (pause de 2s entre chaque député)"
echo ""

docker compose exec app php artisan enrich:deputes-votes

echo ""
echo "========================================="
echo "📥 4/5 - Enrichissement SÉNATEURS"
echo "========================================="
echo ""
echo "🔄 Lancement... (pause de 2s entre chaque sénateur)"
echo ""

docker compose exec app php artisan enrich:senateurs-votes

echo ""
echo "========================================="
echo "📊 5/5 - Résultat final"
echo "========================================="
echo ""

docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Députés' as type,
    COUNT(DISTINCT ds.id) as elus,
    COUNT(DISTINCT vd.id) as votes,
    COUNT(DISTINCT ip.id) as interventions,
    COUNT(DISTINCT qg.id) as questions
FROM deputes_senateurs ds
LEFT JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
LEFT JOIN interventions_parlementaires ip ON ip.depute_senateur_id = ds.id
LEFT JOIN questions_gouvernement qg ON qg.depute_senateur_id = ds.id
WHERE ds.source = 'assemblee'
UNION ALL
SELECT 
    'Sénateurs' as type,
    COUNT(DISTINCT ds.id) as elus,
    COUNT(DISTINCT vd.id) as votes,
    COUNT(DISTINCT ip.id) as interventions,
    COUNT(DISTINCT qg.id) as questions
FROM deputes_senateurs ds
LEFT JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
LEFT JOIN interventions_parlementaires ip ON ip.depute_senateur_id = ds.id
LEFT JOIN questions_gouvernement qg ON qg.depute_senateur_id = ds.id
WHERE ds.source = 'senat';
"

echo ""
echo "📋 Top 5 députés les plus actifs (votes):"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    ds.nom_complet,
    ds.groupe_politique,
    COUNT(vd.id) as nb_votes
FROM deputes_senateurs ds
JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
WHERE ds.source = 'assemblee'
GROUP BY ds.id, ds.nom_complet, ds.groupe_politique
ORDER BY nb_votes DESC
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Enrichissement COMPLET terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "   1. Consulter les votes d'un député : API ou base de données"
echo "   2. Analyser les positions politiques par thématique"
echo "   3. Comparer les votes entre groupes"
echo "   4. Afficher l'activité parlementaire sur le front"
echo "========================================="

