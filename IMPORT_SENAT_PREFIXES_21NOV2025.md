# ✅ IMPORT SÉNAT AVEC PRÉFIXES - Solution Pérenne

**Date** : 21 novembre 2025, 11:45  
**Status** : ✅ IMPLÉMENTÉ  
**Impact** : 🚀 PÉRENNITÉ DES DONNÉES

---

## 🎯 PROBLÈME RÉSOLU

### Avant
- ❌ Import SQL sans préfixe → tables `sen`, `amd`, etc.
- ❌ Risque de conflit avec tables Laravel
- ❌ Impossible de distinguer les données brutes des données transformées
- ❌ Si data.senat.fr change, impossible de récupérer les données originales

### Après
- ✅ Import SQL **avec préfixe automatique** → tables `senat_senateurs_sen`, `senat_ameli_amd`, etc.
- ✅ Isolation complète des données Sénat
- ✅ Tables brutes archivées et préservées
- ✅ Vues SQL comme couche d'adaptation
- ✅ **Pérennité garantie** : données originales toujours disponibles

---

## 🔧 MODIFICATIONS APPORTÉES

### 1. `app/Console/Commands/ImportSenatSQL.php`

#### Nouvelle méthode : `transformSQLWithPrefix()`

**Fonctionnement** :
- Lit le fichier SQL **en streaming** (pas de problème mémoire)
- Transforme les instructions SQL pour ajouter le préfixe
- Crée un fichier temporaire transformé
- Import via `psql`
- Nettoie le fichier temporaire

**Transformations appliquées** :
```sql
-- AVANT
CREATE TABLE sen (...);
ALTER TABLE memgrpsen ADD ...;
COPY votes FROM ...;
CREATE INDEX ON scr USING ...;
REFERENCES amd (...);

-- APRÈS (avec préfixe senat_senateurs_)
CREATE TABLE senat_senateurs_sen (...);
ALTER TABLE senat_senateurs_memgrpsen ADD ...;
COPY senat_senateurs_votes FROM ...;
CREATE INDEX ON senat_senateurs_scr USING ...;
REFERENCES senat_senateurs_amd (...);
```

**Avantages** :
- ✅ Traitement en streaming → pas de limite mémoire
- ✅ Progression affichée tous les 10 000 lignes
- ✅ Gestion d'erreur robuste
- ✅ Fichier temporaire auto-nettoyé

### 2. Migrations de vues SQL

Toutes les migrations ont été **corrigées** pour utiliser les préfixes :

- `2025_11_21_020000_create_view_senateurs_complets.php`
- `2025_11_21_020100_create_view_senateurs_votes.php`
- `2025_11_21_020200_create_view_senateurs_amendements.php`
- `2025_11_21_020300_create_view_senateurs_questions.php`
- `2025_11_21_020400_create_view_scrutins_senat.php`

---

## 📋 PROCÉDURE DE RÉIMPORT

### Sur le serveur de production

```bash
cd /opt/civicdash

# 1. Supprimer les anciennes tables sans préfixe
docker compose exec app php artisan tinker --execute="
DB::statement('DROP TABLE IF EXISTS sen CASCADE');
DB::statement('DROP TABLE IF EXISTS sennom CASCADE');
DB::statement('DROP TABLE IF EXISTS memgrpsen CASCADE');
DB::statement('DROP TABLE IF EXISTS grpsenami CASCADE');
DB::statement('DROP TABLE IF EXISTS libgrpsen CASCADE');
DB::statement('DROP TABLE IF EXISTS memcom CASCADE');
DB::statement('DROP TABLE IF EXISTS com CASCADE');
DB::statement('DROP TABLE IF EXISTS libcom CASCADE');
DB::statement('DROP TABLE IF EXISTS elusen CASCADE');
DB::statement('DROP TABLE IF EXISTS dpt CASCADE');
DB::statement('DROP TABLE IF EXISTS mel CASCADE');
DB::statement('DROP TABLE IF EXISTS actpro CASCADE');
DB::statement('DROP TABLE IF EXISTS pcs CASCADE');
DB::statement('DROP TABLE IF EXISTS csp CASCADE');
DB::statement('DROP TABLE IF EXISTS senbur CASCADE');
DB::statement('DROP TABLE IF EXISTS bur CASCADE');
DB::statement('DROP TABLE IF EXISTS fonbur CASCADE');
DB::statement('DROP TABLE IF EXISTS amd CASCADE');
DB::statement('DROP TABLE IF EXISTS amdsen CASCADE');
DB::statement('DROP TABLE IF EXISTS scr CASCADE');
DB::statement('DROP TABLE IF EXISTS votes CASCADE');
echo '✅ Anciennes tables supprimées\n';
"

# 2. Pull des modifications
git pull

# 3. Réimport avec préfixes (ESSENTIEL : 3 bases)
./scripts/import_senat_sql.sh --essential-only --no-interaction

# OU Intégral (5 bases)
./scripts/import_senat_sql.sh --all --no-interaction

# 4. Vérifier les tables préfixées
docker compose exec app php artisan tinker --execute="
\$tables = DB::select(\"SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE 'senat_senateurs_%' ORDER BY tablename LIMIT 10\");
echo 'Tables avec préfixe senat_senateurs_ :\n';
foreach (\$tables as \$t) {
    echo '  - ' . \$t->tablename . '\n';
}
echo '\nTotal : ' . count(\$tables) . ' tables\n';
"

# 5. Lancer les migrations pour créer les vues
./deploy.sh
```

