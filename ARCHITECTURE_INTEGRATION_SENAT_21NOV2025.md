# 🏗️ ARCHITECTURE SÉNAT : Intégration des Bases SQL

**Date** : 21 novembre 2025, 01:20  
**Objectif** : Déterminer la meilleure stratégie d'intégration des 443 tables SQL

---

## 🎯 PROBLÉMATIQUE

Nous avons actuellement :
- **Tables Laravel** : Structure simplifiée, normalisée
- **Tables SQL Sénat** : Structure native, très complète (443 tables)

**Question** : Faut-il remplacer nos tables ou créer un pont ?

---

## 📊 COMPARAISON : NOS TABLES vs TABLES SQL

### Notre table `senateurs` (actuelle)

```sql
CREATE TABLE senateurs (
    matricule VARCHAR(10) PRIMARY KEY,
    civilite VARCHAR(10),
    nom_usuel VARCHAR(100),
    prenom_usuel VARCHAR(100),
    etat ENUM('ACTIF', 'ANCIEN'),
    date_naissance DATE,
    date_deces DATE,
    groupe_politique VARCHAR(100),        -- DÉNORMALISÉ (snapshot)
    type_appartenance_groupe VARCHAR(50), -- DÉNORMALISÉ
    commission_permanente VARCHAR(100),   -- DÉNORMALISÉ (snapshot)
    circonscription VARCHAR(100),
    fonction_bureau_senat VARCHAR(100),
    email VARCHAR(255),
    pcs_insee VARCHAR(50),
    categorie_socio_pro VARCHAR(100),
    description_profession VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Avantages** :
- ✅ Simple et rapide à requêter
- ✅ Colonnes dénormalisées (groupe, commission) pour accès direct
- ✅ Compatible avec nos modèles Eloquent
- ✅ Timestamps Laravel

**Inconvénients** :
- ❌ Pas d'historique (groupe_politique = snapshot)
- ❌ Pas de scrutins/votes
- ❌ Pas d'amendements
- ❌ Pas de questions

### Tables SQL Sénat (natives)

#### Table `sen` (profils)
```sql
CREATE TABLE sen (
    id INTEGER PRIMARY KEY,
    -- Énormément de colonnes techniques
    sendatnai TIMESTAMP,      -- Date naissance
    sendatdec TIMESTAMP,      -- Date décès
    syscredat TIMESTAMP,
    sysmajdat TIMESTAMP,
    -- + ~50 autres colonnes
);
```

#### Table `memgrpsen` (groupes - HISTORIQUE)
```sql
CREATE TABLE memgrpsen (
    senateur_id INTEGER,
    groupe_id INTEGER,
    memgrpsendatent TIMESTAMP,  -- Date entrée
    memgrpsendatsor TIMESTAMP,  -- Date sortie
    -- Historique complet !
);
```

#### Table `scr` (scrutins)
```sql
CREATE TABLE scr (
    id INTEGER PRIMARY KEY,
    scrdat TIMESTAMP,        -- Date scrutin
    scrint TEXT,             -- Intitulé
    -- + détails scrutin
);
```

#### Table `votes` (votes individuels)
```sql
CREATE TABLE votes (
    senateur_id INTEGER,
    scrutin_id INTEGER,
    position VARCHAR(20),    -- Pour, Contre, Abstention
    -- + détails vote
);
```

**Avantages** :
- ✅ Données exhaustives (443 tables)
- ✅ Historique complet
- ✅ Scrutins et votes
- ✅ Amendements complets
- ✅ Questions au gouvernement
- ✅ Structure officielle du Sénat

**Inconvénients** :
- ❌ Très complexe (443 tables !)
- ❌ Noms de colonnes cryptiques (`sendatnai`, `memgrpsendatent`...)
- ❌ Pas de timestamps Laravel
- ❌ Pas compatible direct avec Eloquent
- ❌ Nécessite des jointures complexes

---

## 🎯 STRATÉGIES POSSIBLES

### OPTION A : Remplacer nos tables (❌ NON RECOMMANDÉ)

**Principe** : Supprimer `senateurs`, utiliser directement les tables SQL

```php
// Modèle Senateur pointant vers la table SQL native
class Senateur extends Model {
    protected $table = 'sen';  // Table SQL native
    protected $primaryKey = 'id';
    // ...
}
```

**Avantages** :
- ✅ Accès direct aux données complètes
- ✅ Pas de duplication

**Inconvénients** :
- ❌ Casse TOUS les modèles existants
- ❌ Noms de colonnes cryptiques partout dans le code
- ❌ Complexité énorme (443 tables)
- ❌ Pas de timestamps Laravel
- ❌ Migrations futures impossibles
- ❌ Perte de contrôle sur la structure

**Verdict** : 🚫 **À ÉVITER** - Trop risqué et complexe

---

### OPTION B : Tables SQL + Vues SQL (⭐ RECOMMANDÉ)

**Principe** : Garder nos tables, créer des vues SQL qui mappent les tables SQL natives

```sql
-- Vue SQL qui unifie sen + memgrpsen + memcom
CREATE OR REPLACE VIEW v_senateurs_enrichis AS
SELECT 
    sen.id AS matricule,
    sen.civilite,
    sen.nom AS nom_usuel,
    sen.prenom AS prenom_usuel,
    sen.etat,
    sen.sendatnai AS date_naissance,
    sen.sendatdec AS date_deces,
    -- Groupe actuel (sous-requête)
    (SELECT grp.libelle 
     FROM memgrpsen msg 
     JOIN grppol grp ON msg.groupe_id = grp.id
     WHERE msg.senateur_id = sen.id 
     AND msg.memgrpsendatsor IS NULL 
     LIMIT 1) AS groupe_politique,
    -- Commission actuelle (sous-requête)
    (SELECT com.libelle 
     FROM memcom mc 
     JOIN com ON mc.commission_id = com.id
     WHERE mc.senateur_id = sen.id 
     AND mc.memcomdatfin IS NULL 
     LIMIT 1) AS commission_permanente,
    -- ...
