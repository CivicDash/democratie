# 💾 Documentation Cache Redis - CivicDash

## 📖 Vue d'ensemble

CivicDash utilise **Redis** comme système de cache pour optimiser les performances et réduire la charge sur la base de données PostgreSQL.

### Pourquoi le cache ?

- ⚡ **Performance** : Réponses 10-100x plus rapides
- 💰 **Économie** : Moins de requêtes SQL coûteuses
- 🔒 **Sécurité** : Limite les requêtes sur données sensibles (votes)
- 📈 **Scalabilité** : Support de milliers d'utilisateurs simultanés

## 🏗️ Architecture

```
User Request
     ↓
Controller
     ↓
Service → CacheService ──→ Redis (cache hit ✓)
     ↓                           ↑
   Database ────────────────────┘
   (si cache miss)          (mise en cache)
```

## 🎨 CacheService

Service centralisé pour gérer tout le cache de l'application.

**Fichier** : `app/Services/CacheService.php`

### Durées de cache

```php
const CACHE_FOREVER = 0;        // Permanent
const CACHE_1_HOUR = 3600;      // 1 heure
const CACHE_4_HOURS = 14400;    // 4 heures
const CACHE_1_DAY = 86400;      // 1 jour
const CACHE_1_WEEK = 604800;    // 1 semaine
```

### Préfixes de clés

```php
const PREFIX_VOTE_RESULTS = 'vote:results:';
const PREFIX_BUDGET_STATS = 'budget:stats';
const PREFIX_BUDGET_AVERAGES = 'budget:averages';
const PREFIX_BUDGET_RANKING = 'budget:ranking';
const PREFIX_MODERATION_STATS = 'moderation:stats';
const PREFIX_DOCUMENT_STATS = 'documents:stats';
const PREFIX_TOPIC_STATS = 'topic:stats:';
const PREFIX_USER_ALLOCATIONS = 'user:allocations:';
```

## 🗳️ Cache des Résultats de Vote

### Pourquoi cacher les résultats ?

- Déchiffrement des votes coûteux (cryptographie)
- Résultats consultés fréquemment
- Calculs lourds (agrégation, pourcentages)

### Implémentation

```php
// Dans BallotService.php
public function calculateResults(Topic $topic): array
{
    // Vérifier le cache d'abord
    $cached = $this->cacheService->getVoteResults($topic->id);
    if ($cached !== null) {
        return $cached;  // Cache hit ✓
    }

    // Calculer les résultats (coûteux)
    $results = $this->performExpensiveCalculation($topic);

    // Mettre en cache pour 1 heure
    $this->cacheService->cacheVoteResults($topic->id, $results);

    return $results;
}
```

### Invalidation

Le cache est invalidé automatiquement quand un nouveau vote est émis :

```php
public function castVote(string $token, array $vote): TopicBallot
{
    // ... voter ...

    // Invalider le cache pour recalculer les résultats
    $this->cacheService->invalidateVoteResults($topic->id);

    return $ballot;
}
```

**Durée** : 1 heure (actualisation automatique)

## 💰 Cache du Budget Participatif

### Données cachées

1. **Statistiques globales** (4 heures)
   - Nombre de participants
   - Date dernière mise à jour
   - Total des allocations

2. **Allocations moyennes** (4 heures)
   - Moyenne par secteur
   - Utilisé pour comparaisons

3. **Classement des secteurs** (4 heures)
   - Secteurs prioritaires
   - Classement avec podium

4. **Allocations utilisateur** (1 jour)
   - Allocations personnelles
   - Cache par utilisateur

### Exemple d'utilisation

```php
// BudgetService.php
public function getAverageAllocations(): array
{
    // Vérifier le cache
    $cached = $this->cacheService->getBudgetAverages();
    if ($cached !== null) {
        return $cached;
    }

    // Calculer (requête SQL complexe)
    $averages = $this->calculateAverages();

    // Mettre en cache
    $this->cacheService->cacheBudgetAverages($averages);

    return $averages;
}
```

### Invalidation

Invalidation automatique quand un utilisateur change son allocation :

