# Policies CivicDash

Cette documentation décrit toutes les **Policies d'autorisation** de CivicDash.

## 📁 Policies créées

```
app/Policies/
├── TopicPolicy.php
├── PostPolicy.php
├── ReportPolicy.php
├── SanctionPolicy.php
├── DocumentPolicy.php
├── UserAllocationPolicy.php
└── BallotPolicy.php
```

---

## 🔐 TopicPolicy

Gère les autorisations pour les **Topics** (débats, propositions de loi, référendums).

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewAny()` | Tous (même non-auth) | - |
| `view()` | Tous pour open/closed/archived<br>Auteur/Modos/Admins pour draft | - |
| `create()` | Citoyens, Législateurs | Permission `topics.create`<br>Pas muted/banned |
| `createBill()` | Législateurs uniquement | Permission `topics.bill` |
| `update()` | Auteur (draft/open)<br>Modos/Admins | Pas muted/banned |
| `delete()` | Auteur (draft)<br>Admins | - |
| `close()` | Auteur, Modos, Admins | Permission `topics.close` |
| `pin()` | Modos, Admins | Permission `topics.pin` |
| `createBallot()` | Auteur ou Législateurs | Topic sans scrutin |
| `vote()` | Citoyens | Scrutin ouvert<br>Pas déjà voté<br>Pas muted/banned |
| `viewResults()` | Tous après deadline<br>Auteur/Admins avant | - |
| `archive()` | Modos, Admins | - |

### Exemple d'utilisation

```php
// Dans un controller
if ($request->user()->can('create', Topic::class)) {
    // Créer un topic
}

if ($request->user()->can('vote', $topic)) {
    // Voter sur le topic
}
```

---

## 💬 PostPolicy

Gère les autorisations pour les **Posts** (messages dans les débats).

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewAny()` | Tous | - |
| `view()` | Tous (sauf cachés)<br>Modos/Admins (cachés) | - |
| `create()` | Tous avec permission | Permission `posts.create`<br>Pas muted/banned<br>Topic ouvert |
| `reply()` | Tous avec permission | Parent pas caché<br>Topic ouvert |
| `update()` | Auteur (pas caché)<br>Modos/Admins | Pas muted/banned |
| `delete()` | Auteur<br>Modos/Admins | Permission `posts.delete` |
| `hide()` | Modos, Admins | Permission `posts.hide` |
| `unhide()` | Modos, Admins | Permission `posts.hide` |
| `vote()` | Tous sauf auteur | Permission `posts.vote`<br>Post pas caché<br>Pas muted/banned |
| `pin()` | Modos, Admins, Auteur topic | - |
| `markAsOfficial()` | Législateurs, State, Admins | - |
| `report()` | Tous sauf auteur | Permission `reports.create`<br>Pas banned |

### Exemple d'utilisation

```php
if ($request->user()->can('reply', $parentPost)) {
    // Créer une réponse
}

if ($request->user()->can('hide', $post)) {
    $post->hide('Contenu inapproprié');
}
```

---

## 🚨 ReportPolicy

Gère les autorisations pour les **Signalements**.

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewAny()` | Modos, Admins | Permission `reports.review` |
| `view()` | Créateur<br>Modos/Admins | - |
| `create()` | Tous non bannis | Permission `reports.create` |
| `review()` | Modos, Admins | Report pending/reviewing |
| `resolve()` | Modos, Admins | Report reviewing<br>Permission `reports.resolve` |
| `reject()` | Modos, Admins | Report reviewing |
| `update()` | Créateur (pending)<br>Modos/Admins | - |
| `delete()` | Créateur (pending)<br>Admins | - |
| `assign()` | Modos, Admins | Report pending |
| `addNotes()` | Modo assigné<br>Admins | - |

### Workflow

```
Pending → Review (assign) → Resolve/Reject
   ↑                              ↓
   └──────── Update/Delete ───────┘
```

---

## ⚖️ SanctionPolicy

Gère les autorisations pour les **Sanctions** (warnings, mutes, bans).

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewAny()` | Modos, Admins | - |
| `view()` | User sanctionné<br>Modos/Admins | - |
| `viewOwn()` | Tous | Voir ses propres sanctions |
| `create()` | Modos, Admins | Permission `sanctions.create`<br>Pas se sanctionner soi-même<br>Pas sanctionner supérieur |
| `createWarning()` | Modos, Admins | - |
| `createMute()` | Modos, Admins | - |
| `createBan()` | **Admins uniquement** | Ban permanent |
| `update()` | Créateur<br>Admins | - |
| `revoke()` | Créateur<br>Admins | Sanction active |
| `delete()` | **Admins uniquement** | Historique |
| `viewHistory()` | User concerné<br>Modos/Admins | - |

