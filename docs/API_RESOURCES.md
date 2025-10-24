# 📦 Documentation API Resources - CivicDash

## 📖 Vue d'ensemble

Les **API Resources** de Laravel transforment les modèles Eloquent en réponses JSON structurées et cohérentes. Elles permettent de :
- **Formater** les données de manière élégante
- **Contrôler** quelles données sont exposées
- **Cacher** les informations sensibles
- **Inclure** des relations conditionnellement
- **Ajouter** des métadonnées et liens

## 🏗️ Structure

```
app/Http/Resources/
├── UserResource.php              # Utilisateurs
├── ProfileResource.php           # Profils utilisateurs
├── TerritoryRegionResource.php   # Régions
├── TerritoryDepartmentResource.php # Départements
├── TopicResource.php             # Topics
├── TopicCollection.php           # Collection de topics
├── PostResource.php              # Posts
├── PostVoteResource.php          # Votes sur posts
├── BallotResultResource.php      # Résultats de vote
├── SectorResource.php            # Secteurs budgétaires
├── UserAllocationResource.php    # Allocations budget
├── ReportResource.php            # Signalements
├── SanctionResource.php          # Sanctions
├── DocumentResource.php          # Documents
└── VerificationResource.php      # Vérifications
```

## 🎨 Resources Créées

### 👤 UserResource

**Données exposées:**
```json
{
  "id": 1,
  "name": "Jean Dupont",
  "email": "jean@example.com",  // Conditionnel (owner ou admin)
  "roles": ["citizen"],
  "profile": { ProfileResource },
  "created_at": "2025-01-24T12:00:00Z",
  "is_online": true
}
```

**Caractéristiques:**
- Email visible uniquement pour l'utilisateur ou les admins
- Status en ligne (dernière activité < 5 min)
- Relations: `roles`, `profile`

### 👨‍💼 ProfileResource

**Données exposées:**
```json
{
  "id": 1,
  "bio": "Citoyen engagé",
  "avatar_url": "https://...",
  "region": { TerritoryRegionResource },
  "department": { TerritoryDepartmentResource },
  "is_verified": true,
  "verified_at": "2025-01-24T12:00:00Z"
}
```

**Relations:** `region`, `department`

### 🗺️ TerritoryRegionResource

**Données exposées:**
```json
{
  "id": 1,
  "code": "IDF",
  "name": "Île-de-France",
  "departments_count": 8  // Conditionnel
}
```

### 📍 TerritoryDepartmentResource

**Données exposées:**
```json
{
  "id": 75,
  "code": "75",
  "name": "Paris",
  "region": { TerritoryRegionResource }
}
```

### 📝 TopicResource

**Données exposées:**
```json
{
  "id": 1,
  "title": "Faut-il développer les transports ?",
  "description": "...",
  "type": "debate",
  "scope": "national",
  "author": { UserResource },
  "region": { TerritoryRegionResource },
  "department": { TerritoryDepartmentResource },
  "ballot_type": "binary",
  "ballot_options": ["Oui", "Non"],  // Pour vote multiple
  "ballot_ends_at": "2025-02-24T12:00:00Z",
  "is_open": true,
  "is_closed": false,
  "is_archived": false,
  "posts_count": 42,
  "ballots_count": 156,
  "posts": [ PostResource ],  // Si chargé
  "created_at": "2025-01-24T12:00:00Z",
  "updated_at": "2025-01-24T12:00:00Z",
  "links": {
    "self": "/api/topics/1",
    "posts": "/api/topics/1/posts",
    "vote": "/api/topics/1/vote/results"
  }
}
```

**Caractéristiques:**
- Liens HATEOAS vers ressources liées
- Status calculés (is_open, is_closed, etc.)
- Relations: `author`, `region`, `department`, `posts`

### 📚 TopicCollection

**Format de collection paginée:**
```json
{
  "data": [
    { TopicResource },
    { TopicResource },
    ...
  ],
  "meta": {
    "total": 150,
    "per_page": 15,
    "current_page": 1,
    "last_page": 10
  },
  "links": {
    "first": "/api/topics?page=1",
    "last": "/api/topics?page=10",
    "prev": null,
    "next": "/api/topics?page=2"
  }
}
```

### 💬 PostResource

**Données exposées:**
```json
{
  "id": 1,
  "content": "Je pense que...",
  "author": { UserResource },
  "topic_id": 1,
  "topic": { TopicResource },
  "parent_id": null,
  "parent": { PostResource },  // Si réponse
  "replies": [ PostResource ],  // Si chargées
  "replies_count": 5,
  "vote_score": 42,
  "user_vote": "up",  // Pour l'utilisateur connecté
  "is_pinned": false,
  "is_solution": false,
  "created_at": "2025-01-24T12:00:00Z",
  "updated_at": "2025-01-24T12:00:00Z",
  "links": {
    "self": "/api/posts/1",
    "vote": "/api/posts/1/vote"
  }
}
```