FROM sen
WHERE sen.etat = 'ACTIF';
```

**Architecture** :

```
┌─────────────────────────────────────────────┐
│  TABLES SQL SÉNAT (443 tables)             │
│  ├─ sen (profils)                           │
│  ├─ memgrpsen (groupes)                     │
│  ├─ memcom (commissions)                    │
│  ├─ scr (scrutins)                          │
│  ├─ votes (votes individuels)               │
│  ├─ amd (amendements)                       │
│  └─ ...                                     │
└─────────────────────────────────────────────┘
              ↓ (VUES SQL)
┌─────────────────────────────────────────────┐
│  VUES COMPATIBLES LARAVEL                  │
│  ├─ v_senateurs_enrichis                    │
│  ├─ v_senateurs_votes                       │
│  ├─ v_senateurs_amendements                 │
│  ├─ v_senateurs_questions                   │
│  └─ ...                                     │
└─────────────────────────────────────────────┘
              ↓ (ELOQUENT)
┌─────────────────────────────────────────────┐
│  MODÈLES LARAVEL (existants)               │
│  ├─ Senateur (pointe vers vue ou table)    │
│  ├─ SenateurVote (nouvelle)                │
│  ├─ SenateurAmendement (nouvelle)          │
│  ├─ SenateurQuestion (nouvelle)            │
│  └─ ...                                     │
└─────────────────────────────────────────────┘
```

**Avantages** :
- ✅ Garde nos tables existantes (pas de casse)
- ✅ Accès aux données SQL via vues
- ✅ Noms de colonnes propres dans les vues
- ✅ Modèles Eloquent inchangés (ou minimes)
- ✅ Peut mixer table + vue selon besoin
- ✅ Flexibilité maximale

**Inconvénients** :
- ⚠️ Nécessite de créer des vues SQL
- ⚠️ Performances légèrement inférieures (vues)
- ⚠️ Maintenance des vues

**Verdict** : ⭐⭐⭐ **RECOMMANDÉ** - Meilleur compromis

---

### OPTION C : Tables SQL + ETL vers nos tables (🟡 POSSIBLE)

**Principe** : Importer les tables SQL, puis copier/transformer vers nos tables

```php
// Commande ETL
php artisan senat:sync-from-sql

// Qui fait :
DB::table('senateurs')->truncate();

DB::table('senateurs')->insert(
    DB::table('sen')
        ->select([
            'id as matricule',
            DB::raw("CASE WHEN sendatnai IS NOT NULL THEN sendatnai::date END as date_naissance"),
            // ... mapping complet
        ])
        ->get()
);
```

**Avantages** :
- ✅ Garde nos tables
- ✅ Contrôle total sur les données
- ✅ Peut transformer/nettoyer les données
- ✅ Performances optimales (pas de vues)

**Inconvénients** :
- ❌ Duplication des données
- ❌ Synchronisation nécessaire
- ❌ Espace disque doublé
- ❌ Risque de désynchronisation

**Verdict** : 🟡 **POSSIBLE** - Si performances critiques

---

## 🚀 RECOMMANDATION FINALE : OPTION B (Vues SQL)

### Architecture proposée

#### 1. Tables SQL natives (préfixées `senat_*`)

Lors de l'import, préfixer toutes les tables :

```sql
CREATE TABLE senat_sen (...);           -- Au lieu de sen
CREATE TABLE senat_memgrpsen (...);     -- Au lieu de memgrpsen
CREATE TABLE senat_scr (...);           -- Au lieu de scr
CREATE TABLE senat_votes (...);         -- Au lieu de votes
CREATE TABLE senat_amd (...);           -- Au lieu de amd (AMELI)
CREATE TABLE senat_tam_questions (...); -- Au lieu de tam_questions
-- etc.
```

**Avantage** : Pas de conflit avec nos tables existantes !

#### 2. Vues SQL pour Eloquent

```sql
-- Vue pour le modèle Senateur (enrichie)
CREATE OR REPLACE VIEW v_senateurs_complets AS
SELECT 
    ss.id::text AS matricule,
    ss.civilite,
    -- ... mapping complet des colonnes
