# 🔍 ANALYSE COMPLÈTE - AMENDEMENTS DÉPUTÉS & SÉNATEURS

## 📊 RÉSUMÉ DES PROBLÈMES IDENTIFIÉS

### 1. AMENDEMENTS DÉPUTÉS (AN)

#### ✅ Structure OK
- **Table** : `amendements_an`
- **Clé primaire** : `uid` (string)
- **Colonne auteur** : `auteur_acteur_ref` → `uid` de `ActeurAN`
- **Modèle** : `AmendementAN` avec scopes `adoptes()`, `rejetes()`, `retires()`

#### ❌ PROBLÈME IDENTIFIÉ : Colonnes sort_code
Le controller utilise les scopes mais la vue affiche `sort_code` et `sort_libelle`.

**Codes attendus dans AN** :
- `ADO` = Adopté
- `REJ` = Rejeté
- `RET` = Retiré
- `TOM` = Tombé

**À vérifier en BDD** :
```sql
SELECT DISTINCT sort_code, sort_libelle, COUNT(*) 
FROM amendements_an 
GROUP BY sort_code, sort_libelle;
```

---

### 2. AMENDEMENTS SÉNATEURS

#### ❌ PROBLÈME CRITIQUE : Colonnes du modèle vs Vue SQL

**Modèle `AmendementSenat`** attend :
- `auteur_senateur_matricule` (colonne fillable)
- `senateur_matricule` (relation `auteur()`)

**Vue SQL `amendements_senat`** fournit :
- `senateur_matricule` ✅ (via jointure sen_ameli)
- Mais PAS `auteur_senateur_matricule` ❌

**Controller `senateurAmendements`** ligne 754-755 :
```php
$query = AmendementSenat::query()
    ->where('senateur_matricule', $matricule);  // ✅ OK
```

**Modèle `AmendementSenat`** ligne 70-73 :
```php
public function auteur(): BelongsTo
{
    return $this->belongsTo(Senateur::class, 'auteur_senateur_matricule', 'matricule');
    // ❌ ERREUR : La colonne s'appelle 'senateur_matricule' dans la vue !
}
```

#### ❌ PROBLÈME : Codes sort_code incohérents

**Modèle `AmendementSenat`** scopes :
```php
public function scopeAdoptes($query) {
    return $query->where('sort_code', 'ADOPTE');  // ❌ Attend 'ADOPTE'
}
```

**Vue SQL `amendements_senat`** :
```sql
sor.cod AS sort_code  -- Fournit le code brut de senat_ameli_sor
```

**À vérifier** : Quels sont les codes réels dans `senat_ameli_sor.cod` ?
- Probablement `ADO`, `REJ`, `RET` comme l'AN
- Ou peut-être `ADOPTE`, `REJETE`, `RETIRE` (format long)

---

### 3. VOTES SÉNATEURS

#### ✅ Vue SQL OK
La vue `senateurs_votes` fait bien le mapping :
```sql
CASE 
    WHEN v.posvotcod = 'P' THEN 'pour'
    WHEN v.posvotcod = 'C' THEN 'contre'
    WHEN v.posvotcod = 'A' THEN 'abstention'
    WHEN v.posvotcod = 'NV' THEN 'non_votant'
    ELSE v.posvotcod
END AS position
```

#### ❌ PROBLÈME : Relation dans VoteSenat

**Modèle `VoteSenat`** ligne 28-29 :
```php
public function senateur(): BelongsTo
{
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'id');
    // ❌ ERREUR : Local key devrait être 'matricule' pas 'id'
}
```

**Correction** :
```php
public function senateur(): BelongsTo
{
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
}
```

---

### 4. MODÈLE SENATEUR

#### ❌ PROBLÈME : Relation votesSenat

**Ligne 74-77** :
```php
public function votesSenat(): HasMany
{
    return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'id');
    // ❌ ERREUR : Local key devrait être 'matricule' pas 'id'
}
```

**Correction** :
```php
public function votesSenat(): HasMany
{
    return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'matricule');
}
```

#### ❌ PROBLÈME : Relation amendementsSenat