**Caractéristiques:**
- Vote de l'utilisateur inclus si connecté
- Support des réponses (parent/replies)
- Score de vote calculé

### 🗳️ BallotResultResource

**Données exposées (vote binaire):**
```json
{
  "topic": { TopicResource },
  "ballot_type": "binary",
  "total_votes": 156,
  "yes": 98,
  "no": 58,
  "percentages": {
    "yes": 62.82,
    "no": 37.18
  },
  "ballot_ends_at": "2025-02-24T12:00:00Z",
  "is_ended": false
}
```

**Données exposées (vote multiple):**
```json
{
  "topic": { TopicResource },
  "ballot_type": "multiple",
  "total_votes": 156,
  "choices": {
    "Option A": 56,
    "Option B": 78,
    "Option C": 22
  },
  "percentages": {
    "Option A": 35.90,
    "Option B": 50.00,
    "Option C": 14.10
  }
}
```

**Caractéristiques:**
- Calcul automatique des pourcentages
- Anonymat préservé (pas de lien user-vote)

### 💰 SectorResource

**Données exposées:**
```json
{
  "id": 1,
  "name": "Santé",
  "description": "Hôpitaux, médecine...",
  "color": "#FF5733",
  "icon": "🏥",
  "average_allocation": 18.5,  // Conditionnel
  "participant_count": 456,     // Conditionnel
  "created_at": "2025-01-24T12:00:00Z"
}
```

### 📊 UserAllocationResource

**Données exposées:**
```json
{
  "id": 1,
  "user_id": 1,
  "sector": { SectorResource },
  "sector_id": 1,
  "percentage": 18.5,
  "difference_from_average": 2.3,  // Conditionnel
  "created_at": "2025-01-24T12:00:00Z",
  "updated_at": "2025-01-24T12:00:00Z"
}
```

### 🚨 ReportResource

**Données exposées:**
```json
{
  "id": 1,
  "reason": "spam",
  "description": "Contenu publicitaire",
  "status": "pending",
  "priority": "high",
  "reporter": { UserResource },  // Visible pour modérateurs
  "assignee": { UserResource },
  "reportable_type": "App\\Models\\Post",
  "reportable_id": 42,
  "reportable": { PostResource },
  "resolution_note": "...",  // Si résolu
  "resolved_at": "2025-01-25T12:00:00Z",
  "created_at": "2025-01-24T12:00:00Z",
  "updated_at": "2025-01-24T12:00:00Z",
  "links": {
    "assign": "/api/moderation/reports/1/assign",
    "resolve": "/api/moderation/reports/1/resolve",
    "reject": "/api/moderation/reports/1/reject"
  }
}
```

**Caractéristiques:**
- Reporter anonyme pour non-modérateurs
- Support polymorphique (Topic, Post, User)
- Liens d'action pour modérateurs

### 🔨 SanctionResource

**Données exposées:**
```json
{
  "id": 1,
  "type": "warning",
  "reason": "Spam répété",
  "user": { UserResource },  // Visible pour modérateurs
  "moderator": { UserResource },
  "starts_at": "2025-01-24T12:00:00Z",
  "ends_at": "2025-01-31T12:00:00Z",
  "is_active": true,
  "is_expired": false,
  "is_revoked": false,
  "revoked_at": null,
  "created_at": "2025-01-24T12:00:00Z",
  "links": {
    "revoke": "/api/moderation/sanctions/1"
  }
}
```

**Caractéristiques:**
- User sanctionné visible pour modérateurs uniquement
- Status calculés (active, expired, revoked)

### 📄 DocumentResource

**Données exposées:**
```json
{
  "id": 1,
  "title": "Loi de finances 2025",
  "document_type": "law",
  "file_path": "documents/...",
  "file_size": 2048576,
  "file_hash": "sha256:...",
  "source_url": "https://...",
  "verification_status": "verified",
  "verified_at": "2025-01-25T12:00:00Z",
  "uploader": { UserResource },
  "verifications": [ VerificationResource ],
  "verifications_count": 3,
  "file_size_formatted": "2.05 MB",
  "created_at": "2025-01-24T12:00:00Z",
  "updated_at": "2025-01-24T12:00:00Z",
  "links": {
    "self": "/api/documents/1",
    "download": "/api/documents/1/download",
    "verify": "/api/documents/1/verify"
  }
}
```

**Caractéristiques:**
- Taille de fichier formatée automatiquement
- Hash pour intégrité
- Lien de vérification si pending

### ✅ VerificationResource

**Données exposées:**
```json
{
  "id": 1,
  "is_valid": true,
  "comment": "Document authentique",
  "verifier": { UserResource },
  "document": { DocumentResource },
  "created_at": "2025-01-24T12:00:00Z"
}
```

## 🔧 Utilisation dans les Controllers

### Retourner une resource

```php
use App\Http\Resources\TopicResource;

public function show(Topic $topic): TopicResource
{
    $topic->load(['author', 'region', 'department']);
    return new TopicResource($topic);
}
```

### Retourner une collection

