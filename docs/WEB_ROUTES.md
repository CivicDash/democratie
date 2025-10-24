# 🌐 Documentation Routes Web - CivicDash

## 📖 Vue d'ensemble

Les routes web de CivicDash utilisent **Inertia.js** pour créer une Single Page Application (SPA) avec Laravel comme backend et Vue 3 comme frontend.

## 🏗️ Architecture

```
Routes Web (routes/web.php)
    ↓
Web Controllers (app/Http/Controllers/Web/)
    ↓
Services (app/Services/)
    ↓
Models + Policies
    ↓
Inertia Response → Vue Components
```

## 📄 Structure des Fichiers

```
app/Http/Controllers/Web/
├── TopicController.php       # Forum citoyen
├── PostController.php         # Réponses aux topics
├── VoteController.php         # Vote anonyme
├── BudgetController.php       # Budget participatif
├── ModerationController.php   # Modération
└── DocumentController.php     # Documents publics

routes/
├── web.php                    # Routes Inertia
└── api.php                    # Routes API REST

app/Http/Middleware/
└── HandleInertiaRequests.php  # Props partagées globalement
```

## 🌐 Routes Complètes

### 🏠 Page d'accueil

```php
GET /                          → Welcome.vue
```

### 📝 Forum Citoyen (Topics)

#### Routes Publiques
```php
GET /topics                    → Topics/Index.vue
GET /topics/trending           → Topics/Index.vue (trending)
GET /topics/{topic}            → Topics/Show.vue
```

#### Routes Authentifiées
```php
GET  /topics/create            → Topics/Create.vue
POST /topics                   → Créer un topic
GET  /topics/{topic}/edit      → Topics/Edit.vue
PUT  /topics/{topic}           → Mettre à jour
DELETE /topics/{topic}         → Supprimer

POST   /topics/{topic}/posts   → Créer une réponse
PUT    /topics/posts/{post}    → Modifier une réponse
DELETE /topics/posts/{post}    → Supprimer une réponse
POST   /topics/posts/{post}/vote → Voter (up/down)
```

**Permissions:**
- Créer topic: `auth`
- Modifier/Supprimer: `owner` ou `admin`
- Répondre: `auth` + topic ouvert

### 🗳️ Vote Anonyme

#### Routes Publiques
```php
GET /vote/topics/{topic}           → Vote/Show.vue
GET /vote/topics/{topic}/results   → Vote/Results.vue
```

#### Routes Authentifiées
```php
POST /vote/topics/{topic}/token    → Demander un jeton
POST /vote/topics/{topic}/cast     → Voter (anonyme)
```

**Workflow:**
1. User demande un jeton cryptographique
2. User vote avec le jeton (anonyme)
3. Résultats affichés en temps réel

**Permissions:**
- Vote: `auth` + `citizen` + pas déjà voté

### 💰 Budget Participatif

#### Routes Publiques
```php
GET /budget                → Budget/Index.vue
GET /budget/stats          → Budget/Stats.vue
GET /budget/sectors        → Budget/Sectors.vue
```

#### Routes Authentifiées
```php
GET    /budget/my-allocations  → Mes allocations (JSON)
POST   /budget/allocate        → Allouer (1 secteur)
POST   /budget/bulk-allocate   → Allouer en masse
DELETE /budget/reset           → Réinitialiser
```

**Permissions:**
- Allouer: `auth` + `citizen`

### 🚨 Modération

**Toutes les routes nécessitent:** `auth` + `role:moderator|admin`

```php
GET /moderation/dashboard                     → Moderation/Dashboard.vue
GET /moderation/reports                       → Moderation/Reports.vue
GET /moderation/reports/priority              → Moderation/PriorityReports.vue
GET /moderation/reports/{report}              → Moderation/ReportDetail.vue

POST   /moderation/reports/{report}/assign    → Assigner un signalement
POST   /moderation/reports/{report}/resolve   → Résoudre
POST   /moderation/reports/{report}/reject    → Rejeter

GET    /moderation/sanctions                  → Moderation/Sanctions.vue
GET    /moderation/sanctions/{sanction}       → Moderation/SanctionDetail.vue
DELETE /moderation/sanctions/{sanction}       → Révoquer

GET /moderation/stats                         → Moderation/Stats.vue
```

