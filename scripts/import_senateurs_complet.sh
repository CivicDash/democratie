#!/bin/bash

echo "========================================="
echo "🏛️  IMPORT COMPLET - SÉNAT"
echo "========================================="
echo ""
echo "Ce script va importer depuis data.senat.fr :"
echo "  - Sénateurs (actifs + historique)"
echo "  - Historique groupes politiques"
echo "  - Commissions permanentes"
echo "  - Mandats (sénateur, député, européen, métropolitain, municipal)"
echo "  - Études et formations"
echo ""
echo "⏱️  Durée estimée : 5-10 minutes"
echo ""
read -p "Continuer ? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Import annulé"
    exit 1
fi

echo "========================================="
echo "📦 Import en cours..."
echo "========================================="
docker compose exec app php artisan import:senateurs-complet

echo ""
echo "========================================="
echo "📊 Statistiques finales"
echo "========================================="
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Sénateurs' as entite,
    COUNT(*) as total,
    COUNT(*) FILTER (WHERE etat = 'ACTIF') as actifs,
    COUNT(*) FILTER (WHERE etat = 'ANCIEN') as anciens
FROM senateurs
UNION ALL
SELECT 
    'Groupes politiques',
    COUNT(*),
    COUNT(*) FILTER (WHERE date_fin IS NULL),
    COUNT(*) FILTER (WHERE date_fin IS NOT NULL)
FROM senateurs_historique_groupes
UNION ALL
SELECT 
    'Commissions',
    COUNT(*),
    COUNT(*) FILTER (WHERE date_fin IS NULL),
    COUNT(*) FILTER (WHERE date_fin IS NOT NULL)
FROM senateurs_commissions
UNION ALL
SELECT 
    'Mandats',
    COUNT(*),
    COUNT(*) FILTER (WHERE date_fin IS NULL),
    COUNT(*) FILTER (WHERE date_fin IS NOT NULL)
FROM senateurs_mandats
UNION ALL
SELECT 
    'Études',
    COUNT(*),
    NULL,
    NULL
FROM senateurs_etudes;
"

echo ""
echo "📊 Top 5 groupes politiques actuels"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    groupe_politique,
    COUNT(*) as nb_senateurs
FROM senateurs
WHERE etat = 'ACTIF'
  AND groupe_politique IS NOT NULL
GROUP BY groupe_politique
ORDER BY nb_senateurs DESC
LIMIT 5;
"

echo ""
echo "========================================="
echo "✅ Import Sénat terminé !"
echo "========================================="
echo ""
echo "💡 Prochaines étapes :"
echo "  1. Tester les données"
echo "  2. Créer les API endpoints"
echo "  3. Finaliser la carte interactive"
echo ""

