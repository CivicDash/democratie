# Routes API CivicDash

Documentation complète des **Routes API** de CivicDash.

## 📁 Fichier de routes

```
routes/api.php
```

Toutes les routes sont préfixées par `/api` et retournent du JSON.

---

## 🌐 Routes Publiques (sans authentification)

### Topics

| Méthode | Route | Controller | Description |
|---------|-------|------------|-------------|
| GET | `/api/topics` | TopicController@index | Liste des topics |
| GET | `/api/topics/trending` | TopicController@trending | Topics populaires |
| GET | `/api/topics/{topic}` | TopicController@show | Détails d'un topic |
| GET | `/api/topics/{topic}/stats` | TopicController@stats | Statistiques |

### Posts

| Méthode | Route | Controller | Description |
|---------|-------|------------|-------------|
| GET | `/api/topics/{topic}/posts` | PostController@index | Posts d'un topic |
| GET | `/api/topics/{topic}/posts/top` | PostController@top | Meilleurs posts |
| GET | `/api/posts/{post}` | PostController@show | Détails d'un post |
| GET | `/api/posts/{post}/replies` | PostController@replies | Réponses |

### Vote

| Méthode | Route | Controller | Description |
|---------|-------|------------|-------------|
| GET | `/api/topics/{topic}/vote/results` | VoteController@results | Résultats (après deadline) |
| GET | `/api/topics/{topic}/vote/count` | VoteController@count | Nombre de votes |

### Budget

| Méthode | Route | Controller | Description |
|---------|-------|------------|-------------|
| GET | `/api/budget/sectors` | BudgetController@sectors | Liste des secteurs |
| GET | `/api/budget/averages` | BudgetController@averages | Moyennes citoyennes |
| GET | `/api/budget/ranking` | BudgetController@ranking | Classement secteurs |
| GET | `/api/budget/stats` | BudgetController@stats | Statistiques |
| POST | `/api/budget/simulate` | BudgetController@simulate | Simuler budget |
| POST | `/api/budget/compare` | BudgetController@compare | Comparer réel |

### Documents

| Méthode | Route | Controller | Description |
|---------|-------|------------|-------------|
| GET | `/api/documents` | DocumentController@index | Liste documents |
| GET | `/api/documents/{document}` | DocumentController@show | Détails |
| GET | `/api/documents/{document}/verifications` | DocumentController@verifications | Historique vérif |
| GET | `/api/documents/{document}/download` | DocumentController@download | Télécharger |
| GET | `/api/documents/stats` | DocumentController@stats | Statistiques |
| GET | `/api/documents/top-verifiers` | DocumentController@topVerifiers | Top vérificateurs |

**Total routes publiques : 22**

---

## 🔐 Routes Authentifiées (auth:sanctum)

### Topics

| Méthode | Route | Controller | Auth | Description |
|---------|-------|------------|------|-------------|
| POST | `/api/topics` | TopicController@store | Auth | Créer topic |
| PUT | `/api/topics/{topic}` | TopicController@update | Auth | Mettre à jour |
| DELETE | `/api/topics/{topic}` | TopicController@destroy | Auth | Supprimer |
| POST | `/api/topics/{topic}/close` | TopicController@close | Auth | Fermer |
| POST | `/api/topics/{topic}/archive` | TopicController@archive | Auth | Archiver |
| POST | `/api/topics/{topic}/ballot` | TopicController@createBallot | Auth | Créer scrutin |

### Posts

| Méthode | Route | Controller | Auth | Description |
|---------|-------|------------|------|-------------|
| POST | `/api/topics/{topic}/posts` | PostController@store | Auth | Créer post |
| PUT | `/api/posts/{post}` | PostController@update | Auth | Mettre à jour |
| DELETE | `/api/posts/{post}` | PostController@destroy | Auth | Supprimer |
| POST | `/api/posts/{post}/vote` | PostController@vote | Auth | Voter |

### Vote Anonyme

