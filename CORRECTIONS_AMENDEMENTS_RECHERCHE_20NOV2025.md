# 🔍 CORRECTIONS AMENDEMENTS & RECHERCHE - 20 Nov 2025

## 📊 ÉTAT DES LIEUX

### ✅ Import Amendements AN
- **Fichiers trouvés** : 63 677
- **Importés** : 34 629
- **Erreurs** : 29 048 (45.5%)
- **Adoptés** : 8 534 (24.6%)
- **Rejetés** : 14 530 (42.0%)

### ❌ Problèmes identifiés

1. **Amendements affichés à 0 sur profils députés**
2. **Taux d'adoption à 0**
3. **Recherche globale ne retourne rien**
4. **Recherche codes postaux/villes ne retourne rien**

---

## 🛠️ CORRECTIONS APPORTÉES

### 1️⃣ **Import Amendements - Extraction états et sorts**

**Problème** : Les champs `etat_code`, `sort_code`, et `sort_libelle` étaient mal extraits depuis le JSON.

**Structure JSON réelle** :
```json
{
  "cycleDeVie": {
    "etatDesTraitements": {
      "etat": {
        "code": "AC",
        "libelle": "A discuter"
      }
    },
    "sort": "Tombé"  // OU {"code": "REJ", "libelle": "Rejeté"}
  }
}
```

**Solution** : Ajout de 4 nouvelles méthodes dans `ImportAmendementsAN.php` :
- `extractStateCode()` : Extrait `etatDesTraitements.etat.code`
- `extractStateLibelle()` : Extrait `etatDesTraitements.etat.libelle`
- `extractSortCode()` : Extrait `cycleDeVie.sort` (string ou objet)
- `extractSortLibelle()` : Extrait le libellé
- `mapSortLibelleToCode()` : Mappe "Adopté" → "ADO", "Rejeté" → "REJ", etc.

**Fichier** : `app/Console/Commands/ImportAmendementsAN.php` (lignes 247-257 + 340-450)

---

### 2️⃣ **Scopes AmendementAN - Mauvaise colonne**

**Problème** : Les scopes `adoptes()`, `rejetes()`, `retires()` cherchaient dans `etat_code` au lieu de `sort_code`.

**Différence entre les colonnes** :
- **`etat_code`** : État de traitement (AC = A discuter, EN_COURS, etc.)
- **`sort_code`** : Sort final (ADO = Adopté, REJ = Rejeté, TOM = Tombé, RET = Retiré)

**Solution** : Modification des scopes dans `AmendementAN.php` :
```php
// AVANT
public function scopeAdoptes($query)
{
    return $query->where('etat_code', 'ADO');
}

// APRÈS
public function scopeAdoptes($query)
{
    return $query->where('sort_code', 'ADO');
}
```

**Fichier** : `app/Models/AmendementAN.php` (lignes 108-141 + 146-169)

**Impact** : 
- ✅ Statistiques amendements sur profils députés
- ✅ Taux d'adoption calculé correctement
- ✅ Filtres amendements (adopté/rejeté/retiré) fonctionnels

---

### 3️⃣ **Recherche Globale - Colonnes incorrectes**

**Problème** : `GlobalSearchController` utilisait des noms de colonnes incorrects pour les sénateurs et amendements.

**Erreurs identifiées** :
- **Sénateurs** : `nom`/`prenom`/`profession` au lieu de `nom_usuel`/`prenom_usuel`/`description_profession`
- **Amendements** : `numero`/`expose_motifs` au lieu de `numero_long`/`expose`
- **Tags** : Tentative d'accéder à `icon` (colonne inexistante)

**Solution** : Correction de toutes les requêtes dans `GlobalSearchController.php` :
```php
// AVANT (Sénateurs)
$q->where('nom', 'ILIKE', "%{$query}%")

// APRÈS
$q->where('nom_usuel', 'ILIKE', "%{$query}%")
```

**Fichiers** : 
- `app/Http/Controllers/Api/GlobalSearchController.php` (lignes 75-102, 181-206, 294-301)

---

### 4️⃣ **Recherche Codes Postaux - Ancien modèle**

