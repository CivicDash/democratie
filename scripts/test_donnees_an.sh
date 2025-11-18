#!/bin/bash

echo "========================================="
echo "📊 STATISTIQUES - Données AN"
echo "========================================="
echo ""

echo "📈 1. Vue d'ensemble"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Acteurs' as entite, 
    COUNT(*) as total,
    COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE) as ajd
FROM acteurs_an
UNION ALL
SELECT 'Organes', COUNT(*), COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE)
FROM organes_an
UNION ALL
SELECT 'Mandats', COUNT(*), COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE)
FROM mandats_an
UNION ALL
SELECT 'Scrutins', COUNT(*), COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE)
FROM scrutins_an
UNION ALL
SELECT 'Votes individuels', COUNT(*), COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE)
FROM votes_individuels_an
UNION ALL
SELECT 'Amendements', COUNT(*), COUNT(*) FILTER (WHERE created_at::date = CURRENT_DATE)
FROM amendements_an;
"

echo ""
echo "📈 2. Statistiques par législature"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    legislature,
    COUNT(DISTINCT uid) as nb_scrutins,
    SUM(pour) as total_pour,
    SUM(contre) as total_contre,
    SUM(abstentions) as total_abstentions
FROM scrutins_an
WHERE legislature IS NOT NULL
GROUP BY legislature
ORDER BY legislature DESC
LIMIT 5;
"

echo ""
echo "📈 3. Top 5 groupes politiques (L17)"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    o.libelle_abrege as groupe,
    COUNT(DISTINCT m.acteur_ref) as nb_deputes
FROM organes_an o
JOIN mandats_an m ON m.organe_ref = o.uid
WHERE o.code_type = 'GP'
  AND o.legislature = 17
  AND m.date_fin IS NULL
GROUP BY o.uid, o.libelle_abrege
ORDER BY nb_deputes DESC
LIMIT 5;
"

echo ""
echo "📈 4. Scrutins récents (L17)"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    date_scrutin,
    numero,
    resultat_code,
    LEFT(titre, 80) || '...' as titre_court
FROM scrutins_an
WHERE legislature = 17
ORDER BY date_scrutin DESC
LIMIT 5;
"

echo ""
echo "📈 5. Amendements par état (L17)"
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    etat_code,
    etat_libelle,
    COUNT(*) as nombre
FROM amendements_an
WHERE legislature = 17
  AND etat_code IS NOT NULL
GROUP BY etat_code, etat_libelle
ORDER BY nombre DESC
LIMIT 10;
"

echo ""
echo "========================================="
echo "✅ Statistiques complètes !"
echo "========================================="

