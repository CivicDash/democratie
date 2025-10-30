# 📚 Guide d'Utilisation - Intégration data.gouv.fr

## 🎯 Vue d'ensemble

CivicDash intègre l'API officielle **data.gouv.fr** pour enrichir les propositions citoyennes avec des données budgétaires réelles des communes françaises.

Cette intégration permet de :
- ✅ Afficher le contexte budgétaire pour chaque projet citoyen
- ✅ Comparer les budgets entre communes
- ✅ Éduquer les citoyens sur les finances publiques
- ✅ Renforcer la crédibilité des propositions avec des données officielles

---

## 🚀 Démarrage Rapide

### 1. Configuration

#### `.env`
```bash
# API Key data.gouv.fr (optionnelle mais recommandée)
DATAGOUV_API_KEY=your_api_key_here

# Durée de cache (7 jours par défaut)
DATAGOUV_CACHE_TTL=604800
```

> 💡 **Comment obtenir une API Key ?**  
> Rendez-vous sur https://www.data.gouv.fr/fr/admin/me/ et générez une clé API.

### 2. Migration

```bash
php artisan migrate
```

Cela créera deux tables :
- `datagouv_cache` : Cache des données récupérées
- `commune_budgets` : Budgets des communes

### 3. Import Initial

#### Importer quelques communes de test
```bash
php artisan datagouv:import-budgets 75056 13055 69123
```

#### Importer les 30 plus grandes villes
```bash
php artisan datagouv:import-budgets --top=30 --year=2024
```

#### Import interactif
```bash
php artisan datagouv:import-budgets
# Entrez les codes INSEE: 75056 13055 69123
```

---

## 📡 API Endpoints

### 1. Budget d'une Commune

**GET** `/api/datagouv/commune/{codeInsee}/budget/{annee?}`

Récupère le budget détaillé d'une commune.

**Paramètres:**
- `codeInsee` (required): Code INSEE 5 chiffres (ex: `75056` pour Paris)
- `annee` (optional): Année du budget (défaut: année en cours)

**Exemple:**
```bash
curl http://localhost:7777/api/datagouv/commune/75056/budget/2024
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "code_insee": "75056",
    "nom_commune": "Paris",
    "annee": 2024,
    "population": 2145906,
    "budget_total": 1050000000000,
    "recettes_fonctionnement": 770000000000,
    "depenses_fonctionnement": 750000000000,
    "recettes_investissement": 280000000000,
    "depenses_investissement": 260000000000,
    "dette": 820000000000,
    "depenses_par_habitant": 4892.34,
    "taux_endettement": 78.1
  }
}
```

---

### 2. Contexte Budgétaire pour un Projet

**GET** `/api/datagouv/project/context`

Génère le contexte budgétaire pour un projet citoyen.

**Paramètres:**
- `code_insee` (required): Code INSEE de la commune
- `montant` (required): Montant du projet en euros
- `categorie` (optional): Catégorie du projet (culture, education, etc.)

**Exemple:**
```bash
curl "http://localhost:7777/api/datagouv/project/context?code_insee=75056&montant=150000&categorie=culture"
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "commune": {
      "code_insee": "75056",
      "nom": "Paris",
      "population": 2145906
    },
    "projet": {
      "montant": 150000,
      "categorie": "culture"
    },
    "impact": {
      "pourcentage_budget_total": 0.0014,
      "pourcentage_investissement": 0.0058,
      "cout_par_habitant": 0.07,
      "equivalent_jours_budget": 0.5
    },
    "comparaisons": [
      {
        "poste": "Culture et sports",
        "montant_annuel_estime": 840000000,
        "pourcentage_du_poste": 1.79,
        "pertinent": true
      }
    ],
    "contexte_lisible": {
      "principal": "Ce projet de 150 k€ représente 0.0014% du budget annuel de Paris.",
      "par_habitant": "Cela correspond à un coût de 0.07 € par habitant.",
      "temporel": "Cela équivaut à 12 heures de budget communal."
    }
  }
}
```

---

### 3. Comparer Plusieurs Communes

**GET** `/api/datagouv/communes/compare`

Compare les budgets de plusieurs communes.

**Paramètres:**
- `codes[]` (required): Liste des codes INSEE (min: 2, max: 10)
- `annee` (optional): Année de référence

**Exemple:**
```bash
curl "http://localhost:7777/api/datagouv/communes/compare?codes[]=75056&codes[]=13055&codes[]=69123&annee=2024"
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "annee": 2024,
    "communes": {
      "75056": {
        "nom": "Paris",
        "population": 2145906,
        "budget_total": 10500000000,
        "depenses_par_habitant": 4892.34,
        "taux_endettement": 78.1
      },
      "13055": {
        "nom": "Marseille",
        "population": 873076,
        "budget_total": 2800000000,
        "depenses_par_habitant": 3206.78,
        "taux_endettement": 65.4
      }
    },
    "moyennes": {
      "depenses_par_habitant": 4049.56
    },
    "totaux": {
      "population": 3018982,
      "budget_total": 13300000000
    }
  }
}
```