**Problème** : `RepresentantsSearchController` utilisait l'ancien modèle `DeputeSenateur` au lieu des nouveaux `ActeurAN` et `Senateur`.

**Solution** : 
- Remplacement de `DeputeSenateur` par `ActeurAN` et `Senateur`
- Correction des colonnes (`nom_usuel` pour sénateurs)
- Ajout de `mandatActif` accessor dans `ActeurAN`

**Fichiers** :
- `app/Http/Controllers/Api/RepresentantsSearchController.php` (lignes 1-12, 130-172)
- `app/Models/ActeurAN.php` (lignes 91-99)

---

## 📋 COMMANDES SERVEUR À EXÉCUTER

```bash
cd /opt/civicdash
git pull

# 1. Vérifier les amendements en base
docker compose exec app php artisan tinker
>>> \App\Models\AmendementAN::where('sort_code', 'ADO')->count()
>>> \App\Models\AmendementAN::where('sort_code', 'REJ')->count()
>>> \App\Models\AmendementAN::whereNotNull('etat_code')->count()
>>> exit

# 2. Tester la recherche globale
curl "http://localhost/api/search?q=climat&types[]=deputes&types[]=senateurs"

# 3. Tester la recherche de codes postaux
curl "http://localhost/api/representants/search?postal_code=75001"
curl "http://localhost/api/representants/search?q=Paris"

# 4. Vérifier les stats d'un député (ex: Bony)
docker compose exec app php artisan tinker
>>> $depute = \App\Models\ActeurAN::where('nom', 'Bony')->first()
>>> $depute->amendementsAuteur()->count()
>>> $depute->amendementsAuteur()->adoptes()->count()
>>> exit
```

---

## 🎯 RÉSULTATS ATTENDUS

### Amendements sur profils députés
✅ Nombre total d'amendements affiché  
✅ Nombre d'amendements adoptés affiché  
✅ Taux d'adoption calculé correctement  
✅ Filtres par statut (adopté/rejeté/retiré) fonctionnels  

### Recherche globale
✅ Députés trouvés par nom/prénom  
✅ Sénateurs trouvés par nom/prénom  
✅ Scrutins trouvés par titre  
✅ Amendements trouvés par dispositif  
✅ Tags suggérés  

### Recherche codes postaux
✅ Recherche par code postal (75001)  
✅ Recherche par nom de ville (Paris)  
✅ Député de la circonscription trouvé  
✅ Sénateurs du département trouvés  

---

## 📝 NOTES TECHNIQUES

### Structure données amendements
- **63 677 fichiers** dans `public/data/amendements/`
- **29 048 erreurs** probablement dues à :
  - Champs manquants dans certains JSON
  - Formats de dates invalides
  - Textes trop longs pour les colonnes
  
### Colonnes à bien distinguer
- **`etat_code`** : État procédural (AC, EN_COURS, etc.)
- **`sort_code`** : Sort final définitif (ADO, REJ, TOM, RET)
- Utiliser **`sort_code`** pour les statistiques et filtres

### Mapping des codes de sort
```php
'Adopté' => 'ADO',
'Rejeté' => 'REJ',
'Tombé' => 'TOM',
'Retiré' => 'RET',
'Non soutenu' => 'NSO',
'Irrecevable' => 'IRR',
'Satisfait' => 'SAT',
```

---

## 🚀 PROCHAINES ÉTAPES

1. ✅ **Tester sur le serveur** les corrections
2. ⏳ **Réimporter les amendements** si nécessaire (pour corriger les 29k erreurs)
3. ⏳ **Créer une commande** pour recalculer les stats amendements des députés
4. ✅ **Implémenter** les amendements Sénat (data.senat.fr) - **FAIT**
5. ⏳ **Tester** la recherche MeiliSearch (si activée)

---

## 🆕 IMPLÉMENTATIONS AJOUTÉES

### 5️⃣ **Corrections mapping champs amendements**

**Problème** : Dans les contrôleurs, les champs des amendements étaient incorrects :
- `numero` au lieu de `numero_long`
- `sort` au lieu de `sort_code` et `sort_libelle`
- `co_signataires` au lieu de `cosignataires_acteur_refs`
- `expose_sommaire` au lieu de `expose`
- `acteur` au lieu de `auteurActeur`

