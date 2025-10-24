# Services CivicDash

Cette documentation décrit tous les **Services métier** de CivicDash.

## 📁 Services créés

```
app/Services/
├── BallotService.php           # Vote anonyme (CRITIQUE)
├── BudgetService.php            # Budget participatif
├── ModerationService.php        # Modération et sanctions
├── TopicService.php             # Topics et posts
└── DocumentService.php          # Documents et vérification
```

---

## 🗳️ BallotService

Service **CRITIQUE** pour gérer le vote anonyme. Garantit qu'aucun lien entre l'identité de l'utilisateur et son vote ne peut être établi.

### Méthodes principales

#### `generateToken(User $user, Topic $topic): BallotToken`
Génère un token de vote unique pour un utilisateur sur un topic.

```php
$ballotService = app(BallotService::class);
$token = $ballotService->generateToken($user, $topic);
```

**Exceptions :**
- User ne peut pas voter
- User a déjà un token

#### `castVote(string $tokenValue, array $vote): TopicBallot`
Caste un vote de manière anonyme. **CRITIQUE** : Ne stocke AUCUNE référence au user_id.

```php
$vote = ['choice' => 'yes'];
$ballot = $ballotService->castVote($tokenValue, $vote);
```

**Workflow :**
1. Vérifie le token
2. Chiffre le vote
3. Crée le bulletin SANS user_id
4. Consomme le token

#### `calculateResults(Topic $topic): array`
Calcule les résultats d'un scrutin après la deadline.

```php
$results = $ballotService->calculateResults($topic);
// [
//     'total_votes' => 150,
//     'results' => ['yes' => 90, 'no' => 60],
//     'revealed_at' => '2025-01-24T12:00:00Z'
// ]
```

#### `verifyIntegrity(Topic $topic): array`
Vérifie l'intégrité d'un scrutin :
- Hashes uniques
- Aucun user_id dans les ballots
- Correspondance tokens/votes

```php
$integrity = $ballotService->verifyIntegrity($topic);
// [
//     'valid' => true,
//     'total_votes' => 150,
//     'consumed_tokens' => 150,
//     'issues' => []
// ]
```

#### Autres méthodes

| Méthode | Description |
|---------|-------------|
| `hasUserVoted()` | Vérifie si un user a voté |
| `countVotes()` | Compte les votes d'un topic |
| `getUserToken()` | Obtient le token d'un user |
| `revokeUnconsumedTokens()` | Révoque les tokens non utilisés |
| `exportResults()` | Exporte les résultats (anonyme) |

---

## 💰 BudgetService

Service pour gérer le budget participatif et les allocations citoyennes.

### Méthodes principales

#### `allocate(User $user, Sector $sector, float $percent): UserAllocation`
Alloue un pourcentage du budget à un secteur.

```php
$budgetService = app(BudgetService::class);
$allocation = $budgetService->allocate($user, $sector, 25.5);
```

**Validations :**
- Pourcentage entre min et max du secteur
- Total ne dépasse pas 100%

#### `bulkAllocate(User $user, array $allocations): Collection`
Répartit le budget complet en une seule transaction.

```php
$allocations = [
    1 => 30.0,  // Santé : 30%
    2 => 25.0,  // Éducation : 25%
    3 => 20.0,  // Infrastructure : 20%
    4 => 15.0,  // Environnement : 15%
    5 => 10.0,  // Culture : 10%
];

$results = $budgetService->bulkAllocate($user, $allocations);
```

**Validations :**
- Total = 100%
- Contraintes min/max par secteur

#### `getAverageAllocations(): Collection`
Calcule les allocations moyennes par secteur (tous les utilisateurs).

```php
$averages = $budgetService->getAverageAllocations();
// [
//     ['sector_name' => 'Santé', 'average_percent' => 28.5, 'total_allocators' => 1250],
//     ...
// ]
```

#### `calculateSimulatedBudget(int $year, float $totalBudget): array`
Simule le budget basé sur les allocations citoyennes.

```php
$simulation = $budgetService->calculateSimulatedBudget(2025, 50000000000);
```

#### `compareWithRealSpending(int $year): array`
Compare les allocations citoyennes avec les dépenses réelles.

```php
$comparison = $budgetService->compareWithRealSpending(2024);
// [
//     'comparison' => [
//         [
//             'sector' => 'Santé',
//             'citizen_allocation_percent' => 30.0,
//             'real_spending_percent' => 22.5,
//             'difference' => 7.5
//         ],
//         ...
//     ]
// ]
```

#### Autres méthodes

| Méthode | Description |
|---------|-------------|
| `resetAllocations()` | Réinitialise les allocations |
| `getUserTotalAllocation()` | Total alloué par user |
| `hasCompletedAllocation()` | Vérification 100% |
| `getUserAllocations()` | Allocations d'un user |
| `getSectorRanking()` | Classement des secteurs |
| `getParticipationStats()` | Statistiques participation |
| `exportData()` | Export données budgétaires |

