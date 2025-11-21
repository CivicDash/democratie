# 🎨 VUES SQL SÉNAT - Documentation

**Date** : 21 novembre 2025, 01:45  
**Status** : ✅ 5 VUES CRÉÉES

---

## 📋 VUES CRÉÉES

| # | Nom Vue | Fichier Migration | Tables Sources | Utilité |
|---|---------|-------------------|----------------|---------|
| 1 | `v_senateurs_complets` | `2025_11_21_020000_...` | `sen`, `sennom`, `memgrpsen`, `memcom`, `elusen`, `senbur`, `mel`, `actpro` | **Profils sénateurs enrichis** |
| 2 | `v_senateurs_votes` | `2025_11_21_020100_...` | `votes`, `scr`, `memgrpsen` | **Votes individuels** |
| 3 | `v_senateurs_amendements` | `2025_11_21_020200_...` | `amd`, `amdsen`, `txt_ameli`, `sub`, `sor` | **Amendements** |
| 4 | `v_senateurs_questions` | `2025_11_21_020300_...` | `tam_questions`, `tam_reponses`, `naturequestion`, `tam_ministeres` | **Questions au Gouvernement** |
| 5 | `v_scrutins_senat` | `2025_11_21_020400_...` | `scr`, `typscr`, `ses`, `texte` | **Scrutins Sénat** |

---

## 1️⃣ VUE `v_senateurs_complets`

### Objectif
Mapper les données SQL natives du Sénat vers une structure compatible avec notre modèle `Senateur` existant.

### Colonnes exposées

```sql
- matricule (text)              -- ID sénateur (sen.id converti)
- civilite (text)               -- M. / Mme
- nom_usuel (text)              -- Nom actuel
- prenom_usuel (text)           -- Prénom actuel
- etat (text)                   -- ACTIF / ANCIEN
- date_naissance (date)         -- Date naissance
- date_deces (date)             -- Date décès (si décédé)
- groupe_politique (text)       -- Groupe actuel
- type_appartenance_groupe (text) -- Membre / Rattaché
- commission_permanente (text)  -- Commission actuelle
- circonscription (text)        -- Département
- fonction_bureau_senat (text)  -- Fonction au Bureau
- email (text)                  -- Email
- pcs_insee (text)              -- Code PCS
- categorie_socio_pro (text)    -- CSP
- description_profession (text) -- Profession détaillée
- created_at (timestamp)        -- Date création
- updated_at (timestamp)        -- Date MAJ
```

### Usage dans Eloquent

```php
class Senateur extends Model {
    // Option 1 : Garder notre table actuelle
    protected $table = 'senateurs';
    
    // Option 2 : Utiliser la vue SQL (recommandé après import)
    // protected $table = 'v_senateurs_complets';
}
```

### Particularités
- **Sous-requêtes** pour groupe et commission actuels (celles sans date de fin)
- **Jointure LEFT** pour gérer les données manquantes
- **Cast `id::text`** pour compatibilité avec notre clé primaire string

---

## 2️⃣ VUE `v_senateurs_votes`

### Objectif
Exposer les votes individuels des sénateurs avec détails du scrutin.

### Colonnes exposées

```sql
- id (bigint)                   -- ID vote
- senateur_matricule (text)     -- Lien vers sénateur
- scrutin_id (bigint)           -- ID scrutin
- date_vote (timestamp)         -- Date du vote
- intitule (text)               -- Intitulé du scrutin
- objet (text)                  -- Objet du vote
- position (text)               -- pour / contre / abstention / non_votant
- resultat_scrutin (text)       -- Résultat global
- groupe_politique (text)       -- Groupe du sénateur au moment du vote
- type_scrutin (text)           -- Type de scrutin
- created_at (timestamp)        -- Date création
```

### Usage dans Eloquent

```php
class SenateurVote extends Model {
    protected $table = 'v_senateurs_votes';
    public $timestamps = false;
    protected $primaryKey = 'id';
    
    public function senateur() {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }
}

// Dans Senateur.php
public function votesSenat(): HasMany {
    return $this->hasMany(SenateurVote::class, 'senateur_matricule', 'matricule');
}
```

### Particularités
- **Mapping position** : `P` → `pour`, `C` → `contre`, `A` → `abstention`
- **Groupe au moment du vote** : Sous-requête avec dates pour avoir le groupe historique
- **Tri par date** décroissante