### Hiérarchie

```
Admin > Moderator > User
└─ Peut sanctionner tout le monde
       └─ Ne peut pas sanctionner Admin ou autre Modo
              └─ Ne peut pas sanctionner
```

---

## 📄 DocumentPolicy

Gère les autorisations pour les **Documents** (pièces jointes, justificatifs).

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewAny()` | Tous | - |
| `view()` | Tous | - |
| `upload()` | Tous avec permission | Permission `documents.upload`<br>Pas muted/banned |
| `update()` | Uploader<br>Admins | Pas muted/banned |
| `delete()` | Uploader (non vérifié)<br>Admins | - |
| `verify()` | Journalistes/ONGs vérifiés | Permission `documents.verify`<br>Profil vérifié<br>Pas son propre document<br>Document non vérifié |
| `download()` | Tous | - |
| `viewVerifications()` | Tous | Historique vérifications |
| `attach()` | Tous avec permission | - |

### Processus de vérification

```
Upload → Vérification (Journaliste/ONG) → Vérifié ✓
   ↓           ↓
Update    Rejet (peut être re-soumis)
```

---

## 💰 UserAllocationPolicy

Gère les autorisations pour les **Allocations budgétaires** citoyennes.

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewAny()` | Tous authentifiés | Agrégations publiques |
| `view()` | User concerné<br>State/Admins | - |
| `viewOwn()` | Citoyens | Permission `budget.allocate` |
| `create()` | Citoyens | Permission `budget.allocate`<br>Pas banned<br>A un profil |
| `allocateToSector()` | Citoyens | - |
| `update()` | User concerné | Ses propres allocations |
| `delete()` | User concerné | Ses propres allocations |
| `resetAll()` | User | Réinitialiser |
| `viewAggregated()` | Tous (même non-auth) | Résultats publics |
| `export()` | State, Admins | Export données |

### Règles métier

- Total allocations = 100%
- Contraintes min/max par secteur
- Un seul secteur par user

---

## 🗳️ BallotPolicy

Gère les autorisations pour les **Scrutins** et le **vote anonyme**.

### Méthodes

| Méthode | Qui peut ? | Conditions |
|---------|-----------|------------|
| `viewResults()` | Tous après deadline<br>Auteur/Admins avant | - |
| `vote()` | Citoyens éligibles | Permission `ballots.vote`<br>Scrutin ouvert<br>Pas déjà voté<br>Éligible (scope territorial)<br>Pas muted/banned |
| `requestToken()` | Citoyens éligibles | Pas de token existant |
| `create()` | Auteur topic<br>Législateurs | Topic sans scrutin<br>Status draft/open |
| `update()` | Auteur<br>Admins | Scrutin pas commencé |
| `close()` | Auteur<br>Admins | Scrutin ouvert |
| `extend()` | **Admins uniquement** | Étendre deadline |
| `viewVotes()` | **Admins après deadline** | ⚠️ Votes chiffrés uniquement |
| `export()` | State, Admins | Après deadline |

### 🔐 Sécurité CRITIQUE

**Même les admins ne peuvent pas voir qui a voté quoi.**

```php
// ✅ Admins peuvent voir les votes chiffrés
if ($user->can('viewVotes', $topic)) {
    $encryptedVotes = $topic->ballots; // Votes chiffrés
}

// ❌ IMPOSSIBLE de lier un vote à un user
// TopicBallot n'a PAS de user_id
```

### Éligibilité territoriale

```php
protected function isEligibleToVote(User $user, Topic $topic): bool
{
    if ($topic->scope === 'national') return true;
    
    if ($topic->scope === 'region') {
        return $user->profile->region_id === $topic->region_id;
    }
    
    if ($topic->scope === 'dept') {
        return $user->profile->department_id === $topic->department_id;
    }
    
    return false;
}
```

---

## 🎯 Gates personnalisés

Définis dans `AppServiceProvider::boot()` :

| Gate | Qui peut ? |
|------|-----------|
| `vote-on-ballot` | Via `BallotPolicy::vote()` |
| `view-ballot-results` | Via `BallotPolicy::viewResults()` |
| `create-ballot` | Via `BallotPolicy::create()` |
| `access-moderation-dashboard` | Modos, Admins |
| `access-admin-dashboard` | Admins |
| `publish-budget-data` | State, Admins |