**Route publique de signalement:**
```php
POST /reports    → Créer un signalement (auth requis)
```

### 📄 Documents Publics

#### Routes Publiques
```php
GET /documents                    → Documents/Index.vue
GET /documents/{document}         → Documents/Show.vue
GET /documents/{document}/download → Télécharger PDF
GET /documents/stats              → Documents/Stats.vue
```

#### Routes Authentifiées
```php
POST   /documents              → Upload document
PUT    /documents/{document}   → Mettre à jour
DELETE /documents/{document}   → Supprimer
```

#### Routes Vérificateurs (`journalist|ong|admin`)
```php
GET  /documents/pending               → Documents/Pending.vue
POST /documents/{document}/verify     → Vérifier un document
```

### 👤 Profil & Dashboard

```php
GET    /dashboard             → Dashboard.vue (auth + verified)
GET    /profile               → Profile/Edit.vue
PATCH  /profile               → Mettre à jour profil
DELETE /profile               → Supprimer compte
```

### 👑 Administration

**Nécessite:** `auth` + `role:admin`

```php
GET /admin/dashboard          → Admin/Dashboard.vue
```

### 🔐 Authentification

Routes fournies par Laravel Breeze :
```php
GET  /login                   → Auth/Login.vue
POST /login                   → Authentifier
POST /logout                  → Déconnecter

GET  /register                → Auth/Register.vue
POST /register                → Créer compte

GET  /forgot-password         → Auth/ForgotPassword.vue
POST /forgot-password         → Envoyer email reset

GET  /reset-password/{token}  → Auth/ResetPassword.vue
POST /reset-password          → Réinitialiser

GET  /verify-email            → Auth/VerifyEmail.vue
POST /email/verification-notification → Renvoyer email

GET  /confirm-password        → Auth/ConfirmPassword.vue
POST /confirm-password        → Confirmer
```

## 🛠️ Web Controllers

### TopicController

**Méthodes:**
- `index()` - Liste avec filtres (search, scope, type)
- `trending()` - Topics populaires
- `show()` - Détails + posts
- `create()` - Formulaire création
- `store()` - Créer
- `edit()` - Formulaire édition
- `update()` - Mettre à jour
- `destroy()` - Supprimer

**Services utilisés:** `TopicService`

### PostController

**Méthodes:**
- `store()` - Créer réponse
- `update()` - Modifier
- `destroy()` - Supprimer
- `vote()` - Voter (up/down)

**Services utilisés:** `TopicService`

### VoteController

**Méthodes:**
- `show()` - Page de vote (3 étapes)
- `results()` - Résultats
- `requestToken()` - Jeton cryptographique
- `cast()` - Voter anonymement

**Services utilisés:** `BallotService`

### BudgetController

**Méthodes:**
- `index()` - Page allocation
- `stats()` - Statistiques
- `sectors()` - Liste secteurs
- `myAllocations()` - Mes allocations (JSON)
- `allocate()` - Allouer (1 secteur)
- `bulkAllocate()` - Allouer en masse
- `reset()` - Réinitialiser

**Services utilisés:** `BudgetService`

### ModerationController

**Méthodes:**
- `dashboard()` - Dashboard
- `reports()` - Liste signalements
- `priorityReports()` - Prioritaires
- `showReport()` - Détails signalement
- `store()` - Créer signalement
- `assignReport()` - Assigner
- `resolveReport()` - Résoudre
- `rejectReport()` - Rejeter
- `sanctions()` - Liste sanctions
- `showSanction()` - Détails sanction
- `revokeSanction()` - Révoquer
- `stats()` - Statistiques

**Services utilisés:** `ModerationService`

### DocumentController

**Méthodes:**
- `index()` - Liste documents
- `show()` - Détails
- `store()` - Upload
- `update()` - Mettre à jour
- `destroy()` - Supprimer
- `download()` - Télécharger PDF
- `pending()` - En attente vérification
- `verify()` - Vérifier
- `stats()` - Statistiques

**Services utilisés:** `DocumentService`

## 🔧 Middleware Inertia

### HandleInertiaRequests

Partage automatiquement des props globales :

