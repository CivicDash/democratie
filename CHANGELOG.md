# CHANGELOG - CivicDash

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

---

## [2025-11-20] - Enrichissement Wikipedia + API v1

### 🎯 **OBJECTIF : Ajouter données Wikipedia + Exposer API v1**

**Durée totale :** ~3h  
**Livrables :** 11 fichiers créés/modifiés

---

### ✨ **NOUVELLES FONCTIONNALITÉS**

#### **Enrichissement Wikipedia (Députés AN)**

**Migration (1)**
- `2025_11_20_091128_add_wikipedia_fields_to_acteurs_an_table.php` - Colonnes Wikipedia

**Service (1)**
- `WikipediaService` - Parsing HTML + API MediaWiki REST
  - Parsing tableau Wikipedia L17 (577 députés)
  - Matching intelligent (Levenshtein, seuil 80%)
  - API REST MediaWiki pour photos et extraits
  - Normalisation des noms (accents, minuscules)

**Commande Artisan (1)**
- `import:deputes-wikipedia` - Import données Wikipedia
  - Options : `--legislature`, `--limit`, `--force`, `--dry-run`
  - Matching automatique avec base de données
  - Statistiques détaillées (taux match, photos)
  - Rate limiting (100ms entre requêtes)

**Script Shell (1)**
- `scripts/import_wikipedia_deputes.sh` - Menu interactif
  - Mode TEST (10 députés, dry-run)
  - Mode SIMULATION (tous, dry-run)
  - Mode IMPORT COMPLET
  - Mode RÉIMPORT FORCÉ
  - Mode LIMITÉ (personnalisé)

**Données récupérées :**
- ✅ URL Wikipedia (page biographique)
- ✅ Photo Wikipedia (haute qualité)
- ✅ Extrait biographique (premier paragraphe)
- ✅ Timestamp de synchronisation

#### **API REST v1**

**Controllers (4)**
- `ActeursANController` - 5 endpoints députés
- `ScrutinsANController` - 4 endpoints scrutins
- `AmendementsANController` - 3 endpoints amendements
- `SenateursController` - 6 endpoints sénateurs

**Routes (1)**
- Préfixe `/api/v1/` pour versioning
- 18 endpoints RESTful publics
- Filtres avancés (dates, recherche full-text, législature)
- Pagination (max 100 par page)

**Documentation (1)**
- `API_DOCUMENTATION_V1.md` (850+ lignes)
- Tous les endpoints documentés
- Exemples cURL, JavaScript, Python
- Codes d'erreur
- Limites et performances

---

### 🔧 **AMÉLIORATIONS TECHNIQUES**

#### **Modèles**
- `ActeurAN` : Ajout colonnes Wikipedia (fillable + casts)
- Exposition données Wikipedia dans API show

#### **Services**
- `WikipediaService` réutilisable pour autres entités (sénateurs)
- Gestion d'erreurs robuste (try-catch, logs)
- User-Agent personnalisé : `CivicDash/1.0`

#### **API**
- Filtres : nom, prénom, search, dates, législature, état
- Recherche full-text PostgreSQL (GIN index)
- Relations Eloquent optionnelles (`with_mandats`, `with_groupe`)
- Statistiques agrégées (taux participation, adoption)
- Tri personnalisable (`sort_by`, `sort_order`)

---

### 📊 **ENDPOINTS API V1**

#### **Acteurs AN**
```
GET /api/v1/acteurs                    # Liste avec filtres
GET /api/v1/acteurs/{uid}              # Détails + Wikipedia
GET /api/v1/acteurs/{uid}/votes        # Historique votes
GET /api/v1/acteurs/{uid}/amendements  # Amendements déposés
GET /api/v1/acteurs/{uid}/stats        # Statistiques activité
```

#### **Scrutins AN**
```
GET /api/v1/scrutins                      # Liste avec filtres
GET /api/v1/scrutins/{uid}                # Détails
GET /api/v1/scrutins/{uid}/votes          # Votes individuels
GET /api/v1/scrutins/{uid}/stats-par-groupe  # Stats par groupe
```

#### **Amendements AN**
```
GET /api/v1/amendements          # Liste avec filtres
GET /api/v1/amendements/stats    # Statistiques générales
GET /api/v1/amendements/{uid}    # Détails
```

