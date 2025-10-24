# Controllers CivicDash

Cette documentation décrit tous les **Controllers API** de CivicDash.

## 📁 Controllers créés

```
app/Http/Controllers/Api/
├── TopicController.php          # Gestion des topics
├── PostController.php            # Gestion des posts
├── VoteController.php            # Vote anonyme
├── BudgetController.php          # Budget participatif
├── ModerationController.php      # Modération
└── DocumentController.php        # Documents et vérification
```

**Total : 6 Controllers API**

---

## 📝 TopicController

Gestion des topics (débats, propositions de loi, référendums).

### Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| GET | `/api/topics` | Liste des topics | Public |
| GET | `/api/topics/{topic}` | Détails d'un topic | Public |
| POST | `/api/topics` | Créer un topic | Auth |
| PUT | `/api/topics/{topic}` | Mettre à jour | Auth |
| DELETE | `/api/topics/{topic}` | Supprimer | Auth |
| POST | `/api/topics/{topic}/close` | Fermer | Auth |
| POST | `/api/topics/{topic}/archive` | Archiver | Auth |
| GET | `/api/topics/trending` | Topics populaires | Public |
| POST | `/api/topics/{topic}/ballot` | Créer scrutin | Auth |
| GET | `/api/topics/{topic}/stats` | Statistiques | Public |

### Exemple : Créer un topic

```php
POST /api/topics
Content-Type: application/json
Authorization: Bearer {token}

{
    "title": "Débat sur la transition énergétique",
    "description": "Discussion sur les mesures...",
    "type": "debate",
    "status": "open",
    "scope": "national"
}

// Response 201
{
    "message": "Topic créé avec succès.",
    "topic": {
        "id": 1,
        "title": "Débat sur la transition énergétique",
        ...
    }
}
```

### Exemple : Topics trending

```php
GET /api/topics/trending?limit=10&days=7

// Response 200
[
    {
        "id": 1,
        "title": "...",
        "posts_count": 245
    },
    ...
]
```

---

## 💬 PostController

Gestion des posts (messages dans les débats).

### Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| GET | `/api/topics/{topic}/posts` | Liste des posts | Public |
| GET | `/api/posts/{post}` | Détails d'un post | Public |
| POST | `/api/topics/{topic}/posts` | Créer un post | Auth |
| PUT | `/api/posts/{post}` | Mettre à jour | Auth |
| DELETE | `/api/posts/{post}` | Supprimer | Auth |
| POST | `/api/posts/{post}/vote` | Voter sur un post | Auth |
| GET | `/api/topics/{topic}/posts/top` | Meilleurs posts | Public |
| GET | `/api/posts/{post}/replies` | Réponses | Public |

### Exemple : Créer un post

```php
POST /api/topics/1/posts
Authorization: Bearer {token}

{
    "content": "Je pense que nous devrions...",
    "parent_id": null  // ou ID du post parent pour une réponse
}

// Response 201
{
    "message": "Post créé avec succès.",
    "post": {
        "id": 42,
        "content": "...",
        "user": {...}
    }
}
```

### Exemple : Voter sur un post

```php
POST /api/posts/42/vote
Authorization: Bearer {token}

{
    "vote": "upvote"  // ou "downvote"
}

// Response 200
{
    "message": "Vote enregistré avec succès.",
    "result": {
        "action": "added",  // "added", "removed", "changed"
        "vote_type": "upvote",
        "score": 15
    }
}
```

---

## 🗳️ VoteController

Gestion du vote anonyme sur les scrutins.

### Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| POST | `/api/topics/{topic}/vote/token` | Demander token | Auth |
| POST | `/api/topics/{topic}/vote/cast` | Voter (anonyme) | Auth |
| GET | `/api/topics/{topic}/vote/results` | Résultats | Public/Auth |
| GET | `/api/topics/{topic}/vote/has-voted` | A voté ? | Auth |
| GET | `/api/topics/{topic}/vote/count` | Nombre de votes | Public |
| GET | `/api/topics/{topic}/vote/integrity` | Vérifier intégrité | Admin |
| GET | `/api/topics/{topic}/vote/export` | Exporter résultats | Admin/State |

### Exemple : Workflow complet