```php
public function share(Request $request): array
{
    return [
        'auth' => [
            'user' => [
                'id' => ...,
                'name' => ...,
                'email' => ...,
                'roles' => [...],
                'permissions' => [...],
            ],
        ],
        'flash' => [
            'success' => ...,
            'error' => ...,
            'warning' => ...,
            'info' => ...,
        ],
    ];
}
```

**Accès dans Vue:**
```vue
<script setup>
const user = $page.props.auth.user;
const flash = $page.props.flash;
</script>
```

## 🎨 Conventions de Réponse

### Succès
```php
return back()->with('success', 'Action réussie !');
return redirect()->route('topics.show', $topic)
    ->with('success', 'Topic créé !');
```

### Erreurs
```php
return back()->with('error', 'Une erreur est survenue.');
abort(403, 'Non autorisé');
abort(404, 'Ressource introuvable');
```

### JSON (API-like)
```php
return response()->json($data);
return response()->json($data, 201); // Created
```

## 🔐 Autorisation

### Policy-based
```php
$this->authorize('update', $topic);
$this->authorize('create', Topic::class);
```

### Gate-based
```php
Gate::authorize('access-moderation-dashboard');
```

### Middleware
```php
Route::middleware('role:moderator|admin')->group(...);
Route::middleware(['auth', 'verified'])->group(...);
```

## 📊 Pagination

Toutes les listes utilisent la pagination Laravel :

```php
$topics = Topic::latest()->paginate(15);

return Inertia::render('Topics/Index', [
    'topics' => $topics, // Inclut automatiquement links, meta, etc.
]);
```

**Dans Vue:**
```vue
<Pagination :links="topics.links" />
```

## 🔍 Filtres & Recherche

Pattern recommandé :

```php
public function index(Request $request)
{
    $query = Model::query();
    
    if ($request->filled('search')) {
        $query->where('title', 'like', "%{$request->search}%");
    }
    
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    $items = $query->paginate(15)->withQueryString();
    
    return Inertia::render('Page', [
        'items' => $items,
        'filters' => $request->only(['search', 'status']),
    ]);
}
```

## 🚀 Performance

### Eager Loading
```php
$topic->load(['author', 'region', 'department']);
$topic->loadCount('ballots');
```

### Lazy Eager Loading
```php
$topics = Topic::with(['author', 'region'])->paginate();
```

### Select Specific Columns
```php
$topics = Topic::select(['id', 'title', 'created_at'])->get();
```

## 🧪 Tester les Routes

```bash
# Lancer le serveur
php artisan serve

# Avec Vite (hot reload)
npm run dev

# Accéder
http://localhost:8000/topics
http://localhost:8000/budget
http://localhost:8000/documents
```

## 📝 Exemples d'Utilisation

### Créer un Topic
```
1. User: GET /topics/create
2. Vue: Affiche formulaire (Topics/Create.vue)
3. User: Remplit et submit
4. POST /topics → TopicController@store
5. TopicService → createTopic()
6. Redirect: /topics/{topic} avec message success
```

### Voter Anonymement
```
1. User: GET /vote/topics/1
2. Vue: Affiche page Vote/Show.vue (étape 1)
3. User: Click "Obtenir jeton"
4. POST /vote/topics/1/token → VoteController@requestToken
5. BallotService → requestBallotToken()
6. Retour avec flash token
7. Vue: Affiche étape 2 (formulaire vote)
8. User: Sélectionne choix et submit
9. POST /vote/topics/1/cast → VoteController@cast
10. BallotService → castVote() (anonyme)
11. Vue: Affiche étape 3 (résultats)
```

### Allouer Budget
```
1. User: GET /budget
2. Vue: Affiche Budget/Index.vue avec sliders
3. User: Ajuste allocations (100%)
4. User: Submit
5. POST /budget/bulk-allocate → BudgetController@bulkAllocate
6. BudgetService → bulkAllocate()
7. Retour avec message success
8. Vue: Rafraîchit avec nouvelles données
```

## 🎯 Prochaines Étapes

1. **API Resources** : Formater proprement les réponses JSON
2. **Rate Limiting** : Limiter les requêtes par user
3. **Cache** : Mettre en cache les stats et résultats
4. **Validation Frontend** : Validation en temps réel
5. **Tests E2E** : Cypress ou Playwright

## 🤝 Contribution

Voir `CONTRIBUTING.md` pour les guidelines.

---

💙 CivicDash - Démocratie Participative Open Source