| Méthode | Route | Controller | Auth | Description |
|---------|-------|------------|------|-------------|
| POST | `/api/topics/{topic}/vote/token` | VoteController@requestToken | Auth | Demander token |
| POST | `/api/topics/{topic}/vote/cast` | VoteController@castVote | Auth | Voter (anonyme) |
| GET | `/api/topics/{topic}/vote/has-voted` | VoteController@hasVoted | Auth | A voté ? |
| GET | `/api/topics/{topic}/vote/integrity` | VoteController@verifyIntegrity | Admin | Vérifier intégrité |
| GET | `/api/topics/{topic}/vote/export` | VoteController@export | Admin/State | Exporter |

### Budget

| Méthode | Route | Controller | Auth | Description |
|---------|-------|------------|------|-------------|
| GET | `/api/budget/allocations` | BudgetController@index | Auth | Mes allocations |
| POST | `/api/budget/allocate` | BudgetController@allocate | Auth | Allouer |
| POST | `/api/budget/bulk-allocate` | BudgetController@bulkAllocate | Auth | Allocation complète |
| DELETE | `/api/budget/reset` | BudgetController@reset | Auth | Réinitialiser |
| GET | `/api/budget/export` | BudgetController@export | Admin/State | Exporter données |

### Modération

| Méthode | Route | Controller | Auth | Description |
|---------|-------|------------|------|-------------|
| POST | `/api/moderation/reports` | ModerationController@storeReport | Auth | Créer signalement |
| GET | `/api/moderation/reports` | ModerationController@reports | Moderator | Liste rapports |
| GET | `/api/moderation/reports/priority` | ModerationController@priorityReports | Moderator | Prioritaires |
| POST | `/api/moderation/reports/{report}/assign` | ModerationController@assignReport | Moderator | S'assigner |
| POST | `/api/moderation/reports/{report}/resolve` | ModerationController@resolveReport | Moderator | Résoudre |
| POST | `/api/moderation/reports/{report}/reject` | ModerationController@rejectReport | Moderator | Rejeter |
| GET | `/api/moderation/users/{user}/sanctions` | ModerationController@userSanctions | Moderator | Sanctions user |
| POST | `/api/moderation/users/{user}/sanctions` | ModerationController@storeSanction | Moderator | Sanctionner |
| DELETE | `/api/moderation/sanctions/{sanction}` | ModerationController@revokeSanction | Moderator | Révoquer |
| GET | `/api/moderation/stats` | ModerationController@stats | Moderator | Statistiques |
| GET | `/api/moderation/top-moderators` | ModerationController@topModerators | Moderator | Top modos |

### Documents

| Méthode | Route | Controller | Auth | Description |
|---------|-------|------------|------|-------------|
| POST | `/api/documents` | DocumentController@store | Auth | Upload |
| PUT | `/api/documents/{document}` | DocumentController@update | Auth | Update desc |
| DELETE | `/api/documents/{document}` | DocumentController@destroy | Auth | Supprimer |
| POST | `/api/documents/{document}/verify` | DocumentController@verify | Journalist/ONG | Vérifier |
| GET | `/api/documents/pending` | DocumentController@pending | Journalist/ONG | En attente |

**Total routes authentifiées : 36**

---

## 📊 Récapitulatif

| Catégorie | Routes Publiques | Routes Auth | Total |
|-----------|------------------|-------------|-------|
| **Topics** | 4 | 6 | 10 |
| **Posts** | 4 | 4 | 8 |
| **Vote** | 2 | 5 | 7 |
| **Budget** | 6 | 5 | 11 |
| **Modération** | 0 | 11 | 11 |
| **Documents** | 6 | 5 | 11 |
| **TOTAL** | **22** | **36** | **58** |

---

## 🔒 Middleware et Protections

### Authentification

```php
Route::middleware('auth:sanctum')->group(function () {
    // Routes authentifiées
});
```

### Rôles

