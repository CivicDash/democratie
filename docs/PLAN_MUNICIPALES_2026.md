# 🏛️ Plan Agent IA — Élections Municipales 2026 — CivicDash
## Plan d'intégration dans l'architecture existante

> **Repo** : https://github.com/CivicDash/democratie (branche `dev`)  
> **Stack** : Laravel 11 / PHP 8.3 / PostgreSQL 15 / Vue 3 + Inertia.js / Tailwind CSS / Meilisearch  
> **Date** : 21 mars 2026 — T1 passé (15/03), T2 demain (22/03)  
> **Cible** : Agent IA Opus 4.6 via Claude Code

---

## 🔍 Audit de l'existant — Ce qui est DÉJÀ en place

### Tables et modèles existants

| Entité | Table | Modèle | État |
|--------|-------|--------|------|
| **Maire** | `maires` | `App\Models\Maire` | ✅ Complet — uid, nom, prenom, code_commune, nuance_politique, photo, mandature, en_exercice, Meilisearch indexé |
| **Ville** | `villes` | `App\Models\Ville` | ✅ Complet — code_insee, nom, slug, population, maire_actuel_id (FK→maires), coordonnées, EPCI, Meilisearch |
| **Mandat Maire** | `maires_mandats` | `App\Models\MaireMandat` | ✅ Complet — ville_id, maire_id, date_debut/fin, nuance_politique, score_election_pct, tour_election, mandature, est_actuel |
| **Stats Ville** | `villes_stats` | `App\Models\VilleStats` | ✅ Complet — budget, population, dette, scores, nb_maires_historique |
| **Liste Électorale** | `listes_electorales` | `App\Models\ListeElectorale` | ✅ Complet — commune_code_insee, nuance_politique, système de modération (brouillon→valide), SoftDeletes. **MAIS** : pensé pour la candidature citoyenne (les candidats créent leur liste sur CivicDash), PAS pour l'import data.gouv.fr |
| **Candidat Municipal** | `candidats_municipaux` | `App\Models\CandidatMunicipal` | ✅ Complet — liste_id, position, est_tete_de_liste, biographie, réseaux sociaux. **MÊME LIMITE** : pensé pour la saisie manuelle |
| **Documents candidature** | `candidatures_documents` | `App\Models\CandidatureDocument` | ✅ Complet — polymorphe, modération |
| **Population ville** | `villes_population` | `App\Models\VillePopulation` | ✅ Historique démographique |
| **INSEE Commune** | `insee_communes` | `App\Models\InseeCommune` | ✅ Référentiel communes |

### Controllers existants

| Controller | Rôle | Routes |
|-----------|------|--------|
| `ElectionsController` | Hub élections (législatives, sénat, présidentielle, municipales) | `elections/*` |
| `ElectionsMunicipalesController` | Page index, carte, recherche listes, show liste, espace candidat, modération | `elections/municipales/*` |
| `EspaceCandidatController` | Création/édition de listes par les candidats eux-mêmes | `elections/municipales/candidat/*` |
| `ModerationMunicipalesController` | Validation des listes par les modérateurs | `elections/municipales/moderation/*` |
| `VilleController` | Fiche ville avec maire actuel, historique mandats, budgets, élus | `villes/*` |
| `AdminElusController` | Admin des maires (CRUD) | `admin/maires/*` |

### Commandes Artisan existantes

| Commande | Rôle |
|----------|------|
| `import:maires-datagouv` | Import maires depuis RNE (data.gouv.fr) — upsert par code_commune |
| `import:maires-csv` | Import maires depuis CSV local |
| `sync:villes` | Sync table villes depuis INSEE |
| `enrich:maires-wikipedia` | Enrichissement Wikipedia des maires |
| `calculate:ville-stats` | Calcul des stats pré-calculées par ville |
| `calculate:elus-global-stats` | Stats globales tous élus |

### Pages Vue existantes

```
Elections/
├── Hub.vue                          — Hub toutes élections
├── Municipales/
│   ├── Index.vue                    — Page principale (stats listes, dates, étapes candidature)
│   ├── Carte.vue                    — Carte SVG des listes par département
│   ├── Recherche.vue                — Recherche listes par commune
│   ├── ShowListe.vue                — Détail d'une liste
│   ├── Tutoriel.vue                 — Comment candidater
│   ├── EspaceCandidat/              — CRUD listes par les candidats
│   └── Moderation/                  — Validation admin
Villes/
├── Index.vue                        — Liste/recherche villes
└── Show.vue                         — Fiche ville (maire, mandats, budgets, élus)
```

---

## 🎯 Ce qui MANQUE — À construire

