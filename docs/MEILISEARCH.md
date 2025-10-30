# 🔍 RECHERCHE MEILISEARCH - Documentation

## 📋 Vue d'ensemble

CivicDash intègre **Meilisearch**, un moteur de recherche full-text ultra-rapide (< 50ms), pour permettre aux citoyens de trouver facilement des topics, posts et documents.

### ✨ Fonctionnalités

- ⚡ **Recherche ultra-rapide** : < 50ms même sur 100k documents
- 🔤 **Typo-tolerant** : Corrige automatiquement les fautes de frappe (1-2 caractères)
- 💡 **Autocomplete intelligent** : Suggestions en temps réel avec debounce
- 🎯 **Filtres avancés** : Par type, scope, région, etc.
- 📊 **Highlighting** : Met en évidence les mots recherchés
- 🇫🇷 **Support français** : Stop words et stemming français

---

## 🏗️ Architecture

### Backend

```
app/
├── Models/
│   ├── Topic.php         (Searchable trait)
│   ├── Post.php          (Searchable trait)
│   └── Document.php      (Searchable trait)
├── Http/Controllers/Api/
│   └── SearchController.php  (3 endpoints)
└── Console/Commands/
    ├── ImportSearchDataCommand.php
    └── SearchStatsCommand.php
```

### Frontend

```
resources/js/
├── Components/
│   └── SearchBar.vue      (Autocomplete)
└── Pages/Search/
    └── Results.vue        (Page de résultats)
```

### Index Meilisearch

- **topics_index** : Sujets de débat, propositions, scrutins
- **posts_index** : Messages de forum
- **documents_index** : Documents publics vérifiés

---

## 🚀 Installation & Configuration

### 1. Vérifier Meilisearch dans Docker

Meilisearch est déjà configuré dans `docker-compose.yml` :

```yaml
meilisearch:
  image: getmeili/meilisearch:v1.5
  ports:
    - "7700:7700"
  environment:
    MEILI_ENV: development
  volumes:
    - meilisearch_data:/meili_data
```

Démarrer le service :

```bash
docker-compose up -d meilisearch
```

### 2. Configurer Laravel Scout

Le fichier `config/scout.php` est déjà configuré. Vérifier le `.env` :

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
```

### 3. Importer les données

```bash
# Import complet
docker exec civicdash-app php artisan search:import

# Réinitialiser et réimporter
docker exec civicdash-app php artisan search:import --fresh

# Importer un model spécifique
docker exec civicdash-app php artisan search:import --model=Topic
docker exec civicdash-app php artisan search:import --model=Post
docker exec civicdash-app php artisan search:import --model=Document
```

### 4. Vérifier les statistiques

```bash
docker exec civicdash-app php artisan search:stats
```

Sortie :

```
📊 Statistiques Meilisearch