---

### 4. Rechercher des Communes

**GET** `/api/datagouv/communes/search`

Recherche des communes par nom.

**Paramètres:**
- `q` (required): Terme de recherche (min: 2 caractères)
- `annee` (optional): Année du budget
- `limit` (optional): Nombre de résultats (max: 50)

**Exemple:**
```bash
curl "http://localhost:7777/api/datagouv/communes/search?q=paris&limit=5"
```

---

### 5. Statistiques du Service

**GET** `/api/datagouv/stats`

Récupère les statistiques d'utilisation du service.

**Exemple:**
```bash
curl http://localhost:7777/api/datagouv/stats
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "service": {
      "cache_ttl": 604800,
      "timeout": 30,
      "retry_times": 3,
      "api_key_configured": true,
      "base_url": "https://www.data.gouv.fr/api/1/"
    },
    "database": {
      "cached_datasets": 45,
      "cached_budgets": 120,
      "communes_with_budget": 30,
      "annees_disponibles": [2022, 2023, 2024],
      "last_fetch": "2025-10-30T14:30:00Z"
    }
  }
}
```

---

### 6. Invalider le Cache (Admin)

**DELETE** `/api/datagouv/cache/commune/{codeInsee}`

Supprime le cache pour une commune spécifique.

**Authentification:** Requiert le rôle `admin`

**Exemple:**
```bash
curl -X DELETE http://localhost:7777/api/datagouv/cache/commune/75056 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🎨 Composant Vue

### Utilisation du composant `BudgetContext`

```vue
<template>
  <div>
    <h2>Nouveau Projet: Piste Cyclable</h2>
    
    <!-- Affiche le contexte budgétaire -->
    <BudgetContext
      code-insee="75056"
      :montant="150000"
      categorie="transport"
      @loaded="onContextLoaded"
      @error="onContextError"
    />
  </div>
</template>

<script setup>
import BudgetContext from '@/Components/BudgetContext.vue';

const onContextLoaded = (context) => {
  console.log('Contexte chargé:', context);
};

const onContextError = (error) => {
  console.error('Erreur:', error);
};
</script>
```

### Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `codeInsee` | String | ✅ | Code INSEE de la commune (5 chiffres) |
| `montant` | Number | ✅ | Montant du projet en euros |
| `categorie` | String | ❌ | Catégorie du projet |
| `autoLoad` | Boolean | ❌ | Charger automatiquement (défaut: `true`) |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `loaded` | `context` | Émis quand le contexte est chargé |
| `error` | `message` | Émis en cas d'erreur |

### Méthodes Exposées

```vue
<script setup>
import { ref } from 'vue';

const budgetContextRef = ref(null);

// Recharger manuellement
const reload = () => {
  budgetContextRef.value.reload();
};
</script>

<template>
  <BudgetContext
    ref="budgetContextRef"
    :auto-load="false"
    ...
  />
  <button @click="reload">Recharger</button>
</template>
```

---

## 🔧 Commandes Artisan

### Import de Budgets

```bash
# Importer des communes spécifiques
php artisan datagouv:import-budgets 75056 13055 69123 --year=2024

# Importer les N plus grandes communes
php artisan datagouv:import-budgets --top=50 --year=2024

# Forcer la réimportation
php artisan datagouv:import-budgets 75056 --force

# Import interactif
php artisan datagouv:import-budgets
```

### Synchronisation Quotidienne

```bash
# Synchroniser toutes les données
php artisan datagouv:sync

# Synchroniser seulement les budgets
php artisan datagouv:sync --type=budgets

# Forcer la synchronisation
php artisan datagouv:sync --force
```

**Planification dans `app/Console/Kernel.php` :**

```php
protected function schedule(Schedule $schedule)
{
    // Synchroniser les budgets tous les jours à 3h du matin
    $schedule->command('datagouv:sync --type=budgets')
        ->dailyAt('03:00')
        ->onOneServer();
}
```

---

## 💾 Cache & Performance

### Stratégie de Cache

L'intégration utilise **deux niveaux de cache** :

1. **Redis** : Cache court terme (requêtes API)
   - TTL: 7 jours par défaut
   - Clés: `datagouv:{type}:{identifier}`

2. **PostgreSQL** : Cache long terme (données structurées)
   - Table: `commune_budgets`
   - Indexé par `code_insee` et `annee`

### Invalidation du Cache

**Manuellement (API):**
```bash
curl -X DELETE http://localhost:7777/api/datagouv/cache/commune/75056
```

**Par code:**
```php
use App\Services\DataGouvService;

$service = app(DataGouvService::class);
$service->invalidateCache('dataset', 'balances-comptables-des-communes');
```

**Tout le cache:**
```php
Cache::tags(['datagouv'])->flush();
```

---

## 📊 Cas d'Usage

### 1. Afficher le Contexte sur une Page Projet

```vue
<template>
  <div class="projet-detail">
    <h1>{{ projet.titre }}</h1>
    <p>Montant: {{ projet.montant }}€</p>
    
    <!-- Contexte budgétaire -->
    <BudgetContext
      :code-insee="projet.commune.code_insee"
      :montant="projet.montant"
      :categorie="projet.categorie"
    />
    
    <button @click="vote">Voter pour ce projet</button>
  </div>
