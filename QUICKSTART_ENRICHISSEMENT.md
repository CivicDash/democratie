# 🚀 QUICK START - Enrichissement Parlementaire

## ⚡ Installation Rapide (5 minutes)

### 1️⃣ Pull & Migration
```bash
cd /opt/civicdash
git pull origin main
docker-compose restart app
docker-compose exec app php artisan migrate --force
```

### 2️⃣ Test Rapide (1 minute)
```bash
# Test 1 député
docker-compose exec app php artisan enrich:deputes-votes --limit=1
```

**Résultat attendu :**
```
✅ Enrichissement terminé !
📊 Résumé :
   ✓ 1 députés traités
   📝 500-800 votes importés        ← DOIT ÊTRE > 0 !
   🎤 50-100 interventions importées
   ❓ 20-50 questions importées
```

### 3️⃣ Import Complet (1h10) - Option AUTO 🎯
```bash
bash scripts/enrich_all.sh
```

### 3️⃣ BIS - Import Complet (1h10) - Option MANUELLE
```bash
# Organes (~4 min)
bash scripts/import_organes.sh

# Votes/interventions/questions (~32 min)
bash scripts/enrich_complete.sh

# Amendements (~32 min)
bash scripts/enrich_amendements.sh
```

---

## 📊 Vérification Rapide

```bash
docker-compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    'Votes' as type, COUNT(*) as total FROM votes_deputes
UNION ALL
SELECT 'Interventions', COUNT(*) FROM interventions_parlementaires
UNION ALL
SELECT 'Questions', COUNT(*) FROM questions_gouvernement
UNION ALL
SELECT 'Amendements', COUNT(*) FROM amendements_parlementaires
UNION ALL
SELECT 'Organes', COUNT(*) FROM organes_parlementaires;
"
```

**Résultats attendus :**
| Type | Total Attendu |
|------|---------------|
| Votes | ~200 000 |
| Interventions | ~60 000 |
| Questions | ~25 000 |
| Amendements | ~150 000 |
| Organes | ~60 |

---

## 🎯 Commandes Utiles

### Import partiel (test)
```bash
# Test 10 députés
docker-compose exec app php artisan enrich:deputes-votes --limit=10

# Test amendements (10 parlementaires)
docker-compose exec app php artisan enrich:amendements --limit=10

# Test organes (Assemblée uniquement)
docker-compose exec app php artisan import:organes-parlementaires --source=assemblee
```

### Import complet par source
```bash
# Assemblée uniquement (~16 min)
docker-compose exec app php artisan enrich:deputes-votes
docker-compose exec app php artisan enrich:amendements --source=assemblee

# Sénat uniquement (~12 min)
docker-compose exec app php artisan enrich:senateurs-votes
docker-compose exec app php artisan enrich:amendements --source=senat
```

### Statistiques avancées
```bash
# Top 10 députés les plus actifs
docker-compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 
    ds.nom_complet,
    COUNT(DISTINCT vd.id) as votes,
    COUNT(DISTINCT ip.id) as interventions,
    COUNT(DISTINCT ap.id) as amendements
FROM deputes_senateurs ds
LEFT JOIN votes_deputes vd ON vd.depute_senateur_id = ds.id
LEFT JOIN interventions_parlementaires ip ON ip.depute_senateur_id = ds.id
LEFT JOIN amendements_parlementaires ap ON ap.depute_senateur_id = ds.id
WHERE ds.source = 'assemblee'
GROUP BY ds.id, ds.nom_complet
ORDER BY (votes + interventions + amendements) DESC
LIMIT 10;
"

# Commissions les plus importantes
docker-compose exec postgres psql -U civicdash -d civicdash -c "
SELECT nom, nombre_membres, source
FROM organes_parlementaires
WHERE type = 'commission'
ORDER BY nombre_membres DESC
LIMIT 10;
"
```

---

## 🔥 Scripts Disponibles

| Script | Description | Durée |
|--------|-------------|-------|
| `enrich_all.sh` | **TOUT EN 1 CLICK** | ~1h10 |
| `import_organes.sh` | Organes + membres | ~4 min |
| `enrich_complete.sh` | Votes/interventions/questions | ~32 min |
| `enrich_amendements.sh` | Amendements | ~32 min |
| `test_enrich_votes.sh` | Test 1 député | ~30s |

---

## 📚 Documentation Complète

- **ROADMAP_ENRICHISSEMENT.md** → Phases 1-4 détaillées
- **SESSION_8_NOV_FINAL.md** → Résumé complet de la session
- **PHASE1_RESUME.md** → Phase 1 (votes/interventions/questions/amendements)
- **PHASE2_ORGANES_RESUME.md** → Phase 2 (organes parlementaires)
- **CHANGELOG.md** → Historique des modifications

---

## ❓ En cas de problème

### Erreur : "Table does not exist"
```bash
docker-compose exec app php artisan migrate --force
```

### Erreur : "0 votes importés"
👉 C'est le bug qu'on a corrigé ! Vérifie que tu as bien pull le dernier code :
```bash
git pull origin main
docker-compose restart app
```

### Import trop lent
👉 C'est normal ! Il y a des pauses de 2 secondes entre chaque parlementaire pour respecter l'API.
- 566 députés × 2s = ~19 min
- 336 sénateurs × 2s = ~11 min

### Logs
```bash
# Suivre les logs en temps réel
docker-compose logs -f app

# Voir les dernières erreurs
docker-compose exec app tail -f storage/logs/laravel.log
```

---

## 🎉 C'est tout !

**Tu es prêt ! Lance `bash scripts/enrich_all.sh` et attends ~1h10 ! 🚀**

