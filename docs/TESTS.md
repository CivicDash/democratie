# Tests CivicDash

Cette documentation décrit la suite de tests complète de **CivicDash** avec Pest.

## 📁 Structure des tests

```
tests/
├── Unit/                      # Tests unitaires des modèles
│   ├── ProfileTest.php
│   ├── TopicTest.php
│   ├── PostTest.php
│   ├── TerritoryTest.php
│   ├── SectorTest.php
│   └── UserTest.php
├── Feature/                   # Tests fonctionnels
│   ├── Auth/
│   │   └── PermissionsTest.php
│   ├── Vote/
│   │   └── AnonymousVotingTest.php
│   ├── Budget/
│   │   └── BudgetAllocationTest.php
│   ├── Moderation/
│   │   └── ModerationWorkflowTest.php
│   └── Documents/
│       └── DocumentVerificationTest.php
└── Pest.php                   # Configuration Pest
```

## 🧪 Catégories de tests

### 1. Tests Unitaires (Unit)

Tests des modèles Eloquent, leurs relations, méthodes et scopes.

#### ProfileTest.php (23 tests)
- ✅ Génération de noms d'affichage aléatoires
- ✅ Hash du citizen_ref avec PEPPER
- ✅ Validation de la configuration PEPPER
- ✅ Scopes territoriaux (national, régional, départemental)
- ✅ Relations (user, region, department)
- ✅ Vérification (verified_at)
- ✅ Unicité du citizen_ref_hash

#### TopicTest.php (18 tests)
- ✅ Topics avec/sans scrutin
- ✅ Dates d'ouverture et fermeture de vote
- ✅ Révélation des résultats après deadline
- ✅ Relations (posts, author)
- ✅ Types (debate, bill, referendum)
- ✅ Scopes territoriaux

#### PostTest.php (22 tests)
- ✅ Réponses (threading)
- ✅ Calcul du score (upvotes - downvotes)
- ✅ Masquage de posts (modération)
- ✅ Posts épinglés et officiels
- ✅ Incrémentation des votes
- ✅ Unicité du vote par user/post
- ✅ Scopes (visible, official)

#### TerritoryTest.php (4 tests)
- ✅ Relations région/départements
- ✅ Unicité des codes INSEE

#### SectorTest.php (3 tests)
- ✅ Unicité du nom
- ✅ Icône et couleur
- ✅ Description

#### UserTest.php (13 tests)
- ✅ Profil utilisateur
- ✅ Relations (sanctions, topics, posts, votes, tokens, allocations, documents, verifications, reports)
- ✅ Helpers (isMuted, isBanned, canPost)

---

### 2. Tests de Vote Anonyme (Feature/Vote)

**CRITIQUE** : Ces tests vérifient l'intégrité du système de vote anonyme.

#### AnonymousVotingTest.php (18 tests)
- ✅ Génération de tokens uniques
- ✅ Validation des tokens (expiré, consommé)
- ✅ Consommation d'un token
- ✅ Un token par user/topic
- ✅ **TopicBallot ne contient JAMAIS de user_id**
- ✅ **Impossible de lier un bulletin à un user**
- ✅ Chiffrement/déchiffrement des votes
- ✅ Unicité du vote_hash
- ✅ **Workflow complet de vote anonyme**
- ✅ User ne peut pas voter deux fois
- ✅ User peut voter sur différents topics
- ✅ Révélation des résultats après deadline
- ✅ Helpers User (canVoteOn, hasVotedOn)

---

### 3. Tests de Budget Participatif (Feature/Budget)

#### BudgetAllocationTest.php (15 tests)
- ✅ Contraintes min/max d'allocation par secteur
- ✅ Validation des allocations dans les contraintes
- ✅ Allocation totale = 100%
- ✅ Impossible de dépasser 100%
- ✅ Unicité user/sector
- ✅ Mise à jour d'allocation
- ✅ Helper hasCompletedBudgetAllocation
- ✅ Relations PublicRevenue et PublicSpend
- ✅ Agrégation des allocations par secteur

---

### 4. Tests de Modération (Feature/Moderation)

#### ModerationWorkflowTest.php (28 tests)
- ✅ Signalement de posts et topics
- ✅ Workflow : pending → reviewing → resolved/rejected
- ✅ Résolution et rejet de rapports
- ✅ Masquage de posts
- ✅ Sanctions (warning, mute, ban)
- ✅ Expiration des sanctions
- ✅ Révocation de sanctions
- ✅ Helpers User (isMuted, isBanned, canPost)
- ✅ Multiples sanctions par user
- ✅ Comptage des rapports actifs
- ✅ Priorisation des posts avec multiples signalements
- ✅ Relations moderator/reporter