---

## 🚨 ModerationService

Service pour gérer la modération (signalements et sanctions).

### Workflow de modération

```
Report → Assign → Review → Resolve/Reject
                              ↓
                          Sanction (optionnel)
```

### Méthodes principales

#### `createReport(User $reporter, Model $reportable, string $reason): Report`
Crée un signalement pour un contenu.

```php
$moderationService = app(ModerationService::class);
$report = $moderationService->createReport($user, $post, 'Contenu inapproprié');
```

#### `assignReport(Report $report, User $moderator): Report`
Assigne un signalement à un modérateur.

```php
$report = $moderationService->assignReport($report, $moderator);
// Status: pending → reviewing
```

#### `resolveReport(Report $report, User $moderator, ?string $notes, bool $applyAction): Report`
Résout un signalement (valide).

```php
$report = $moderationService->resolveReport(
    $report,
    $moderator,
    'Violation confirmée',
    applyAction: true  // Masque le contenu
);
```

#### `rejectReport(Report $report, User $moderator, ?string $notes): Report`
Rejette un signalement (non fondé).

```php
$report = $moderationService->rejectReport($report, $moderator, 'Aucune violation');
```

### Sanctions

#### `warnUser(User $targetUser, User $moderator, string $reason): Sanction`
Crée un avertissement.

```php
$sanction = $moderationService->warnUser($targetUser, $moderator, 'Premier avertissement');
```

#### `muteUser(User $targetUser, User $moderator, string $reason, int $days = 7): Sanction`
Mute un utilisateur temporairement.

```php
$sanction = $moderationService->muteUser($targetUser, $moderator, 'Spam répété', 7);
```

#### `banUser(User $targetUser, User $moderator, string $reason, ?int $days = null): Sanction`
Ban un utilisateur (permanent ou temporaire).

```php
// Ban temporaire 30 jours
$sanction = $moderationService->banUser($targetUser, $moderator, 'Violations multiples', 30);

// Ban permanent
$sanction = $moderationService->banUser($targetUser, $moderator, 'Comportement grave');
```

#### `revokeSanction(Sanction $sanction, User $moderator): Sanction`
Révoque une sanction.

```php
$sanction = $moderationService->revokeSanction($sanction, $moderator);
```

### Statistiques et rapports

| Méthode | Description |
|---------|-------------|
| `getActiveSanctions()` | Sanctions actives d'un user |
| `getSanctionHistory()` | Historique sanctions |
| `getPriorityReports()` | Signalements prioritaires |
| `getModerationStats()` | Statistiques modération |
| `getTopModerators()` | Modérateurs les plus actifs |
| `hidePostWithReport()` | Masque post + résout rapports |

---

## 📝 TopicService

Service pour gérer les topics (débats, propositions de loi) et les posts.

### Méthodes principales

#### `createTopic(User $author, array $data): Topic`
Crée un nouveau topic.

```php
$topicService = app(TopicService::class);
$topic = $topicService->createTopic($user, [
    'title' => 'Débat sur la transition énergétique',
    'description' => '...',
    'type' => 'debate',
    'scope' => 'national',
]);
```

#### `createPost(Topic $topic, User $user, string $content, ?int $parentId = null): Post`
Crée un post dans un topic.

```php
// Post racine
$post = $topicService->createPost($topic, $user, 'Mon avis est...');

// Réponse
$reply = $topicService->createPost($topic, $user, 'Je suis d\'accord car...', $post->id);
```

#### `voteOnPost(Post $post, User $user, string $voteType): array`
Vote sur un post (upvote/downvote).

```php
$result = $topicService->voteOnPost($post, $user, 'upvote');
// [
//     'action' => 'added',  // 'added', 'removed', 'changed'
//     'vote_type' => 'upvote',
//     'score' => 42
// ]
```

#### Autres méthodes

| Méthode | Description |
|---------|-------------|
| `updateTopic()` | Met à jour un topic |
| `closeTopic()` | Ferme un topic |
| `archiveTopic()` | Archive un topic |
| `updatePost()` | Met à jour un post |
| `deletePost()` | Supprime un post |
| `deleteTopic()` | Supprime un topic |
| `getTrendingTopics()` | Topics populaires |
| `getTopPosts()` | Posts les mieux notés |
| `getReplies()` | Réponses d'un post |
| `getTopicStats()` | Statistiques d'un topic |

---

## 📄 DocumentService

Service pour gérer les documents et leur vérification.

### Méthodes principales

#### `uploadDocument(User $uploader, UploadedFile $file, Model $documentable, ?string $description): Document`
Upload un document et l'attache à un contenu.

```php
$documentService = app(DocumentService::class);
$document = $documentService->uploadDocument(
    $user,
    $request->file('document'),
    $topic,
    'Rapport officiel du Sénat'
);
```

**Sécurité :**
- Calcul SHA256 pour intégrité
- Détection de doublons
- Stockage sécurisé

#### `startVerification(Document $document, User $verifier): Verification`
Démarre une vérification de document.