L'architecture actuelle couvre la **phase pré-électorale** (candidatures citoyennes, modération, carte des listes). Il manque tout le **volet résultats et post-électoral** :

1. **Tables de résultats** — participation, voix par liste, élus par tour
2. **Import des données officielles** — les fichiers data.gouv.fr du ministère de l'Intérieur (candidatures officielles + résultats T1/T2)
3. **Lien ancien maire → nouveau maire** — transition mandature 2020-2026 → 2026-2032
4. **Dashboard statistiques résultats** — participation, nuances, parité, renouvellement
5. **Pages Vue résultats** — par commune, par département, comparaisons

### Contrainte d'intégration clé

Les `listes_electorales` existantes sont des listes **citoyennes** (créées sur CivicDash, avec workflow de modération). Les listes **officielles** importées depuis data.gouv.fr sont un jeu de données différent. Il faut :
- soit les importer dans la même table avec un flag `source` (et statut auto-validé)
- soit créer une table parallèle `listes_officielles`

**Recommandation : enrichir la table existante** avec des colonnes `source` et `numero_panneau`, et importer avec `statut = 'officiel'` (nouveau statut). Cela évite la duplication et permet de relier une liste citoyenne CivicDash à sa version officielle via `commune_code_insee + nuance_politique + nom_liste`.

---

## 🏗️ Plan d'exécution détaillé

### PHASE 1 — Migrations (enrichir l'existant + nouvelles tables résultats)

#### 1.1 Migration : enrichir `listes_electorales`

```php
// database/migrations/2026_03_21_100000_add_resultats_fields_to_listes_electorales.php

Schema::table('listes_electorales', function (Blueprint $table) {
    // Source de la donnée
    $table->enum('source', ['civicdash', 'datagouv', 'prefectures'])
          ->default('civicdash')->after('statut');

    // Données officielles (import data.gouv)
    $table->integer('numero_panneau')->nullable()->after('source');
    $table->string('libelle_abrege', 50)->nullable();
    $table->string('libelle_etendu')->nullable();
    $table->integer('tour')->default(1)->after('departement_code');
    // FK optionnelle vers la liste T1 d'origine (pour les fusions T2)
    $table->unsignedBigInteger('liste_t1_id')->nullable();
    $table->foreign('liste_t1_id')
          ->references('id')->on('listes_electorales')->nullOnDelete();

    // Correspondance avec liste citoyenne CivicDash
    $table->unsignedBigInteger('liste_civicdash_id')->nullable();

    $table->index(['commune_code_insee', 'tour']);
    $table->index('source');
});

// Ajouter le statut 'officiel' à l'enum existante
// (en PostgreSQL, utiliser ALTER TYPE ou modifier la contrainte CHECK)
DB::statement("ALTER TABLE listes_electorales DROP CONSTRAINT IF EXISTS listes_electorales_statut_check");
DB::statement("ALTER TABLE listes_electorales ADD CONSTRAINT listes_electorales_statut_check CHECK (statut IN ('brouillon','en_attente','documents_requis','en_verification','valide','rejete','suspendu','officiel'))");
```

#### 1.2 Migration : enrichir `candidats_municipaux`

```php
// database/migrations/2026_03_21_100100_add_official_fields_to_candidats_municipaux.php

Schema::table('candidats_municipaux', function (Blueprint $table) {
    // Import officiel
    $table->enum('source', ['civicdash', 'datagouv'])->default('civicdash');
    $table->string('sexe', 1)->nullable(); // 'M' / 'F' — le champ civilite existe mais pas sexe brut
    $table->boolean('sortant')->default(false);
    $table->boolean('elu')->nullable(); // null = pas encore déterminé

    // Lien vers la fiche Maire (si c'est un maire sortant ou nouveau)
    $table->foreignId('maire_id')->nullable()->constrained('maires')->nullOnDelete();

    $table->index('elu');
    $table->index('sortant');
});
```

#### 1.3 Migration : créer `resultats_municipaux` (résultats par commune/tour)