#### **Sénateurs**
```
GET /api/v1/senateurs                      # Liste avec filtres
GET /api/v1/senateurs/stats                # Statistiques générales
GET /api/v1/senateurs/{matricule}          # Détails
GET /api/v1/senateurs/{matricule}/mandats     # Mandats
GET /api/v1/senateurs/{matricule}/commissions # Commissions
GET /api/v1/senateurs/{matricule}/groupes     # Historique groupes
```

---

### 📚 **DOCUMENTATION**

**Nouvelles documentations (2)**
- `API_DOCUMENTATION_V1.md` (850 lignes) - Référence API complète
- `WIKIPEDIA_ENRICHMENT.md` (450 lignes) - Guide enrichissement Wikipedia

---

### 🚀 **UTILISATION**

#### **Import Wikipedia**
```bash
# Test rapide
bash scripts/import_wikipedia_deputes.sh
# Choisir : 1) TEST

# Import complet
bash scripts/import_wikipedia_deputes.sh
# Choisir : 3) IMPORT COMPLET

# Mise à jour forcée
docker compose exec app php artisan import:deputes-wikipedia --force
```

#### **Exemples API**
```bash
# Rechercher un député
curl "https://demo.objectif2027.fr/api/v1/acteurs?search=David&deputes_only=true"

# Statistiques d'activité
curl "https://demo.objectif2027.fr/api/v1/acteurs/PA1008/stats?legislature=17"

# Scrutins adoptés en 2024
curl "https://demo.objectif2027.fr/api/v1/scrutins?legislature=17&date_min=2024-01-01&adoptes_only=true"

# Amendements sur le climat
curl "https://demo.objectif2027.fr/api/v1/amendements?legislature=17&search=climat&adoptes_only=true"
```

---

### ⚠️ **NOTES IMPORTANTES**

#### **Wikipedia**
- **Taux de match attendu** : ~95% (550/577 députés)
- **Rate limiting** : 200 req/s max (API Wikipedia)
- **Délai** : 100ms entre requêtes (configurable)
- **Maintenance** : Réimport mensuel recommandé

#### **API**
- **Pagination obligatoire** : max 100 résultats/page (200 pour votes)
- **Pas de cache** : données temps réel (à implémenter)
- **Pas de rate limiting** : à implémenter en production
- **CORS** : À configurer selon besoins frontend

---

### 🎯 **PROCHAINES ÉTAPES**

- [ ] Tests unitaires (Services + Controllers)
- [ ] Cache Redis (TTL 1h)
- [ ] Rate limiting (100 req/min)
- [ ] Enrichissement Wikipedia pour sénateurs
- [ ] Webhooks pour nouveaux scrutins
- [ ] Export CSV/PDF

---

## [2025-11-18] - Session Implémentation AN + Sénat

### 🎯 **OBJECTIF : Import complet données parlementaires (Option C)**

**Durée totale :** ~6 heures  
**Stratégie :** Assemblée Nationale L17 + Sénat Basique  
**Livrables :** 45 fichiers créés

---

### ✨ **NOUVELLES FONCTIONNALITÉS**

#### **Phase 1 : Assemblée Nationale Législature 17**

**Migrations (10)**
- `2025_11_18_100000_create_acteurs_an_table.php` - Députés (603 acteurs)
- `2025_11_18_100100_create_organes_an_table.php` - Groupes, commissions, délégations
- `2025_11_18_100200_create_mandats_an_table.php` - Historique mandats (~6 000)
- `2025_11_18_100300_create_scrutins_an_table.php` - Scrutins publics (~3 200 L17)
- `2025_11_18_100400_create_votes_individuels_an_table.php` - Votes dénormalisés (~320 000)
- `2025_11_18_100500_create_dossiers_legislatifs_an_table.php` - Dossiers législatifs
- `2025_11_18_100600_create_textes_legislatifs_an_table.php` - Propositions/projets de loi
- `2025_11_18_100700_create_amendements_an_table.php` - Amendements (~68 000 L17)
- `2025_11_18_100800_create_reunions_an_table.php` - Réunions commissions
- `2025_11_18_100900_create_deports_an_table.php` - Déports

