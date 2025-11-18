# 🚀 PLAN D'IMPLÉMENTATION - DONNÉES AN LÉGISLATURE 17

**Date de création :** 18 novembre 2025  
**Stratégie choisie :** OPTION B - Import Législature 17 (2024-2029)  
**Durée estimée :** 10-12h

---

## 📊 **VOLUMÉTRIE CIBLE**

| Entité | Volume L17 | Description |
|--------|-----------|-------------|
| **Acteurs** | 603 | Tous les acteurs (toutes législatures) |
| **Mandats** | ~6 000 | Mandats actifs législature 17 |
| **Organes** | ~100 | Organes actifs législature 17 |
| **Scrutins** | ~3 200 | Scrutins législature 17 |
| **Votes individuels** | ~320 000 | 100 députés × 3 200 scrutins (moyenne) |
| **Dossiers législatifs** | ~500 | Dossiers législature 17 |
| **Textes législatifs** | ~1 000 | Propositions/projets de loi L17 |
| **Amendements** | ~68 000 | Amendements législature 17 |
| **Réunions** | ~4 000 | Réunions législature 17 |
| **Déports** | ~30 | Déports législature 17 |

**Base de données finale : ~2 GB**

---

## 🏗️ **PHASE 1 : STRUCTURE BDD (2-3h)**

### 1.1. Migrations

#### `2025_11_18_100000_create_acteurs_an_table.php`
```php
Schema::create('acteurs_an', function (Blueprint $table) {
    $table->string('uid', 20)->primary(); // PA1008
    $table->string('civilite', 10);
    $table->string('prenom', 100);
    $table->string('nom', 100);
    $table->string('trigramme', 3)->index();
    $table->date('date_naissance')->nullable();
    $table->string('ville_naissance')->nullable();
    $table->string('departement_naissance')->nullable();
    $table->string('pays_naissance')->nullable();
    $table->string('profession')->nullable();
    $table->string('categorie_socio_pro')->nullable();
    $table->string('url_hatvp')->nullable();
    $table->json('adresses')->nullable();
    $table->timestamps();
    
    $table->index(['nom', 'prenom']);
    $table->fullText(['nom', 'prenom']);
});
```

#### `2025_11_18_100100_create_organes_an_table.php`
```php
Schema::create('organes_an', function (Blueprint $table) {
    $table->string('uid', 20)->primary(); // PO838901
    $table->string('code_type', 50); // GP, COMPER, DELEG, etc.
    $table->string('libelle', 255);
    $table->string('libelle_abrege', 100)->nullable();
    $table->integer('legislature')->nullable()->index();
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->string('regime', 50)->nullable();
    $table->string('site_internet')->nullable();
    $table->timestamps();
    
    $table->index(['code_type', 'legislature']);
});
```

#### `2025_11_18_100200_create_mandats_an_table.php`
```php
Schema::create('mandats_an', function (Blueprint $table) {
    $table->string('uid', 20)->primary(); // PM842426
    $table->string('acteur_ref', 20)->index();
    $table->string('organe_ref', 20)->nullable()->index();
    $table->integer('legislature')->nullable()->index();
    $table->string('type_organe', 50); // ASSEMBLEE, COMPER, GP, etc.
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->string('code_qualite', 50); // Membre, Président, etc.
    $table->string('libelle_qualite', 100);
    $table->integer('preseance')->nullable();
    $table->boolean('nomination_principale')->default(false);
    $table->timestamps();
    
    $table->foreign('acteur_ref')->references('uid')->on('acteurs_an')->onDelete('cascade');
    $table->foreign('organe_ref')->references('uid')->on('organes_an')->onDelete('set null');
    $table->index(['acteur_ref', 'legislature']);
    $table->index(['organe_ref', 'legislature']);
});
```

#### `2025_11_18_100300_create_scrutins_an_table.php`
```php
Schema::create('scrutins_an', function (Blueprint $table) {
    $table->string('uid', 30)->primary(); // VTANR5L17V1000
    $table->integer('numero')->index();
    $table->string('organe_ref', 20)->index(); // Assemblée
    $table->integer('legislature')->index();
    $table->date('date_scrutin')->index();
    $table->string('type_vote_code', 10);
    $table->string('type_vote_libelle', 100);
    $table->string('resultat_code', 20); // adopté, rejeté
    $table->string('resultat_libelle', 255);
    $table->text('titre');
    $table->integer('nombre_votants');
    $table->integer('suffrages_exprimes');
    $table->integer('suffrage_requis');
    $table->integer('pour');
    $table->integer('contre');
    $table->integer('abstentions');
    $table->integer('non_votants')->nullable();
    $table->json('ventilation_votes'); // Votes par groupe (JSON complet)
    $table->timestamps();
    
    $table->index(['legislature', 'date_scrutin']);
    $table->fullText('titre');
});
```