</template>
```

### 2. Validation Côté Serveur

```php
// Dans un Controller
public function store(Request $request)
{
    $validated = $request->validate([
        'titre' => 'required|string',
        'montant' => 'required|numeric|min:1000|max:10000000',
        'code_insee' => 'required|string|size:5',
    ]);

    // Vérifier que le budget de la commune est disponible
    $budget = app(BudgetTerritorialService::class)
        ->getCommuneBudget($validated['code_insee']);

    if (!$budget) {
        return response()->json([
            'error' => 'Budget communal non disponible',
        ], 400);
    }

    // Calculer le contexte
    $context = app(BudgetTerritorialService::class)
        ->getProjectContext($validated['code_insee'], $validated['montant']);

    // Sauvegarder le projet avec le contexte
    $projet = Project::create([
        ...$validated,
        'contexte_budgetaire' => $context,
    ]);

    return response()->json($projet, 201);
}
```

### 3. Comparaison de Communes

```vue
<template>
  <div class="comparaison-communes">
    <h2>Comparer les Budgets</h2>
    
    <select v-model="selectedCommunes" multiple>
      <option value="75056">Paris</option>
      <option value="13055">Marseille</option>
      <option value="69123">Lyon</option>
    </select>
    
    <button @click="comparer">Comparer</button>
    
    <div v-if="comparison" class="resultats">
      <table>
        <thead>
          <tr>
            <th>Commune</th>
            <th>Population</th>
            <th>Budget Total</th>
            <th>€/habitant</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(data, code) in comparison.communes" :key="code">
            <td>{{ data.nom }}</td>
            <td>{{ data.population.toLocaleString() }}</td>
            <td>{{ formatMontant(data.budget_total) }}</td>
            <td>{{ data.depenses_par_habitant }}€</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const selectedCommunes = ref(['75056', '13055']);
const comparison = ref(null);

const comparer = async () => {
  const params = new URLSearchParams();
  selectedCommunes.value.forEach(code => params.append('codes[]', code));
  
  const response = await axios.get(`/api/datagouv/communes/compare?${params}`);
  comparison.value = response.data.data;
};

const formatMontant = (centimes) => {
  const euros = centimes / 100;
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(euros);
};
</script>
```

---

## 🚨 Gestion des Erreurs

### Erreurs API

| Code | Message | Cause | Solution |
|------|---------|-------|----------|
| 400 | Code INSEE invalide | Format incorrect | Utiliser 5 chiffres |
| 404 | Budget non trouvé | Commune ou année inexistante | Vérifier le code INSEE |
| 422 | Validation échouée | Paramètres manquants | Vérifier les params requis |
| 500 | Erreur serveur | data.gouv.fr indisponible | Réessayer plus tard |

### Logs

Les erreurs sont automatiquement loguées :

```php
// Voir les logs
tail -f storage/logs/laravel.log | grep datagouv
```

---

## 📈 Monitoring

### Métriques à Surveiller

```bash
# Statistiques du service
curl http://localhost:7777/api/datagouv/stats

# Nombre de budgets en cache
psql civicdash -c "SELECT COUNT(*) FROM commune_budgets;"

# Communes avec données récentes (< 30 jours)
psql civicdash -c "SELECT COUNT(*) FROM commune_budgets WHERE fetched_at > NOW() - INTERVAL '30 days';"

# Taille du cache Redis
redis-cli INFO keyspace | grep datagouv
```

---

## 🔐 Sécurité & Limites

### Rate Limiting

- **Sans API Key** : 1000 requêtes/jour
- **Avec API Key** : 10 000 requêtes/jour

### Bonnes Pratiques

✅ **À faire:**
- Utiliser une API Key en production
- Mettre en cache les données (7 jours recommandé)
- Planifier les imports la nuit
- Monitorer les erreurs

❌ **À éviter:**
- Importer toutes les communes (36 000+)
- Requêter l'API en temps réel sans cache
- Ignorer les erreurs 429 (Too Many Requests)

---

## 🆘 Support & Ressources

### Documentation Officielle

- **data.gouv.fr API** : https://www.data.gouv.fr/fr/apidoc/
- **Guides** : https://guides.data.gouv.fr/
- **Forum** : https://forum.data.gouv.fr/

### Contact

- **Email** : contact@data.gouv.fr
- **Discord CivicDash** : https://discord.gg/jeGaDZcXP5

---

## 🎉 Prochaines Étapes

Maintenant que l'intégration budgétaire est en place, vous pouvez :

1. ✅ Importer les budgets de vos communes cibles
2. ✅ Ajouter le composant `BudgetContext` sur vos pages de projets
3. ✅ Planifier la synchronisation quotidienne
4. 🔜 Implémenter d'autres sources (élections, propositions de loi)
5. 🔜 Créer des visualisations avancées (graphiques, comparaisons)

**Bon développement ! 🚀**