```php
public function bulkAllocate(User $user, array $allocations): void
{
    // ... sauvegarder ...

    // Invalider tout le cache budget
    $this->cacheService->invalidateBudgetCache();
}
```

## 🚨 Cache de Modération

### Données cachées

- **Statistiques modération** (1 heure)
  - Signalements en attente
  - En investigation
  - Résolus aujourd'hui
  - Modérateurs actifs

### Pourquoi 1 heure ?

- Données sensibles qui changent fréquemment
- Besoin de voir les nouveaux signalements rapidement
- Balance entre performance et actualité

## 📄 Cache des Documents

### Données cachées

- **Statistiques documents** (4 heures)
  - Total documents
  - Documents vérifiés
  - En attente de vérification
  - Nombre de vérificateurs

## 📝 Cache des Topics

### Données cachées

- **Statistiques par topic** (1 heure)
  - Nombre de posts
  - Nombre de votes
  - Activité récente

## 🔧 Méthodes du CacheService

### Méthodes générales

```php
// Obtenir ou calculer
$value = $cacheService->remember($key, $ttl, function() {
    return $this->expensiveCalculation();
});

// Obtenir
$value = $cacheService->get($key, $default);

// Stocker
$cacheService->put($key, $value, $ttl);

// Supprimer
$cacheService->forget($key);

// Supprimer par pattern
$count = $cacheService->forgetPattern('vote:*');

// Vérifier existence
if ($cacheService->has($key)) { ... }

// Incrémenter/Décrémenter
$cacheService->increment('counter');
$cacheService->decrement('counter');
```

### Méthodes spécifiques Vote

```php
// Cache
$cacheService->cacheVoteResults($topicId, $results);

// Récupérer
$results = $cacheService->getVoteResults($topicId);

// Invalider
$cacheService->invalidateVoteResults($topicId);
```

### Méthodes spécifiques Budget

```php
// Cache stats
$cacheService->cacheBudgetStats($stats);
$cacheService->cacheBudgetAverages($averages);
$cacheService->cacheBudgetRanking($ranking);

// Récupérer
$stats = $cacheService->getBudgetStats();
$averages = $cacheService->getBudgetAverages();
$ranking = $cacheService->getBudgetRanking();

// Invalider tout
$count = $cacheService->invalidateBudgetCache();
```

### Méthodes spécifiques User

```php
// Cache allocations utilisateur
$cacheService->cacheUserAllocations($userId, $allocations);

// Récupérer
$allocations = $cacheService->getUserAllocations($userId);

// Invalider
$cacheService->invalidateUserAllocations($userId);
```

## 🎮 Commandes Artisan

### Vider le cache de vote

```bash
# Vider tout le cache de vote
php artisan cache:clear-vote

# Vider le cache d'un topic spécifique
php artisan cache:clear-vote 42
```

### Vider le cache budget

```bash
php artisan cache:clear-budget
```

**Vide** :
- Stats budget
- Allocations moyennes
- Ranking des secteurs
- Allocations utilisateurs

### Vider TOUT le cache CivicDash

```bash
# Avec confirmation
php artisan cache:clear-civicdash

# Sans confirmation (scripts automatisés)
php artisan cache:clear-civicdash --force
```

**Vide** :
- ✓ Vote
- ✓ Budget
- ✓ Modération
- ✓ Documents
- ✓ Topics

## 📊 Exemple Complet : Workflow de Vote

### 1. Utilisateur demande les résultats

```
GET /api/topics/1/vote/results
     ↓
VoteController@results
     ↓
BallotService->calculateResults($topic)
     ↓
CacheService->getVoteResults(1)  ← Cache hit ✓ (si existe)
     ↓
[Retour immédiat des résultats cachés]
```

### 2. Cache miss : Calcul des résultats

```
BallotService->calculateResults($topic)
     ↓
CacheService->getVoteResults(1)  ← Cache miss ✗
     ↓
[Déchiffrement de tous les votes]
[Agrégation des résultats]
[Calcul des pourcentages]
     ↓
CacheService->cacheVoteResults(1, $results)  ← Mise en cache
     ↓
[Retour des résultats + cache pour 1h]
```

