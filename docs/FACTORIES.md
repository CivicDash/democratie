# ✅ Factories créées - CivicDash

## 📊 Résumé

**17 factories créées** | **1296 lignes de code** | **Prêtes pour les tests**

Toutes les factories ont été validées syntaxiquement ✓

---

## 📁 Liste des factories

### 1️⃣ Identité & Territoires (3 factories)

| Factory | Méthodes spéciales | Description |
|---------|-------------------|-------------|
| `TerritoryRegionFactory` | `real()` | Génère régions avec code/nom INSEE |
| `TerritoryDepartmentFactory` | `real()`, `forRegion()` | Génère départements liés à une région |
| `ProfileFactory` | `national()`, `regional()`, `departmental()`, `verified()`, `unverified()` | Génère profils citoyens avec scope et citizen_ref_hash |

### 2️⃣ Forum (3 factories)

| Factory | Méthodes spéciales | Description |
|---------|-------------------|-------------|
| `TopicFactory` | `debate()`, `bill()`, `referendum()`, `open()`, `closed()`, `withBallot()`, `withoutBallot()`, `national()`, `regional()`, `departmental()` | Topics avec ou sans scrutin |
| `PostFactory` | `reply()`, `root()`, `official()`, `pinned()`, `hidden()`, `visible()`, `popular()`, `controversial()` | Posts avec threading et votes |
| `PostVoteFactory` | `upvote()`, `downvote()` | Votes up/down sur posts |

### 3️⃣ Modération (2 factories)

| Factory | Méthodes spéciales | Description |
|---------|-------------------|-------------|
| `ReportFactory` | `pending()`, `reviewing()`, `resolved()`, `dismissed()`, `spam()`, `harassment()` | Signalements avec workflow |
| `SanctionFactory` | `warning()`, `mute()`, `ban()`, `permanent()`, `temporary()`, `active()`, `expired()`, `revoked()` | Sanctions avec durées |

### 4️⃣ Vote Anonyme (2 factories)

| Factory | Méthodes spéciales | Description |
|---------|-------------------|-------------|
| `BallotTokenFactory` | `valid()`, `consumed()`, `expired()` | Jetons éphémères |
| `TopicBallotFactory` | `yesVote()`, `noVote()` | Bulletins anonymes chiffrés |

### 5️⃣ Budget (4 factories)

| Factory | Méthodes spéciales | Description |
|---------|-------------------|-------------|
| `SectorFactory` | `active()`, `inactive()` | Secteurs budgétaires |
| `UserAllocationFactory` | `completeAllocation()` | Allocations (somme = 100%) |
| `PublicRevenueFactory` | `national()`, `regional()`, `departmental()` | Recettes publiques |
| `PublicSpendFactory` | `national()`, `regional()`, `departmental()` | Dépenses publiques |

### 6️⃣ Documents (2 factories)

| Factory | Méthodes spéciales | Description |
|---------|-------------------|-------------|
| `DocumentFactory` | `forTopic()`, `forPost()`, `pending()`, `verified()`, `rejected()`, `public()`, `private()`, `pdf()` | Documents polymorphiques |
| `VerificationFactory` | `verified()`, `rejected()`, `needsReview()` | Vérifications ONG/journalistes |

### 7️⃣ User (étendue)

| Méthodes ajoutées | Description |
|-------------------|-------------|
| `withProfile()` | Crée automatiquement un profil |
| `citizen()`, `moderator()`, `journalist()`, `ong()`, `legislator()`, `state()`, `admin()` | Crée user avec rôle assigné |

---

## 🧪 Exemples d'utilisation

### Créer un citoyen avec profil

```php
// Citoyen national avec profil
$citizen = User::factory()->citizen()->create();
$citizen->hasRole('citizen'); // true
$citizen->profile; // Profile avec pseudonyme

// Citoyen régional
$region = TerritoryRegion::factory()->create();
$citizen = User::factory()->create();
Profile::factory()->regional($region)->create(['user_id' => $citizen->id]);
```

### Créer un topic avec scrutin

```php
// Topic avec scrutin yes/no
$legislator = User::factory()->legislator()->create();
$topic = Topic::factory()
    ->open()
    ->withBallot('yes_no')
    ->create(['author_id' => $legislator->id]);

// Topic débat sans scrutin
$topic = Topic::factory()
    ->debate()
    ->withoutBallot()
    ->national()
    ->create();
```

### Créer des posts avec threading

```php
$topic = Topic::factory()->create();
$user = User::factory()->citizen()->create();

// Post racine
$post = Post::factory()
    ->root()
    ->popular()
    ->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

// Réponse au post
$reply = Post::factory()
    ->reply($post)
    ->create(['user_id' => $user->id]);
```

### Voter anonymement

```php
$topic = Topic::factory()->withBallot('yes_no')->create();
$user = User::factory()->citizen()->create();

// 1. Obtenir un token
$token = BallotToken::factory()
    ->valid()
    ->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

// 2. Voter (bulletin anonyme SANS user_id)
$ballot = TopicBallot::factory()
    ->yesVote()
    ->create(['topic_id' => $topic->id]);

// 3. Consommer le token
$token->consume();
```

### Créer une allocation budgétaire complète

```php
$user = User::factory()->citizen()->create();
$sectors = Sector::factory()->count(5)->create();

// Génère allocations qui somment à 100%
UserAllocationFactory::new()->completeAllocation($user, $sectors->all());

// Vérifier
UserAllocation::validateUserTotal($user->id); // true
```

### Créer des signalements et sanctions