---

## 3️⃣ VUE `v_senateurs_amendements`

### Objectif
Exposer les amendements déposés par les sénateurs (base AMELI).

### Colonnes exposées

```sql
- uid (bigint)                  -- ID amendement
- senateur_matricule (text)     -- Lien vers sénateur
- numero (text)                 -- Numéro amendement
- numero_long (text)            -- Numéro complet
- texte_ref (bigint)            -- ID texte législatif
- texte_titre (text)            -- Titre du texte
- article (text)                -- Article visé
- alinea (text)                 -- Alinéa visé
- dispositif (text)             -- Dispositif de l'amendement
- expose (text)                 -- Exposé des motifs
- auteur_type (text)            -- SENATEUR / GOUVERNEMENT / COMMISSION
- auteur_nom (text)             -- Nom auteur
- auteur_groupe_id (bigint)     -- ID groupe
- sort_code (text)              -- Code sort
- sort_libelle (text)           -- Libellé sort
- avis_commission (text)        -- Avis de la commission
- avis_gouvernement (text)      -- Avis du gouvernement
- date_depot (timestamp)        -- Date dépôt
- date_sort (timestamp)         -- Date sort
- date_seance (timestamp)       -- Date séance
- created_at (timestamp)        -- Date création
```

### Usage dans Eloquent

```php
class SenateurAmendement extends Model {
    protected $table = 'v_senateurs_amendements';
    protected $primaryKey = 'uid';
    public $timestamps = false;
    
    public function senateur() {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }
    
    // Scopes
    public function scopeAdoptes($query) {
        return $query->where('sort_code', 'ADOPTE');
    }
    
    public function scopeRejetes($query) {
        return $query->where('sort_code', 'REJETE');
    }
}

// Dans Senateur.php
public function amendementsSenat(): HasMany {
    return $this->hasMany(SenateurAmendement::class, 'senateur_matricule', 'matricule');
}
```

### Particularités
- **Mapping auteur_type** : `S` → `SENATEUR`, `G` → `GOUVERNEMENT`, `C` → `COMMISSION`
- **Jointures multiples** pour récupérer texte, sort, avis
- **Tri par date** de dépôt décroissante

---

## 4️⃣ VUE `v_senateurs_questions`

### Objectif
Exposer les questions au gouvernement posées par les sénateurs.

### Colonnes exposées

```sql
- uid (bigint)                  -- ID question
- senateur_matricule (text)     -- Lien vers sénateur
- type_question_code (text)     -- Code type
- type_question (text)          -- Type (écrite, orale, QAG...)
- legislature (integer)         -- Numéro législature
- numero_question (text)        -- Numéro question
- objet (text)                  -- Objet
- texte_question (text)         -- Texte complet question
- texte_reponse (text)          -- Texte complet réponse
- ministere_destinataire (text) -- Ministère concerné
- date_depot (timestamp)        -- Date dépôt
- date_transmission (timestamp) -- Date transmission
- date_signalement (timestamp)  -- Date signalement
- date_reponse (timestamp)      -- Date réponse
- date_cloture (timestamp)      -- Date clôture
- etat_code (text)              -- Code état
- etat (text)                   -- État
- sort_code (text)              -- Code sort
- sort (text)                   -- Sort
- delai_reponse_jours (integer) -- Délai réponse (calculé)
- theme (text)                  -- Thème
- url_senat (text)              -- URL sur senat.fr
- created_at (timestamp)        -- Date création
```

### Usage dans Eloquent

```php
class SenateurQuestion extends Model {
    protected $table = 'v_senateurs_questions';
    protected $primaryKey = 'uid';
    public $timestamps = false;
    
    protected $casts = [
        'date_depot' => 'datetime',
        'date_reponse' => 'datetime',
    ];
    
    public function senateur() {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }
    
    // Scopes
    public function scopeRepondues($query) {
        return $query->whereNotNull('texte_reponse');
    }
    
    public function scopeEnAttente($query) {
        return $query->whereNull('texte_reponse');
    }
}

// Dans Senateur.php
public function questionsSenat(): HasMany {
    return $this->hasMany(SenateurQuestion::class, 'senateur_matricule', 'matricule');
}
```

### Particularités
- **Calcul délai réponse** : Différence entre date_reponse et date_depot en jours
- **URL construite** : Basée sur le numéro de question
- **Jointures complexes** pour récupérer tous les détails

