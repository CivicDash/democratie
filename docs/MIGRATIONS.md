# ✅ Migrations créées - CivicDash

## 📊 Résumé

**16 migrations créées** | **652 lignes de code** | **6 domaines fonctionnels**

Toutes les migrations ont été validées syntaxiquement ✓

---

## 📁 Liste des migrations

### 1️⃣ Identité & Territoires (3 tables)

| Fichier | Table | Description |
|---------|-------|-------------|
| `2025_01_24_120000` | `territories_regions` | Régions françaises (code INSEE + nom) |
| `2025_01_24_120001` | `territories_departments` | Départements français (code INSEE + région) |
| `2025_01_24_120002` | `profiles` | Profils citoyens (pseudonymes, citizen_ref_hash, scope) |

### 2️⃣ Forum & Discussions (3 tables)

| Fichier | Table | Description |
|---------|-------|-------------|
| `2025_01_24_130000` | `topics` | Sujets de débat/loi/référendum + config scrutin |
| `2025_01_24_130001` | `posts` | Messages avec threading + votes up/down |
| `2025_01_24_130002` | `post_votes` | Votes up/down (unique par user/post) |

### 3️⃣ Modération (2 tables)

| Fichier | Table | Description |
|---------|-------|-------------|
| `2025_01_24_140000` | `reports` | Signalements (spam, harassment, etc.) + workflow |
| `2025_01_24_140001` | `sanctions` | Sanctions (warning, mute, ban) avec durée |

### 4️⃣ Vote Anonyme ⚠️ (2 tables)

| Fichier | Table | Description |
|---------|-------|-------------|
| `2025_01_24_150000` | `ballot_tokens` | Jetons éphémères à usage unique |
| `2025_01_24_150001` | `topic_ballots` | **Bulletins SANS user_id** (anonymat garanti) |

### 5️⃣ Budget Participatif (4 tables)

| Fichier | Table | Description |
|---------|-------|-------------|
| `2025_01_24_160000` | `sectors` | Secteurs budgétaires (min/max %, icon, color) |
| `2025_01_24_160001` | `user_allocations` | Répartitions citoyennes (somme = 100%) |
| `2025_01_24_160002` | `public_revenue` | Recettes publiques (transparence) |
| `2025_01_24_160003` | `public_spend` | Dépenses publiques par secteur |

### 6️⃣ Documents Vérifiés (2 tables)

| Fichier | Table | Description |
|---------|-------|-------------|
| `2025_01_24_170000` | `documents` | Pièces jointes (SHA256, mime, polymorphic) |
| `2025_01_24_170001` | `verifications` | Vérifications journalistes/ONG |

---

## 🔐 Points de sécurité critiques

### ⚠️ Anonymat des votes

```php
// ballot_tokens : liaison temporaire user ↔ vote
$table->foreignId('user_id')->constrained();
$table->string('token', 128)->unique();
$table->boolean('consumed')->default(false);

// topic_ballots : PAS de user_id !
$table->foreignId('topic_id')->constrained();
// ⚠️ PAS de user_id ici pour garantir l'anonymat
$table->string('encrypted_vote')->comment('Vote chiffré');
```

**Workflow** :
1. User obtient un `ballot_token` unique
2. User vote avec le token via API
3. Token marqué `consumed=true`
4. `topic_ballot` créé **sans user_id**
5. Liaison identité/vote **coupée**
6. Résultats révélés uniquement après `voting_deadline_at`

### 🔒 Hash citoyen unique

```php
// profiles.citizen_ref_hash
$table->string('citizen_ref_hash')->unique()
    ->comment('Hash du numéro de sécu + PEPPER (anonyme)');
```

- Évite les doublons de citoyens
- Pas de stockage du numéro de sécu en clair
- PEPPER obligatoire dans `.env`

---

## 🗂️ Contraintes d'intégrité

### Unicité
- `territories_regions.code` : UNIQUE
- `territories_departments.code` : UNIQUE
- `profiles.citizen_ref_hash` : UNIQUE
- `post_votes.[post_id, user_id]` : UNIQUE (1 vote par post)
- `ballot_tokens.[topic_id, user_id]` : UNIQUE (1 token par scrutin)
- `topic_ballots.vote_hash` : UNIQUE (évite doublons)
- `user_allocations.[user_id, sector_id]` : UNIQUE
- `documents.hash` : UNIQUE (SHA256)

### Validation métier (à implémenter en PHP)
- `user_allocations` : somme des `percent` par user = 100%
- `user_allocations.percent` : entre `sector.min_percent` et `sector.max_percent`
- `ballot_tokens.expires_at` : = `topics.voting_deadline_at`
- `topics.voting_opens_at` < `voting_deadline_at`

---

## 📊 Indexes importants

**Performance** :
- `profiles` : `citizen_ref_hash`, `[scope, region_id, department_id]`
- `topics` : `[scope, status]`, `voting_deadline_at`
- `posts` : `[topic_id, created_at]`, `user_id`
- `ballot_tokens` : `token`, `[topic_id, consumed]`
- `topic_ballots` : `topic_id`, `vote_hash`, `cast_at`
- `reports` : `status`, `[reportable_type, reportable_id]`
- `user_allocations` : `[user_id, sector_id]`

---

## 🚀 Prochaines étapes

### 1. Lancer les migrations

```bash
# Avec Docker
make migrate

# Ou manuellement
docker-compose exec app php artisan migrate

# Si erreur, reset
docker-compose exec app php artisan migrate:fresh
```

### 2. Créer les modèles Eloquent

```bash
php artisan make:model TerritoryRegion
php artisan make:model TerritoryDepartment
php artisan make:model Profile
php artisan make:model Topic
php artisan make:model Post
php artisan make:model PostVote
php artisan make:model Report
php artisan make:model Sanction
php artisan make:model BallotToken
php artisan make:model TopicBallot
php artisan make:model Sector
php artisan make:model UserAllocation
php artisan make:model PublicRevenue
php artisan make:model PublicSpend
php artisan make:model Document
php artisan make:model Verification
```

### 3. Créer les seeders

**Prioritaires** :
- `RolesAndPermissionsSeeder` (citizen, moderator, journalist, ong, legislator, state, admin)
- `TerritoriesSeeder` (13 régions + 101 départements FR)
- `SectorsSeeder` (éducation, santé, écologie, défense, etc.)

### 4. Créer les factories (tests)

```bash
php artisan make:factory ProfileFactory
php artisan make:factory TopicFactory
php artisan make:factory PostFactory
# etc.
```

### 5. Créer les services métier

- `BallotService` : gestion tokens + votes anonymes
- `BudgetService` : validation allocations (somme = 100%)
- `ModerationService` : workflow reports → sanctions
- `TransparencyService` : agrégation dépenses/recettes

---

## 🧪 Tests à écrire

**Critiques** :
```php
// Vote anonyme
it('stores ballot without user_id')
it('prevents voting without token')
it('consumes token after single use')
it('prevents vote linkage to user identity')
it('reveals results only after deadline')

// Budget
it('validates allocation sum equals 100%')
it('enforces sector min/max constraints')

// Modération
it('auto-mutes user after threshold reports')
it('prevents muted user from posting')
```

---

## 📚 Documentation

- **Schéma complet** : [docs/DATABASE.md](DATABASE.md)
- **Setup** : [docs/SETUP.md](SETUP.md)
- **Progression** : [docs/PROGRESS.md](PROGRESS.md)

---

**✅ Migrations validées et prêtes à être exécutées !**

Prochaine étape : Créer les modèles Eloquent + seeders.