### Exemple d'utilisation

```php
if (Gate::allows('access-moderation-dashboard')) {
    return view('moderation.dashboard');
}

if (Gate::denies('publish-budget-data')) {
    abort(403);
}
```

---

## 📝 Utilisation dans les Controllers

### Authorisation manuelle

```php
public function store(Request $request)
{
    $this->authorize('create', Topic::class);
    
    $topic = Topic::create($request->validated());
    
    return response()->json($topic, 201);
}
```

### Middleware

```php
Route::middleware(['auth', 'can:update,topic'])->group(function () {
    Route::put('/topics/{topic}', [TopicController::class, 'update']);
});
```

### Dans les vues (Blade)

```blade
@can('create', App\Models\Topic::class)
    <a href="{{ route('topics.create') }}">Créer un débat</a>
@endcan

@can('update', $topic)
    <a href="{{ route('topics.edit', $topic) }}">Modifier</a>
@endcan
```

### Dans les requêtes API (Form Requests)

```php
public function authorize()
{
    return $this->user()->can('create', Post::class);
}
```

---

## 🧪 Tests des Policies

Les tests Pest vérifient automatiquement les policies :

```php
// tests/Feature/Auth/PermissionsTest.php
test('citizen cannot moderate', function () {
    $citizen = createCitizen();
    
    expect($citizen->can('reports.review'))->toBeFalse();
});

test('moderator can moderate content', function () {
    $moderator = createModerator();
    
    expect($moderator->can('reports.review'))->toBeTrue();
});
```

---

## 🔧 Enregistrement des Policies

Les policies sont enregistrées dans `app/Providers/AppServiceProvider.php` :

```php
protected $policies = [
    Topic::class => TopicPolicy::class,
    Post::class => PostPolicy::class,
    Report::class => ReportPolicy::class,
    Sanction::class => SanctionPolicy::class,
    Document::class => DocumentPolicy::class,
    UserAllocation::class => UserAllocationPolicy::class,
];

public function boot(): void
{
    foreach ($this->policies as $model => $policy) {
        Gate::policy($model, $policy);
    }
    
    // Gates personnalisés...
}
```

---

## 📊 Récapitulatif

| Policy | Fichier | Méthodes | Focus |
|--------|---------|----------|-------|
| **TopicPolicy** | `TopicPolicy.php` | 12 | Création, vote, résultats |
| **PostPolicy** | `PostPolicy.php` | 13 | Replies, votes, modération |
| **ReportPolicy** | `ReportPolicy.php` | 10 | Workflow signalements |
| **SanctionPolicy** | `SanctionPolicy.php` | 11 | Hiérarchie modération |
| **DocumentPolicy** | `DocumentPolicy.php` | 9 | Upload, vérification |
| **UserAllocationPolicy** | `UserAllocationPolicy.php` | 10 | Budget participatif |
| **BallotPolicy** | `BallotPolicy.php` | 10 | **Vote anonyme critique** |
| **TOTAL** | **7 policies** | **75 méthodes** | **Autorisation complète** |

---

## 🎓 Bonnes pratiques

### 1. Toujours vérifier l'autorisation

```php
// ❌ BAD
public function delete(Topic $topic) {
    $topic->delete();
}

// ✅ GOOD
public function delete(Topic $topic) {
    $this->authorize('delete', $topic);
    $topic->delete();
}
```

### 2. Utiliser les gates pour les actions globales

```php
// Actions sans modèle spécifique
Gate::define('publish-budget', function ($user) {
    return $user->hasRole('state');
});
```

### 3. Combiner policies et permissions

```php
public function update(User $user, Topic $topic): bool
{
    // Vérification basée sur le modèle
    if ($topic->author_id === $user->id) {
        return true;
    }
    
    // Fallback sur permissions Spatie
    return $user->hasPermissionTo('topics.edit');
}
```

### 4. Retourner des booléens

```php
// ✅ Toujours retourner true/false
public function view(User $user, Topic $topic): bool
{
    return $topic->status === 'open';
}

// ❌ Ne pas retourner null ou lever d'exception
```

---

## 🔗 Liens utiles

- [Laravel Authorization](https://laravel.com/docs/11.x/authorization)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Tests Pest](../docs/TESTS.md)
- [RBAC Seeders](../docs/SEEDERS.md)