### 3. Nouveau vote émis

```
POST /api/topics/1/vote/cast
     ↓
BallotService->castVote($token, $vote)
     ↓
[Création du ballot anonyme]
     ↓
CacheService->invalidateVoteResults(1)  ← Invalidation
     ↓
[Prochain appel recalculera les résultats]
```

## 🔥 Performance

### Avant le cache

```
GET /api/topics/1/vote/results
- Déchiffrement de 1000 votes : 2500ms
- Agrégation SQL : 150ms
- Calcul pourcentages : 50ms
────────────────────────────────
TOTAL : ~2700ms (2.7 secondes) ❌
```

### Après le cache

```
GET /api/topics/1/vote/results
- Redis GET : 5ms
────────────────────────────────
TOTAL : ~5ms (0.005 seconde) ✅

Amélioration : 540x plus rapide ! 🚀
```

## 🛠️ Configuration

### .env

```env
# Cache driver (Redis)
CACHE_DRIVER=redis

# Redis configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1  # Base dédiée au cache
```

### config/cache.php

```php
'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
    'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
],
```

### config/database.php

```php
'redis' => [
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

## 🔍 Monitoring

### Vérifier les clés en cache

```bash
# Se connecter à Redis
docker exec -it demoscratos-redis-1 redis-cli

# Lister les clés de vote
KEYS "*vote:results:*"

# Voir une clé spécifique
GET "laravel_cache:vote:results:1"

# Voir TTL (temps restant)
TTL "laravel_cache:vote:results:1"

# Nombre total de clés
DBSIZE

# Vider toute la base (ATTENTION)
FLUSHDB
```

### Stats Redis

```bash
# Informations Redis
INFO stats

# Taux de cache hit
INFO stats | grep hits
INFO stats | grep misses
```

## 📈 Stratégie de Cache

### Quand cacher ?

✅ **À cacher** :
- Résultats de vote (déchiffrement coûteux)
- Stats aggregées (calculs complexes)
- Données consultées fréquemment
- Résultats de requêtes lentes (> 100ms)

❌ **À ne PAS cacher** :
- Données en temps réel critiques
- Données personnelles sensibles
- Flux d'activité en direct
- Données changeant constamment

### Durées recommandées

| Donnée | Durée | Raison |
|--------|-------|--------|
| Résultats vote | 1h | Balance actualité/performance |
| Stats budget | 4h | Change rarement |
| Allocations user | 1 jour | Données personnelles stables |
| Stats modération | 1h | Données sensibles |
| Stats documents | 4h | Change rarement |

## 🚀 Bonnes Pratiques

1. **Préfixes de clés** : Toujours utiliser des préfixes clairs
2. **TTL approprié** : Adapter selon la fréquence de changement
3. **Invalidation** : Invalider immédiatement après modification
4. **Fallback** : Toujours avoir un fallback si cache indisponible
5. **Monitoring** : Surveiller le taux de cache hit/miss
6. **Pattern** : Utiliser `forgetPattern()` pour invalidations groupées

## ⚠️ Attention

### Cache et Anonymat

Le cache des résultats de vote **ne contient JAMAIS** :
- ❌ user_id
- ❌ token
- ❌ Lien vote → utilisateur

Il contient **uniquement** les résultats aggregés :
- ✅ Total votes
- ✅ Résultats par choix
- ✅ Pourcentages

### Sécurité

- Cache Redis doit être **protégé** (firewall)
- Pas accessible de l'extérieur
- Mot de passe Redis en production
- TTL pour auto-expiration

## 🎯 Prochaines Étapes

1. **Cache tags** : Grouper les clés pour invalidation
2. **Cache warming** : Pré-chauffer les données critiques
3. **Cache aside** : Pattern pour haute disponibilité
4. **CDN** : Cacher les assets statiques
5. **Query cache** : Cacher les requêtes SQL

---

💙 CivicDash - Cache Redis pour Performance Optimale