```php
// Admin uniquement
Route::middleware('role:admin')->group(function () {
    Route::get('/topics/{topic}/vote/integrity', ...);
});

// Admin OU State
Route::middleware('role:admin|state')->group(function () {
    Route::get('/topics/{topic}/vote/export', ...);
    Route::get('/budget/export', ...);
});

// Moderator OU Admin
Route::middleware('role:moderator|admin')->group(function () {
    // Routes de modération
});

// Journalist OU ONG OU Admin
Route::middleware('role:journalist|ong|admin')->group(function () {
    Route::post('/documents/{document}/verify', ...);
});
```

---

## 🎯 Exemples d'utilisation

### Créer un topic

```bash
curl -X POST https://civicdash.fr/api/topics \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Débat sur la transition énergétique",
    "description": "Discussion sur les mesures à prendre...",
    "type": "debate",
    "scope": "national"
  }'
```

### Workflow de vote anonyme

```bash
# 1. Demander un token
curl -X POST https://civicdash.fr/api/topics/1/vote/token \
  -H "Authorization: Bearer {token}"

# Response
{
  "token": "abc123...",
  "expires_at": "2025-01-30T23:59:59Z"
}

# 2. Voter (anonyme)
curl -X POST https://civicdash.fr/api/topics/1/vote/cast \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "abc123...",
    "vote": {
      "choice": "yes"
    }
  }'

# 3. Voir les résultats (après deadline)
curl https://civicdash.fr/api/topics/1/vote/results
```

### Allocation budget complète

```bash
curl -X POST https://civicdash.fr/api/budget/bulk-allocate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "allocations": {
      "1": 30.0,
      "2": 25.0,
      "3": 20.0,
      "4": 15.0,
      "5": 10.0
    }
  }'
```

---

## 🚫 Route Fallback

Si une route n'existe pas :

```json
{
  "message": "Endpoint introuvable. Vérifiez l'URL et la méthode HTTP."
}
```

Status : `404`

---

## 🔧 Configuration

### Rate Limiting

Par défaut, Laravel applique un rate limiting sur les routes API :
- **60 requêtes par minute** par utilisateur authentifié
- **60 requêtes par minute** par IP pour les utilisateurs non authentifiés

Pour modifier, éditer `bootstrap/app.php` :

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('60,1');  // 60 req/min
})
```

### CORS

Pour permettre les requêtes depuis le frontend, configurer dans `config/cors.php` :

```php
'paths' => ['api/*'],
'allowed_origins' => ['https://civicdash.fr'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

---

## 🧪 Tests des Routes

### Avec Pest

```php
test('can list topics', function () {
    Topic::factory()->count(5)->create();
    
    getJson('/api/topics')
        ->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

test('can create topic when authenticated', function () {
    $user = User::factory()->citizen()->create();
    
    actingAs($user)
        ->postJson('/api/topics', [
            'title' => 'Test topic',
            'description' => 'A long description...',
            'type' => 'debate',
            'scope' => 'national',
        ])
        ->assertStatus(201)
        ->assertJsonStructure(['message', 'topic']);
});

test('cannot create topic when unauthenticated', function () {
    postJson('/api/topics', [
        'title' => 'Test topic',
        'description' => 'A long description...',
        'type' => 'debate',
        'scope' => 'national',
    ])
    ->assertStatus(401);
});
```

### Avec curl

```bash
# Lister les routes
php artisan route:list --path=api

# Tester une route
curl -X GET https://civicdash.fr/api/topics \
  -H "Accept: application/json"
```

---

## 📖 Documentation Interactive

### Génération avec Scribe

Pour générer une documentation API interactive :

```bash
composer require --dev knuckleswtf/scribe

php artisan scribe:generate
```

Cela créera une documentation Postman/OpenAPI accessible à `/docs`.

---

## 🔗 Liens utiles

- [Controllers](../docs/CONTROLLERS.md) - Documentation des controllers
- [Form Requests](../docs/FORM_REQUESTS.md) - Validation
- [Policies](../docs/POLICIES.md) - Autorisations
- [Services](../docs/SERVICES.md) - Logique métier
- [Laravel Routing](https://laravel.com/docs/11.x/routing)
- [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)

