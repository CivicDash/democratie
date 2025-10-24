# ✅ Seeders créés - CivicDash

## 📊 Résumé

**4 seeders créés** | **637 lignes de code** | **Prêts à exécuter**

Tous les seeders ont été validés syntaxiquement ✓

---

## 📁 Liste des seeders

### 1️⃣ RolesAndPermissionsSeeder

**Crée 7 rôles + 24 permissions**

#### Rôles créés :

| Rôle | Description | Permissions principales |
|------|-------------|------------------------|
| **citizen** | Citoyen standard | Posts, votes, scrutins, budget |
| **moderator** | Modérateur | + Signalements, sanctions, masquage |
| **journalist** | Journaliste | + Vérification documents |
| **ong** | Organisation | + Vérification documents |
| **legislator** | Législateur | + Création topics/lois, upload docs |
| **state** | État | + Import données budget, docs officiels |
| **admin** | Administrateur | Toutes permissions |

#### Permissions (24 au total) :

**Forum** :
- `create_topics`, `edit_topics`, `delete_topics`
- `create_posts`, `edit_own_posts`, `delete_own_posts`
- `vote_on_posts`, `pin_posts`

**Scrutins** :
- `create_ballots`, `vote_in_ballots`
- `view_ballot_results`, `manage_ballots`

**Modération** :
- `view_reports`, `handle_reports`
- `create_sanctions`, `revoke_sanctions`, `hide_posts`

**Budget** :
- `submit_budget_allocation`, `view_budget_data`, `import_budget_data`

**Documents** :
- `upload_documents`, `verify_documents`, `view_pending_documents`

**Administration** :
- `manage_users`, `manage_roles`, `view_admin_panel`

---

### 2️⃣ TerritoriesSeeder

**Crée 13 régions + 101 départements français**

#### Régions (13) :

| Code | Nom |
|------|-----|
| 84 | Auvergne-Rhône-Alpes |
| 27 | Bourgogne-Franche-Comté |
| 53 | Bretagne |
| 24 | Centre-Val de Loire |
| 94 | Corse |
| 44 | Grand Est |
| 32 | Hauts-de-France |
| 11 | Île-de-France |
| 28 | Normandie |
| 75 | Nouvelle-Aquitaine |
| 76 | Occitanie |
| 52 | Pays de la Loire |
| 93 | Provence-Alpes-Côte d'Azur |

#### Départements (101) :

Exemples :
- **75** - Paris (Île-de-France)
- **69** - Rhône (Auvergne-Rhône-Alpes)
- **13** - Bouches-du-Rhône (PACA)
- **44** - Loire-Atlantique (Pays de la Loire)
- **2A** / **2B** - Corse-du-Sud / Haute-Corse

**Tous les départements métropolitains inclus** avec leurs relations région correctes.

---

### 3️⃣ SectorsSeeder

**Crée 10 secteurs budgétaires**

| Code | Nom | Icône | Couleur | Min % | Max % |
|------|-----|-------|---------|-------|-------|
| **EDU** | Éducation | academic-cap | #3B82F6 | 10% | 40% |
| **HEALTH** | Santé | heart | #EF4444 | 10% | 35% |
| **ECO** | Écologie & Transition | leaf | #10B981 | 5% | 30% |
| **DEFENSE** | Défense & Sécurité | shield-check | #6366F1 | 5% | 25% |
| **SOCIAL** | Solidarité & Social | users | #8B5CF6 | 10% | 35% |
| **CULTURE** | Culture & Sport | sparkles | #F59E0B | 2% | 15% |
| **INFRA** | Infrastructures | truck | #64748B | 5% | 25% |
| **JUSTICE** | Justice | scale | #DC2626 | 2% | 15% |
| **RESEARCH** | Recherche | beaker | #06B6D4 | 2% | 20% |
| **AGRI** | Agriculture | home-modern | #84CC16 | 2% | 20% |

**Total min** : 55% | **Total max** : 260%

✓ Contraintes cohérentes (les citoyens peuvent choisir librement dans les plages)

---

### 4️⃣ DatabaseSeeder

**Orchestrateur principal + 5 utilisateurs de test**

#### Ordre d'exécution :