**Modèles Eloquent (10)**
- `ActeurAN` - Avec scopes (deputes), accessors (nomComplet, groupePolitiqueActuel)
- `OrganeAN` - Avec scopes (groupesPolitiques, commissionsPermanentes, actifs)
- `MandatAN` - Avec scopes (actifs, assemblee, groupePolitique)
- `ScrutinAN` - Avec accessors (tauxParticipation, tauxPour, estAdopte)
- `VoteIndividuelAN` - Avec scopes (pour, contre, abstention) et accessor (estRebelle)
- `DossierLegislatifAN` - Avec relations vers textes
- `TexteLegislatifAN` - Avec relations vers amendements
- `AmendementAN` - Avec scopes (adoptes, rejetes, parAuteur)
- `ReunionAN` - Avec relations vers organes
- `DeportAN` - Avec relations vers acteurs et scrutins

**Commandes Artisan (7)**
- `import:acteurs-an` - Import 603 acteurs depuis JSON local
- `import:organes-an` - Import organes avec filtrage législature
- `import:mandats-an` - Import mandats depuis acteurs JSON
- `import:scrutins-an` - Import scrutins avec filtrage L17
- `extract:votes-individuels-an` - Dénormalisation votes depuis scrutins.ventilation_votes
- `import:dossiers-textes-an` - Extraction dossiers/textes depuis structure amendements/
- `import:amendements-an` - Parsing récursif 68 000 amendements L17

**Scripts Shell (3)**
- `scripts/import_donnees_an_l17.sh` - Orchestration import complet (2-3h)
- `scripts/test_import_an_l17.sh` - Test rapide avec --limit (5 min)
- `scripts/test_donnees_an.sh` - Statistiques SQL détaillées

#### **Phase 2 : Sénat Basique**

**Migrations (5)**
- `2025_11_18_110000_create_senateurs_table.php` - Sénateurs (~2 000)
- `2025_11_18_110100_create_senateurs_historique_groupes_table.php` - Historique groupes
- `2025_11_18_110200_create_senateurs_commissions_table.php` - Commissions permanentes
- `2025_11_18_110300_create_senateurs_mandats_table.php` - Mandats tous types (~4 000)
- `2025_11_18_110400_create_senateurs_etudes_table.php` - Formations académiques

**Modèles Eloquent (5)**
- `Senateur` - Avec scopes (actifs, parCirconscription, parGroupe)
- `SenateurHistoriqueGroupe` - Évolution politique
- `SenateurCommission` - Affectations commissions
- `SenateurMandat` - 5 types (SENATEUR, DEPUTE, EUROPEEN, METROPOLITAIN, MUNICIPAL)
- `SenateurEtude` - Parcours académique

**Commandes Artisan (1)**
- `import:senateurs-complet` - Import depuis 14 APIs REST data.senat.fr (5-10 min)

**Scripts Shell (1)**
- `scripts/import_senateurs_complet.sh` - Import API REST avec stats

---

### 🔧 **AMÉLIORATIONS TECHNIQUES**

#### **Base de données**
- ✅ Index composites sur (legislature, date) pour performances
- ✅ Foreign keys avec CASCADE/SET NULL
- ✅ Full-text search GIN (PostgreSQL) sur nom, titre, dispositif, exposé
- ✅ JSON pour adresses, ventilation_votes, cosignataires

#### **Architecture**
- ✅ Relations Eloquent complètes (hasMany, belongsTo, belongsToMany)
- ✅ Scopes réutilisables sur tous les modèles
- ✅ Accessors pour calculs dynamiques (taux, statuts)
- ✅ Commandes avec options --limit, --fresh, --all pour flexibilité

#### **Performances**
- ✅ Import idempotent (updateOrCreate)
- ✅ Batch processing pour amendements (affichage tous les 1000)
- ✅ Pagination obligatoire sur votes_individuels_an (320k lignes)

---

### 📊 **VOLUMÉTRIE**

| Entité | Nombre | Taille estimée |
|--------|--------|----------------|
| Acteurs AN | 603 | ~500 KB |
| Organes AN L17 | ~100 | ~50 KB |
| Mandats AN L17 | ~6 000 | ~2 MB |
| Scrutins AN L17 | ~3 200 | ~10 MB |
| Votes individuels | ~320 000 | ~100 MB |
| Amendements L17 | ~68 000 | ~500 MB |
| Sénateurs | ~2 000 | ~1 MB |
| Mandats Sénat | ~4 000 | ~2 MB |
| **TOTAL** | **~408 000 enregistrements** | **~2 GB** |

---

### 📚 **DOCUMENTATION**