```php
$verification = $documentService->startVerification($document, $journalist);
```

#### `approveDocument(Document $document, User $verifier, ?string $notes): array`
Approuve un document (vérification réussie).

```php
$result = $documentService->approveDocument($document, $journalist, 'Document authentique vérifié');
// [
//     'document' => Document (is_verified = true),
//     'verification' => Verification (status = 'verified')
// ]
```

#### `rejectDocument(Document $document, User $verifier, string $reason): Verification`
Rejette un document (vérification échouée).

```php
$verification = $documentService->rejectDocument($document, $journalist, 'Document non authentique');
```

#### `verifyIntegrity(Document $document): bool`
Vérifie l'intégrité d'un document (hash).

```php
$isValid = $documentService->verifyIntegrity($document);
```

#### Autres méthodes

| Méthode | Description |
|---------|-------------|
| `updateDescription()` | Met à jour description |
| `deleteDocument()` | Supprime document + fichier |
| `getVerificationHistory()` | Historique vérifications |
| `getPendingDocuments()` | Documents en attente |
| `getVerificationStats()` | Statistiques vérification |
| `getTopVerifiers()` | Vérificateurs actifs |
| `getDownloadUrl()` | URL de téléchargement |

---

## 🎯 Utilisation dans les Controllers

### Injection de dépendances

```php
use App\Services\BallotService;

class VoteController extends Controller
{
    public function __construct(
        protected BallotService $ballotService
    ) {}

    public function vote(Request $request, Topic $topic)
    {
        try {
            $token = $this->ballotService->generateToken(
                $request->user(),
                $topic
            );

            return response()->json(['token' => $token->token]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Résolution via Facade Service Container

```php
public function store(Request $request)
{
    $budgetService = app(BudgetService::class);
    
    $allocations = $budgetService->bulkAllocate(
        $request->user(),
        $request->validated('allocations')
    );

    return response()->json($allocations);
}
```

---

## 🧪 Tests des Services

Les services peuvent être testés unitairement :

```php
use App\Services\BallotService;

test('ballot service generates unique tokens', function () {
    $service = app(BallotService::class);
    $topic = Topic::factory()->withBallot()->create();
    $user = User::factory()->citizen()->create();

    $token = $service->generateToken($user, $topic);

    expect($token->token)->toBeString()
        ->and($token->token)->toHaveLength(128)
        ->and($token->isValid())->toBeTrue();
});

test('ballot service casts anonymous votes', function () {
    $service = app(BallotService::class);
    $topic = Topic::factory()->votingOpen()->create();
    $user = User::factory()->citizen()->create();
    
    $token = $service->generateToken($user, $topic);
    $ballot = $service->castVote($token->token, ['choice' => 'yes']);

    expect($ballot)->not->toHaveKey('user_id')
        ->and($ballot->encrypted_vote)->not->toBeNull();
});
```

---

## 🔐 Bonnes pratiques

### 1. Toujours utiliser les transactions

```php
return DB::transaction(function () use ($data) {
    // Logique métier complexe
    // En cas d'erreur, rollback automatique
});
```

### 2. Lancer des exceptions pour les erreurs métier

```php
if (!$user->can('vote', $topic)) {
    throw new RuntimeException('User cannot vote on this topic.');
}
```

### 3. Retourner des types cohérents

```php
// ✅ Retourne toujours le même type
public function create(): Model { ... }

// ❌ Ne pas mélanger les types de retour
public function create(): Model|array { ... }
```

### 4. Documenter les exceptions

```php
/**
 * @throws RuntimeException Si le user ne peut pas voter
 * @throws RuntimeException Si le token est invalide
 */
public function castVote(string $token, array $vote): TopicBallot { ... }
```

### 5. Encapsuler la logique complexe

```php
// ✅ Logique métier dans le service
$result = $ballotService->calculateResults($topic);

// ❌ Logique métier dans le controller
$ballots = TopicBallot::where('topic_id', $topic->id)->get();
$results = [];
foreach ($ballots as $ballot) { ... }
```

---

## 📊 Récapitulatif

| Service | Fichier | Méthodes | Focus |
|---------|---------|----------|-------|
| **BallotService** | `BallotService.php` | 11 | **Vote anonyme critique** |
| **BudgetService** | `BudgetService.php` | 13 | Allocations budgétaires |
| **ModerationService** | `ModerationService.php` | 17 | Signalements et sanctions |
| **TopicService** | `TopicService.php` | 13 | Topics et posts |
| **DocumentService** | `DocumentService.php` | 12 | Upload et vérification |
| **TOTAL** | **5 services** | **66 méthodes** | **Logique métier complète** |

---

## 🔗 Liens utiles

- [Policies](../docs/POLICIES.md) - Autorisations
- [Models](../docs/MODELS.md) - Modèles Eloquent
- [Tests](../docs/TESTS.md) - Tests Pest
- [Laravel Services](https://laravel.com/docs/11.x/container)