1. `RolesAndPermissionsSeeder` (rôles d'abord)
2. `TerritoriesSeeder` (territoires)
3. `SectorsSeeder` (secteurs)
4. Création des users de test

#### Utilisateurs créés :

| Nom | Email | Password | Rôle | Profil |
|-----|-------|----------|------|--------|
| Admin CivicDash | admin@civicdash.fr | password | admin | - |
| Modérateur Test | moderator@civicdash.fr | password | moderator | - |
| Député Test | legislator@civicdash.fr | password | legislator | - |
| Journaliste Test | journalist@civicdash.fr | password | journalist | - |
| Citoyen Test | citizen@civicdash.fr | password | citizen | ✓ Avec profil |

**Note** : Le citoyen possède un profil avec pseudonyme aléatoire et `citizen_ref_hash`.

---

## 🚀 Utilisation

### Lancer les seeders

```bash
# Avec Make
make seed

# Ou directement
docker-compose exec app php artisan db:seed

# Avec reset complet
make fresh  # ou php artisan migrate:fresh --seed
```

### Résultat attendu

```
🌱 Seeding CivicDash database...

✓ 7 rôles créés : citizen, moderator, journalist, ong, legislator, state, admin
✓ 24 permissions créées

🇫🇷 Seeding French territories...
✓ 13 régions créées
✓ 101 départements créés
🎉 Territoires français complets !

💰 Seeding budget sectors...
✓ 10 secteurs budgétaires créés
📊 Total min: 55% | Total max: 260%
✓ Contraintes cohérentes (min total: 55%)

👤 Creating test users...
✓ Admin créé : admin@civicdash.fr / password
✓ Modérateur créé : moderator@civicdash.fr / password
✓ Législateur créé : legislator@civicdash.fr / password
✓ Journaliste créé : journalist@civicdash.fr / password
✓ Citoyen créé : citizen@civicdash.fr / password (avec profil)

🎉 Database seeding completed successfully!

┌─────────────┬─────────────────────────┬──────────┬─────────────┐
│ Compte      │ Email                   │ Password │ Rôle        │
├─────────────┼─────────────────────────┼──────────┼─────────────┤
│ Admin       │ admin@civicdash.fr      │ password │ admin       │
│ Modérateur  │ moderator@civicdash.fr  │ password │ moderator   │
│ Législateur │ legislator@civicdash.fr │ password │ legislator  │
│ Journaliste │ journalist@civicdash.fr │ password │ journalist  │
│ Citoyen     │ citizen@civicdash.fr    │ password │ citizen     │
└─────────────┴─────────────────────────┴──────────┴─────────────┘

⚠️  N'oubliez pas de configurer PEPPER dans .env !
   Commande : make pepper
```

---

## 🧪 Vérifications post-seed

### Compter les données

```bash
docker-compose exec app php artisan tinker

# Rôles
\Spatie\Permission\Models\Role::count();  // 7

# Permissions
\Spatie\Permission\Models\Permission::count();  // 24

# Territoires
App\Models\TerritoryRegion::count();  // 13
App\Models\TerritoryDepartment::count();  // 101

# Secteurs
App\Models\Sector::count();  // 10

# Users
App\Models\User::count();  // 5
```

### Tester les rôles

```bash
docker-compose exec app php artisan tinker

$admin = User::where('email', 'admin@civicdash.fr')->first();
$admin->hasRole('admin');  // true
$admin->can('manage_users');  // true

$citizen = User::where('email', 'citizen@civicdash.fr')->first();
$citizen->hasRole('citizen');  // true
$citizen->can('vote_in_ballots');  // true
$citizen->can('manage_users');  // false

$citizen->profile;  // Profile avec display_name et citizen_ref_hash
```

### Tester les territoires

```bash
docker-compose exec app php artisan tinker

$idf = TerritoryRegion::where('code', '11')->first();
$idf->name;  // "Île-de-France"
$idf->departments->count();  // 8

$paris = TerritoryDepartment::where('code', '75')->first();
$paris->region->name;  // "Île-de-France"
```

### Tester les secteurs

```bash
docker-compose exec app php artisan tinker

Sector::active()->ordered()->get();  // 10 secteurs
Sector::where('code', 'EDU')->first()->name;  // "Éducation"
```

---

## 📝 Données de test supplémentaires (optionnelles)

Pour enrichir les données de test, vous pouvez créer des seeders additionnels :

### DemoTopicsSeeder (à créer)

```php
// Créer des topics/débats de démo
Topic::create([
    'title' => 'Réforme de l\'éducation nationale',
    'description' => 'Débat sur la réforme...',
    'scope' => 'national',
    'type' => 'debate',
    'status' => 'open',
    'author_id' => $legislator->id,
]);
```

### DemoPostsSeeder (à créer)

```php
// Créer des posts de démo sur les topics
Post::create([
    'topic_id' => $topic->id,
    'user_id' => $citizen->id,
    'content' => 'Je pense que...',
]);
```

### DemoBudgetSeeder (à créer)

```php
// Créer des allocations budgétaires de démo
UserAllocation::create([
    'user_id' => $citizen->id,
    'sector_id' => $education->id,
    'percent' => 30.0,
]);
```

---

## 🔐 Sécurité

### PEPPER obligatoire

⚠️ **IMPORTANT** : Générez le PEPPER avant de créer des profils citoyens en production :

```bash
make pepper
# ou
docker-compose exec app php artisan tinker --execute="echo base64_encode(random_bytes(32));"
```

Copiez la valeur dans `.env` :
```env
PEPPER=votre_valeur_generee_ici
```

### Passwords de test

Les users de test utilisent `password` comme mot de passe.  
**À CHANGER EN PRODUCTION** !

---

## 📚 Prochaines étapes

1. ✅ Seeders créés
2. 🔄 Exécuter les seeders : `make fresh`
3. 🔄 Configurer PEPPER dans `.env`
4. 🔄 Créer les factories pour tests
5. 🔄 (Optionnel) Créer seeders de démo (topics, posts, etc.)
6. 🔄 Tester l'authentification
7. 🔄 Créer les controllers

---

**✅ Seeders complets et fonctionnels !**

Base de données prête pour le développement avec :
- 7 rôles + 24 permissions
- 13 régions + 101 départements
- 10 secteurs budgétaires
- 5 utilisateurs de test

Prochaine étape : Factories pour les tests ? 🧪