**Analyses (2)**
- `ANALYSE_DONNEES_AN.md` (610 lignes) - Structure complète JSON AN
- `ANALYSE_DONNEES_SENAT.md` (704 lignes) - 14 endpoints API Sénat

**Plans d'implémentation (1)**
- `PLAN_IMPLEMENTATION_AN_L17.md` (464 lignes) - Roadmap détaillée

**Synthèses (2)**
- `SESSION_18_NOV_2025_PHASE1_AN_COMPLETE.md` - Récap Phase 1
- `SESSION_18_NOV_2025_COMPLETE.md` - Synthèse finale complète

**Total documentation :** ~2 500 lignes

---

### 🚀 **UTILISATION**

#### **Test rapide (10 min)**
```bash
bash scripts/test_import_an_l17.sh
bash scripts/import_senateurs_complet.sh
```

#### **Import complet (2-3h)**
```bash
bash scripts/import_donnees_an_l17.sh
bash scripts/import_senateurs_complet.sh
```

#### **Exemples PHP**
```php
// Récupérer un député avec son groupe
$depute = ActeurAN::with('mandats.organe')->find('PA1008');
echo $depute->groupe_politique_actuel->libelle;

// Analyser un scrutin
$scrutin = ScrutinAN::with('votesIndividuels')->where('numero', 1000)->first();
echo "Taux participation : {$scrutin->taux_participation}%";

// Top 10 auteurs d'amendements
$top = AmendementAN::select('auteur_acteur_ref', DB::raw('COUNT(*) as total'))
    ->where('legislature', 17)
    ->groupBy('auteur_acteur_ref')
    ->orderByDesc('total')
    ->limit(10)
    ->get();

// Rechercher un sénateur
$senateur = Senateur::actifs()->parCirconscription('Paris')->first();
echo $senateur->commission_permanente;
```

---

### 🔗 **RELATIONS CRÉÉES**

```
ActeurAN (PA1008)
  ├── MandatAN (ASSEMBLEE) → OrganeAN (Assemblée)
  ├── MandatAN (GP) → OrganeAN (Groupe politique)
  ├── MandatAN (COMPER) → OrganeAN (Commission)
  ├── VoteIndividuelAN → ScrutinAN
  └── AmendementAN → TexteLegislatifAN → DossierLegislatifAN

Senateur (21077M)
  ├── SenateurHistoriqueGroupe (évolution politique)
  ├── SenateurCommission (affectations)
  ├── SenateurMandat (SENATEUR, MUNICIPAL, etc.)
  └── SenateurEtude (parcours académique)
```

---

### ⚠️ **NOTES IMPORTANTES**

#### **Performances**
- Table `votes_individuels_an` très volumineuse (320k) → pagination obligatoire
- Import amendements lent (~2h pour 68k) → prévoir batch
- Full-text search nécessite PostgreSQL avec extension pg_trgm

#### **Données**
- Certains acteurs n'ont pas de mandat ASSEMBLEE (anciens députés, sénateurs)
- Scrutins : ventilation_votes peut être incomplète si < 577 députés
- Amendements : champs titre et date_depot souvent NULL
- APIs Sénat temps réel : données toujours à jour

#### **Maintenance**
- AN : réimport mensuel recommandé (nouvelles données)
- Sénat : réimport hebdomadaire (API temps réel)
- Scrutins : import incrémental après chaque séance

---

### 🎯 **PROCHAINES ÉTAPES**

- [ ] Phase 3 : API Endpoints REST (2-3h)
- [ ] Phase 4 : Frontend pages députés/sénateurs (4-5h)
- [ ] Phase 5 : Analyses avancées (cohésion groupes, députés rebelles)
- [ ] Tests unitaires et d'intégration
- [ ] Documentation API (OpenAPI/Swagger)

---

## [2025-11-08] - Import Députés & Sénateurs depuis CSV

### Ajouté
- Commande `ImportDeputesFromCsv` pour importer les députés depuis `elus-deputes-dep.csv`
- Commande `ImportSenateursFromCsv` pour importer les sénateurs depuis `elus-senateurs-sen.csv`
- Script shell `import_representants.sh` pour automatiser l'import
- Guide `GUIDE_IMPORT_REPRESENTANTS.md`

### Modifié
- Table `deputes_senateurs` : ajout de colonnes manquantes pour CSV
- Migration `fix_postal_codes_unique_constraint` : rendue idempotente

---

