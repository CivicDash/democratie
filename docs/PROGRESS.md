# Setup Laravel - Résumé

## ✅ Ce qui a été créé

### 🏗️ Infrastructure
- ✅ **Laravel 11** installé avec PHP 8.3
- ✅ **Docker Compose** configuré (app, db, redis, horizon, scheduler, meilisearch)
- ✅ **Dockerfile** optimisé pour PHP 8.3-fpm-alpine
- ✅ **Makefile** avec commandes pratiques
- ✅ **.gitignore** complet

### 📦 Packages installés
- ✅ **laravel/breeze** - Authentification (Inertia + Vue)
- ✅ **spatie/laravel-permission** - RBAC (rôles & permissions)
- ✅ **laravel/horizon** - Gestion des queues Redis
- ✅ **laravel/scout** - Recherche full-text
- ✅ **meilisearch/meilisearch-php** - Client Meilisearch
- ✅ **laravel/telescope** - Debugging (dev)
- ✅ **pestphp/pest** - Framework de tests

### 📄 Documentation
- ✅ **README.md** - Documentation principale
- ✅ **CONTRIBUTING.md** - Guide de contribution
- ✅ **LICENSE** - AGPL-3.0
- ✅ **SECURITY.md** - Politique de sécurité
- ✅ **docs/SETUP.md** - Guide de setup détaillé

### ⚙️ Configuration
- ✅ **.env.example** avec toutes les variables CivicDash (PEPPER, feature flags, etc.)
- ✅ **docker/php/local.ini** - Configuration PHP
- ✅ **.gitlab-ci.yml** - Pipeline CI/CD complet

### 🧪 Tests
- ✅ **tests/Pest.php** - Configuration Pest

## 🚀 Prochaines étapes

### 1. Lancer le projet

```bash
# Setup complet automatique
make setup

# Ou manuellement :
docker-compose build
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app npm install
```

### 2. Configurer le PEPPER

```bash
make pepper
# Copier la valeur générée dans .env
```

### 3. Initialiser Breeze

```bash
docker-compose exec app php artisan breeze:install vue --ssr
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 4. Publier les packages

```bash
# Spatie Permission
docker-compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Horizon
docker-compose exec app php artisan horizon:install

# Telescope
docker-compose exec app php artisan telescope:install
```

### 5. Lancer les migrations

```bash
docker-compose exec app php artisan migrate
```

## 📋 Commandes utiles (Makefile)

```bash
make help           # Afficher toutes les commandes
make up             # Démarrer les conteneurs
make down           # Arrêter les conteneurs
make install        # Installer les dépendances
make migrate        # Lancer les migrations
make test           # Lancer les tests
make lint           # Linter le code
make shell          # Ouvrir un shell dans le conteneur
make logs           # Voir les logs
make pepper         # Générer un PEPPER
```

## 🌐 Accès aux services

Une fois lancé :

- **Application** : http://localhost:8000
- **Horizon (queues)** : http://localhost:8000/horizon
- **Telescope (debug)** : http://localhost:8000/telescope
- **Meilisearch** : http://localhost:7700
- **PostgreSQL** : localhost:5432
- **Redis** : localhost:6379

## 📁 Structure du projet

```
civicdash/
├── app/                    # Code applicatif
│   ├── Http/              # Controllers, Requests, Middleware
│   ├── Models/            # Modèles Eloquent
│   ├── Policies/          # (à créer) Politiques d'autorisation
│   └── Services/          # (à créer) Services métier
├── config/                # Configurations Laravel
├── database/
│   ├── factories/         # Factories pour tests
│   ├── migrations/        # Migrations DB
│   └── seeders/           # Seeders
├── docker/                # Configuration Docker
├── docs/                  # Documentation
├── resources/
│   ├── js/               # Code Vue.js/Inertia
│   └── views/            # Templates Blade
├── routes/               # Définition des routes
├── tests/                # Tests Pest
├── docker-compose.yml    # Configuration Docker Compose
├── Dockerfile            # Image Docker app
├── Makefile              # Commandes pratiques
└── README.md             # Documentation principale
```

## 🎯 Roadmap de développement

### Semaine 1 : Fondations ✅
- [x] Setup Laravel + Docker
- [x] Configuration des packages de base
- [x] Documentation et CI/CD
- [ ] Auth Breeze + RBAC (à faire)
- [ ] Migrations tables core (à faire)

### Semaine 2 : Forum
- [ ] Migrations topics/posts
- [ ] Modèles + relations
- [ ] Controllers + policies
- [ ] Sanitizer Markdown strict
- [ ] Tests

### Semaine 3 : Vote anonyme
- [ ] Migrations ballot_tokens/topic_ballots
- [ ] BallotService (tokens, chiffrement)
- [ ] Endpoints scrutin
- [ ] Tests anonymat
- [ ] Deadline + révélation

### Semaine 4 : Budget
- [ ] Migrations sectors/allocations
- [ ] BudgetService (contraintes min/max)
- [ ] Agrégation
- [ ] UI sliders
- [ ] Tests

### Semaine 5 : Modération & Transparence
- [ ] Système de signalement
- [ ] Workflow modération
- [ ] Import CSV dépenses/recettes
- [ ] Graphiques

### Semaine 6 : Polish & QA
- [ ] Tests E2E
- [ ] Optimisations
- [ ] Documentation API
- [ ] Déploiement

## 🔐 Points de vigilance

1. **PEPPER obligatoire** : Ne jamais commiter le PEPPER en clair
2. **Anonymat des votes** : Tester exhaustivement la séparation identité/bulletin
3. **Pas d'images/liens** : Valider côté serveur ET client
4. **Tests systématiques** : Min 80% coverage pour nouvelles features
5. **AGPL-3.0** : Toute modification doit être partagée

## 📞 Support

- Issues GitHub : (à configurer)
- Discussions : (à configurer)
- Email : contact@civicdash.fr

---

**Le setup de base est terminé ! 🎉**

Prochaine étape : Initialiser Breeze et créer les premières migrations.