#### `2025_11_18_100400_create_votes_individuels_an_table.php`
```php
Schema::create('votes_individuels_an', function (Blueprint $table) {
    $table->id();
    $table->string('scrutin_ref', 30)->index();
    $table->string('acteur_ref', 20)->index();
    $table->string('mandat_ref', 20)->nullable();
    $table->string('groupe_ref', 20)->nullable()->index();
    $table->enum('position', ['pour', 'contre', 'abstention', 'non_votant']);
    $table->enum('position_groupe', ['pour', 'contre', 'abstention', 'mixte'])->nullable();
    $table->string('numero_place', 10)->nullable();
    $table->boolean('par_delegation')->default(false);
    $table->string('cause_non_vote', 50)->nullable(); // PAN, PSE, etc.
    $table->timestamps();
    
    $table->foreign('scrutin_ref')->references('uid')->on('scrutins_an')->onDelete('cascade');
    $table->foreign('acteur_ref')->references('uid')->on('acteurs_an')->onDelete('cascade');
    $table->foreign('groupe_ref')->references('uid')->on('organes_an')->onDelete('set null');
    
    $table->unique(['scrutin_ref', 'acteur_ref']);
    $table->index(['acteur_ref', 'position']);
    $table->index(['groupe_ref', 'position']);
});
```

#### `2025_11_18_100500_create_dossiers_legislatifs_an_table.php`
```php
Schema::create('dossiers_legislatifs_an', function (Blueprint $table) {
    $table->string('uid', 30)->primary(); // DLR5L17N51035
    $table->integer('legislature')->index();
    $table->integer('numero')->nullable();
    $table->string('titre')->nullable();
    $table->date('date_creation')->nullable();
    $table->timestamps();
    
    $table->index(['legislature', 'numero']);
});
```

#### `2025_11_18_100600_create_textes_legislatifs_an_table.php`
```php
Schema::create('textes_legislatifs_an', function (Blueprint $table) {
    $table->string('uid', 30)->primary(); // PIONANR5L17B0689
    $table->string('dossier_ref', 30)->nullable()->index();
    $table->integer('legislature')->index();
    $table->string('type_texte', 10); // PION, PRJL, etc.
    $table->integer('numero')->nullable();
    $table->string('titre')->nullable();
    $table->date('date_depot')->nullable();
    $table->timestamps();
    
    $table->foreign('dossier_ref')->references('uid')->on('dossiers_legislatifs_an')->onDelete('set null');
    $table->index(['legislature', 'type_texte']);
});
```

#### `2025_11_18_100700_create_amendements_an_table.php`
```php
Schema::create('amendements_an', function (Blueprint $table) {
    $table->string('uid', 50)->primary(); // AMANR5L17PO838901B0689P0D1N000007
    $table->string('texte_legislatif_ref', 30)->nullable()->index();
    $table->string('examen_ref', 50)->nullable();
    $table->integer('legislature')->index();
    $table->string('numero_long', 20)->nullable();
    $table->integer('numero_ordre_depot')->nullable();
    $table->string('prefixe_organe_examen', 20)->nullable(); // AN, CION_LOIS, etc.
    
    // Auteur
    $table->string('auteur_type', 50); // Député, Gouvernement
    $table->string('auteur_acteur_ref', 20)->nullable()->index();
    $table->string('auteur_groupe_ref', 20)->nullable()->index();
    $table->text('auteur_libelle')->nullable();
    
    // Cosignataires
    $table->json('cosignataires_acteur_refs')->nullable();
    $table->integer('nombre_cosignataires')->default(0);
    
    // Article visé
    $table->string('article_designation', 100)->nullable();
    $table->string('article_designation_courte', 50)->nullable();
    $table->string('division_titre')->nullable();
    $table->string('division_type', 20)->nullable(); // ARTICLE, ANNEXE
    
    // Contenu
    $table->text('cartouche_informatif')->nullable();
    $table->longText('dispositif')->nullable();
    $table->longText('expose')->nullable();
    
    // Cycle de vie
    $table->date('date_depot')->nullable()->index();
    $table->date('date_publication')->nullable();
    $table->boolean('soumis_article_40')->default(false);
    $table->string('etat_code', 20)->nullable()->index(); // ADO, REJ, IRR45, etc.
    $table->string('etat_libelle', 100)->nullable();
    $table->string('sous_etat_code', 20)->nullable();
    $table->string('sous_etat_libelle', 100)->nullable();
    $table->date('date_sort')->nullable();
    $table->string('sort_code', 20)->nullable();
    $table->string('sort_libelle', 100)->nullable();
    
    $table->timestamps();
    
    $table->foreign('texte_legislatif_ref')->references('uid')->on('textes_legislatifs_an')->onDelete('set null');
    $table->foreign('auteur_acteur_ref')->references('uid')->on('acteurs_an')->onDelete('set null');
    $table->foreign('auteur_groupe_ref')->references('uid')->on('organes_an')->onDelete('set null');
    
    $table->index(['legislature', 'etat_code']);
    $table->index(['auteur_acteur_ref', 'legislature']);
    $table->index(['legislature', 'date_depot']);
    $table->fullText('dispositif');
    $table->fullText('expose');
});
```