```php
// database/migrations/2026_03_21_100200_create_resultats_municipaux_table.php

Schema::create('resultats_municipaux', function (Blueprint $table) {
    $table->id();

    // Commune
    $table->string('code_commune', 5)->index();
    $table->string('nom_commune');
    $table->string('code_departement', 3)->index();

    // Tour (1 ou 2)
    $table->tinyInteger('tour');

    // Participation
    $table->integer('inscrits');
    $table->integer('abstentions');
    $table->decimal('taux_abstention', 5, 2);
    $table->integer('votants');
    $table->decimal('taux_participation', 5, 2);
    $table->integer('blancs')->default(0);
    $table->integer('nuls')->default(0);
    $table->integer('exprimes');

    // Méta
    $table->integer('nb_sieges_a_pourvoir')->nullable();
    $table->integer('nb_sieges_pourvus')->nullable();
    $table->integer('nb_listes')->nullable();
    $table->enum('statut_commune', [
        'elu_t1',         // Majorité absolue au T1
        'second_tour',    // → T2 nécessaire
        'elu_t2',         // Résolu au T2
        'sans_candidat',  // Aucune liste déposée
        'annule',         // Élection annulée (contentieux)
    ])->nullable();

    // Lien avec Ville
    $table->foreignId('ville_id')->nullable()->constrained('villes')->nullOnDelete();

    $table->timestamps();

    $table->unique(['code_commune', 'tour']);
    $table->index(['code_departement', 'tour']);
    $table->index('statut_commune');
});
```

#### 1.4 Migration : créer `resultats_listes_municipales` (voix par liste/tour)

```php
// database/migrations/2026_03_21_100300_create_resultats_listes_municipales_table.php

Schema::create('resultats_listes_municipales', function (Blueprint $table) {
    $table->id();

    $table->foreignId('resultat_commune_id')
          ->constrained('resultats_municipaux')->cascadeOnDelete();
    $table->foreignId('liste_id')->nullable()
          ->constrained('listes_electorales')->nullOnDelete();

    // Identifiant liste (si pas de FK = import brut)
    $table->integer('numero_panneau')->nullable();
    $table->string('nom_liste')->nullable();
    $table->string('nuance_politique', 10)->nullable();
    $table->string('tete_de_liste_nom')->nullable();
    $table->string('tete_de_liste_prenom')->nullable();

    // Résultats
    $table->integer('voix');
    $table->decimal('pourcentage_exprimes', 5, 2);
    $table->decimal('pourcentage_inscrits', 5, 2)->nullable();
    $table->boolean('elu')->default(false);
    $table->integer('sieges_obtenus')->nullable();
    $table->integer('sieges_conseil_communautaire')->nullable();

    $table->timestamps();

    $table->index(['resultat_commune_id', 'voix']);
    $table->index('nuance_politique');
});
```

#### 1.5 Migration : enrichir `maires` pour la transition 2020→2026

```php
// database/migrations/2026_03_21_100400_add_transition_fields_to_maires.php

Schema::table('maires', function (Blueprint $table) {
    // Lien vers le prédécesseur (maire sortant de la même commune)
    $table->unsignedBigInteger('predecesseur_id')->nullable();
    $table->foreign('predecesseur_id')->references('id')->on('maires')->nullOnDelete();

    // Score électoral
    $table->decimal('score_election_pct', 5, 2)->nullable();
    $table->tinyInteger('tour_election')->nullable(); // 1 ou 2
    $table->boolean('reelu')->nullable(); // true = sortant réélu, false = nouveau, null = inconnu

    // FK vers la liste qui a gagné
    $table->foreignId('liste_id')->nullable()->constrained('listes_electorales')->nullOnDelete();

    $table->index('reelu');
    $table->index('mandature');
});
```

#### 1.6 Migration : table de statistiques agrégées

```php
// database/migrations/2026_03_21_100500_create_stats_elections_municipales_table.php

Schema::create('stats_elections_municipales', function (Blueprint $table) {
    $table->id();
    $table->integer('annee'); // 2026
    $table->string('scope', 20); // 'national', 'region', 'departement'
    $table->string('scope_code', 5)->nullable(); // null pour national, code pour region/dept
    $table->jsonb('data'); // Toutes les stats agrégées (voir structure ci-dessous)
    $table->timestamp('calculated_at');
    $table->timestamps();

    $table->unique(['annee', 'scope', 'scope_code']);
});
```

---

### PHASE 2 — Modèles Eloquent

#### 2.1 Nouveau modèle : `ResultatMunicipal`

Fichier : `app/Models/ResultatMunicipal.php`

```php
class ResultatMunicipal extends Model
{
    protected $table = 'resultats_municipaux';

    // Relations
    belongsTo Ville (ville_id)
    hasMany ResultatListeMunicipale (resultat_commune_id)

    // Scopes
    scopeTour($query, $tour)
    scopeByDepartement($query, $code)
    scopeElusAuT1($query) // statut_commune = 'elu_t1'
    scopeSecondTour($query) // statut_commune = 'second_tour'

    // Accessors
    getTauxParticipationFormateAttribute() // "62,3%"
    getAbstentionFormateAttribute()
```

#### 2.2 Nouveau modèle : `ResultatListeMunicipale`

Fichier : `app/Models/ResultatListeMunicipale.php`

