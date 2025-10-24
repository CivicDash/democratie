# ✅ Modèles Eloquent créés - CivicDash

## 📊 Résumé

**17 modèles créés** | **2000+ lignes de code** | **Relations complètes**

Tous les modèles ont été validés syntaxiquement ✓

---

## 📁 Liste des modèles

### 1️⃣ Identité & Territoires (3 modèles)

| Modèle | Description | Relations principales |
|--------|-------------|----------------------|
| `TerritoryRegion` | Régions françaises | departments, profiles, topics, publicRevenue, publicSpend |
| `TerritoryDepartment` | Départements français | region, profiles, topics, publicRevenue, publicSpend |
| `Profile` | Profils citoyens | user, region, department |

**Méthodes clés** :
- `Profile::generateDisplayName()` - Génère un pseudonyme aléatoire
- `Profile::hashCitizenRef()` - Hash référence citoyenne + PEPPER
- Scopes : `verified()`, `national()`, `regional()`, `departmental()`

### 2️⃣ Forum & Discussions (3 modèles)

| Modèle | Description | Relations principales |
|--------|-------------|----------------------|
| `Topic` | Sujets/lois/référendums | author, region, department, posts, ballotTokens, ballots, documents |
| `Post` | Messages avec threading | topic, user, parent, replies, votes, documents |
| `PostVote` | Votes up/down | post, user |

**Méthodes clés** :
- `Topic::isVotingOpen()` - Vérifie si scrutin ouvert
- `Topic::canRevealResults()` - Vérifie si révélation possible
- `Post::hide()` / `Post::unhide()` - Masquer/afficher post
- `Post::incrementUpvotes()` / `Post::incrementDownvotes()`
- Scopes : `open()`, `withActiveVoting()`, `debates()`, `bills()`, `referendums()`

### 3️⃣ Modération (2 modèles)

| Modèle | Description | Relations principales |
|--------|-------------|----------------------|
| `Report` | Signalements | reporter, moderator, reportable (polymorphic) |
| `Sanction` | Warnings/mutes/bans | user, moderator, report |

**Méthodes clés** :
- `Report::assignModerator()` - Assigne un modérateur
- `Report::resolve()` / `Report::dismiss()` - Résoudre/rejeter
- `Sanction::isExpired()`, `Sanction::isPermanent()`, `Sanction::revoke()`
- Scopes : `pending()`, `active()`, `expired()`, `warnings()`, `mutes()`, `bans()`

### 4️⃣ Vote Anonyme ⚠️ (2 modèles)

| Modèle | Description | Relations principales |
|--------|-------------|----------------------|
| `BallotToken` | Jetons éphémères | topic, user |
| `TopicBallot` | **Bulletins SANS user_id** | topic |

**Méthodes clés** :
- `BallotToken::generateToken()` - Génère jeton signé SHA512
- `BallotToken::isValid()`, `BallotToken::consume()`
- `TopicBallot::encryptVote()` / `TopicBallot::decryptVote()` - Chiffrement Laravel Crypt
- `TopicBallot::cast()` - **Crée bulletin anonyme (SANS user_id)**
- Scopes : `valid()`, `consumed()`, `expired()`

**⚠️ CRITIQUE** : `TopicBallot` ne contient AUCUNE relation `user()` pour garantir l'anonymat.

### 5️⃣ Budget Participatif (4 modèles)

| Modèle | Description | Relations principales |
|--------|-------------|----------------------|
| `Sector` | Secteurs budgétaires | allocations, publicSpend |
| `UserAllocation` | Répartitions citoyennes | user, sector |
| `PublicRevenue` | Recettes publiques | region, department |
| `PublicSpend` | Dépenses publiques | region, department, sector |

**Méthodes clés** :
- `Sector::isPercentValid()` - Vérifie % dans limites min/max
- `Sector::averageAllocation()` - Moyenne allocations citoyennes
- `UserAllocation::validateUserTotal()` - Vérifie somme = 100%
- `PublicSpend::calculatePercentBySector()` - Calcule % dépenses par secteur
- Scopes : `active()`, `ordered()`, `forYear()`, `national()`, `regional()`, `departmental()`

### 6️⃣ Documents Vérifiés (2 modèles)

| Modèle | Description | Relations principales |
|--------|-------------|----------------------|
| `Document` | Pièces jointes | uploader, documentable (polymorphic), verifications |
| `Verification` | Vérifications ONG/journalistes | document, verifier |

**Méthodes clés** :
- `Document::hashFile()` - Hash SHA256
- `Document::getHumanSizeAttribute()` - Taille human-readable
- `Document::isVerified()`, `Document::isPending()`, `Document::isRejected()`
- Scopes : `public()`, `verified()`, `pending()`, `pdf()`

### 7️⃣ User (modèle étendu)

**Relations ajoutées** (13 relations) :
- `profile()` (HasOne)
- `topics()`, `posts()`, `postVotes()`
- `reportsCreated()`, `reportsHandled()`, `sanctions()`, `sanctionsGiven()`
- `ballotTokens()`, `allocations()`, `documents()`, `verifications()`

**Méthodes helpers** :
- `isMuted()`, `isBanned()`, `canPost()`
- `canVoteOn(Topic)`, `hasVotedOn(Topic)`
- `activeReportsCount()`, `hasCompletedBudgetAllocation()`

