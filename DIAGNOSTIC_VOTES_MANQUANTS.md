# 🔍 DIAGNOSTIC : 0 Votes et Amendements pour tous les députés

## ❌ Problème identifié

**Symptôme :** Tous les députés affichent 0 votes et 0 amendements dans leur fiche.

**Cause racine :** L'import des données a échoué en 2 étapes critiques :

### 1️⃣ Échec de l'import des scrutins
```
⚠️  Erreur dans VTANR5L17V1.json: SQLSTATE[23502]: Not null violation: 
null value in column "resultat_code" of relation "scrutins_an"
```

**Explication :** Certains scrutins dans les données JSON ont `resultat_code` et `resultat_libelle` à `null` (motions de censure en cours, scrutins non finalisés, etc.), mais la table `scrutins_an` avait ces colonnes en `NOT NULL`.

### 2️⃣ Absence des votes individuels
```
[11:42:20] Test 5/9 - Votes Individuels
❌ Aucun scrutin trouvé. Lancer d'abord : import:scrutins-an
```

**Explication :** La commande `extract:votes-individuels-an` dépend des scrutins déjà importés. Comme l'étape 4 (scrutins) a échoué, l'étape 5 n'a importé aucun vote individuel.

### 3️⃣ Amendements probablement aussi manquants

L'import des amendements (`import:amendements-an`) n'a pas été lancé non plus.

---

## ✅ Solution appliquée

### Fichiers créés/modifiés :

1. **`database/migrations/2025_11_20_114000_make_scrutins_resultat_nullable.php`**
   - Rend `resultat_code` et `resultat_libelle` **nullable**
   - Permet d'importer les scrutins avec résultats null

2. **`scripts/fix_votes_amendements.sh`** (NOUVEAU)
   - Script automatisé pour relancer tous les imports manquants
   - 6 étapes : migration + scrutins + votes + amendements + dossiers + vérification

---

## 🚀 Instructions de correction (SERVEUR)

```bash
# 1. Se connecter au serveur
ssh civicdash@ns3153447

# 2. Aller dans le projet
cd /opt/civicdash

# 3. Pull les derniers changements (migration + script)
git pull

# 4. Lancer le script de correction automatique
./scripts/fix_votes_amendements.sh
```

**Durée estimée :** 2-4 heures (selon nombre de scrutins L17)

---

## 📊 Résultat attendu

Après l'exécution du script, chaque député devrait avoir :

- **Votes individuels** : Entre 500 et 1500 votes (selon présence)
- **Amendements** : Entre 10 et 200 amendements (selon activité)
- **Statistiques** correctement affichées sur les fiches députés

### Vérification manuelle après import :

```bash
# Dans tinker
docker compose exec -T app php artisan tinker

# Vérifier un député spécifique
$depute = \App\Models\ActeurAN::where('nom', 'Bony')->first();
echo $depute->votesIndividuels()->count() . " votes";
echo $depute->amendementsAuteur()->count() . " amendements";
```

---

## 🔗 Relation entre les tables

```
scrutins_an (3876 scrutins L17)
    ↓ (extraction)
votes_individuels_an (~400k enregistrements attendus)
    ↓ (relation)
acteurs_an (577 députés)
```

**Commandes dans l'ordre :**
1. `import:scrutins-an` → Remplit `scrutins_an`
2. `extract:votes-individuels-an` → Lit `scrutins_an.ventilation_votes`, crée `votes_individuels_an`
3. `import:amendements-an` → Remplit `amendements_an`

---

## 🎯 Commit associé

```
feat: Add interactive SVG France map (18 regions) + DROM-COM support + fix script for missing votes/amendments data
```

**Fichiers :**
- ✅ Migration `resultat_code` nullable
- ✅ Script de correction `fix_votes_amendements.sh`
- ✅ Carte SVG interactive (bonus)
- ✅ Support DOM-COM (5 régions)

---

**Date :** 20 novembre 2025  
**Statut :** ✅ Solution prête, en attente d'exécution sur serveur