**Solution** : Correction des transformations de données dans :
- `RepresentantANController::deputeAmendements()` (lignes 322-340)
- `RepresentantANController::deputeActivite()` (lignes 442-461)
- `LegislationController::showAmendement()` (lignes 437-489)

**Fichiers** :
- `app/Http/Controllers/Web/RepresentantANController.php`
- `app/Http/Controllers/Web/LegislationController.php`

---

### 6️⃣ **Accessor mandatActif pour ActeurAN**

**Problème** : `GlobalSearchController` et `RepresentantsSearchController` utilisaient `mandatActif` mais cet accessor n'existait pas.

**Solution** : Ajout de `getMandatActifAttribute()` dans `ActeurAN.php` :
```php
public function getMandatActifAttribute()
{
    return $this->mandats()
        ->where('type_organe', 'ASSEMBLEE')
        ->whereNull('date_fin')
        ->with('organe')
        ->first();
}
```

**Fichier** : `app/Models/ActeurAN.php` (lignes 91-99)

---

### 7️⃣ **Import Amendements Sénat (data.senat.fr)**

**Nouvelle fonctionnalité** : Commande Artisan pour importer les amendements du Sénat depuis le CSV OpenData.

**Features** :
- ✅ Import depuis `https://data.senat.fr/data/opendata/ODSEN_AMEND.csv`
- ✅ Filtrage par législature (année)
- ✅ Mapping automatique des colonnes CSV → BDD
- ✅ Support des cosignataires (JSON)
- ✅ Mapping des codes de sort (ADOPTE, REJETE, TOMBE, etc.)
- ✅ Options `--fresh`, `--limit`, `--legislature`
- ✅ Barre de progression et statistiques

**Commande** :
```bash
php artisan import:amendements-senat --legislature=2024 --fresh
php artisan import:amendements-senat --legislature=2024 --limit=100  # Test
```

**Fichiers créés** :
- `app/Console/Commands/ImportAmendementsSenat.php` (253 lignes)
- `app/Models/AmendementSenat.php` (déjà créé précédemment)
- `database/migrations/2025_11_20_220000_create_amendements_senat_table.php` (déjà créé)

**Structure CSV attendue** :
- `Cle` : UID unique (ex: AMELI1720308S0B0001)
- `Annee` : Législature (ex: 2024)
- `Texte_numero` : Référence du texte
- `Auteur_matricule` : Matricule du sénateur
- `Numero` : Numéro court
- `Numero_long` : Numéro complet
- `Sort` : Sort final (Adopté, Rejeté, Tombé, etc.)
- `Dispositif` : Texte du dispositif
- `Expose` : Exposé sommaire
- `Date_depot`, `Date_sort` : Dates

---

## 📊 RÉCAPITULATIF DES FICHIERS MODIFIÉS

### Modèles
- ✅ `app/Models/AmendementAN.php` - Scopes et accessors corrigés
- ✅ `app/Models/ActeurAN.php` - Ajout accessor `mandatActif`
- ✅ `app/Models/AmendementSenat.php` - **NOUVEAU**

### Contrôleurs
- ✅ `app/Http/Controllers/Api/GlobalSearchController.php` - Colonnes corrigées
- ✅ `app/Http/Controllers/Api/RepresentantsSearchController.php` - Modèles mis à jour
- ✅ `app/Http/Controllers/Web/RepresentantANController.php` - Mapping champs amendements
- ✅ `app/Http/Controllers/Web/LegislationController.php` - Mapping champs amendements

### Commandes
- ✅ `app/Console/Commands/ImportAmendementsAN.php` - Extraction états/sorts
- ✅ `app/Console/Commands/ImportAmendementsSenat.php` - **NOUVEAU**

### Migrations
- ✅ `database/migrations/2025_11_20_220000_create_amendements_senat_table.php` - **NOUVEAU**

### Documentation
- ✅ `CORRECTIONS_AMENDEMENTS_RECHERCHE_20NOV2025.md` - **CE FICHIER**

---

**Dernière mise à jour** : 20 novembre 2025, 23:45