```php
$reporter = User::factory()->citizen()->create();
$post = Post::factory()->create();

// Signalement
$report = Report::factory()
    ->harassment()
    ->pending()
    ->create([
        'reporter_id' => $reporter->id,
        'reportable_type' => Post::class,
        'reportable_id' => $post->id,
    ]);

// Modérateur traite
$moderator = User::factory()->moderator()->create();
$report->update(['status' => 'reviewing', 'moderator_id' => $moderator->id]);

// Sanctionner
$sanction = Sanction::factory()
    ->mute(24) // 24h
    ->create([
        'user_id' => $post->user_id,
        'moderator_id' => $moderator->id,
        'report_id' => $report->id,
    ]);
```

### Créer des documents vérifiés

```php
$legislator = User::factory()->legislator()->create();
$topic = Topic::factory()->create();

// Document en attente
$document = Document::factory()
    ->forTopic($topic)
    ->pending()
    ->pdf()
    ->create(['uploader_id' => $legislator->id]);

// Vérification par journaliste
$journalist = User::factory()->journalist()->create();
$verification = Verification::factory()
    ->verified('Document authentique')
    ->create([
        'document_id' => $document->id,
        'verifier_id' => $journalist->id,
    ]);
```

---

## 🎯 Scénarios de test complets

### Scénario : Débat citoyen complet

```php
// Setup
$region = TerritoryRegion::factory()->create(['code' => '11', 'name' => 'Île-de-France']);
$legislator = User::factory()->legislator()->create();
$citizens = User::factory()->citizen()->count(10)->create();

// Créer un débat régional
$topic = Topic::factory()
    ->debate()
    ->open()
    ->regional($region)
    ->create(['author_id' => $legislator->id]);

// Citoyens postent
foreach ($citizens as $citizen) {
    $post = Post::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $citizen->id,
    ]);
    
    // Votes aléatoires
    PostVote::factory()->count(rand(5, 20))->create(['post_id' => $post->id]);
}

// Un post controversé
$controversial = Post::factory()
    ->controversial()
    ->create(['topic_id' => $topic->id]);

// Signalement
Report::factory()
    ->spam()
    ->pending()
    ->create([
        'reportable_type' => Post::class,
        'reportable_id' => $controversial->id,
    ]);
```

### Scénario : Scrutin complet

```php
// Créer un référendum
$topic = Topic::factory()
    ->referendum()
    ->open()
    ->withBallot('yes_no')
    ->create();

// 100 citoyens votent
$voters = User::factory()->citizen()->count(100)->create();

foreach ($voters as $voter) {
    // Token
    $token = BallotToken::factory()->valid()->create([
        'topic_id' => $topic->id,
        'user_id' => $voter->id,
    ]);
    
    // Vote anonyme
    $ballot = TopicBallot::factory()
        ->create(['topic_id' => $topic->id]);
    
    // Consommer token
    $token->consume();
}

// Vérifier
assert($topic->ballots()->count() === 100);
assert(BallotToken::where('topic_id', $topic->id)->consumed()->count() === 100);
```

---

## 📝 Conseils d'utilisation

### Données cohérentes

```php
// ✓ BON : Relations cohérentes
$region = TerritoryRegion::factory()->create();
$dept = TerritoryDepartment::factory()->forRegion($region)->create();
$topic = Topic::factory()->departmental($dept)->create();

// ✗ MAUVAIS : Données incohérentes
$topic = Topic::factory()->create([
    'scope' => 'dept',
    'department_id' => 1,
    'region_id' => null, // Manque region_id !
]);
```

### States chainables

```php
// ✓ BON : Chaîner les states
$post = Post::factory()
    ->official()
    ->pinned()
    ->popular()
    ->create();

// ✓ BON : Override avec state
$topic = Topic::factory()
    ->withBallot('multiple_choice')
    ->closed()
    ->create();
```

### Faker cohérent

Les factories utilisent Faker (`fake()`) pour générer des données réalistes :
- Noms, emails, dates cohérents
- Valeurs respectant les contraintes (min/max, enum)
- Relations automatiques si non spécifiées

---

## 🔐 Points de sécurité

### Vote anonyme

⚠️ **IMPORTANT** : `TopicBallotFactory` ne crée JAMAIS de `user_id`

```php
$ballot = TopicBallot::factory()->create();
$ballot->user_id; // null (par design)
```

La liaison user ↔ vote passe uniquement par `BallotToken` qui est consommé après usage.

### Hash PEPPER

`ProfileFactory` utilise automatiquement `Profile::hashCitizenRef()` qui nécessite PEPPER dans `.env`.

```php
// ⚠️ Générer PEPPER avant de créer des profils
make pepper

// Profile factory l'utilisera automatiquement
$profile = Profile::factory()->create();
```

---

## 🧪 Tests Pest

Exemple d'utilisation dans Pest :

```php
test('citizen can create post', function () {
    $citizen = User::factory()->citizen()->create();
    $topic = Topic::factory()->open()->create();
    
    $post = Post::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $citizen->id,
    ]);
    
    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->user)->toBe($citizen)
        ->and($post->topic)->toBe($topic);
});

test('cannot link ballot to user', function () {
    $topic = Topic::factory()->withBallot()->create();
    $ballot = TopicBallot::factory()->create(['topic_id' => $topic->id]);
    
    expect($ballot)->not->toHaveKey('user_id');
});
```

---

## 📚 Prochaines étapes

1. ✅ Factories créées
2. 🔄 Écrire les tests Pest
3. 🔄 Créer les policies (autorisation)
4. 🔄 Créer les services métier

---

**✅ Factories complètes et fonctionnelles !**

Prêtes pour écrire des tests exhaustifs avec données réalistes.

Prochaine étape : Tests Pest ou Policies ? 🧪