## [2025-11-08] - Import Maires + Table dédiée

### Ajouté
- Migration `create_maires_table` pour table dédiée aux maires
- Modèle `Maire` avec relations
- Commande `ImportMairesFromCsv` pour import depuis `elus-maires-mai.csv`
- Script shell `import_maires.sh`

### Modifié
- Migration `increase_maires_uid_length` : VARCHAR(50) → VARCHAR(150)

---

## [2025-11-08] - API Recherche Représentants

### Ajouté
- Controller `RepresentantsSearchController` pour recherche par code postal/ville
- Route API `/api/representants/search`
- Guide `GUIDE_RECHERCHE_REPRESENTANTS.md`

---

## [2025-11-08] - Enrichissement Députés via API

### Ajouté
- Commande `EnrichDeputesFromApi` pour enrichir depuis NosDéputés.fr
- Colonnes : groupe_politique, photo_url, nb_mandats, stats activité
- Script shell `enrich_deputes.sh`

---

## [2025-11-08] - Enrichissement Sénateurs via API

### Ajouté
- Commande `EnrichSenateursFromApi` pour enrichir depuis NosSénateurs.fr
- Colonnes : groupe_politique, photo_url, stats activité
- Script shell `enrich_senateurs.sh`

---

## [2025-11-08] - Import COMPLET : Votes + Interventions + Questions

### Ajouté
- Migration `create_votes_interventions_tables` : 3 nouvelles tables
  - `votes_deputes` : votes détaillés par scrutin
  - `interventions_parlementaires` : interventions en séance
  - `questions_gouvernement` : questions au gouvernement
- Modèles : `VoteDepute`, `InterventionParlementaire`, `QuestionGouvernement`
- Commandes :
  - `EnrichDeputesVotesFromApi` : import votes + interventions + questions députés
  - `EnrichSenateursVotesFromApi` : import votes + interventions + questions sénateurs
- Script shell `enrich_complete.sh` : orchestration complète
- Documentation `IMPORT_VOTES_COMPLET.md`

### Modifié
- Modèle `DeputeSenateur` : ajout relations votes(), interventions(), questions()
- Commandes enrichissement : appels API séparés pour votes/interventions/questions

---

## [2025-11-08] - Amendements Parlementaires Détaillés

### Ajouté
- Migration `create_amendements_parlementaires_table`
- Modèle `AmendementParlementaire` avec relations
- Commande `EnrichAmendementsFromApi`
- Script shell `enrich_amendements.sh`

---

## [2025-11-08] - Organes Parlementaires (Groupes, Commissions, Délégations)

### Ajouté
- Migration `create_organes_parlementaires_tables` : 2 tables
  - `organes_parlementaires` : groupes, commissions, délégations
  - `membres_organes` : table pivot avec fonction, dates
- Modèles : `OrganeParlementaire`, `MembreOrgane`
- Commande `ImportOrganesFromApi`
- Script shell `import_organes.sh`
- Relations dans `DeputeSenateur` : membresOrganes(), organesActuels(), organes()

### Modifié
- Migration : suppression index dupliqué sur `sigle`

---

## [2025-11-08] - Scripts & Documentation

### Ajouté
- `enrich_all.sh` : script master pour tous les enrichissements
- `QUICKSTART_ENRICHISSEMENT.md` : guide rapide
- `ROADMAP_ENRICHISSEMENT.md` : roadmap détaillée phases 1-5
- `PHASE1_RESUME.md` : résumé Phase 1
- `PHASE2_ORGANES_RESUME.md` : résumé Phase 2
- `SESSION_8_NOV_FINAL.md` : synthèse complète session
- `VISUAL_RECAP.md` : récap visuel

### Modifié
- Tous les scripts : `docker-compose` → `docker compose`

---

## [2025-11-08] - Corrections & Optimisations

### Corrigé
- Migration `fix_postal_codes_unique_constraint` : checks existence contraintes
- Modèle `InterventionParlementaire` : ajout `protected $table` (pluralisation)
- Scripts shell : compatibilité `docker compose` (sans tiret)
- Migration organes : suppression index dupliqué `sigle`

### Ajouté
- Scripts debug :
  - `check_postal_table.sh`
  - `fix_postal_table.sh`
  - `clean_postal_table.sh`
  - `debug_votes_import.sh`

---

## [Versions antérieures]

Voir historique Git pour les versions précédentes.