```php
class ResultatListeMunicipale extends Model
{
    protected $table = 'resultats_listes_municipales';

    // Relations
    belongsTo ResultatMunicipal (resultat_commune_id)
    belongsTo ListeElectorale (liste_id) — nullable

    // Scopes
    scopeElues($query)
    scopeByNuance($query, $nuance)
```

#### 2.3 Nouveau modèle : `StatsElectionMunicipale`

Fichier : `app/Models/StatsElectionMunicipale.php`

#### 2.4 Enrichir les modèles existants

**`ListeElectorale.php`** — ajouter :
```php
// Nouvelles relations
hasMany ResultatListeMunicipale (liste_id)
belongsTo ListeElectorale as listeT1 (liste_t1_id)
hasMany ListeElectorale as listesT2 (liste_t1_id)

// Nouveaux scopes
scopeOfficielles($query) → where('source', 'datagouv')
scopeCivicdash($query) → where('source', 'civicdash')
scopeTour($query, $tour)

// Propriétés mass-assignables à ajouter aux $fillable
'source', 'numero_panneau', 'tour', 'libelle_abrege',
'libelle_etendu', 'liste_t1_id', 'liste_civicdash_id'
```

**`CandidatMunicipal.php`** — ajouter :
```php
// Nouvelle relation
belongsTo Maire (maire_id)

// Nouveaux scopes
scopeElus($query)
scopeSortants($query)
scopeOfficiels($query) → where('source', 'datagouv')

// $fillable
'source', 'sexe', 'sortant', 'elu', 'maire_id'
```

**`Maire.php`** — ajouter :
```php
// Nouvelles relations
belongsTo Maire as predecesseur (predecesseur_id)
hasOne Maire as successeur (predecesseur_id) → inverse
belongsTo ListeElectorale (liste_id)
hasOne MaireMandat as mandatActuel → where('est_actuel', true)
belongsTo Ville (ville_id) → VÉRIFIER si ville_id existe déjà (migration villes_system l'ajoute)

// Nouveaux scopes
scopeMandature($query, $mandature) → where('mandature', $mandature)
scopeReelus($query) → where('reelu', true)
scopeNouveaux($query) → where('reelu', false)

// $fillable
'predecesseur_id', 'score_election_pct', 'tour_election', 'reelu', 'liste_id'
```

**`Ville.php`** — ajouter :
```php
// Nouvelles relations
hasMany ResultatMunicipal (ville_id)

// Méthode utilitaire
public function resultatsElection(int $annee = 2026) {
    return $this->resultats()->where('annee', $annee);
}
```

---

### PHASE 3 — Commandes d'import

#### 3.1 `ImportCandidaturesOfficielles` — Import data.gouv.fr

Fichier : `app/Console/Commands/ImportCandidaturesOfficielles.php`

```
Signature : php artisan municipales:import-candidatures {tour=1} {--file=} {--url=}

Sources data.gouv.fr :
  T1 → https://www.data.gouv.fr/datasets/elections-municipales-2026-listes-candidates-au-premier-tour
  T2 → data.gouv.fr candidatures 2nd tour (publié 17/03/2026)

Logique :
  1. Télécharger le CSV (ou utiliser --file pour un fichier local)
  2. Détecter encoding (ISO-8859-1 → UTF-8) et séparateur (;)
  3. Pour chaque ligne :
     a. Upsert dans listes_electorales avec source='datagouv', statut='officiel'
     b. Upsert dans candidats_municipaux avec source='datagouv'
     c. Si tour=2, relier à la liste T1 via liste_t1_id
  4. Tenter le matching avec les listes CivicDash existantes :
     → même commune_code_insee + similitude nom_liste > 80% (Levenshtein)
     → si match, remplir liste_civicdash_id

Format CSV attendu (séparateur ;) :
  Code du département | Libellé du département | Code de la commune |
  Libellé de la commune | N° Panneau | Nuance liste |
  Libellé abrégé liste | Libellé étendu liste |
  Nom tête de liste | Prénom tête de liste | Sexe | Date naissance |
  [puis pour chaque candidat : Rang | Nom | Prénom | Sexe | Date naissance]

Points d'attention :
  - code_commune TOUJOURS en varchar(5) avec zéros initiaux
  - PLM : fichiers séparés pour arrondissements Paris/Lyon/Marseille
  - Polynésie : fichier séparé
  - En 2026, le scrutin de liste paritaire est unifié (plus de distinction </>1000 hab)
```

#### 3.2 `ImportResultatsMunicipales` — Résultats T1/T2

Fichier : `app/Console/Commands/ImportResultatsMunicipales.php`

