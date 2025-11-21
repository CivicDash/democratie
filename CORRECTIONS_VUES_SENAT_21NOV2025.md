# ✅ CORRECTIONS APPLIQUÉES - Migrations Vues Sénat

**Date** : 21 novembre 2025, 11:20  
**Status** : ✅ CORRIGÉ (5 fichiers modifiés)

---

## 🔧 PROBLÈME RÉSOLU

**Erreur initiale** : `SQLSTATE[42703]: Undefined column: 7 ERROR: column sen.id does not exist`

**Cause** : Les tables SQL importées ont un préfixe automatique (`senat_senateurs_`, `senat_ameli_`, `senat_questions_`) mais les migrations de vues utilisaient les noms de tables sans préfixe.

**Solution appliquée** : Ajout des préfixes corrects dans toutes les migrations de vues SQL.

---

## 📝 FICHIERS MODIFIÉS

### 1. `database/migrations/2025_11_21_020000_create_view_senateurs_complets.php`
✅ Correction des tables :
- `sen` → `senat_senateurs_sen`
- `memgrpsen` → `senat_senateurs_memgrpsen`
- `grpsenami` → `senat_senateurs_grpsenami`
- `libgrpsen` → `senat_senateurs_libgrpsen`
- `memcom` → `senat_senateurs_memcom`
- `com` → `senat_senateurs_com`
- `libcom` → `senat_senateurs_libcom`
- `elusen` → `senat_senateurs_elusen`
- `dpt` → `senat_senateurs_dpt`
- `senbur` → `senat_senateurs_senbur`
- `bur` → `senat_senateurs_bur`
- `mel` → `senat_senateurs_mel`
- `actpro` → `senat_senateurs_actpro`
- `pcs` → `senat_senateurs_pcs`
- `csp` → `senat_senateurs_csp`
- `sennom` → `senat_senateurs_sennom`

### 2. `database/migrations/2025_11_21_020100_create_view_senateurs_votes.php`
✅ Correction des tables :
- `votes` → `senat_senateurs_votes`
- `scr` → `senat_senateurs_scr`
- `memgrpsen` → `senat_senateurs_memgrpsen`
- `grpsenami` → `senat_senateurs_grpsenami`
- `libgrpsen` → `senat_senateurs_libgrpsen`

### 3. `database/migrations/2025_11_21_020200_create_view_senateurs_amendements.php`
✅ Correction des tables :
- `amd` → `senat_ameli_amd`
- `amdsen` → `senat_ameli_amdsen`
- `txt_ameli` → `senat_ameli_txt_ameli`
- `sub` → `senat_ameli_sub`
- `sor` → `senat_ameli_sor`
- `avicom` → `senat_ameli_avicom`
- `avigvt` → `senat_ameli_avigvt`
- `sea` → `senat_ameli_sea`

### 4. `database/migrations/2025_11_21_020300_create_view_senateurs_questions.php`
✅ Correction des tables :
- `tam_questions` → `senat_questions_tam_questions`
- `tam_reponses` → `senat_questions_tam_reponses`
- `naturequestion` → `senat_questions_naturequestion`
- `etatquestion` → `senat_questions_etatquestion`
- `sortquestion` → `senat_questions_sortquestion`
- `legquestion` → `senat_questions_legquestion`
- `tam_ministeres` → `senat_questions_tam_ministeres`
- `the` → `senat_questions_the`

### 5. `database/migrations/2025_11_21_020400_create_view_scrutins_senat.php`
✅ Correction des tables :
- `scr` → `senat_senateurs_scr`
- `typscr` → `senat_senateurs_typscr`
- `ses` → `senat_senateurs_ses`
- `texte` → `senat_senateurs_texte`

---

## 🚨 NOUVEAU PROBLÈME DÉTECTÉ

**Erreur** : `SQLSTATE[42P07]: Duplicate table: 7 ERROR: relation "fulltext_search" already exists`

**Migration concernée** : `2025_11_08_141000_create_maires_table`

**Cause** : L'index `fulltext_search` existe déjà, mais la migration n'est pas marquée comme exécutée dans la table `migrations`.

---

## 🎯 COMMANDES À EXÉCUTER SUR LE SERVEUR

### Option 1 : Via Docker Compose (si disponible)

```bash
cd /opt/civicdash

# Marquer la migration comme exécutée
docker compose exec db psql -U civicdash -d civicdash -c "
INSERT INTO migrations (migration, batch) 
VALUES ('2025_11_08_141000_create_maires_table', 1)
ON CONFLICT DO NOTHING;
"

# Relancer le déploiement
./deploy.sh
```

### Option 2 : Via psql directement

```bash
cd /opt/civicdash

# Exécuter le script SQL de correction
psql -U civicdash -d civicdash -f fix_migrations.sql

# Relancer le déploiement
./deploy.sh
```

### Option 3 : Via PHP Artisan (avec accès DB natif)

```bash
cd /opt/civicdash

# Marquer manuellement dans la table
php artisan db
# Puis exécuter :
# INSERT INTO migrations (migration, batch) VALUES ('2025_11_08_141000_create_maires_table', 1) ON CONFLICT DO NOTHING;
# \q

# Relancer le déploiement
./deploy.sh
```

---

## 📋 RÉSUMÉ DES CORRECTIONS

| Migration | Status | Tables corrigées |
|-----------|--------|------------------|
| `2025_11_21_020000` (Sénateurs complets) | ✅ | 16 tables |
| `2025_11_21_020100` (Votes sénateurs) | ✅ | 5 tables |
| `2025_11_21_020200` (Amendements) | ✅ | 8 tables |
| `2025_11_21_020300` (Questions) | ✅ | 8 tables |
| `2025_11_21_020400` (Scrutins Sénat) | ✅ | 4 tables |
| **TOTAL** | **✅ 5 migrations** | **41 tables préfixées** |

---

## 💡 LEÇON APPRISE

Les imports SQL via `psql` créent les tables **avec les préfixes définis dans ImportSenatSQL.php** :
- `DATABASES['senateurs']['table_prefix']` = `'senat_senateurs_'`
- `DATABASES['ameli']['table_prefix']` = `'senat_ameli_'`
- `DATABASES['questions']['table_prefix']` = `'senat_questions_'`

Il faut **toujours utiliser ces préfixes** dans les vues SQL et les requêtes Eloquent.

---

## ✅ PROCHAINES ÉTAPES

1. **Exécuter une des 3 options** ci-dessus pour marquer `2025_11_08_141000_create_maires_table` comme exécutée
2. **Relancer `./deploy.sh`** pour créer les vues SQL
3. **Vérifier que les 5 vues sont créées** :
   ```bash
   docker compose exec db psql -U civicdash -d civicdash -c "\dv"
   ```
4. **Continuer avec l'enrichissement Wikipedia des sénateurs**

---

**Fichier créé** : `fix_migrations.sql` (à utiliser avec Option 2)

**Status global** : 🟡 EN ATTENTE EXÉCUTION SERVEUR