#### `2025_11_18_100800_create_reunions_an_table.php`
```php
Schema::create('reunions_an', function (Blueprint $table) {
    $table->string('uid', 30)->primary(); // RUANR5L17S2025IDS29165
    $table->string('organe_ref', 20)->nullable()->index();
    $table->integer('legislature')->nullable()->index();
    $table->date('date_reunion')->nullable()->index();
    $table->string('type_reunion', 50)->nullable();
    $table->json('details')->nullable(); // Ordre du jour, présences, etc.
    $table->timestamps();
    
    $table->foreign('organe_ref')->references('uid')->on('organes_an')->onDelete('set null');
    $table->index(['legislature', 'date_reunion']);
});
```

#### `2025_11_18_100900_create_deports_an_table.php`
```php
Schema::create('deports_an', function (Blueprint $table) {
    $table->string('uid', 50)->primary(); // DPTR5L17PA795950D0001
    $table->string('acteur_ref', 20)->index();
    $table->string('scrutin_ref', 30)->nullable()->index();
    $table->integer('legislature')->index();
    $table->string('raison')->nullable();
    $table->json('details')->nullable();
    $table->timestamps();
    
    $table->foreign('acteur_ref')->references('uid')->on('acteurs_an')->onDelete('cascade');
    $table->foreign('scrutin_ref')->references('uid')->on('scrutins_an')->onDelete('set null');
});
```

---

## 🏗️ **PHASE 2 : MODÈLES ELOQUENT (1-2h)**

### 2.1. Modèles avec relations

Créer les 10 modèles :
- `ActeurAN`
- `OrganeAN`
- `MandatAN`
- `ScrutinAN`
- `VoteIndividuelAN`
- `DossierLegislatifAN`
- `TexteLegislatifAN`
- `AmendementAN`
- `ReunionAN`
- `DeportAN`

Avec toutes les relations `hasMany`, `belongsTo`, `belongsToMany`.

---

## 🏗️ **PHASE 3 : COMMANDES D'IMPORT (4-5h)**

### 3.1. `ImportActeursAN` (603 acteurs)
- Parse tous les fichiers `acteur/*.json`
- Insert avec `updateOrCreate` (idempotent)
- Extraction adresses, profession, HATVP
- **Durée estimée : 30 min**

### 3.2. `ImportOrganesAN` (L17)
- Parse `organe/*.json` filtrés sur `legislature=17` ou `dateFin=null`
- Types : GP, COMPER, DELEG
- **Durée estimée : 20 min**

### 3.3. `ImportMandatsAN` (L17)
- Parse `mandat/*.json` filtrés sur `legislature=17`
- Relations : acteur_ref, organe_ref
- **Durée estimée : 1h (6 000 mandats)**

### 3.4. `ImportScrutinsAN` (L17)
- Parse `scrutins/*.json` filtrés sur `legislature=17`
- Extraction titre, synthèse, ventilation votes
- **Durée estimée : 1h (3 200 scrutins)**

### 3.5. `ExtractVotesIndividuelsAN`
- Depuis `scrutins_an.ventilation_votes` (JSON)
- Dénormalise en `votes_individuels_an`
- **Durée estimée : 1-2h (320 000 votes)**

### 3.6. `ImportDossiersLegislatifsAN` (L17)
- Extrait depuis structure `amendements/DLR.../`
- **Durée estimée : 30 min (500 dossiers)**

### 3.7. `ImportTextesLegislatifsAN` (L17)
- Extrait depuis structure `amendements/DLR.../PION.../`
- Lien avec dossiers
- **Durée estimée : 30 min (1 000 textes)**