---

### 5. Tests de Permissions RBAC (Feature/Auth)

#### PermissionsTest.php (30+ tests)
- ✅ Création des 7 rôles
- ✅ Permissions par rôle :
  - **citizen** : topics, posts, votes, budget
  - **moderator** : modération, sanctions
  - **journalist** : vérification de documents
  - **ong** : vérification de documents
  - **legislator** : création de bills
  - **state** : publication budget
  - **admin** : toutes permissions
- ✅ Rôles multiples
- ✅ Permissions directes
- ✅ Révocation de permissions/rôles
- ✅ Middleware de permission
- ✅ Middleware de rôle
- ✅ Vérification de l'existence de toutes les permissions
- ✅ Scopes par rôle
- ✅ Vérification de profil (is_verified)

---

### 6. Tests de Documents (Feature/Documents)

#### DocumentVerificationTest.php (10 tests)
- ✅ Attachement polymorphique (topic, post)
- ✅ Hash SHA256 pour intégrité
- ✅ Unicité du hash
- ✅ Vérification par journaliste
- ✅ Vérification par ONG
- ✅ Rejet de vérification
- ✅ Multiples tentatives de vérification
- ✅ Relation uploader
- ✅ Priorisation des documents vérifiés

---

## 🚀 Commandes

### Lancer tous les tests
```bash
make test
```

### Lancer une catégorie spécifique
```bash
php artisan test tests/Unit
php artisan test tests/Feature/Vote
php artisan test tests/Feature/Budget
```

### Lancer un fichier spécifique
```bash
php artisan test tests/Feature/Vote/AnonymousVotingTest.php
```

### Coverage
```bash
make test-coverage
```

### Tests avec parallélisation
```bash
php artisan test --parallel
```

---

## 📊 Statistiques

| Catégorie | Fichiers | Tests | Focus |
|-----------|----------|-------|-------|
| **Unit** | 6 | ~86 | Modèles et relations |
| **Feature/Vote** | 1 | 18 | **Anonymat critique** |
| **Feature/Budget** | 1 | 15 | Contraintes budgétaires |
| **Feature/Moderation** | 1 | 28 | Workflow modération |
| **Feature/Auth** | 1 | 30+ | RBAC et permissions |
| **Feature/Documents** | 1 | 10 | Vérification |
| **TOTAL** | **11** | **~187+** | Couverture complète |

---

## ⚠️ Tests critiques

Ces tests sont **ESSENTIELS** pour la sécurité et l'intégrité de CivicDash :

### 1. Anonymat des votes
```php
// tests/Feature/Vote/AnonymousVotingTest.php
test('topic ballot does NOT have user_id column')
test('topic ballot cannot be linked to user')
test('complete anonymous voting workflow')
test('user cannot vote twice on same topic')
```

### 2. Contraintes budgétaires
```php
// tests/Feature/Budget/BudgetAllocationTest.php
test('user total allocation must equal 100 percent')
test('user cannot allocate more than 100 percent total')
```

### 3. Intégrité des documents
```php
// tests/Feature/Documents/DocumentVerificationTest.php
test('document sha256 hash is unique')
```

---

## 🔧 Configuration Pest

Le fichier `tests/Pest.php` configure les traits et helpers globaux :

```php
<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
```

---

## 📝 Conventions

### Nommage des tests
- Phrase descriptive en anglais
- Commence par un verbe : `test('user can vote on topic')`
- Un seul concept par test

### Structure des tests
```php
test('description du comportement', function () {
    // 1. Arrange (préparation)
    $user = User::factory()->create();
    
    // 2. Act (action)
    $result = $user->doSomething();
    
    // 3. Assert (vérification)
    expect($result)->toBeTrue();
});
```

### Factories
Utiliser les states pour des scénarios réalistes :
```php
User::factory()->citizen()->create();
Topic::factory()->withBallot()->create();
Post::factory()->hidden()->create();
```

---

## 🎯 Prochaines étapes

Une fois tous les tests passent :

1. **Services** : BallotService, BudgetService, ModerationService
2. **Controllers** : API et Web
3. **Policies** : Autorisation fine
4. **Jobs** : Queue pour tâches lourdes
5. **Events** : Notification et audit
6. **Frontend** : Inertia + Vue 3

---

## 📖 Ressources

- [Pest Documentation](https://pestphp.com/)
- [Laravel Testing](https://laravel.com/docs/11.x/testing)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [CivicDash Setup](../docs/SETUP.md)

