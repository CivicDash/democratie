# ⚡ REPRISE RAPIDE - VÉRIFICATIONS LIAISONS

## 🎯 TU ES LÀ
Tu as demandé de vérifier :
1. **Liaison amendements / sénateur**
2. **Liaison vote / détails vote** (pour/contre/abstentions)

## 🚀 COMMANDES RAPIDES

### Option A : Utiliser Docker (si configuré)
```bash
cd /home/kevin/www/demoscratos
docker compose up -d
docker compose exec app ./scripts/test_liaisons_amendements_votes.sh
```

### Option B : Local sans Docker
```bash
cd /home/kevin/www/demoscratos

# Vérifier si import SQL terminé
ps aux | grep "import:senat-sql"

# Si terminé, lancer le test
./scripts/test_liaisons_amendements_votes.sh
```

### Option C : Corriger DB_HOST puis tester
```bash
cd /home/kevin/www/demoscratos

# Mettre à jour .env pour PostgreSQL local
sed -i 's/DB_HOST=db/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_DATABASE=civicdash/DB_DATABASE=demoscratos_local/' .env
sed -i 's/DB_USERNAME=civicdash/DB_USERNAME=demoscratos/' .env
sed -i 's/DB_PASSWORD=secret/DB_PASSWORD=demoscratos/' .env

# Créer base locale si besoin
sudo -u postgres psql -c "CREATE DATABASE demoscratos_local;"
sudo -u postgres psql -c "CREATE USER demoscratos WITH PASSWORD 'demoscratos';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE demoscratos_local TO demoscratos;"

# Clear cache
php artisan config:clear

# Lancer migrations
php artisan migrate --force

# Importer données Sénat
php artisan import:senat-sql senateurs --fresh --no-interaction

# Lancer test
./scripts/test_liaisons_amendements_votes.sh
```

---

## 📊 CE QUE LE TEST VA MONTRER

### 1. Amendements → Sénateur
```
✅ Trouvé dans sen_ameli :
  senid: 1234
  matricule: 19954N
  nom: Catherine Belrhiti

✅ Amendements trouvés (senat_ameli_amdsen) : 42
  Exemple AMD ID: 56789 - Auteur: Catherine Belrhiti

✅ Vue amendements_senat pour 19954N : 42
  Exemple : AMD 123 - Belrhiti - Sort: Adopté

✅ Jointure fonctionne ! Exemples :
  - AMD 123 (ID: 56789) - senid: 1234 → matricule: 19954N
```

### 2. Votes → Scrutin
```
✅ Votes dans senat_senateurs_votes : 245
  Exemple : Vote ID 12345 - Scrutin: 789 - Position: P

✅ Scrutin trouvé :
  - Numéro: 42
  - Date: 2024-11-15
  - Pour: 180
  - Contre: 120
  - Abstentions: 20
  - Votants: 320
  - Résultat code: ADO
  - Résultat libellé: Adopté

✅ Vue senateurs_votes pour 19954N : 245
  Exemple : Scrutin 789 - Position: pour - Date: 2024-11-15
```

### 3. Résumé
```
📝 AMENDEMENTS :
  - Total raw (senat_ameli_amd): 52143
  - Total vue (amendements_senat): 52143
  - Pour 19954N: 42
  ✅ OK

🗳️  VOTES :
  - Total raw (senat_senateurs_votes): 534291
  - Total vue (senateurs_votes): 534291
  - Pour 19954N: 245
  ✅ OK

📊 SCRUTINS :
  - Total raw (senat_senateurs_scr): 2456
  - Total vue (senateurs_scrutins): 2456
  ✅ OK
```

---

## ❌ SI PROBLÈMES

### Erreur "could not translate host name"
→ Mauvais DB_HOST, utilise Option C ci-dessus

### Erreur "Table does not exist"
→ Import SQL pas terminé ou migrations pas lancées
```bash
php artisan migrate --force
php artisan import:senat-sql senateurs --fresh
```

### Vue vide (0) alors que raw plein
→ Problème migration vue SQL, à corriger dans :
- `database/migrations/2025_11_21_030800_create_amendements_senat_view.php`
- `database/migrations/2025_11_21_030600_create_senateurs_votes_view.php`

---

## 📁 DOCUMENTS RÉFÉRENCE

1. **RAPPORT_FINAL_SESSION_21NOV2025.md** : Rapport complet
2. **SYNTHESE_SESSION_21NOV2025.md** : Synthèse session
3. **COMMANDES_TEST_LIAISONS.md** : Guide détaillé tests

---

## 🎯 RÉSULTAT ATTENDU

Si tout fonctionne :
- ✅ Amendements sénateurs affichés avec matricule correct
- ✅ Votes affichent position + détails scrutin
- ✅ Pour/contre/abstentions visibles
- ✅ Résultat scrutin (adopté/rejeté) affiché

---

**28 commits prêts | Script test créé | Tout documenté**  
**Bon courage ! 💪**