```
Signature : php artisan municipales:import-resultats {tour=1} {--file=} {--url=}

Sources :
  T1 → https://www.data.gouv.fr/datasets/elections-municipales-2026-resultats-du-premier-tour
  T2 → sera publié le soir du 22/03/2026

Logique :
  1. Télécharger + parser CSV
  2. Pour chaque commune :
     a. Créer/update ResultatMunicipal (participation, blancs, nuls, exprimés)
     b. Pour chaque liste dans la commune :
        - Créer ResultatListeMunicipale (voix, %, sièges)
        - Tenter rattachement à listes_electorales via
          (commune_code_insee + numero_panneau + tour)
     c. Déterminer statut_commune :
        Si tour=1 :
          - Liste avec >50% exprimés ET >25% inscrits → 'elu_t1'
          - Sinon → 'second_tour'
        Si tour=2 :
          - Liste en tête → 'elu_t2'
     d. Lier à ville_id via Ville::where('code_insee', $code)->first()
     e. Marquer les candidats élus (elu=true dans candidats_municipaux)
  3. Résumé : X communes T1 terminé, Y → T2, Z sans candidat
```

#### 3.3 `TransitionMaires2026` — Basculement des maires

Fichier : `app/Console/Commands/TransitionMaires2026.php`

**C'est la commande clé pour le lien ancien maire → nouveau maire.**

```
Signature : php artisan municipales:transition-maires {--dry-run}

Logique (à exécuter APRÈS import résultats + import RNE 2026) :

  1. CLÔTURER les maires sortants (mandature 2020-2026) :
     Pour chaque Maire WHERE mandature = '2020-2026' AND en_exercice = true :
       a. Mettre en_exercice = false
       b. Mettre fin_mandat = '2026-03-22' (ou date d'installation du nouveau conseil)
       c. Dans maires_mandats : mettre est_actuel = false, date_fin = idem

  2. CRÉER les nouveaux maires (mandature 2026-2032) :
     Pour chaque commune avec un résultat :
       a. Identifier la liste gagnante (elu=true dans resultats_listes_municipales)
       b. Récupérer la tête de liste → c'est le nouveau maire
       c. Chercher si cette personne existe déjà dans la table maires
          (matching par nom + prenom + code_commune OU nom + prenom + date_naissance)
       d. Si OUI (réélection) :
          - Mettre à jour le Maire existant :
            en_exercice = true, mandature = '2026-2032',
            debut_mandat = date installation, reelu = true,
            score_election_pct = %, tour_election = 1 ou 2
          - Créer un nouveau MaireMandat (est_actuel = true)
       e. Si NON (nouveau maire) :
          - Créer un nouveau Maire avec :
            mandature = '2026-2032', en_exercice = true,
            predecesseur_id = ancien_maire.id, reelu = false,
            source des données = tête de liste officielle
          - Créer un MaireMandat (est_actuel = true)
       f. Dans les deux cas :
          - Mettre à jour Ville.maire_actuel_id → nouveau Maire.id

  3. FALLBACK via RNE :
     Si des communes n'ont pas de résultat importé mais que le RNE 2026
     a été mis à jour, utiliser import:maires-datagouv en mode update
     pour compléter.

  4. STATISTIQUES de transition :
     - X maires réélus
     - Y nouveaux maires
     - Z communes sans successeur identifié
     - Taux de renouvellement global
     - Parité avant/après
```

#### 3.4 `CalculateStatsMunicipales` — Statistiques agrégées

Fichier : `app/Console/Commands/CalculateStatsMunicipales.php`

```
Signature : php artisan municipales:calculate-stats {--annee=2026}

Calcule et stocke dans stats_elections_municipales (jsonb) :

Pour scope='national' :
{
  "participation": {
    "t1": { "inscrits": N, "votants": N, "taux": 48.90, "abstention": 51.10 },
    "t2": { ... }
  },
  "communes": {
    "total": 34875,
    "elues_t1": ~33354,
    "second_tour": ~1521,
    "sans_candidat": 68,
    "liste_unique": 23700
  },
  "parite_maires": {
    "hommes": N, "femmes": N, "taux_femmes": N,
    "evolution": { "2020": N, "2014": N }
  },
  "nuances": {
    "LDVG": { "listes_t1": N, "communes_gagnees": N, "voix_total": N },
    "LRN": { ... }, ...
  },
  "age_maires": {
    "moyenne": N, "mediane": N,
    "plus_jeune": { "nom": "", "commune": "", "age": N },
    "plus_age": { "nom": "", "commune": "", "age": N },
    "distribution": { "18-30": N, "31-40": N, ... }
  },
  "renouvellement": {
    "sortants_representes": N,
    "sortants_reelus": N,
    "taux_reelection": N,
    "nouveaux": N
  },
  "professions": {
    "top10": [{ "libelle": "", "count": N }, ...]
  }
}

Pour scope='departement', scope_code='21' (Côte-d'Or) :
  Même structure mais filtré sur le département.

Pour scope='region', scope_code='27' (Bourgogne-Franche-Comté) :
  Idem filtré par région.
```