**Ligne 79-82** :
```php
public function amendementsSenat(): HasMany
{
    return $this->hasMany(AmendementSenat::class, 'auteur_senateur_matricule', 'matricule');
    // ❌ ERREUR : Foreign key dans la vue est 'senateur_matricule'
}
```

**Correction** :
```php
public function amendementsSenat(): HasMany
{
    return $this->hasMany(AmendementSenat::class, 'senateur_matricule', 'matricule');
}
```

---

## 🛠️ CORRECTIONS À APPLIQUER

### Correction 1 : Modèle `Senateur`
```php
// Ligne 74-77 : votesSenat
public function votesSenat(): HasMany
{
    return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'matricule');
}

// Ligne 79-82 : amendementsSenat
public function amendementsSenat(): HasMany
{
    return $this->hasMany(AmendementSenat::class, 'senateur_matricule', 'matricule');
}
```

### Correction 2 : Modèle `VoteSenat`
```php
// Ligne 28-29 : senateur
public function senateur(): BelongsTo
{
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
}
```

### Correction 3 : Modèle `AmendementSenat`
```php
// Ligne 70-73 : auteur
public function auteur(): BelongsTo
{
    return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
}
```

### Correction 4 : Scopes AmendementSenat (si codes = ADO/REJ/RET)
```php
public function scopeAdoptes($query) {
    return $query->where('sort_code', 'ADO'); // ou whereIn(['ADO', 'ADOPTE'])
}

public function scopeRejetes($query) {
    return $query->where('sort_code', 'REJ');
}

public function scopeRetires($query) {
    return $query->where('sort_code', 'RET');
}
```

---

## 📋 CHECKLIST AVANT DÉPLOIEMENT

### Vérifications BDD
```sql
-- 1. Vérifier les codes sort dans amendements AN
SELECT DISTINCT sort_code, sort_libelle, COUNT(*) 
FROM amendements_an 
GROUP BY sort_code, sort_libelle 
ORDER BY COUNT(*) DESC;

-- 2. Vérifier les codes sort dans amendements Sénat (vue)
SELECT DISTINCT sort_code, sort_libelle, COUNT(*) 
FROM amendements_senat 
GROUP BY sort_code, sort_libelle 
ORDER BY COUNT(*) DESC;

-- 3. Vérifier les positions de vote Sénat
SELECT DISTINCT position, COUNT(*) 
FROM senateurs_votes 
GROUP BY position 
ORDER BY COUNT(*) DESC;

-- 4. Vérifier la liaison amendements → sénateurs
SELECT 
    a.senateur_matricule,
    s.nom_usuel,
    COUNT(*) as nb_amendements
FROM amendements_senat a
LEFT JOIN senateurs s ON a.senateur_matricule = s.matricule
GROUP BY a.senateur_matricule, s.nom_usuel
HAVING COUNT(*) > 0
ORDER BY nb_amendements DESC
LIMIT 10;
```

---

## 🎯 IMPACT SUR LES VUES

### Vue Amendements Députés (`Deputes/Amendements.vue`)
- ✅ Utilise `sort_code` et `sort_libelle` correctement
- ✅ Badges colorés selon `ADO`, `REJ`, `RET`

### Vue Amendements Sénateurs (`Senateurs/Amendements.vue`)
- ✅ Utilise `sort_code` et `sort_libelle`
- ❓ À vérifier : les codes correspondent-ils ?

### Vue Votes Sénateurs (`Senateurs/Votes.vue`)
- ✅ Positions mappées dans la vue SQL (`pour`, `contre`, `abstention`)
- ✅ Résultat scrutin calculé dynamiquement

---

## 📊 DONNÉES ATTENDUES APRÈS CORRECTIONS

### Pour un sénateur (ex: 19954N)
- **Amendements** : 10-100 (si actif depuis longtemps)
- **Votes** : 100-500 (selon participation)
- **Positions** : Répartition pour/contre/abstention

### Statistiques globales
- **Total amendements Sénat** : 50 000+ (AMELI)
- **Total votes Sénat** : 500 000+ (senat_senateurs_votes)
- **Total scrutins Sénat** : 2 000+ (senat_senateurs_scr)

---

**Document créé le 21 nov 2025**
**À appliquer avant tests et déploiement**

