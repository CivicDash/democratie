# 🔴 CORRECTION URGENTE - Migrations Vues Sénat

**Problème** : `SQLSTATE[42703]: Undefined column: 7 ERROR: column sen.id does not exist`

**Cause** : Les migrations des vues SQL référencent des tables qui n'existent pas ou ont des noms différents.

---

## ✅ SOLUTION RAPIDE

### Option A : Désactiver temporairement les migrations de vues

```bash
cd /opt/civicdash

# Renommer les migrations de vues pour les désactiver temporairement
mv database/migrations/2025_11_21_020000_create_view_senateurs_complets.php database/migrations/2025_11_21_020000_create_view_senateurs_complets.php.disabled
mv database/migrations/2025_11_21_020100_create_view_senateurs_votes.php database/migrations/2025_11_21_020100_create_view_senateurs_votes.php.disabled
mv database/migrations/2025_11_21_020200_create_view_senateurs_amendements.php database/migrations/2025_11_21_020200_create_view_senateurs_amendements.php.disabled
mv database/migrations/2025_11_21_020300_create_view_senateurs_questions.php database/migrations/2025_11_21_020300_create_view_senateurs_questions.php.disabled
mv database/migrations/2025_11_21_020400_create_view_scrutins_senat.php database/migrations/2025_11_21_020400_create_view_scrutins_senat.php.disabled

# Relancer le déploiement
./deploy.sh
```

### Option B : Identifier les vraies tables et corriger les migrations

```bash
# Se connecter à PostgreSQL
docker compose exec db psql -U civicdash -d civicdash

# Lister les tables importées
\dt

# Chercher les tables du Sénat
SELECT tablename FROM pg_tables 
WHERE schemaname = 'public' 
AND tablename NOT LIKE 'senateurs%'
ORDER BY tablename 
LIMIT 50;

# Quitter
\q
```

Une fois les noms identifiés, corriger toutes les migrations pour utiliser les vrais noms de tables.

---

## 🔍 DIAGNOSTIC

Le problème vient probablement de :

1. **Préfixe de tables** : Les tables SQL ont peut-être un préfixe automatique ajouté par PostgreSQL
2. **Noms de colonnes** : Les colonnes peuvent avoir des noms différents de ce qu'on attend
3. **Structure différente** : La structure réelle peut différer de la documentation

---

## 📋 CHECKLIST DE CORRECTION

### Étape 1 : Identifier les tables réelles

```sql
-- Exemple de requêtes de diagnostic
SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '%sen%';
SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '%grp%';
SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '%vote%';
SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '%amd%';
```

### Étape 2 : Identifier la structure d'une table

```sql
-- Exemple pour voir les colonnes de la table 'sen' (ou équivalent)
\d sen
-- OU
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'sen';
```

### Étape 3 : Corriger les migrations

Une fois les vrais noms identifiés, mettre à jour les 5 migrations de vues :

- `2025_11_21_020000_create_view_senateurs_complets.php`
- `2025_11_21_020100_create_view_senateurs_votes.php`
- `2025_11_21_020200_create_view_senateurs_amendements.php`
- `2025_11_21_020300_create_view_senateurs_questions.php`
- `2025_11_21_020400_create_view_scrutins_senat.php`

---

## 🎯 RECOMMANDATION IMMÉDIATE

**Pour débloquer le déploiement maintenant** :

1. Désactiver les 5 migrations de vues (Option A)
2. Relancer `./deploy.sh`
3. Une fois déployé, diagnostiquer les vraies tables
4. Corriger les migrations
5. Réactiver et relancer

**Commandes** :

```bash
cd /opt/civicdash

# Désactiver temporairement
for file in database/migrations/2025_11_21_0200*_create_view_*.php; do
    mv "$file" "$file.disabled"
done

# Déployer
./deploy.sh

# Puis diagnostiquer
docker compose exec db psql -U civicdash -d civicdash -c "\dt" | grep -v senateurs
```

---

## 💡 HYPOTHÈSE

Il est probable que :
- Les tables SQL ont été importées avec un **préfixe automatique** (ex: `import_`, `senat_`, etc.)
- OU les tables ont des **noms différents** de la documentation
- OU les tables n'ont **pas été importées** du tout (erreur silencieuse)

Il faut absolument vérifier avec `\dt` dans psql.

---

**Document créé le** : 21 novembre 2025, 11:10  
**Status** : 🔴 CORRECTION URGENTE  
**Action** : Désactiver les migrations de vues OU identifier les vraies tables