#### 3.5 `MunicipalesFullPipeline` — Orchestrateur

```
Signature : php artisan municipales:full-pipeline {--tour=all} {--skip-download}

Exécute dans l'ordre :
  1. municipales:import-candidatures --tour=1
  2. municipales:import-resultats --tour=1
  3. municipales:import-candidatures --tour=2    (si --tour=all ou --tour=2)
  4. municipales:import-resultats --tour=2       (idem)
  5. import:maires-datagouv                      (RNE à jour)
  6. municipales:transition-maires
  7. municipales:calculate-stats
  8. calculate:ville-stats                       (existant — recalcul post-transition)
  9. calculate:elus-global-stats                 (existant — recalcul)
```

---

### PHASE 4 — Backend (Controllers + Routes)

#### 4.1 Enrichir `ElectionsMunicipalesController`

Ajouter les méthodes résultats au controller existant :

```php
// NOUVELLES MÉTHODES à ajouter à ElectionsMunicipalesController.php

public function resultats(Request $request)
  // Page globale résultats (T1/T2)
  // Stats nationales, top communes, carte résultats
  → Inertia::render('Elections/Municipales/Resultats')

public function resultatCommune(string $codeInsee, Request $request)
  // Résultats détaillés d'une commune (T1 + T2 si applicable)
  // Participation, voix par liste, transition maire
  → Inertia::render('Elections/Municipales/ResultatCommune')

public function resultatDepartement(string $codeDept)
  // Agrégation résultats par département
  → Inertia::render('Elections/Municipales/ResultatDepartement')

public function statistiques()
  // Dashboard complet statistiques
  → Inertia::render('Elections/Municipales/Statistiques')

public function transitionMaires()
  // Page dédiée : ancien maire vs nouveau maire, stats de renouvellement
  → Inertia::render('Elections/Municipales/TransitionMaires')

// API JSON (pour les composants Vue dynamiques)
public function apiResultatsCommune(string $code, int $tour)
public function apiStatsNuances()
public function apiCarteParticipation()
public function apiCarteNuances()
public function apiTransitionMaire(string $codeInsee)
```

#### 4.2 Enrichir `VilleController::show()`

Ajouter dans la fiche ville :
```php
// Dans show() — données supplémentaires à passer à Inertia

'resultats_municipales_2026' => [
    'tour1' => $this->getResultatsTour($ville, 1),
    'tour2' => $this->getResultatsTour($ville, 2),
    'transition' => [
        'ancien_maire' => $this->getAncienMaire($ville),  // mandature 2020-2026
        'nouveau_maire' => $this->getNouveauMaire($ville), // mandature 2026-2032
        'est_reelu' => $nouveauMaire?->reelu,
    ],
]
```

#### 4.3 Nouvelles routes

```php
// Dans routes/web.php — à ajouter dans le groupe elections/municipales

Route::get('/resultats', [..., 'resultats'])->name('resultats');
Route::get('/resultats/statistiques', [..., 'statistiques'])->name('statistiques');
Route::get('/resultats/transition-maires', [..., 'transitionMaires'])->name('transition');
Route::get('/resultats/commune/{code}', [..., 'resultatCommune'])->name('resultat.commune');
Route::get('/resultats/departement/{code}', [..., 'resultatDepartement'])->name('resultat.departement');

// API
Route::prefix('api/municipales/resultats')->group(function () {
    Route::get('/commune/{code}/{tour}', [..., 'apiResultatsCommune']);
    Route::get('/stats/nuances', [..., 'apiStatsNuances']);
    Route::get('/carte/participation', [..., 'apiCarteParticipation']);
    Route::get('/carte/nuances', [..., 'apiCarteNuances']);
    Route::get('/transition/{codeInsee}', [..., 'apiTransitionMaire']);
});
```

---

### PHASE 5 — Frontend Vue 3

#### 5.1 Nouvelles pages

```
Elections/Municipales/
├── Resultats.vue              — Vue d'ensemble résultats T1/T2
├── ResultatCommune.vue        — Détail commune (voix, listes, maire élu)
├── ResultatDepartement.vue    — Agrégation par département
├── Statistiques.vue           — Dashboard statistiques complet
└── TransitionMaires.vue       — Avant/après, renouvellement

(modification)
├── Index.vue                  — ENRICHIR avec section "Résultats" post-15/03
```

#### 5.2 Composants réutilisables