```php
use App\Http\Resources\TopicCollection;

public function index(Request $request): TopicCollection
{
    $topics = Topic::with(['author', 'region'])
        ->paginate(15);
    
    return new TopicCollection($topics);
}
```

### Retourner avec code status

```php
public function store(Request $request): JsonResponse
{
    $topic = $this->topicService->createTopic(...);
    
    return (new TopicResource($topic))
        ->response()
        ->setStatusCode(201);
}
```

### Retourner plusieurs resources

```php
public function show(Topic $topic): JsonResponse
{
    return response()->json([
        'topic' => new TopicResource($topic),
        'related' => TopicResource::collection($relatedTopics),
    ]);
}
```

## 🎯 Patterns Avancés

### Chargement conditionnel

```php
'email' => $this->when($condition, $this->email)
```

### Chargement de relations

```php
'author' => new UserResource($this->whenLoaded('author'))
'posts' => PostResource::collection($this->whenLoaded('posts'))
```

### Attributs calculés

```php
'is_online' => $this->last_seen_at?->gt(now()->subMinutes(5))
'vote_score' => $this->when(isset($this->vote_score), (int) $this->vote_score)
```

### Liens HATEOAS

```php
'links' => [
    'self' => route('api.topics.show', $this->id),
    'posts' => route('api.topics.posts.index', $this->id),
]
```

### Métadonnées de pagination

```php
'meta' => [
    'total' => $this->total(),
    'per_page' => $this->perPage(),
    'current_page' => $this->currentPage(),
]
```

## 📊 Exemples de Réponses

### GET /api/topics/1

```json
{
  "id": 1,
  "title": "Débat sur les transports",
  "description": "Discussion nationale...",
  "type": "debate",
  "scope": "national",
  "author": {
    "id": 42,
    "name": "Jean Dupont",
    "roles": ["citizen"]
  },
  "is_open": true,
  "posts_count": 156,
  "ballots_count": 0,
  "created_at": "2025-01-24T12:00:00Z",
  "links": {
    "self": "/api/topics/1",
    "posts": "/api/topics/1/posts"
  }
}
```

### GET /api/topics?page=1

```json
{
  "data": [
    { TopicResource },
    { TopicResource }
  ],
  "meta": {
    "total": 150,
    "per_page": 15,
    "current_page": 1,
    "last_page": 10
  },
  "links": {
    "first": "/api/topics?page=1",
    "last": "/api/topics?page=10",
    "next": "/api/topics?page=2"
  }
}
```

### GET /api/topics/1/vote/results

```json
{
  "ballot_type": "binary",
  "total_votes": 156,
  "yes": 98,
  "no": 58,
  "percentages": {
    "yes": 62.82,
    "no": 37.18
  },
  "ballot_ends_at": "2025-02-24T12:00:00Z",
  "is_ended": false
}
```

## ✨ Avantages

1. **Consistance** : Format JSON uniforme
2. **Sécurité** : Contrôle fin des données exposées
3. **Performance** : Chargement conditionnel des relations
4. **Maintenabilité** : Un seul endroit pour le formatage
5. **Flexibilité** : Adaptation selon l'utilisateur
6. **Documentation** : Auto-documentation via structure
7. **Versioning** : Facile de créer des versions d'API

## 🔐 Sécurité

### Données sensibles

```php
// Email visible uniquement pour l'owner ou admin
'email' => $this->when(
    $request->user()?->id === $this->id || $request->user()?->hasRole('admin'),
    $this->email
)
```

### Relations conditionnelles

```php
// Reporter visible uniquement pour modérateurs
'reporter' => $this->when(
    $request->user()?->hasAnyRole(['moderator', 'admin']),
    new UserResource($this->whenLoaded('reporter'))
)
```

## 🚀 Performance

### Eager Loading

```php
// Dans le controller
$topics = Topic::with(['author', 'region', 'department'])
    ->withCount(['posts', 'ballots'])
    ->paginate(15);
```

### Lazy Loading prévention

```php
// Dans Resource
'author' => new UserResource($this->whenLoaded('author'))
```

### N+1 queries évitées

```php
// Bon : Eager loading dans controller
$topics->load(['author', 'region']);

// Mauvais : Lazy loading dans Resource
// $this->author (déclenche une query pour chaque topic)
```

## 📝 Bonnes Pratiques

1. **Toujours** utiliser `whenLoaded()` pour les relations
2. **Éviter** la logique métier dans les Resources
3. **Préférer** les attributs calculés aux getters
4. **Inclure** des liens HATEOAS pour la navigation
5. **Utiliser** des Collections pour les listes paginées
6. **Documenter** les formats de réponse
7. **Versionner** les Resources si changements breaking

## 🎯 Prochaines Étapes

1. **Versioning** : Créer v2 des Resources si needed
2. **Caching** : Mettre en cache les Resources lourdes
3. **Compression** : Gzip des réponses JSON
4. **GraphQL** : Alternative aux REST Resources
5. **OpenAPI** : Générer spec depuis Resources

---

💙 CivicDash - API Resources Documentation