### 3.8. `ImportAmendementsAN` (L17)
- Parse récursif `amendements/**/*.json`
- Filtrer `legislature=17`
- Extraction auteur, cosignataires, dispositif, état
- **Durée estimée : 2-3h (68 000 amendements) 🔥**

---

## 🏗️ **PHASE 4 : SCRIPTS SHELL (30 min)**

### 4.1. `scripts/import_donnees_an_l17.sh`
Script master qui orchestre tout :
```bash
#!/bin/bash
echo "🚀 Import des données AN - Législature 17"
docker compose exec app php artisan import:acteurs-an
docker compose exec app php artisan import:organes-an --legislature=17
docker compose exec app php artisan import:mandats-an --legislature=17
docker compose exec app php artisan import:scrutins-an --legislature=17
docker compose exec app php artisan extract:votes-individuels-an --legislature=17
docker compose exec app php artisan import:dossiers-legislatifs-an --legislature=17
docker compose exec app php artisan import:textes-legislatifs-an --legislature=17
docker compose exec app php artisan import:amendements-an --legislature=17
echo "✅ Import terminé !"
```

### 4.2. `scripts/test_import_an_l17.sh`
Version test avec `--limit` :
```bash
#!/bin/bash
echo "🧪 Test import AN - Législature 17 (limité)"
docker compose exec app php artisan import:acteurs-an --limit=10
docker compose exec app php artisan import:scrutins-an --legislature=17 --limit=10
docker compose exec app php artisan import:amendements-an --legislature=17 --limit=100
echo "✅ Test terminé !"
```

---

## 🏗️ **PHASE 5 : TESTS & VALIDATION (1h)**

### 5.1. Tests unitaires
- Vérifier les relations Eloquent
- Tester les requêtes complexes

### 5.2. Tests d'intégration
- Import avec `--limit=10` sur chaque commande
- Vérifier cohérence des données
- Contrôler les FK

### 5.3. Validation production
```bash
# Sur le serveur
bash scripts/test_import_an_l17.sh

# Vérifier en BDD
docker compose exec postgres psql -U civicdash -d civicdash -c "
SELECT 'acteurs_an' as table, COUNT(*) FROM acteurs_an
UNION ALL
SELECT 'mandats_an', COUNT(*) FROM mandats_an
UNION ALL
SELECT 'scrutins_an', COUNT(*) FROM scrutins_an
UNION ALL
SELECT 'votes_individuels_an', COUNT(*) FROM votes_individuels_an
UNION ALL
SELECT 'amendements_an', COUNT(*) FROM amendements_an;
"
```

---

## 📊 **INDICATEURS DE SUCCÈS**

| Table | Attendu | Tolérance |
|-------|---------|-----------|
| `acteurs_an` | 603 | ±0 |
| `organes_an` | ~100 | ±20 |
| `mandats_an` | ~6 000 | ±500 |
| `scrutins_an` | ~3 200 | ±100 |
| `votes_individuels_an` | ~320 000 | ±10% |
| `dossiers_legislatifs_an` | ~500 | ±100 |
| `textes_legislatifs_an` | ~1 000 | ±200 |
| `amendements_an` | ~68 000 | ±2000 |

---

## 🚀 **PROCHAINES ÉTAPES (après import)**

1. **API Endpoints** (2-3h)
   - `/api/acteurs/{uid}/votes`
   - `/api/acteurs/{uid}/amendements`
   - `/api/scrutins?legislature=17`
   - `/api/amendements?auteur={uid}`

2. **Page "Mon Député"** (3-4h)
   - Historique de votes
   - Amendements déposés
   - Stats d'activité
   - Graphique de présence

3. **Analyse de données** (2-3h)
   - Cohésion de groupe
   - Députés rebelles
   - Taux de réussite amendements
   - Graphe relationnel

4. **Dashboard Admin** (2h)
   - Stats globales
   - Monitoring imports
   - Logs

---

## ⏱️ **PLANNING DÉTAILLÉ**

| Phase | Durée | Tâches |
|-------|-------|--------|
| **Phase 1** | 2-3h | 10 migrations |
| **Phase 2** | 1-2h | 10 modèles + relations |
| **Phase 3** | 4-5h | 8 commandes d'import |
| **Phase 4** | 30min | 2 scripts shell |
| **Phase 5** | 1h | Tests + validation |
| **TOTAL** | **9-11h** | ✅ |

---

## 🎯 **READY TO GO !**

✅ Plan validé  
✅ Structure claire  
✅ Durée réaliste (9-11h)  
✅ Objectifs mesurables  

**On démarre ? 🚀**