FROM senat_sen ss
LEFT JOIN LATERAL (
    SELECT grp.libelle, grp.sigle
    FROM senat_memgrpsen msg
    JOIN senat_grppol grp ON msg.groupe_id = grp.id
    WHERE msg.senateur_id = ss.id
    AND msg.memgrpsendatsor IS NULL
    ORDER BY msg.memgrpsendatent DESC
    LIMIT 1
) AS groupe_actuel ON true
-- ... autres jointures
;

-- Vue pour les votes (nouvelle)
CREATE OR REPLACE VIEW v_senateurs_votes AS
SELECT 
    sv.senateur_id::text AS senateur_matricule,
    ss.scrdat AS date_vote,
    ss.scrint AS intitule,
    sv.position,
    -- ...
FROM senat_votes sv
JOIN senat_scr ss ON sv.scrutin_id = ss.id
JOIN senat_sen sen ON sv.senateur_id = sen.id;

-- Vue pour les amendements (nouvelle)
CREATE OR REPLACE VIEW v_senateurs_amendements AS
SELECT 
    sa.id AS uid,
    sa.senateur_id::text AS senateur_matricule,
    sa.dispositif,
    sa.sort,
    -- ...
FROM senat_amd sa
JOIN senat_sen sen ON sa.auteur_id = sen.id;

-- Vue pour les questions (nouvelle)
CREATE OR REPLACE VIEW v_senateurs_questions AS
SELECT 
    stq.id AS uid,
    stq.senateur_id::text AS senateur_matricule,
    stq.txtque AS texte_question,
    str.txtrep AS texte_reponse,
    -- ...
FROM senat_tam_questions stq
LEFT JOIN senat_tam_reponses str ON stq.id = str.question_id;
```

#### 3. Modèles Eloquent

```php
// Modèle Senateur (INCHANGÉ ou presque)
class Senateur extends Model {
    protected $table = 'senateurs';  // Notre table actuelle
    // OU
    protected $table = 'v_senateurs_complets'; // Vue SQL
    
    // Relations vers les nouvelles données
    public function votesSenat(): HasMany {
        return $this->hasMany(SenateurVote::class, 'senateur_matricule', 'matricule');
    }
}

// Nouveau modèle pour les votes
class SenateurVote extends Model {
    protected $table = 'v_senateurs_votes';
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;
}

// Nouveau modèle pour les amendements
class SenateurAmendement extends Model {
    protected $table = 'v_senateurs_amendements';
    // ...
}

// Nouveau modèle pour les questions
class SenateurQuestion extends Model {
    protected $table = 'v_senateurs_questions';
    // ...
}
```

#### 4. Controllers (usage)

```php
// Dans RepresentantANController (ou SenateurController)