```php
// 1. Demander un token
POST /api/topics/1/vote/token
Authorization: Bearer {token}

// Response 200
{
    "message": "Token de vote généré avec succès.",
    "token": "abc123...",  // SHA512, 128 chars
    "expires_at": "2025-01-30T23:59:59Z"
}

// 2. Voter (anonyme)
POST /api/topics/1/vote/cast
Authorization: Bearer {token}

{
    "token": "abc123...",
    "vote": {
        "choice": "yes"
    }
}

// Response 200
{
    "message": "Vote enregistré avec succès.",
    "ballot_id": 789,
    "voted_at": "2025-01-24T15:30:00Z"
}

// 3. Voir les résultats (après deadline)
GET /api/topics/1/vote/results

// Response 200
{
    "total_votes": 1250,
    "results": {
        "yes": 750,
        "no": 450,
        "abstain": 50
    },
    "revealed_at": "2025-01-30T23:59:59Z"
}
```

---

## 💰 BudgetController

Gestion du budget participatif.

### Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| GET | `/api/budget/sectors` | Liste des secteurs | Public |
| GET | `/api/budget/allocations` | Mes allocations | Auth |
| POST | `/api/budget/allocate` | Allouer à un secteur | Auth |
| POST | `/api/budget/bulk-allocate` | Allocation complète | Auth |
| DELETE | `/api/budget/reset` | Réinitialiser | Auth |
| GET | `/api/budget/averages` | Moyennes citoyennes | Public |
| GET | `/api/budget/ranking` | Classement secteurs | Public |
| GET | `/api/budget/stats` | Statistiques | Public |
| POST | `/api/budget/simulate` | Simuler budget | Public |
| POST | `/api/budget/compare` | Comparer réel | Public |
| GET | `/api/budget/export` | Exporter données | Admin/State |

### Exemple : Allocation complète

```php
POST /api/budget/bulk-allocate
Authorization: Bearer {token}

{
    "allocations": {
        "1": 30.0,  // Santé : 30%
        "2": 25.0,  // Éducation : 25%
        "3": 20.0,  // Infrastructure : 20%
        "4": 15.0,  // Environnement : 15%
        "5": 10.0   // Culture : 10%
    }
}

// Response 200
{
    "message": "Allocations enregistrées avec succès.",
    "allocations": [...]
}
```

### Exemple : Comparaison avec dépenses réelles

```php
POST /api/budget/compare?year=2024

// Response 200
{
    "year": 2024,
    "comparison": [
        {
            "sector": "Santé",
            "citizen_allocation_percent": 30.0,
            "real_spending_percent": 22.5,
            "difference": 7.5  // Citoyens veulent +7.5%
        },
        ...
    ]
}
```

---

## 🚨 ModerationController

Gestion de la modération (signalements et sanctions).

### Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| GET | `/api/moderation/reports` | Liste rapports | Moderator |
| GET | `/api/moderation/reports/priority` | Rapports prioritaires | Moderator |
| POST | `/api/moderation/reports` | Créer rapport | Auth |
| POST | `/api/moderation/reports/{report}/assign` | S'assigner | Moderator |
| POST | `/api/moderation/reports/{report}/resolve` | Résoudre | Moderator |
| POST | `/api/moderation/reports/{report}/reject` | Rejeter | Moderator |
| GET | `/api/moderation/users/{user}/sanctions` | Sanctions d'un user | Auth |
| POST | `/api/moderation/users/{user}/sanctions` | Créer sanction | Moderator |
| DELETE | `/api/moderation/sanctions/{sanction}` | Révoquer | Moderator |
| GET | `/api/moderation/stats` | Statistiques | Moderator |
| GET | `/api/moderation/top-moderators` | Top modérateurs | Moderator |

### Exemple : Créer un signalement

```php
POST /api/moderation/reports
Authorization: Bearer {token}

{
    "reportable_type": "App\\Models\\Post",
    "reportable_id": 42,
    "reason": "Ce post contient des propos inappropriés..."
}

// Response 201
{
    "message": "Signalement créé avec succès.",
    "report": {
        "id": 15,
        "status": "pending",
        ...
    }
}
```

### Exemple : Créer une sanction

```php
POST /api/moderation/users/123/sanctions
Authorization: Bearer {moderator-token}

{
    "type": "mute",
    "reason": "Comportement inapproprié répété",
    "duration_days": 7
}

// Response 201
{
    "message": "Sanction créée avec succès.",
    "sanction": {
        "type": "mute",
        "expires_at": "2025-01-31T...",
        ...
    }
}
```

---

## 📄 DocumentController

Gestion des documents et leur vérification.

### Endpoints

