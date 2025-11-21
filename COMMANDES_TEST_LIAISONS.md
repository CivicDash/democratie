# 🚀 COMMANDES À EXÉCUTER - TESTS LOCAUX SÉNAT

## 📝 CONTEXTE
Tu as demandé de vérifier :
1. Liaison amendements → sénateurs
2. Liaison votes → détails scrutins (pour/contre/abstentions)

**Problème actuel** : `.env` a `DB_HOST=db` (Docker) mais PostgreSQL tourne en local

---

## ⚡ OPTION 1 : UTILISER DOCKER (RECOMMANDÉ)

Si tu as un `docker-compose.yml` configuré, utilise Docker :

```bash
cd /home/kevin/www/demoscratos

# 1. Vérifier que Docker tourne
docker compose ps

# 2. Lancer les services si arrêtés
docker compose up -d

# 3. Vérifier la connexion
docker compose exec app php artisan tinker --execute="echo 'Connexion OK : ' . DB::connection()->getDatabaseName() . PHP_EOL;"

# 4. Lancer le test des liaisons
docker compose exec app ./scripts/test_liaisons_amendements_votes.sh

# 5. Si l'import SQL tourne toujours, attendre
docker compose exec app ps aux | grep "import:senat-sql"
```

---

## ⚡ OPTION 2 : CONFIGURER POSTGRESQL LOCAL

Si tu préfères tester en local sans Docker :

```bash
cd /home/kevin/www/demoscratos

# 1. Créer base + utilisateur PostgreSQL
sudo -u postgres psql << 'EOF'
CREATE DATABASE demoscratos_local;
CREATE USER demoscratos WITH PASSWORD 'demoscratos';
GRANT ALL PRIVILEGES ON DATABASE demoscratos_local TO demoscratos;
\q
EOF

# 2. Mettre à jour .env
cat > .env << 'EOF'
APP_NAME=DemosCratos
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=demoscratos_local
DB_USERNAME=demoscratos
DB_PASSWORD=demoscratos
EOF

# 3. Copier APP_KEY depuis l'ancien .env
grep "APP_KEY=" .env.backup >> .env  # Si tu as un backup

# 4. Clear cache Laravel
php artisan config:clear
php artisan cache:clear

# 5. Lancer migrations
php artisan migrate --force

# 6. Importer données Sénat
php artisan import:senat-sql senateurs --fresh --no-interaction

# 7. Une fois terminé, lancer le test
./scripts/test_liaisons_amendements_votes.sh
```

---

## 🔍 CE QUE LE TEST VA VÉRIFIER

### 1. Liaison Amendements → Sénateur
- ✅ Table `sen_ameli` : Mapping `senid` ↔ `matricule`
- ✅ Table `senat_ameli_amdsen` : Auteurs avec `senid`
- ✅ Jointure complète : `amd` + `amdsen` + `sen_ameli`
- ✅ Vue `amendements_senat` : Résultat final avec `matricule`

### 2. Liaison Votes → Scrutin
- ✅ Table `senat_senateurs_votes` : Votes individuels (senmat, scrid, position)
- ✅ Table `senat_senateurs_scr` : Détails scrutins (pour/contre/abstentions/résultat)
- ✅ Vue `senateurs_votes` : Jointure votes + scrutins
- ✅ Codes positions : Liste des valeurs possibles (pour/contre/abs/etc)

### 3. Résumé Global
- ✅ Totaux : Raw vs Vues
- ✅ Pour sénateur test (19954N) : Nombre amendements + votes
- ✅ Diagnostic : Problèmes de mapping ou vues vides

---

## 📊 RÉSULTATS ATTENDUS

### ✅ Si tout fonctionne
```
📝 AMENDEMENTS :
  - Total raw (senat_ameli_amd): 50000+
  - Total vue (amendements_senat): 50000+
  - Pour 19954N: 10-50 amendements
  ✅ OK

🗳️  VOTES :
  - Total raw (senat_senateurs_votes): 500000+
  - Total vue (senateurs_votes): 500000+
  - Pour 19954N: 100-500 votes
  ✅ OK

📊 SCRUTINS :
  - Total raw (senat_senateurs_scr): 2000+
  - Total vue (senateurs_scrutins): 2000+
  ✅ OK
```

### ❌ Si problème
```
📝 AMENDEMENTS :
  - Total raw: 50000
  - Total vue: 0 ou très peu
  ❌ PROBLÈME : Vue vide alors que raw a des données

→ Cause : Migration vue SQL incorrecte ou jointure manquante
→ Solution : Corriger database/migrations/2025_11_21_030800_create_amendements_senat_view.php
```

---

## 🐛 DÉBOGAGE SI ÉCHEC

### Erreur : "could not translate host name"
```bash
# Vérifier .env
grep "^DB_" .env

# Si DB_HOST=db → Utiliser Docker (Option 1)
# Si DB_HOST=127.0.0.1 → Vérifier PostgreSQL local :
sudo systemctl status postgresql
psql -U demoscratos -d demoscratos_local -h 127.0.0.1 -c "SELECT 1;"
```

### Erreur : "password authentication failed"
```bash
# Réinitialiser mot de passe
sudo -u postgres psql -c "ALTER USER demoscratos WITH PASSWORD 'demoscratos';"

# Vérifier pg_hba.conf
sudo nano /etc/postgresql/16/main/pg_hba.conf
# Ligne : host all all 127.0.0.1/32 md5

sudo systemctl restart postgresql
```

### Erreur : "Table does not exist"
```bash
# Lancer import SQL
php artisan import:senat-sql senateurs --fresh

# Vérifier tables
php artisan tinker --execute="DB::select('SELECT COUNT(*) FROM senat_senateurs_sen');"

# Lancer migrations vues
php artisan migrate --force
```

---

## ✅ CHOIX RAPIDE

**Pour aller vite** :

```bash
cd /home/kevin/www/demoscratos

# Si Docker disponible :
docker compose up -d && docker compose exec app ./scripts/test_liaisons_amendements_votes.sh

# Si local uniquement :
sed -i 's/DB_HOST=db/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_DATABASE=civicdash/DB_DATABASE=demoscratos_local/' .env
sed -i 's/DB_USERNAME=civicdash/DB_USERNAME=demoscratos/' .env
sed -i 's/DB_PASSWORD=secret/DB_PASSWORD=demoscratos/' .env
php artisan config:clear
./scripts/test_liaisons_amendements_votes.sh
```

---

**Créé le 21 nov 2025 à 19:18**