```
Components/Municipales/
├── ResultatsTable.vue          — Tableau résultats (voix, %, sièges par liste)
├── ParticipationCard.vue       — Card inscrits/votants/exprimés avec barres
├── TransitionMaireCard.vue     — Avant/après : ancien maire → nouveau
├── NuanceBadge.vue             — Badge coloré par nuance politique
├── NuancesChart.vue            — Pie/bar chart communes gagnées par nuance
├── PariteDonut.vue             — Donut H/F maires
├── ComparisonBar2020vs2026.vue — Barres comparaison taux participation
├── CommuneResultCard.vue       — Mini-card résultat d'une commune
└── DepartementHeatmap.vue      — Carte SVG heatmap (participation ou nuance)
```

#### 5.3 Maquette `ResultatCommune.vue`

```
┌─────────────────────────────────────────────────────────┐
│  🏘️ {Ville.nom} ({Département})                        │
│  Population: {pop} | Sièges: {nb}                      │
├─────────────────────────────────────────────────────────┤
│  ┌─ TRANSITION MAIRE ─────────────────────────────────┐ │
│  │ [Photo] Ancien maire         →  [Photo] Nouveau    │ │
│  │ Jean Dupont (LR)                Marie Martin (DVG) │ │
│  │ 2020-2026                       2026-2032          │ │
│  │ ⟳ Changement de couleur politique                  │ │
│  └────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────┤
│  📊 Tour 1 — 15 mars 2026                               │
│  Participation: 62,3% (vs 44,7% en 2020)                │
│  ┌─────────────┬────────┬─────────┬───────┬──────────┐ │
│  │ Liste       │ Voix   │ %       │Sièges │ Statut   │ │
│  │ Liste A  ●  │ 1 234  │ 45,6%   │  15   │ → T2     │ │
│  │ Liste B  ●  │   876  │ 32,3%   │   8   │ → T2     │ │
│  │ Liste C  ●  │   598  │ 22,1%   │   4   │ Éliminée │ │
│  └─────────────┴────────┴─────────┴───────┴──────────┘ │
├─────────────────────────────────────────────────────────┤
│  📊 Tour 2 — 22 mars 2026 (si applicable)               │
│  [Même structure — avec indication de la liste élue]     │
├─────────────────────────────────────────────────────────┤
│  🔗 Liens                                               │
│  → Fiche ville complète (budgets, élus, stats)          │
│  → Résultats 2020 dans cette commune                    │
│  → Listes candidates CivicDash (si existantes)          │
└─────────────────────────────────────────────────────────┘
```

#### 5.4 Maquette `Statistiques.vue`

```
┌─────────────────────────────────────────────────────────┐
│  📊 Statistiques Municipales 2026                       │
│  [Tabs: National | Par département | Par région]        │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─ COMPTEURS ──────────────────────────────────────┐  │
│  │ 34 875       48,90%        33 354      1 521     │  │
│  │ communes    particip.T1   élues T1     → T2      │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─ PARTICIPATION ──────────────────────────────────┐  │
│  │ Bar chart T1 vs T2 vs 2020                       │  │
│  │ Heatmap carte participation par département      │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─ NUANCES POLITIQUES ────────────────────────────┐  │
│  │ Pie chart: communes gagnées par nuance           │  │
│  │ Carte: coloration départements par nuance domin. │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─ PARITÉ ────────────────────────────────────────┐  │
│  │ Donut: % maires H/F                             │  │
│  │ Ligne: évolution 2008 → 2014 → 2020 → 2026     │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─ RENOUVELLEMENT ────────────────────────────────┐  │
│  │ Bar: sortants réélus vs nouveaux                 │  │
│  │ Histogramme: âge des maires                      │  │
│  │ Treemap: professions des maires                  │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─ FOCUS GRANDES VILLES ──────────────────────────┐  │
│  │ TransitionMaireCard × 50+ grandes villes         │  │
│  │ Tri par: population | changement | nuance        │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

#### 5.5 Enrichir `Villes/Show.vue`

Ajouter un onglet/section "Élections 2026" dans la fiche ville existante :
- Bloc `TransitionMaireCard` (ancien → nouveau)
- Tableau résultats T1 (+ T2 si applicable)
- Lien vers la page résultats complète

#### 5.6 Enrichir `Elections/Municipales/Index.vue`

Actuellement cette page affiche uniquement les candidatures CivicDash et les dates. Après le 15/03/2026, ajouter :
- Section "Résultats du 1er tour" en haut
- Compteurs animés (communes résolues, participation)
- Lien vers `/resultats` et `/statistiques`
- Section "En attente du 2nd tour — 22 mars"

---

### PHASE 6 — Meilisearch

#### Index existants à mettre à jour
- `maires` → réindexer après transition (nouveaux maires 2026)
- `villes` → pas de changement structurel nécessaire

#### Nouvel index optionnel
- `resultats_municipaux` → permettre la recherche de résultats par commune

---

### PHASE 7 — Tests (Pest)

```
tests/
├── Unit/
│   ├── Models/ResultatMunicipalTest.php
│   ├── Models/ResultatListeMunicipaleTest.php
│   └── Models/MaireTransitionTest.php
├── Feature/
│   ├── ImportCandidaturesOfficiellesTest.php
│   ├── ImportResultatsMunicipalesTest.php
│   ├── TransitionMaires2026Test.php
│   ├── MunicipalesResultatsPageTest.php
│   └── MunicipalesStatistiquesTest.php
└── Fixtures/
    ├── candidatures_t1_sample.csv   (50 lignes de test)
    ├── resultats_t1_sample.csv      (50 lignes de test)
    └── rne_maires_sample.csv        (50 lignes de test)