public function showSenateur($matricule) {
    $senateur = Senateur::with([
        'votesSenat',           // Via vue SQL
        'amendementsSenat',     // Via vue SQL
        'questionsSenat',       // Via vue SQL
    ])->findOrFail($matricule);
    
    // Calculs
    $stats = [
        'votes' => [
            'total' => $senateur->votesSenat->count(),
            'pour' => $senateur->votesSenat->where('position', 'pour')->count(),
            'contre' => $senateur->votesSenat->where('position', 'contre')->count(),
        ],
        'amendements' => [
            'total' => $senateur->amendementsSenat->count(),
            'adoptes' => $senateur->amendementsSenat->where('sort', 'ADOPTE')->count(),
        ],
        'questions' => [
            'total' => $senateur->questionsSenat->count(),
            'repondues' => $senateur->questionsSenat->whereNotNull('texte_reponse')->count(),
        ],
    ];
    
    return Inertia::render('Representants/Senateurs/Show', [
        'senateur' => $senateur,
        'stats' => $stats,
    ]);
}
```

---

## 📋 PLAN D'ACTION

### Phase 1 : Import avec préfixe ✅ (en cours)

```bash
php artisan import:senat-sql senateurs --fresh --prefix=senat_
```

**Résultat** : 336 tables préfixées `senat_*`

### Phase 2 : Créer les vues SQL (2h dev)

```bash
php artisan migrate --path=database/migrations/vues_senat
```

Créer :
- `2025_11_21_create_view_senateurs_complets.php`
- `2025_11_21_create_view_senateurs_votes.php`
- `2025_11_21_create_view_senateurs_amendements.php`
- `2025_11_21_create_view_senateurs_questions.php`

### Phase 3 : Créer les modèles Eloquent (1h dev)

- `app/Models/SenateurVote.php`
- `app/Models/SenateurAmendement.php` (adapter l'existant)
- `app/Models/SenateurQuestion.php`

### Phase 4 : Adapter les controllers (2h dev)

- Ajouter relations dans `Senateur.php`
- Modifier `RepresentantANController::showSenateur()`
- Créer méthodes pour votes/amendements/questions

### Phase 5 : Créer les pages Vue.js (3h dev)

- `Senateurs/Votes.vue`
- `Senateurs/Amendements.vue`
- `Senateurs/Questions.vue`
- `Senateurs/Activite.vue`

**TOTAL** : ~8h de dev après l'import

---

## 🎯 AVANTAGES DE CETTE APPROCHE

1. **Pas de casse** : Nos tables actuelles restent intactes
2. **Flexibilité** : On peut choisir table ou vue selon le besoin
3. **Performance** : Vues PostgreSQL très optimisées
4. **Maintenance** : Vues = abstractions propres
5. **Évolutivité** : Facile d'ajouter de nouvelles vues
6. **Testabilité** : Peut tester avec/sans vues
7. **Rollback facile** : Supprimer les vues = retour à l'ancien

---

## ⚠️ POINTS D'ATTENTION

### 1. Préfixe des tables SQL

**IMPORTANT** : Modifier `ImportSenatSQL.php` pour ajouter un préfixe

```php
// Dans ImportSenatSQL::importSqlDump()
$sqlContent = str_replace(
    'CREATE TABLE ',
    'CREATE TABLE senat_',
    $sqlContent
);
```

### 2. Clés primaires

Les tables SQL utilisent des `id` entiers, nos tables utilisent `matricule` string.

**Solution** : Cast dans les vues :
```sql
ss.id::text AS matricule
```

### 3. Noms de colonnes

Mapper les noms cryptiques vers nos noms :
- `sendatnai` → `date_naissance`
- `sendatdec` → `date_deces`
- `memgrpsendatent` → `date_entree_groupe`
- etc.

### 4. Performances

Les vues avec jointures peuvent être lentes.

**Solution** : Indexer les tables SQL :
```sql
CREATE INDEX idx_senat_memgrpsen_senateur ON senat_memgrpsen(senateur_id, memgrpsendatsor);
```

---

## 📊 SCHÉMA FINAL

```
┌──────────────────────────────────────────────┐
│  APPLICATION LARAVEL                         │
│                                               │
│  ┌────────────────────────────────────────┐  │
│  │  MODÈLES ELOQUENT                      │  │
│  │  ├─ Senateur (table senateurs)         │  │
│  │  ├─ SenateurVote (vue)                 │  │
│  │  ├─ SenateurAmendement (vue)           │  │
│  │  └─ SenateurQuestion (vue)             │  │
│  └────────────────────────────────────────┘  │
│                ↓                              │
│  ┌────────────────────────────────────────┐  │
│  │  VUES SQL                              │  │
│  │  ├─ v_senateurs_complets               │  │
│  │  ├─ v_senateurs_votes                  │  │
│  │  ├─ v_senateurs_amendements            │  │
│  │  └─ v_senateurs_questions              │  │
│  └────────────────────────────────────────┘  │
│                ↓                              │
│  ┌────────────────────────────────────────┐  │
│  │  TABLES SQL SÉNAT (443 tables)        │  │
│  │  ├─ senat_sen                          │  │
│  │  ├─ senat_memgrpsen                    │  │
│  │  ├─ senat_scr                          │  │
│  │  ├─ senat_votes                        │  │
│  │  ├─ senat_amd                          │  │
│  │  └─ ...                                │  │
│  └────────────────────────────────────────┘  │
│                                               │
└──────────────────────────────────────────────┘
```

---

**Document créé le** : 21 novembre 2025, 01:30  
**Status** : ✅ ARCHITECTURE DÉFINIE  
**Recommandation** : **OPTION B (Vues SQL) ⭐⭐⭐**