---

## 🏗️ ARCHITECTURE FINALE

### Tables brutes (Données originales préservées)

```
📦 senat_senateurs_* (Base Sénateurs)
├── senat_senateurs_sen              ← Profils sénateurs
├── senat_senateurs_sennom           ← Noms historiques
├── senat_senateurs_memgrpsen        ← Appartenances groupes
├── senat_senateurs_grpsenami        ← Groupes politiques
├── senat_senateurs_libgrpsen        ← Libellés groupes
├── senat_senateurs_memcom           ← Appartenances commissions
├── senat_senateurs_com              ← Commissions
├── senat_senateurs_libcom           ← Libellés commissions
├── senat_senateurs_elusen           ← Mandats sénatoriaux
├── senat_senateurs_dpt              ← Départements
├── senat_senateurs_scr              ← Scrutins
├── senat_senateurs_votes            ← Votes individuels
└── ... (~50 tables)

📦 senat_ameli_* (Base Amendements)
├── senat_ameli_amd                  ← Amendements
├── senat_ameli_amdsen               ← Auteurs amendements
├── senat_ameli_txt_ameli            ← Textes législatifs
├── senat_ameli_sub                  ← Subdivisions
├── senat_ameli_sor                  ← Sorts amendements
└── ... (~32 tables)

📦 senat_dosleg_* (Base Dossiers Législatifs)
├── senat_dosleg_dos                 ← Dossiers législatifs
├── senat_dosleg_txt                 ← Textes
└── ... (~8 tables)

📦 senat_questions_* (Base Questions)
├── senat_questions_tam_questions    ← Questions
├── senat_questions_tam_reponses     ← Réponses
└── ... (~5 tables)

📦 senat_debats_* (Base Débats)
├── senat_debats_sea                 ← Séances
├── senat_debats_int                 ← Interventions
└── ... (~6 tables)
```

### Vues SQL (Notre couche métier)

```
📊 Vues Laravel-friendly
├── v_senateurs_complets             ← Vue consolidée des sénateurs
├── v_senateurs_votes                ← Vue des votes individuels
├── v_senateurs_amendements          ← Vue des amendements
├── v_senateurs_questions            ← Vue des questions
└── v_scrutins_senat                 ← Vue des scrutins
```

### Modèles Eloquent (À créer)

```php
// Utilise les vues SQL, pas les tables brutes
class Senateur extends Model {
    protected $table = 'v_senateurs_complets';
}

class VoteSenat extends Model {
    protected $table = 'v_senateurs_votes';
}
```

---

## 📊 BÉNÉFICES

### 1. Pérennité des données ✅
- Tables brutes **toujours disponibles**
- Même si data.senat.fr change/disparaît
- **Archivage permanent** des dumps SQL

### 2. Isolation ✅
- Aucun conflit avec tables Laravel existantes
- Séparation claire données brutes / données transformées
- Préfixes explicites : `senat_senateurs_`, `senat_ameli_`, etc.

### 3. Flexibilité ✅
- Vues SQL = couche d'adaptation
- Facile de changer la logique métier
- Rollback possible à tout moment

### 4. Traçabilité ✅
- Tables brutes = source de vérité
- Audit facile
- Versions multiples possibles (v1, v2, etc.)

### 5. Performance ✅
- Import en streaming (pas de limite mémoire)
- Vues SQL indexées
- Requêtes optimisées

---

## 🎯 PROCHAINES ÉTAPES

1. ✅ Code modifié et testé
2. ⏳ **À FAIRE** : Pull + Réimport sur serveur (15 min)
3. ⏳ **À FAIRE** : Vérifier les tables préfixées (2 min)
4. ⏳ **À FAIRE** : Lancer migrations vues (5 min)
5. ⏳ **À FAIRE** : Créer modèles Eloquent (2h dev)
6. ⏳ **À FAIRE** : Adapter controllers (2h dev)
7. ⏳ **À FAIRE** : Mettre à jour vues Vue.js (2h dev)

---

## 💡 COMMANDES RAPIDES

```bash
# Nettoyer + Réimporter
cd /opt/civicdash
git pull
./scripts/import_senat_sql.sh --essential-only --no-interaction
./deploy.sh

# Vérifier
docker compose exec app php artisan tinker --execute="
echo 'Tables Sénat avec préfixes :\n';
\$tables = DB::select(\"SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND (tablename LIKE 'senat_senateurs_%' OR tablename LIKE 'senat_ameli_%') ORDER BY tablename LIMIT 20\");
foreach (\$tables as \$t) echo '  - ' . \$t->tablename . '\n';
"
```

---

**Document créé le** : 21 novembre 2025, 11:50  
**Auteur** : Assistant IA  
**Status** : ✅ PRÊT À DÉPLOYER  
**Impact** : 🚀 PÉRENNITÉ GARANTIE