| Méthode | Route | Description | Auth |
|---------|-------|-------------|------|
| GET | `/api/documents` | Liste documents | Public |
| GET | `/api/documents/{document}` | Détails | Public |
| POST | `/api/documents` | Upload | Auth |
| PUT | `/api/documents/{document}` | Mettre à jour desc | Auth |
| DELETE | `/api/documents/{document}` | Supprimer | Auth |
| POST | `/api/documents/{document}/verify` | Vérifier | Journalist/ONG |
| GET | `/api/documents/{document}/verifications` | Historique vérif | Public |
| GET | `/api/documents/pending` | En attente | Verifier |
| GET | `/api/documents/stats` | Statistiques | Public |
| GET | `/api/documents/top-verifiers` | Top vérificateurs | Public |
| GET | `/api/documents/{document}/download` | Télécharger | Public |

### Exemple : Upload document

```php
POST /api/documents
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "file": <binary>,
    "documentable_type": "App\\Models\\Topic",
    "documentable_id": 1,
    "description": "Rapport officiel du Sénat"
}

// Response 201
{
    "message": "Document uploadé avec succès.",
    "document": {
        "id": 7,
        "file_name": "rapport.pdf",
        "sha256_hash": "abc123...",
        ...
    }
}
```

### Exemple : Vérifier document

```php
POST /api/documents/7/verify
Authorization: Bearer {journalist-token}

{
    "status": "verified",
    "notes": "Document authentique, vérifié auprès de la source"
}

// Response 200
{
    "message": "Document vérifié avec succès.",
    "document": {
        "is_verified": true,
        ...
    },
    "verification": {...}
}
```

---

## 🔧 Utilisation générale

### Structure de réponse

```json
// Success
{
    "message": "Opération réussie.",
    "data": {...}
}

// Error
{
    "message": "Description de l'erreur.",
    "error": "Détails techniques (si disponible)"
}

// Validation Error (422)
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

### Pagination

```json
{
    "current_page": 1,
    "data": [...],
    "first_page_url": "...",
    "from": 1,
    "last_page": 10,
    "last_page_url": "...",
    "links": [...],
    "next_page_url": "...",
    "path": "...",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 150
}
```

### Filtres et tri communs

```php
// Filtrer
GET /api/topics?type=debate&status=open&scope=national

// Trier
GET /api/topics?sort_by=created_at&sort_order=desc

// Pagination
GET /api/topics?page=2&per_page=20
```

---

## 🔐 Authentification

Tous les endpoints marqués "Auth" nécessitent un token Bearer :

```http
Authorization: Bearer {your-access-token}
```

### Niveaux d'accès

- **Public** : Aucune authentification
- **Auth** : Utilisateur authentifié
- **Moderator** : Rôle moderator ou admin
- **Admin** : Rôle admin uniquement
- **Verifier** : Journaliste ou ONG vérifié
- **State** : Rôle state ou admin

---

## 📊 Récapitulatif

| Controller | Endpoints | Fonctionnalités |
|-----------|-----------|-----------------|
| **TopicController** | 10 | CRUD topics, scrutins, stats |
| **PostController** | 8 | CRUD posts, votes, threading |
| **VoteController** | 7 | Vote anonyme, résultats, intégrité |
| **BudgetController** | 11 | Allocations, stats, comparaison |
| **ModerationController** | 11 | Reports, sanctions, stats |
| **DocumentController** | 11 | Upload, vérification, download |
| **TOTAL** | **58 endpoints** | **API complète** |

---

## 🧪 Tests des Controllers

Les controllers peuvent être testés avec Pest :

```php
test('can create a topic', function () {
    $user = User::factory()->citizen()->create();
    
    actingAs($user)
        ->postJson('/api/topics', [
            'title' => 'Débat test',
            'description' => 'Description longue pour le test...',
            'type' => 'debate',
            'scope' => 'national',
        ])
        ->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'topic' => ['id', 'title', 'author']
        ]);
});

test('anonymous user cannot vote without token', function () {
    $topic = Topic::factory()->withBallot()->create();
    
    postJson("/api/topics/{$topic->id}/vote/cast", [
        'token' => 'invalid',
        'vote' => ['choice' => 'yes']
    ])
    ->assertStatus(400);
});
```

---

## 🔗 Liens utiles

- [Form Requests](../docs/FORM_REQUESTS.md) - Validation
- [Services](../docs/SERVICES.md) - Logique métier
- [Policies](../docs/POLICIES.md) - Autorisations
- [Models](../docs/MODELS.md) - Modèles Eloquent