+-------------+--------+-----------+-------------+
| Model       | Total  | Indexable | Pourcentage |
+-------------+--------+-----------+-------------+
| Topics      | 152    | 145       | 95.4%       |
| Posts       | 1248   | 1201      | 96.2%       |
| Documents   | 34     | 28        | 82.4%       |
| TOTAL       | 1434   | 1374      | -           |
+-------------+--------+-----------+-------------+
```

---

## 📡 API Endpoints

### 1. Recherche globale

```http
GET /api/search
```

**Paramètres :**

| Paramètre    | Type   | Description                              | Exemple     |
|-------------|--------|------------------------------------------|-------------|
| `q`         | string | Requête de recherche (obligatoire)       | `cyclable`  |
| `type`      | string | Type de résultat (`topics`, `posts`, `documents`, `all`) | `topics` |
| `limit`     | int    | Nombre max de résultats par catégorie   | `20`        |
| `scope`     | string | Portée (`national`, `region`, `dept`)    | `region`    |
| `type_topic`| string | Type de topic (`question`, `proposal`, `debate`, `announcement`) | `proposal` |
| `region_id` | int    | ID de la région                          | `5`         |

**Exemple :**

```bash
curl "http://localhost:7777/api/search?q=piste+cyclable&type=topics&scope=region&limit=10"
```

**Réponse :**

```json
{
  "success": true,
  "query": "piste cyclable",
  "type": "topics",
  "results": {
    "topics": [
      {
        "id": 1,
        "title": "Créer des pistes cyclables sécurisées",
        "description": "Pour améliorer la mobilité douce dans notre région...",
        "type": "proposal",
        "scope": "region",
        "author": "Jean Dupont",
        "created_at": "il y a 2 jours",
        "url": "/topics/1"
      }
    ]
  },
  "total": 5,
  "took_ms": 23.45
}
```

### 2. Autocomplete

```http
GET /api/search/autocomplete
```

**Paramètres :**

| Paramètre | Type   | Description                        | Exemple |
|----------|--------|-------------------------------------|---------|
| `q`      | string | Requête (obligatoire, min 2 chars) | `pist`  |
| `limit`  | int    | Nombre max de suggestions          | `5`     |

**Exemple :**

```bash
curl "http://localhost:7777/api/search/autocomplete?q=pist&limit=5"
```

**Réponse :**

```json
{
  "success": true,
  "query": "pist",
  "suggestions": [
    {
      "id": 1,
      "title": "Piste cyclable rue de la République",
      "type": "proposal",
      "url": "/topics/1"
    },
    {
      "id": 5,
      "title": "Piste pour trottinettes électriques",
      "type": "question",
      "url": "/topics/5"
    }
  ]
}
```

### 3. Statistiques

```http
GET /api/search/stats
```

**Réponse :**

```json
{
  "success": true,
  "stats": {
    "topics_indexed": 145,
    "posts_indexed": 1201,
    "documents_indexed": 28
  },
  "total_indexed": 1374
}
```

---

## 🎨 Composants Frontend

### SearchBar.vue

Composant d'autocomplete réutilisable.

**Props :**

| Prop          | Type    | Default                          | Description                      |
|--------------|---------|----------------------------------|----------------------------------|
| `placeholder`| String  | `"Rechercher des sujets..."`     | Texte du placeholder            |
| `minChars`   | Number  | `2`                              | Nombre min de caractères        |
| `debounceMs` | Number  | `300`                            | Délai de debounce (ms)          |
| `autoFocus`  | Boolean | `false`                          | Focus automatique au montage    |

**Events :**

| Event    | Payload      | Description                          |
|---------|--------------|--------------------------------------|
| `search`| `String`     | Émis lors d'une recherche complète  |
| `select`| `Object`     | Émis lors de la sélection d'une suggestion |

**Exemple d'utilisation :**

```vue
<template>
  <SearchBar
    placeholder="Rechercher des sujets..."
    :min-chars="2"
    :debounce-ms="300"
    @search="onSearch"
    @select="onSelect"
  />
</template>

<script setup>
import SearchBar from '@/Components/SearchBar.vue';

const onSearch = (query) => {
  console.log('Recherche complète:', query);
  // Redirection vers /search?q=...
};

const onSelect = (suggestion) => {
  console.log('Suggestion sélectionnée:', suggestion);
  // Redirection vers suggestion.url
};
</script>
```

**Navigation clavier :**

- ↓ : Descendre dans les suggestions
- ↑ : Monter dans les suggestions
- Enter : Sélectionner la suggestion active ou lancer recherche complète
- Esc : Fermer les suggestions

---

## 🔧 Configuration des modèles

### Trait Searchable

Chaque modèle utilise le trait `Laravel\Scout\Searchable` :

```php
use Laravel\Scout\Searchable;

