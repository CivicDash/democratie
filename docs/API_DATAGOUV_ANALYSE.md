# 🇫🇷 Analyse des API data.gouv.fr pour CivicDash

**Date:** 30 octobre 2025  
**Objectif:** Identifier et planifier l'intégration des API de données publiques françaises pertinentes pour enrichir CivicDash

---

## 📊 Vue d'ensemble

[data.gouv.fr](https://www.data.gouv.fr/) est la plateforme nationale française d'open data, hébergée par la DINUM (Direction Interministérielle du Numérique). Elle expose **71 000+ jeux de données** et **374 000+ fichiers** via une API REST complète.

### 🔗 Ressources principales

- **API Documentation:** https://www.data.gouv.fr/fr/apidoc/
- **Portail principal:** https://www.data.gouv.fr/
- **Endpoint base:** `https://www.data.gouv.fr/api/1/`
- **Format:** JSON REST API
- **Authentification:** API Key (optionnelle pour la lecture, requise pour l'écriture)

---

## 🎯 API Prioritaires pour CivicDash

### 1. 💰 Données Budgétaires de l'État

#### **Source:** Ministère de l'Économie, des Finances et de l'Industrie

**Jeux de données disponibles:**
- Projet de Loi de Finances (PLF) annuel
- Lois de Finances Initiales (LFI)
- Projets Annuels de Performance (PAP)
- Exécution budgétaire par mission et programme
- Effort financier de l'État en faveur des associations

**Intérêt pour CivicDash:**
```
✅ Contexte national pour les budgets participatifs locaux
✅ Comparaison des montants (budget citoyen vs budget État)
✅ Transparence sur l'allocation des ressources publiques
✅ Éducation citoyenne sur les finances publiques
```

**Endpoint exemple:**
```
GET /api/1/datasets/plf-2025/
GET /api/1/datasets/plf-2025/resources/
```

**Structure de données (exemple PLF):**
```json
{
  "mission": "Justice",
  "programme": "Accès au droit et à la justice",
  "action": "Aide juridictionnelle",
  "credits_2025": 580000000,
  "credits_2024": 550000000,
  "evolution": "+5.45%"
}
```

**Use Cases CivicDash:**
1. **Module "Budget National"** : Afficher les grands postes budgétaires de l'État
2. **Comparatif** : "Votre budget participatif de 500k€ représente X% du budget Justice"
3. **Inspiration** : Catégories de dépenses pour les projets citoyens
4. **Transparence** : Lien entre budget local et national

---

### 2. 📜 Propositions de Loi & Activité Parlementaire

#### **Source:** Assemblée Nationale + Sénat

**Jeux de données disponibles:**
- Propositions de loi déposées
- Amendements (format Akoma Ntoso XML)
- Résultats des votes parlementaires
- Travaux législatifs en cours
- Dispositifs des textes (Monalisa format)

**Intérêt pour CivicDash:**
```
✅ Inspiration pour les propositions citoyennes
✅ Suivi de l'impact des consultations citoyennes sur la loi
✅ Transparence sur le processus législatif
✅ Éducation civique (comment une loi est votée)
```

**Endpoint exemple:**
```
GET /api/1/datasets/amendements-deposes-senat/
GET /api/1/datasets/travaux-legislatifs-senat/
```

**Structure de données (exemple amendement):**
```json
{
  "numero": "CL123",
  "legislature": "17",
  "texte": "Proposition de loi relative à...",
  "auteur": "Mme Isabelle Santiago",
  "date_depot": "2025-01-15",
  "statut": "En discussion",
  "groupe_politique": "Socialiste",
  "theme": "Protection de l'enfance"
}
```

**Use Cases CivicDash:**
1. **Module "Législation"** : Suivre les propositions en cours
2. **Inspiration citoyenne** : "Cette proposition citoyenne ressemble à la PPL N°1552"
3. **Impact tracking** : "12 propositions CivicDash ont inspiré des amendements"
4. **Éducation** : Timeline du processus législatif

---

### 3. 🏛️ Budgets des Collectivités Territoriales

#### **Source:** DGFiP + Collectivités locales

**Jeux de données disponibles:**
- Budgets primitifs des communes
- Comptes administratifs
- Dette publique locale
- Investissements par secteur
- Dotations de l'État (DSR, DGF, etc.)

**Intérêt pour CivicDash:**
```
✅ ESSENTIEL pour le module Budget Participatif
✅ Contexte local pour les citoyens
✅ Comparaison inter-communes
✅ Suivi de l'exécution budgétaire
```

**Endpoint exemple:**
```
GET /api/1/datasets/balances-comptables-communes/
GET /api/1/datasets/dotation-globale-fonctionnement/
```

**Structure de données (exemple budget commune):**
```json
{
  "code_insee": "75056",
  "nom_commune": "Paris",
  "annee": 2025,
  "population": 2145906,
  "budget_total": 10500000000,
  "investissement": 2800000000,
  "fonctionnement": 7700000000,
  "dette": 8200000000,
  "depenses_par_habitant": 4892,
  "sections": [
    {
      "nom": "Culture",
      "montant": 420000000,
      "pourcentage": 4.0
    },
    {
      "nom": "Éducation",
      "montant": 1575000000,
      "pourcentage": 15.0
    }
  ]
}
```

**Use Cases CivicDash:**
1. **📊 Dashboard Territorial** : Budget de MA commune en temps réel
2. **🎯 Budget Participatif Contextualisé** : "Ce projet représente 0.02% du budget communal"
3. **📈 Comparaisons** : "Votre commune investit X€/habitant en culture vs moyenne nationale Y€"
4. **🔍 Transparence** : Où vont les impôts locaux ?

---

### 4. 🗳️ Données Électorales

#### **Source:** Ministère de l'Intérieur

**Jeux de données disponibles:**
- Résultats élections (présidentielles, législatives, municipales, européennes)
- Taux de participation
- Répertoire des élus (RNE)
- Bureaux de vote

**Intérêt pour CivicDash:**
```
✅ Profil démocratique du territoire
✅ Comparaison participation élections vs plateformes citoyennes
✅ Identification des élus locaux
✅ Analyse de l'engagement citoyen
```

**Endpoint exemple:**
```
GET /api/1/datasets/elections-legislatives-2022/
GET /api/1/datasets/repertoire-national-elus/
```

**Use Cases CivicDash:**
1. **Module "Mon Territoire"** : Mes élus, résultats récents
2. **Engagement** : "Participation : 42% aux législatives vs 68% sur CivicDash"
3. **Connexion** : Lier propositions citoyennes aux élus locaux
4. **Transparence** : Qui représente mon quartier ?

---

### 5. 🌍 Données Géographiques & Démographiques

#### **Source:** INSEE + IGN

**Jeux de données disponibles:**
- Découpage administratif (communes, départements, régions)
- Populations légales
- Code Officiel Géographique (COG)
- Données socio-économiques (revenus, emploi, logement)
- Base Adresse Nationale (BAN)

**Intérêt pour CivicDash:**
```
✅ FONDAMENTAL pour la segmentation territoriale
✅ Ciblage des projets par quartier
✅ Statistiques démographiques
✅ Géolocalisation des propositions
```

**Endpoint exemple:**
```
GET /api/1/datasets/code-officiel-geographique/
GET /api/1/datasets/base-adresse-nationale/
```

**Use Cases CivicDash:**
1. **Carte interactive** : Visualiser les projets par quartier
2. **Ciblage** : "Ce projet concerne les 25 000 habitants du quartier X"
3. **Équité** : Répartition du budget par densité de population
4. **Statistiques** : Profil socio-économique des votants

---

### 6. 💡 Marchés Publics & Appels d'Offres

#### **Source:** DILA (Direction de l'information légale et administrative)

**Jeux de données disponibles:**
- Marchés publics notifiés
- Appels d'offres en cours
- Attributaires et montants
- Données essentielles de la commande publique (DECP)

**Intérêt pour CivicDash:**
```
✅ Transparence sur l'exécution des projets votés
✅ Suivi des appels d'offres lancés après vote citoyen
✅ Coûts réels vs estimés
✅ Identification d'entrepreneurs locaux
```

**Endpoint exemple:**
```
GET /api/1/datasets/donnees-essentielles-commande-publique/
```

**Use Cases CivicDash:**
1. **Suivi de projet** : "Le marché pour la piste cyclable a été attribué"
2. **Transparence** : Coût réel vs budget initial
3. **Alerte** : "Appel d'offres en cours pour VOTRE projet"
4. **Impact** : "12 entreprises locales ont répondu"

---

### 7. 🏗️ Données d'Urbanisme

#### **Source:** Ministère de la Transition Écologique

**Jeux de données disponibles:**
- Plans Locaux d'Urbanisme (PLU)
- Permis de construire
- Documents d'urbanisme (PLUi, SCOT)
- Zones constructibles

**Intérêt pour CivicDash:**
```
✅ Validation de la faisabilité des projets
✅ Contraintes réglementaires
✅ Contexte urbanistique
✅ Impact environnemental
```

---

### 8. 🚆 Transport & Mobilité

#### **Source:** transport.data.gouv.fr (DGTIM)

**Jeux de données disponibles:**
- Horaires transports en commun (GTFS)
- Itinéraires cyclables
- Bornes de recharge électrique
- Stationnements
- Trafic routier

**Intérêt pour CivicDash:**
```
✅ Projets de mobilité douce
✅ Validation de tracés (pistes cyclables)
✅ Accessibilité des projets proposés
✅ Impact transport
```

---

### 9. 🌱 Environnement & Écologie

#### **Source:** ecologie.data.gouv.fr (Ministère Transition Écologique)

**Jeux de données disponibles:**
- Émissions CO2
- Qualité de l'air
- Énergies renouvelables
- Espaces verts
- Installations classées (ICPE)

**Intérêt pour CivicDash:**
```
✅ Impact environnemental des projets
✅ Scoring "éco-responsable"
✅ Priorisation projets verts
✅ Sensibilisation
```

---

### 10. 📚 Subventions & Aides Publiques

#### **Source:** Ministère Économie

**Jeux de données disponibles:**
- Subventions versées par l'État
- Aides aux associations
- Dispositifs de soutien
- Bénéficiaires

**Intérêt pour CivicDash:**
```
✅ Co-financement des projets citoyens
✅ Identification d'aides complémentaires
✅ Optimisation budgétaire
✅ Partenariats public-privé
```

---

## 🏗️ Architecture d'Intégration Proposée

### Phase 1 : Fondations (Semaines 1-2)

```php
// Nouveau service Laravel
app/Services/DataGouvService.php

- Connexion API data.gouv.fr
- Cache Redis des données (7 jours)
- Rate limiting (1000 req/jour)
- Gestion erreurs & retry
```

### Phase 2 : Modules Prioritaires (Semaines 3-6)

1. **Budget Territorial** (Semaine 3)
   - Import budgets communes
   - Dashboard comparatif
   - Contexte pour budget participatif

2. **Législation** (Semaine 4)
   - Flux propositions de loi
   - Mapping avec propositions citoyennes
   - Timeline législative

3. **Données Électorales** (Semaine 5)
   - Profil territoire
   - Élus locaux
   - Taux participation

4. **Géolocalisation** (Semaine 6)
   - Intégration BAN
   - Carte projets
   - Ciblage territorial

### Phase 3 : Enrichissements (Semaines 7-10)

5. **Marchés Publics** (Semaine 7)
   - Suivi exécution projets
   - Transparence coûts

6. **Transport/Mobilité** (Semaine 8)
   - Validation projets transport
   - Impact accessibilité

7. **Environnement** (Semaine 9)
   - Scoring écologique
   - Impact CO2

8. **Subventions** (Semaine 10)
   - Co-financement projets
   - Optimisation budget

---

## 🔧 Implémentation Technique

### 1. Service DataGouv

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DataGouvService
{
    private const BASE_URL = 'https://www.data.gouv.fr/api/1/';
    private const CACHE_TTL = 604800; // 7 jours

    public function __construct(
        private ?string $apiKey = null
    ) {
        $this->apiKey = config('services.datagouv.api_key');
    }

    /**
     * Récupère les données d'un dataset
     */
    public function getDataset(string $datasetId): ?array
    {
        $cacheKey = "datagouv:dataset:{$datasetId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($datasetId) {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->retry(3, 1000)
                ->get(self::BASE_URL . "datasets/{$datasetId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        });
    }

    /**
     * Recherche de datasets
     */
    public function searchDatasets(string $query, array $filters = []): array
    {
        $params = array_merge([
            'q' => $query,
            'page_size' => 20,
        ], $filters);

        $response = Http::withHeaders($this->getHeaders())
            ->get(self::BASE_URL . 'datasets/', $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Récupère une ressource (fichier) d'un dataset
     */
    public function getResource(string $resourceId): ?array
    {
        $cacheKey = "datagouv:resource:{$resourceId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resourceId) {
            $response = Http::withHeaders($this->getHeaders())
                ->get(self::BASE_URL . "datasets/resources/{$resourceId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        });
    }

    /**
     * Télécharge et parse un CSV
     */
    public function downloadCsv(string $url): array
    {
        $response = Http::timeout(60)->get($url);
        
        if (!$response->successful()) {
            return [];
        }

        $csv = array_map('str_getcsv', explode("\n", $response->body()));
        $headers = array_shift($csv);
        
        return array_map(function ($row) use ($headers) {
            return array_combine($headers, $row);
        }, $csv);
    }

    private function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'CivicDash/1.0',
        ];

        if ($this->apiKey) {
            $headers['X-API-KEY'] = $this->apiKey;
        }

        return $headers;
    }
}
```

### 2. Service Budget Territorial

```php
<?php

namespace App\Services;

class BudgetTerritorialService
{
    public function __construct(
        private DataGouvService $dataGouvService
    ) {}

    /**
     * Récupère le budget d'une commune
     */
    public function getCommuneBudget(string $codeInsee, int $annee = 2025): ?array
    {
        $cacheKey = "budget:commune:{$codeInsee}:{$annee}";
        
        return Cache::remember($cacheKey, 86400 * 30, function () use ($codeInsee, $annee) {
            // Dataset des balances comptables des communes
            $datasetId = 'balances-comptables-communes';
            
            $data = $this->dataGouvService->getDataset($datasetId);
            
            if (!$data) {
                return null;
            }

            // Trouver la ressource CSV pour l'année demandée
            $resource = collect($data['resources'] ?? [])
                ->firstWhere('title', "like", "%{$annee}%");

            if (!$resource) {
                return null;
            }

            // Télécharger et parser le CSV
            $csvData = $this->dataGouvService->downloadCsv($resource['url']);
            
            // Filtrer pour la commune
            $communeData = collect($csvData)
                ->where('ident', $codeInsee)
                ->first();

            if (!$communeData) {
                return null;
            }

            return $this->formatBudgetData($communeData);
        });
    }

    /**
     * Compare le budget de plusieurs communes
     */
    public function compareBudgets(array $codesInsee, int $annee = 2025): array
    {
        $budgets = [];
        
        foreach ($codesInsee as $code) {
            $budget = $this->getCommuneBudget($code, $annee);
            if ($budget) {
                $budgets[$code] = $budget;
            }
        }

        return $this->calculateComparaisons($budgets);
    }

    /**
     * Contexte pour un projet de budget participatif
     */
    public function getProjectContext(string $codeInsee, float $montantProjet): array
    {
        $budget = $this->getCommuneBudget($codeInsee);
        
        if (!$budget) {
            return [];
        }

        $pourcentageBudgetTotal = ($montantProjet / $budget['budget_total']) * 100;
        $coutParHabitant = $montantProjet / $budget['population'];

        return [
            'pourcentage_budget_total' => round($pourcentageBudgetTotal, 4),
            'cout_par_habitant' => round($coutParHabitant, 2),
            'equivalent_jours_budget' => round(($montantProjet / $budget['budget_total']) * 365, 1),
            'comparaison_depenses' => $this->compareToCategories($montantProjet, $budget),
        ];
    }

    private function formatBudgetData(array $rawData): array
    {
        return [
            'code_insee' => $rawData['ident'],
            'nom_commune' => $rawData['lbudg'] ?? '',
            'annee' => $rawData['exer'],
            'population' => (int) ($rawData['population'] ?? 0),
            'budget_total' => (float) ($rawData['sd'] ?? 0),
            'recettes_fonctionnement' => (float) ($rawData['rf'] ?? 0),
            'depenses_fonctionnement' => (float) ($rawData['df'] ?? 0),
            'recettes_investissement' => (float) ($rawData['ri'] ?? 0),
            'depenses_investissement' => (float) ($rawData['di'] ?? 0),
            'dette' => (float) ($rawData['dette'] ?? 0),
            'depenses_par_habitant' => round((float) ($rawData['sd'] ?? 0) / max(1, (int) ($rawData['population'] ?? 1)), 2),
        ];
    }

    private function calculateComparaisons(array $budgets): array
    {
        // Calcul moyennes, médianes, comparaisons
        // ...
        return [];
    }

    private function compareToCategories(float $montant, array $budget): array
    {
        // Comparer le montant du projet aux grandes catégories du budget
        // ...
        return [];
    }
}
```

### 3. Migration & Models

```php
// Migration
Schema::create('datagouv_cache', function (Blueprint $table) {
    $table->id();
    $table->string('dataset_id')->index();
    $table->string('resource_id')->nullable()->index();
    $table->string('code_insee')->nullable()->index();
    $table->integer('annee')->nullable()->index();
    $table->json('data');
    $table->timestamp('fetched_at');
    $table->timestamps();
    
    $table->index(['dataset_id', 'code_insee', 'annee']);
});

Schema::create('commune_budgets', function (Blueprint $table) {
    $table->id();
    $table->string('code_insee', 5)->index();
    $table->string('nom_commune');
    $table->integer('annee')->index();
    $table->integer('population');
    $table->bigInteger('budget_total');
    $table->bigInteger('recettes_fonctionnement');
    $table->bigInteger('depenses_fonctionnement');
    $table->bigInteger('recettes_investissement');
    $table->bigInteger('depenses_investissement');
    $table->bigInteger('dette');
    $table->decimal('depenses_par_habitant', 10, 2);
    $table->json('sections')->nullable();
    $table->timestamps();
    
    $table->unique(['code_insee', 'annee']);
});
```

### 4. Controller & Routes

```php
// routes/api.php
Route::prefix('datagouv')->group(function () {
    Route::get('commune/{codeInsee}/budget/{annee?}', [DataGouvController::class, 'getCommuneBudget']);
    Route::get('communes/compare', [DataGouvController::class, 'compareBudgets']);
    Route::get('project/{projectId}/context', [DataGouvController::class, 'getProjectContext']);
    Route::get('propositions-loi', [DataGouvController::class, 'getPropositionsLoi']);
    Route::get('elus/{codeInsee}', [DataGouvController::class, 'getElus']);
});

// Controller
class DataGouvController extends Controller
{
    public function __construct(
        private BudgetTerritorialService $budgetService
    ) {}

    public function getCommuneBudget(string $codeInsee, ?int $annee = null)
    {
        $budget = $this->budgetService->getCommuneBudget($codeInsee, $annee ?? date('Y'));
        
        if (!$budget) {
            return response()->json(['error' => 'Budget non trouvé'], 404);
        }

        return response()->json($budget);
    }

    // ...
}
```

### 5. Vue Component Exemple

```vue
<template>
  <div class="budget-context">
    <h3>Contexte Budgétaire</h3>
    
    <div v-if="context" class="stats-grid">
      <div class="stat-card">
        <span class="stat-label">Part du budget communal</span>
        <span class="stat-value">{{ context.pourcentage_budget_total }}%</span>
      </div>
      
      <div class="stat-card">
        <span class="stat-label">Coût par habitant</span>
        <span class="stat-value">{{ context.cout_par_habitant }}€</span>
      </div>
      
      <div class="stat-card">
        <span class="stat-label">Équivalent en jours de budget</span>
        <span class="stat-value">{{ context.equivalent_jours_budget }} jours</span>
      </div>
    </div>

    <div class="comparaison">
      <p>Ce projet représente environ {{ comparaison }} de la commune</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  projectId: Number,
  montant: Number,
});

const context = ref(null);

const comparaison = computed(() => {
  if (!context.value?.comparaison_depenses) return '';
  
  const comp = context.value.comparaison_depenses[0];
  return `${comp.pourcentage}% du budget "${comp.categorie}"`;
});

onMounted(async () => {
  try {
    const response = await axios.get(`/api/datagouv/project/${props.projectId}/context`);
    context.value = response.data;
  } catch (error) {
    console.error('Erreur chargement contexte budget:', error);
  }
});
</script>
```

---

## 📋 Plan de Déploiement

### Étape 1 : Configuration (1 jour)

```bash
# .env
DATAGOUV_API_KEY=your_api_key_here  # Optionnel mais recommandé
DATAGOUV_CACHE_TTL=604800  # 7 jours
```

### Étape 2 : Import Initial (2-3 jours)

```php
// Command Artisan
php artisan datagouv:import budgets --year=2025
php artisan datagouv:import propositions-loi --since=2024-01-01
php artisan datagouv:import elus --departement=75
```

### Étape 3 : Synchronisation Quotidienne

```php
// app/Console/Kernel.php
$schedule->command('datagouv:sync budgets')->daily();
$schedule->command('datagouv:sync propositions-loi')->twiceDaily();
```

### Étape 4 : Monitoring

- **Logs:** Erreurs API, timeouts, limites de taux
- **Alertes:** Données manquantes, incohérences
- **Métriques:** Taux de succès, temps de réponse, cache hit rate

---

## 💡 Fonctionnalités Innovantes

### 1. **Budget Participatif Augmenté**

```
📊 Afficher le contexte budgétaire pour chaque projet :
  - "Ce projet de piste cyclable (150k€) représente 0.12% du budget communal"
  - "Coût : 2.34€ par habitant"
  - "Équivalent à 4.8 jours de budget culture"
```

### 2. **Proposition Législative Citoyenne**

```
📜 Connecter propositions citoyennes et propositions de loi :
  - "Votre proposition ressemble à la PPL N°1552 actuellement en discussion"
  - "12 propositions CivicDash ont inspiré des amendements"
  - Timeline : "Proposition → Consultation → Amendement → Vote"
```

### 3. **Tableau de Bord Territorial**

```
🗺️ Vue d'ensemble du territoire :
  - Budget communal en temps réel
  - Élus locaux et leurs positions
  - Résultats électoraux récents
  - Projets en cours (marchés publics)
  - Comparaison avec communes similaires
```

### 4. **Suivi d'Impact Citoyen**

```
📈 Mesurer l'impact des propositions :
  - "3 projets CivicDash ont généré 2.4M€ de marchés publics"
  - "Taux de participation 2x supérieur aux élections locales"
  - "12 entreprises locales ont bénéficié des projets votés"
```

### 5. **Transparence Financière**

```
💰 Suivi de l'exécution budgétaire :
  - "Budget voté : 150k€ → Marché attribué : 148k€ → Économie : 2k€"
  - "Délai moyen de réalisation : 8 mois"
  - "92% des projets respectent le budget initial"
```

---

## 🎯 Métriques de Succès

### Indicateurs de Performance

- **Fraîcheur des données** : < 24h pour données critiques
- **Disponibilité API** : > 99.5%
- **Temps de réponse** : < 500ms (avec cache)
- **Taux d'utilisation** : 60%+ des utilisateurs consultent le contexte budgétaire

### Indicateurs d'Impact

- **Engagement** : +25% de participation avec contexte budgétaire
- **Qualité** : +40% de projets réalistes (grâce au contexte)
- **Transparence** : 85% des utilisateurs trouvent le contexte utile
- **Éducation** : +30% de compréhension des finances publiques

---

## 🚨 Risques & Mitigation

### Risque 1 : Disponibilité API data.gouv.fr

**Impact** : Moyen  
**Probabilité** : Faible  
**Mitigation** :
- Cache Redis 7 jours
- Fallback sur données locales
- Retry automatique (3 tentatives)
- Monitoring avec alertes

### Risque 2 : Qualité/Complétude des Données

**Impact** : Moyen  
**Probabilité** : Moyen  
**Mitigation** :
- Validation des données à l'import
- Signalement des incohérences
- Données par défaut si manquantes
- Feedback utilisateurs

### Risque 3 : Performance (gros volumes)

**Impact** : Faible  
**Probabilité** : Faible  
**Mitigation** :
- Cache agressif (7 jours)
- Indexation base de données
- Pagination systématique
- Import asynchrone (queues)

### Risque 4 : Évolution des API

**Impact** : Faible  
**Probabilité** : Moyen  
**Mitigation** :
- Tests automatisés d'intégration
- Versioning des parsers
- Documentation des changements
- Abstraction (Design Pattern Adapter)

---

## 📚 Ressources & Documentation

### Liens Officiels

- **API data.gouv.fr** : https://www.data.gouv.fr/fr/apidoc/
- **Guides** : https://guides.data.gouv.fr/
- **Forum** : https://forum.data.gouv.fr/
- **GitHub** : https://github.com/datagouv

### Datasets Clés (IDs)

```
plf-2025                              # Projet Loi de Finances 2025
balances-comptables-communes          # Budgets communes
amendements-deposes-senat             # Amendements Sénat
travaux-legislatifs-senat             # Travaux législatifs
repertoire-national-elus              # Élus locaux
code-officiel-geographique            # COG INSEE
base-adresse-nationale                # BAN
donnees-essentielles-commande-publique # DECP (marchés publics)
elections-legislatives-2022           # Résultats élections
```

### Contact & Support

- **Email** : contact@data.gouv.fr
- **Discord CivicDash** : https://discord.gg/jeGaDZcXP5
- **Issues GitHub** : (créer repo datagouv-integration)

---

## ✅ Checklist d'Implémentation

### Phase 1 : Fondations
- [ ] Créer `DataGouvService`
- [ ] Configurer API Key dans `.env`
- [ ] Ajouter tests unitaires
- [ ] Mettre en place cache Redis
- [ ] Créer migrations tables
- [ ] Documenter API locale

### Phase 2 : Budget Territorial
- [ ] Créer `BudgetTerritorialService`
- [ ] Import budgets communes
- [ ] Endpoint API `/api/datagouv/commune/{code}/budget`
- [ ] Component Vue `BudgetContext.vue`
- [ ] Dashboard comparatif
- [ ] Tests fonctionnels

### Phase 3 : Propositions de Loi
- [ ] Créer `LegislationService`
- [ ] Import propositions + amendements
- [ ] Matching avec propositions citoyennes
- [ ] Timeline législative
- [ ] Tests

### Phase 4 : Autres Modules
- [ ] Données électorales
- [ ] Marchés publics
- [ ] Transport/mobilité
- [ ] Environnement
- [ ] Subventions

### Phase 5 : Monitoring & Optimisation
- [ ] Dashboard admin (stats API)
- [ ] Alertes erreurs
- [ ] Optimisation performances
- [ ] Documentation utilisateur
- [ ] Formation équipe

---

## 🎉 Conclusion

L'intégration des API data.gouv.fr représente une **opportunité majeure** pour CivicDash :

✅ **Différenciation** : Seule plateforme citoyenne avec contexte budgétaire réel  
✅ **Crédibilité** : Données officielles de l'État français  
✅ **Éducation** : Transparence sur les finances publiques  
✅ **Impact** : Suivi de l'exécution des projets citoyens  
✅ **Innovation** : Pont entre démocratie locale et nationale  

**Effort estimé** : 8-10 semaines (1 développeur)  
**Valeur ajoutée** : 🔥🔥🔥🔥🔥 (critique pour la réussite du projet)

**Prochaine étape** : Validation de la roadmap et début Phase 1.

---

**Rédigé le 30 octobre 2025**  
**Pour CivicDash - Plateforme de Démocratie Participative**  
**Par l'équipe IA CivicDash** 🚀