```

---

## 🔑 Schéma de relations final

```
Ville (code_insee)
  ├── maire_actuel_id ──→ Maire (mandature 2026-2032)
  │                         ├── predecesseur_id ──→ Maire (mandature 2020-2026)
  │                         ├── liste_id ──→ ListeElectorale (source='datagouv')
  │                         └── reelu (bool)
  │
  ├── mandatsMaires ──→ MaireMandat[]
  │                       ├── mandature '2020-2026' (est_actuel=false)
  │                       └── mandature '2026-2032' (est_actuel=true)
  │
  ├── resultats ──→ ResultatMunicipal[] (tour=1, tour=2)
  │                   └── listes ──→ ResultatListeMunicipale[]
  │                                   └── liste_id ──→ ListeElectorale
  │
  └── stats ──→ VilleStats (recalculé post-transition)

ListeElectorale (commune_code_insee, tour)
  ├── source = 'datagouv' (officielle) / 'civicdash' (citoyenne)
  ├── statut = 'officiel' (import) / 'valide' (modérée)
  ├── candidats ──→ CandidatMunicipal[]
  │                   ├── est_tete_de_liste (= candidat maire)
  │                   ├── elu (bool)
  │                   └── maire_id ──→ Maire (si c'est un maire sortant)
  └── liste_t1_id ──→ ListeElectorale (pour les fusions T2)
```

---

## 📅 Ordre d'exécution pour l'agent

```
ÉTAPE 1 — Migrations (30 min)
  Créer les 6 fichiers de migration dans l'ordre donné

ÉTAPE 2 — Modèles (30 min)
  Créer ResultatMunicipal, ResultatListeMunicipale, StatsElectionMunicipale
  Enrichir ListeElectorale, CandidatMunicipal, Maire, Ville

ÉTAPE 3 — Commandes d'import (2-3h)
  ImportCandidaturesOfficielles → tester avec un CSV échantillon
  ImportResultatsMunicipales → tester avec un CSV échantillon
  TransitionMaires2026 → tester en dry-run
  CalculateStatsMunicipales
  MunicipalesFullPipeline

ÉTAPE 4 — Controllers + Routes (1-2h)
  Enrichir ElectionsMunicipalesController
  Enrichir VilleController
  Ajouter les routes

ÉTAPE 5 — Pages Vue (3-4h)
  Resultats.vue, ResultatCommune.vue, Statistiques.vue, TransitionMaires.vue
  Composants : TransitionMaireCard, ResultatsTable, NuancesChart, etc.
  Enrichir Index.vue et Villes/Show.vue

ÉTAPE 6 — Tests (1h)
  Fixtures CSV, tests import, tests pages
```

---

## 📚 Sources de données — URLs exactes

| Donnée | URL data.gouv.fr |
|--------|-----------------|
| Candidatures T1 | https://www.data.gouv.fr/datasets/elections-municipales-2026-listes-candidates-au-premier-tour |
| Résultats T1 | https://www.data.gouv.fr/datasets/elections-municipales-2026-resultats-du-premier-tour |
| Candidatures T2 | Publication via interieur.gouv.fr → data.gouv.fr (17/03/2026) |
| Résultats T2 | Sera publié le soir du 22/03/2026 |
| RNE Maires | https://www.data.gouv.fr/datasets/repertoire-national-des-elus-1 |
| Maires sortants | https://www.data.gouv.fr/datasets/elections-municipales-2026-maires-et-conseillers-municipaux-sortants |
| Résultats officiels | https://www.elections.interieur.gouv.fr |

---

*Document généré le 21 mars 2026 — Analyse complète du repo CivicDash existant*