class Topic extends Model
{
    use Searchable;
    
    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'scope' => $this->scope,
            'status' => $this->status,
            'region_id' => $this->region_id,
            'author_name' => $this->author?->name,
            'created_at' => $this->created_at->timestamp,
        ];
    }
    
    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'topics_index';
    }
    
    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }
}
```

### Indexation automatique

Les modèles sont automatiquement indexés lors de :

- **Création** : `Topic::create(...)` → indexation auto
- **Mise à jour** : `$topic->update(...)` → réindexation auto
- **Suppression** : `$topic->delete()` → suppression de l'index

Pour désactiver temporairement l'indexation :

```php
Topic::withoutSyncingToSearch(function () {
    Topic::create([...]);
});
```

---

## 🎯 Optimisations

### 1. Debounce

L'autocomplete utilise un debounce de 300ms pour éviter trop de requêtes :

```javascript
let debounceTimeout = null;

const onInput = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    fetchSuggestions();
  }, 300);
};
```

### 2. Limit

Les résultats sont limités pour optimiser les performances :

- **Autocomplete** : 5 suggestions max
- **Recherche** : 20 résultats par catégorie

### 3. shouldBeSearchable()

Seuls les éléments "pertinents" sont indexés :

- **Topics** : Uniquement `status = 'published'`
- **Posts** : Uniquement `is_hidden = false` et non soft-deleted
- **Documents** : Uniquement `is_public = true` et `status = 'verified'`

### 4. Import par chunks

L'import se fait par chunks de 100 pour éviter les timeouts :

```php
Topic::chunk(100, function ($items) {
    foreach ($items as $item) {
        $item->searchable();
    }
});
```

---

## 📊 Métriques & KPIs

### Métriques attendues

- 🎯 **> 40% des users** utilisent la recherche
- 🎯 **< 50ms** : Temps de réponse moyen
- 🎯 **> 60%** : Taux de clic sur les résultats
- 🎯 **> 4/5** : Satisfaction utilisateur

### Surveillance

À implémenter (Phase 2 - Analytics) :

- Nombre de recherches / jour
- Top 10 des requêtes
- Taux de clic par position
- Requêtes sans résultats

---

## 🛠️ Maintenance

### Réindexer tous les modèles

```bash
docker exec civicdash-app php artisan search:import --fresh
```

### Réindexer un modèle spécifique

```bash
docker exec civicdash-app php artisan search:import --model=Topic --fresh
```

### Vider un index

```bash
docker exec civicdash-app php artisan scout:flush "App\Models\Topic"
```

### Vérifier la santé de Meilisearch

```bash
curl http://localhost:7700/health
```

Réponse attendue :

```json
{"status": "available"}
```

---

## 🐛 Debugging

### Logs Meilisearch

```bash
docker logs civicdash-meilisearch
```

### Tester manuellement l'API Meilisearch

```bash
# Lister les index
curl http://localhost:7700/indexes

# Rechercher dans topics_index
curl -X POST 'http://localhost:7700/indexes/topics_index/search' \
  -H 'Content-Type: application/json' \
  --data-binary '{ "q": "cyclable" }'
```

### Vérifier qu'un model est bien indexé

```php
php artisan tinker

>>> $topic = Topic::find(1);
>>> $topic->searchable();  // Force réindexation
>>> $topic->unsearchable();  // Supprimer de l'index
```

---

## 🚀 Évolutions futures (Phase 2)

- [ ] **Synonymes** : "vélo" → "bicyclette"
- [ ] **Faceting avancé** : Filtres dynamiques
- [ ] **Geo search** : Recherche par proximité géographique
- [ ] **Analytics** : Dashboard des recherches populaires
- [ ] **A/B testing** : Tester différents algorithmes de ranking
- [ ] **Highlighting avancé** : Snippets contextuels
- [ ] **Recherche vocale** : Intégration Web Speech API
- [ ] **Recherche multi-langue** : Support anglais/espagnol

---

## 📚 Ressources

- [Documentation Meilisearch](https://www.meilisearch.com/docs)
- [Laravel Scout](https://laravel.com/docs/11.x/scout)
- [Meilisearch PHP SDK](https://github.com/meilisearch/meilisearch-php)

---

**Maintenu par** : CivicDash Core Team  
**Version** : 1.0  
**Dernière mise à jour** : 30 octobre 2025