**Trait ajouté** : `HasRoles` (Spatie Permission)

---

## 🔗 Graphe des relations

```
User
├─ profile (1:1) → Profile
│   ├─ region → TerritoryRegion
│   └─ department → TerritoryDepartment
├─ topics (1:N) → Topic
│   ├─ posts (1:N) → Post
│   │   ├─ votes (1:N) → PostVote
│   │   └─ reports (1:N polymorphic)
│   ├─ ballotTokens (1:N) → BallotToken
│   ├─ ballots (1:N) → TopicBallot ⚠️ (ANONYME)
│   └─ documents (1:N polymorphic)
├─ allocations (1:N) → UserAllocation
│   └─ sector → Sector
├─ sanctions (1:N) → Sanction
│   └─ report → Report
└─ documents (1:N) → Document
    └─ verifications (1:N) → Verification
```

---

## 🔐 Points de sécurité implémentés

### ⚠️ Anonymat des votes

```php
// BallotToken : liaison temporaire
class BallotToken {
    public function user() // ✓ Relation user
    public function consume() // Consomme le token
}

// TopicBallot : PAS de relation user !
class TopicBallot {
    // ⚠️ AUCUNE méthode user() !
    public static function cast(int $topicId, mixed $vote) // Crée ballot anonyme
}
```

### 🔒 Hash citoyen sécurisé

```php
Profile::hashCitizenRef($citizenRef);
// hash('sha256', $citizenRef . config('app.pepper'))
```

### 🛡️ Modération

```php
$user->isMuted();
$user->isBanned();
$user->canPost(); // false si muted/banned
```

### 📊 Validation budget

```php
UserAllocation::validateUserTotal($userId); // somme = 100%
$sector->isPercentValid($percent); // min <= % <= max
```

---

## 📚 Scopes utiles

### Territoriaux
```php
Profile::national()->get();
Profile::regional($regionId)->get();
Profile::departmental($deptId)->get();

Topic::byScope('region')->get();
PublicRevenue::forYear(2024)->national()->get();
```

### Forum
```php
Topic::open()->debates()->get();
Topic::withActiveVoting()->get();
Post::visible()->root()->orderByScore()->get();
```

### Modération
```php
Report::pending()->unassigned()->get();
Sanction::active()->mutes()->get();
```

### Vote
```php
BallotToken::valid()->forTopic($topicId)->get();
BallotToken::consumed()->get();
```

### Budget
```php
Sector::active()->ordered()->get();
PublicSpend::forYear(2024)->forSector($sectorId)->get();
```

---

## 🧪 Exemples d'utilisation

### Créer un profil citoyen

```php
$user = User::create([...]);

$profile = Profile::create([
    'user_id' => $user->id,
    'display_name' => Profile::generateDisplayName(),
    'citizen_ref_hash' => Profile::hashCitizenRef($citizenRef),
    'scope' => 'national',
    'is_verified' => false,
]);
```

### Voter anonymement

```php
// 1. Obtenir un token
$token = BallotToken::create([
    'topic_id' => $topic->id,
    'user_id' => $user->id,
    'token' => BallotToken::generateToken(),
    'expires_at' => $topic->voting_deadline_at,
]);

// 2. Voter (consomme le token, crée ballot SANS user_id)
$ballot = TopicBallot::cast($topic->id, ['choice' => 'yes']);
$token->consume();

// 3. Vérifier
$user->hasVotedOn($topic); // true
```

### Répartir son budget

```php
$allocations = [
    ['sector_id' => 1, 'percent' => 30], // Éducation
    ['sector_id' => 2, 'percent' => 25], // Santé
    ['sector_id' => 3, 'percent' => 20], // Écologie
    ['sector_id' => 4, 'percent' => 15], // Défense
    ['sector_id' => 5, 'percent' => 10], // Culture
];

foreach ($allocations as $alloc) {
    UserAllocation::create([
        'user_id' => $user->id,
        ...$alloc
    ]);
}

// Valider
UserAllocation::validateUserTotal($user->id); // true
```

### Modération

```php
// Signaler un post
$report = Report::create([
    'reporter_id' => $reporter->id,
    'reportable_type' => Post::class,
    'reportable_id' => $post->id,
    'reason' => 'harassment',
    'description' => '...',
]);

// Modérateur traite
$report->assignModerator($moderator);
$report->resolve('Post masqué');

// Sanctionner
Sanction::create([
    'user_id' => $post->user_id,
    'moderator_id' => $moderator->id,
    'report_id' => $report->id,
    'type' => 'mute',
    'reason' => 'Harcèlement',
    'starts_at' => now(),
    'expires_at' => now()->addDays(7),
]);
```

---

## 🚀 Prochaines étapes

1. ✅ Modèles créés et validés
2. 🔄 Créer les factories pour tests
3. 🔄 Créer les seeders (rôles, territoires, secteurs)
4. 🔄 Créer les policies (autorisation)
5. 🔄 Créer les services métier (BallotService, BudgetService)
6. 🔄 Créer les controllers
7. 🔄 Écrire les tests

---

**✅ Tous les modèles Eloquent sont prêts !**

Les relations, casts, scopes et méthodes helpers sont implémentés.
Prochaine étape : seeders ou factories ?