---

## 5️⃣ VUE `v_scrutins_senat`

### Objectif
Exposer les scrutins du Sénat (indépendamment des votes individuels).

### Colonnes exposées

```sql
- uid (text)                    -- ID scrutin (converti)
- numero (integer)              -- Numéro scrutin
- date_scrutin (timestamp)      -- Date
- intitule (text)               -- Intitulé
- objet (text)                  -- Objet
- type_code (text)              -- Code type
- type_libelle (text)           -- Libellé type
- pour (integer)                -- Nombre pour
- contre (integer)              -- Nombre contre
- abstentions (integer)         -- Nombre abstentions
- non_votants (integer)         -- Nombre non votants
- nombre_votants (integer)      -- Total votants
- suffrages_exprimes (integer)  -- Suffrages exprimés
- resultat_code (text)          -- Code résultat
- resultat_libelle (text)       -- Libellé résultat
- session (text)                -- Session
- annee_session (integer)       -- Année session
- texte_titre (text)            -- Titre texte associé
- texte_numero (text)           -- Numéro texte
- created_at (timestamp)        -- Date création
- updated_at (timestamp)        -- Date MAJ
```

### Usage dans Eloquent

```php
class ScrutinSenat extends Model {
    protected $table = 'v_scrutins_senat';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $casts = [
        'date_scrutin' => 'datetime',
    ];
    
    public function votes() {
        return $this->hasMany(SenateurVote::class, 'scrutin_id', 'uid');
    }
}
```

### Particularités
- **Cast `id::text`** pour cohérence avec ScrutinAN
- **Résultats agrégés** directement dans la vue
- **Lien vers texte** législatif si disponible

---

## 🚀 PROCHAINES ÉTAPES

### 1. Attendre la fin de l'import SQL (en cours)

```bash
# Vérifier si l'import est terminé
tail -f /tmp/import_senateurs_test.log
```

### 2. Appliquer les migrations (après import)

```bash
php artisan migrate
```

**Résultat attendu** : 5 vues SQL créées

### 3. Créer les modèles Eloquent

```bash
php artisan make:model SenateurVote
php artisan make:model SenateurQuestion
# Adapter SenateurAmendement existant
# Créer ScrutinSenat
```

### 4. Tester les vues

```bash
php artisan tinker
```

```php
// Tester la vue sénateurs
DB::table('v_senateurs_complets')->count();
DB::table('v_senateurs_complets')->first();

// Tester la vue votes
DB::table('v_senateurs_votes')->count();

// Tester la vue amendements
DB::table('v_senateurs_amendements')->count();

// Tester la vue questions
DB::table('v_senateurs_questions')->count();

// Tester la vue scrutins
DB::table('v_scrutins_senat')->count();
```

### 5. Adapter les controllers

Modifier `RepresentantANController::showSenateur()` pour utiliser les nouvelles relations.

---

## ⚠️ NOTES IMPORTANTES

### Préfixes des tables SQL

**ATTENTION** : Les vues supposent que les tables SQL sont **SANS préfixe**.

Si l'import ajoute un préfixe `senat_` :
- `sen` → `senat_sen`
- `votes` → `senat_votes`
- etc.

**IL FAUDRA MODIFIER LES VUES** pour ajouter le préfixe partout :

```sql
FROM senat_sen          -- Au lieu de FROM sen
JOIN senat_votes        -- Au lieu de JOIN votes
```

### Noms de colonnes

Les vues font des hypothèses sur les noms de colonnes basées sur l'analyse.

Si les noms réels sont différents, **il faudra ajuster les vues** après inspection des tables réelles.

### Performances

Les vues avec sous-requêtes peuvent être lentes sur de gros volumes.

**Solutions** :
1. Indexer les tables SQL sources
2. Créer des **vues matérialisées** (MATERIALIZED VIEW) pour cache
3. Ajouter des index sur les vues

---

## 📊 RÉSUMÉ

**5 vues SQL créées** ✅  
**Prêtes à être appliquées** après l'import  
**Mapping complet** des données Sénat vers notre architecture Laravel

**Prochaine action** : Attendre la fin de l'import puis `php artisan migrate`

---

**Document créé le** : 21 novembre 2025, 01:50  
**Status** : ✅ VUES PRÊTES À DÉPLOYER

