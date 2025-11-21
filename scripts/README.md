# 🏛️ Scripts CivicDash - Documentation Complète

Ce répertoire contient **tous les scripts** nécessaires pour gérer, importer et enrichir les données parlementaires.

---

## 📋 TABLE DES MATIÈRES

1. [🎯 Script Principal (RECOMMANDÉ)](#-script-principal-recommandé)
2. [🚀 Import Bases SQL Sénat (NOUVEAU)](#-import-bases-sql-sénat-nouveau)
3. [📊 Scripts Import Données Parlementaires](#-scripts-import-données-parlementaires)
4. [🔍 Scripts Analyse & Diagnostic](#-scripts-analyse--diagnostic)
5. [📝 Scripts Enrichissement (Ancienne API)](#-scripts-enrichissement-ancienne-api)
6. [🗺️ Scripts Codes Postaux & Géo](#️-scripts-codes-postaux--géo)
7. [🧪 Scripts Tests & Debug](#-scripts-tests--debug)
8. [🗑️ Scripts Obsolètes](#️-scripts-obsolètes)

---

## 🎯 Script Principal (RECOMMANDÉ)

### `import_parlement_master.sh` ⭐ **NOUVEAU**

**Script unique** qui remplace tous les anciens scripts d'import avec menu interactif.

```bash
./scripts/import_parlement_master.sh
```

#### 📋 Options du Menu

```
1) 🏛️  ASSEMBLÉE NATIONALE UNIQUEMENT (L17)
   └─ 8 étapes • ~12-15h • ~400k enregistrements
   
2) 🏰 SÉNAT UNIQUEMENT  
   └─ 1 étape • ~5-10 min • ~8k enregistrements
   
3) 🇫🇷 PARLEMENT COMPLET (AN + SÉNAT)
   └─ 9 étapes • ~12-16h • ~408k enregistrements
   
4) 🧪 MODE TEST (Limité pour tests)
   └─ Toutes étapes avec --limit=10
```

#### ✅ Avantages

- ✅ **Interface unique** - Menu clair et guidé
- ✅ **Pas de redondance** - Code DRY, 1 seul script
- ✅ **Logs unifiés** - Sauvegardés dans `logs/import_parlement_YYYYMMDD_HHMMSS/`
- ✅ **Mode test intégré** - Plus besoin de script séparé
- ✅ **Gestion d'erreurs** - Arrêt propre si problème
- ✅ **Chronomètre** - Suivi précis des durées
- ✅ **Stats complètes** - AN + Sénat en un coup d'œil

#### 📦 Import Complet

Ce script importe dans l'ordre :

**ASSEMBLÉE NATIONALE L17** (8 étapes) :
1. Acteurs AN (députés) - 5-10 min
2. Organes AN (groupes, commissions) - 2-5 min
3. Mandats AN - 10-15 min
4. Scrutins AN - 1-2h
5. Votes Individuels - 2-3h
6. Dossiers + Textes - 2-3h
7. Amendements - 4-6h
8. Wikipedia - 10-15 min

**SÉNAT** (1 étape) :
9. Sénateurs complets (API REST) - 5-10 min

#### 📈 Résultats Attendus

| Source | Enregistrements |
|--------|----------------|
| Acteurs AN | ~577 |
| Organes AN | ~500 |
| Mandats AN | ~10 000 |
| Scrutins AN | ~1 000 |
| Votes individuels | ~300 000 |
| Dossiers | ~500 |
| Textes | ~2 000 |
| Amendements | ~80 000 |
| Sénateurs | ~348 |
| Mandats Sénat | ~4 000 |
| **TOTAL** | **~408 000** |

---

## 🚀 Import Bases SQL Sénat (NOUVEAU)

### `import_senat_sql.sh` ⭐⭐⭐ **RÉVOLUTIONNAIRE**

**Script ultime** pour importer les 5 bases SQL PostgreSQL complètes du Sénat.

```bash
./scripts/import_senat_sql.sh
```

#### 🎯 Bases disponibles

| Base | Description | Priorité | Durée |
|------|-------------|----------|-------|
| **Sénateurs** | Profils complets + mandats + commissions | ⭐⭐⭐ | 5 min |
| **DOSLEG** | Dossiers législatifs complets | ⭐⭐⭐ | 10 min |
| **AMELI** | Amendements (base complète) | ⭐⭐⭐ | 15 min |
| **Questions** | Questions au Gouvernement | ⭐⭐ | 10 min |
| **Débats** | Comptes rendus des séances | ⭐ | 30 min |

#### 📋 Options du Menu

```
1) 🔍 ANALYSER TOUTES LES BASES (sans import)
   └─ 5 analyses • ~5 min • Voir la structure SQL
   
2) ⭐ IMPORT ESSENTIEL (Sénateurs + AMELI + DOSLEG)
   └─ 3 bases • ~30 min • Données critiques
   
3) 🎯 IMPORT COMPLET (Tout sauf Débats)
   └─ 4 bases • ~40 min • Recommandé
   
4) 🌟 IMPORT INTÉGRAL (5 bases)
   └─ 5 bases • ~60-70 min • Tout importer
   
5) 📦 IMPORT PERSONNALISÉ (choisir les bases)
```

#### ✅ Avantages vs API REST

| Critère | API REST (ancien) | SQL Direct (nouveau) |
|---------|-------------------|----------------------|
| **Durée** | 30-45 min | 30 min |
| **Couverture** | 60% | **95%** ✨ |
| **Erreurs** | Fréquentes (404) | Aucune |
| **Maintenance** | Complexe (350+ appels) | Simple (5 fichiers) |
| **Amendements** | 0% | **100%** 🎉 |
| **Questions** | 0% | **100%** 🎉 |

#### 🚀 Usage

```bash
# Analyser d'abord (RECOMMANDÉ)
./scripts/import_senat_sql.sh
# → Choisir option 1

# Import essentiel (30 min)
./scripts/import_senat_sql.sh --essential-only

# Import complet (40 min)
./scripts/import_senat_sql.sh --all

# Vérifier les données importées
docker compose exec app php artisan tinker
>>> DB::select("SELECT tablename FROM pg_tables WHERE tablename LIKE 'senat_%'")
```

#### 📊 Résultat

**Avant** : Sénat 60% → **Après** : Sénat 95% (+35%) 🚀

#### 📖 Documentation complète

Voir `BASES_SQL_SENAT_COMPLETES_21NOV2025.md` et `SYNTHESE_BASES_SQL_SENAT_21NOV2025.md`

---

## 📊 Scripts Import Données Parlementaires

### Import Wikipédia Députés

**`import_wikipedia_deputes.sh`**

Enrichit les députés avec données Wikipedia (photo, URL, extrait biographique).

```bash
./scripts/import_wikipedia_deputes.sh
```

- ⏱️ Durée : ~10-15 minutes
- 📊 ~577 députés enrichis
- 🔄 Pause de 500ms entre chaque requête (API MediaWiki)

---

### Import Représentants (CSV Historique)

**`import_representants.sh`**

Importe députés/sénateurs depuis CSV locaux (ancienne méthode, avant API officielle).

```bash
./scripts/import_representants.sh
```

**Source** :
- `public/data/elus-deputes-dep.csv`
- `public/data/elus-senateurs-sen.csv`

⚠️ **Note** : Script historique, préférer `import_parlement_master.sh` pour données officielles.

---

### Import Organes Parlementaires

**`import_organes.sh`**

Importe groupes politiques, commissions, délégations (ancienne API).

```bash
./scripts/import_organes.sh
# Choix: 1 (AN) / 2 (Sénat) / 3 (Les deux)
```

⚠️ **Note** : Inclus dans `import_parlement_master.sh` (étape 2).

---

## 🔍 Scripts Analyse & Diagnostic

### Analyse Complète Données AN

**`analyse_complete_donnees_an.sh`**

Rapport détaillé sur toutes les données AN importées.

```bash
./scripts/analyse_complete_donnees_an.sh
```

**Affiche** :
- ✅ Comptages par table
- ✅ Groupes parlementaires + répartition
- ✅ Top 10 députés actifs (votes, amendements)
- ✅ Scrutins par année/mois
- ✅ Dossiers législatifs par statut
- ✅ Qualité des données (% remplis)

---

### Test Données AN

**`test_donnees_an.sh`**

Tests rapides de cohérence des données.

```bash
./scripts/test_donnees_an.sh
```

---

## 📝 Scripts Enrichissement (Ancienne API)

Ces scripts utilisent **NosDéputés.fr / NosSénateurs.fr** (API RegardsCitoyens).

⚠️ **Note** : Données complémentaires, non utilisées dans les vues actuelles (on utilise l'API officielle AN).

### Enrichissement Complet

**`enrich_complete.sh`**

Import complet : votes + interventions + questions.

```bash
./scripts/enrich_complete.sh
```

- ⏱️ Durée : ~32 min
- 📊 ~200k votes + ~60k interventions + ~25k questions

---

### Enrichissement Amendements

**`enrich_amendements.sh`**

Import amendements depuis NosDéputés/NosSénateurs.

```bash
./scripts/enrich_amendements.sh
# Choix: 1 (Test) / 2 (Députés) / 3 (Sénateurs) / 4 (Tous)
```

---

### Enrichissement Votes Députés

**`enrich_deputes.sh`**

Import votes détaillés (ancienne API).

```bash
./scripts/enrich_deputes.sh
```

---

### Enrichissement Votes Sénateurs

**`enrich_senateurs.sh`**

Import votes détaillés sénateurs (ancienne API).

```bash
./scripts/enrich_senateurs.sh
```

---

### Tout Enrichir

**`enrich_all.sh`**

Lance tous les enrichissements d'un coup.

```bash
./scripts/enrich_all.sh
```

---

## 🗺️ Scripts Codes Postaux & Géo

### Import Codes Postaux

**`import_postal_codes_local.sh`**

Importe codes postaux depuis CSV local.

```bash
./scripts/import_postal_codes_local.sh
```

**Source** : `public/data/codes_postaux_france.csv`

---

### Diagnostic Codes Postaux

**`check_postal_codes.sh`**

Vérifie l'intégrité des codes postaux en base.

```bash
./scripts/check_postal_codes.sh
```

**Affiche** :
- ✅ Total codes postaux
- ✅ Villes uniques
- ✅ Codes par département
- ✅ Échantillon test

---

### Test Recherche Postale

**`test_postal_search.sh`**

Teste les recherches par code postal ET par ville.

```bash
./scripts/test_postal_search.sh
```

---

## 🧪 Scripts Tests & Debug

### Test Enrichissement Votes

**`test_enrich_votes.sh`**

Test rapide enrichissement votes (limite 5 députés).

```bash
./scripts/test_enrich_votes.sh
```

---

### Debug API NosDéputés

**`debug_api_nosdeputes.sh`**

Teste connexion et parsing API RegardsCitoyens.

```bash
./scripts/debug_api_nosdeputes.sh
```

---

### Debug Recherche Postale

**`debug_postal_search.sh`**

Debug recherches codes postaux.

```bash
./scripts/debug_postal_search.sh
```

---

### Dossier Debug

**`debug/`** (gitignored)

Scripts de debug temporaires :
- `check_postal_table.sh`
- `clean_postal_table.sh`
- `debug_votes_import.sh`
- `fix_organes_migration.sh`
- `fix_postal_table.sh`
- `list_organes_objects.sh`

---

## 🗑️ Scripts Obsolètes

Ces scripts sont **redondants** avec `import_parlement_master.sh` et peuvent être supprimés :

### ❌ À Supprimer

```bash
# Scripts AN redondants
rm scripts/import_complet_an_l17.sh
rm scripts/import_donnees_an_l17.sh
rm scripts/test_import_an_l17.sh

# Script Sénat redondant
rm scripts/import_senateurs_complet.sh
```

**Raison** : Tous remplacés par `import_parlement_master.sh` avec menu interactif.

---

## 🚀 USAGE RECOMMANDÉ

### 1️⃣ Import Production (Première fois)

```bash
cd /home/kevin/www/demoscratos

# Import COMPLET (AN + Sénat)
./scripts/import_parlement_master.sh
# Choix: 3
# Confirmer: oui
# Attendre 12-16h
```

### 2️⃣ Tests Rapides

```bash
# Mode test (--limit=10)
./scripts/import_parlement_master.sh
# Choix: 4
# Terminé en 2-3 min
```

### 3️⃣ Mise à Jour AN Seule

```bash
# Import AN uniquement
./scripts/import_parlement_master.sh
# Choix: 1
# Attendre 12-15h
```

### 4️⃣ Analyse Post-Import

```bash
# Diagnostic complet
./scripts/analyse_complete_donnees_an.sh

# Tests cohérence
./scripts/test_donnees_an.sh
```

---

## 📈 Logs

Tous les logs sont sauvegardés automatiquement :

```
logs/import_parlement_YYYYMMDD_HHMMSS/
├── 01_acteurs_an.log
├── 02_organes_an.log
├── 03_mandats_an.log
├── 04_scrutins_an.log
├── 05_votes_an.log
├── 06_dossiers_textes_an.log
├── 07_amendements_an.log
├── 08_wikipedia_an.log
└── 09_senateurs.log
```

**Suivi en temps réel** :
```bash
tail -f logs/import_parlement_*/07_amendements_an.log
```

---

## 🎯 RÉSUMÉ RAPIDE

| Script | Usage | Durée |
|--------|-------|-------|
| **`import_parlement_master.sh`** ⭐ | **Import complet (AN + Sénat)** | **12-16h** |
| `import_wikipedia_deputes.sh` | Enrichir Wikipedia | 10-15 min |
| `analyse_complete_donnees_an.sh` | Diagnostic complet | 2-3 min |
| `test_donnees_an.sh` | Tests cohérence | 1 min |
| `enrich_complete.sh` | Ancienne API (optionnel) | 32 min |
| `import_postal_codes_local.sh` | Codes postaux | 2 min |

---

## 📚 Documentation Complémentaire

- **`IMPORT_COMPLET_README.md`** - Guide détaillé import AN
- **`SCRIPT_MASTER_README.md`** - Guide script master
- **`SESSION_COMPLETE_README.md`** - Résumé session complète
- **`CHANGELOG.md`** - Historique modifications

---

## 💡 Support

**Problèmes courants** :

1. **Docker non démarré** :
   ```bash
   docker compose up -d
   ```

2. **Permissions script** :
   ```bash
   chmod +x scripts/*.sh
   ```

3. **Données source manquantes** :
   ```bash
   ls -lh public/data/acteur/
   ```

4. **Vérifier import** :
   ```bash
   docker compose exec app php artisan tinker --execute="
   echo 'Acteurs: ' . \App\Models\ActeurAN::count();
   "
   ```

---

**🎉 Plateforme CivicDash prête pour la production !**

*Un seul script pour tout gouverner : `import_parlement_master.sh` ⭐*
