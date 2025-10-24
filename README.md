# CivicDash

**PoC open-source pour débat citoyen, vote anonyme et répartition budgétaire participative**

[![License: AGPL-3.0](https://img.shields.io/badge/License-AGPL%203.0-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-purple.svg)](https://php.net)

## 📋 Description

CivicDash est une plateforme démocratique qui permet aux citoyens de :

- 💬 **Débattre** sur des projets de lois et propositions sans starification
- 🗳️ **Voter anonymement** avec des résultats révélés après échéance
- 💰 **Répartir leur budget** par secteurs (éducation, santé, écologie, etc.) avec contraintes min/max
- 📊 **Consulter la transparence** des recettes et dépenses publiques
- 🏛️ **Participer localement** (national, régional, départemental)
- 🛡️ **Modérer** les contenus avec un système de signalement et sanctions

## 🎯 Principes clés

- **Pas d'images ni de liens** pour les citoyens (texte uniquement, Markdown restreint)
- **Vote anonyme** avec séparation identité/bulletin via jetons à usage unique
- **Confidentialité** : bulletins chiffrés, stockage séparé, aucun traçage user
- **Gouvernance territoriale** : scope national/régional/départemental
- **Open Source** : licence AGPL-3.0, contributions bienvenues

## 🚀 Stack Technique

- **Backend** : Laravel 11, PHP 8.3+
- **Base de données** : PostgreSQL 15
- **Cache & Queues** : Redis 7
- **Recherche** : Meilisearch
- **Frontend** : Inertia.js + Vue 3 + Tailwind CSS
- **Tests** : Pest
- **DevOps** : Docker Compose, GitLab CI

## 📦 Prérequis

- Docker & Docker Compose
- Git
- (Optionnel) PHP 8.3+, Composer, Node.js 20+ pour dev local

## 🛠️ Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-org/civicdash.git
cd civicdash
```

### 2. Copier et configurer l'environnement

```bash
cp .env.example .env
```

**Important** : Générez le `PEPPER` pour le hashing des références citoyennes :

```bash
php artisan tinker --execute="echo base64_encode(random_bytes(32));"
```

Copiez le résultat dans `.env` à la variable `PEPPER`.

### 3. Lancer avec Docker Compose

```bash
# Construire les images
docker-compose build

# Démarrer les services
docker-compose up -d

# Installer les dépendances PHP
docker-compose exec app composer install

# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Installer les dépendances Node.js
docker-compose exec app npm install

# Lancer les migrations
docker-compose exec app php artisan migrate

# Publier les assets des packages
docker-compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
docker-compose exec app php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
docker-compose exec app php artisan vendor:publish --provider="Laravel\Telescope\TelescopeServiceProvider"

# Compiler les assets frontend
docker-compose exec app npm run build
```

### 4. Accès

- **Application** : http://localhost:8000
- **Horizon (queues)** : http://localhost:8000/horizon
- **Telescope (debug)** : http://localhost:8000/telescope
- **Meilisearch** : http://localhost:7700

## 🧪 Tests

```bash
# Lancer tous les tests
docker-compose exec app php artisan test

# Lancer Pest avec coverage
docker-compose exec app ./vendor/bin/pest --coverage

# Tests spécifiques
docker-compose exec app php artisan test --filter=BallotTest
```

## 📁 Structure du projet

```
civicdash/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Middleware/
│   ├── Models/
│   ├── Policies/
│   ├── Services/
│   │   ├── BallotService.php
│   │   └── BudgetService.php
│   └── ...
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   └── Components/
│   └── views/
├── tests/
│   ├── Feature/
│   └── Unit/
├── docker-compose.yml
├── Dockerfile
├── .env.example
└── README.md
```

## 🗄️ Modèle de données (schéma minimal)

### Identité & territoires
- `users` : utilisateurs avec rôles
- `profiles` : pseudos aléatoires, ref citoyenne hashée
- `territories_regions` / `territories_departments`

### Forum & modération
- `topics` : sujets de débat/loi
- `posts` : messages de débat
- `post_votes` : votes up/down sur posts
- `reports` : signalements
- `sanctions` : mutes/bans

### Scrutins anonymes
- `topic_ballots` : bulletins chiffrés **sans user_id**
- `ballot_tokens` : jetons éphémères à usage unique

### Budgets
- `sectors` : secteurs budgétaires (min/max %)
- `user_allocations` : répartition personnelle
- `public_revenue` / `public_spend` : données de transparence

### Documents
- `documents` : pièces jointes (législateurs/État)
- `verifications` : validation par journalistes/ONG

## 🔐 Sécurité & Conformité

- **Hashing** : Argon2id pour mots de passe
- **CSRF** : protection Laravel native
- **Rate limiting** : throttle API + Redis
- **Anonymat des votes** :
  - Séparation DB identité/vote
  - Jetons opaques signés à usage unique
  - Bulletins chiffrés (Laravel Crypt)
  - Révélation agrégée après échéance uniquement
- **Audit** : logs immuables (append-only)
- **DPIA** : à compléter pour production

## 🤝 Contribution

Les contributions sont les bienvenues ! Consultez [CONTRIBUTING.md](CONTRIBUTING.md) pour les guidelines.

1. Fork le projet
2. Créer une branche (`git checkout -b feature/ma-feature`)
3. Commit (`git commit -m 'feat: ajout de ma feature'`)
4. Push (`git push origin feature/ma-feature`)
5. Ouvrir une Pull Request

### Conventions

- **Commits** : [Conventional Commits](https://www.conventionalcommits.org/) (feat, fix, docs, test, refactor)
- **Code style** : PSR-12, Laravel Pint (`./vendor/bin/pint`)
- **Tests** : Pest, minimum 80% coverage pour nouvelles features
- **PHPStan** : niveau 8 minimum

## 📜 Licence

Ce projet est sous licence **AGPL-3.0**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

L'AGPL-3.0 garantit que toute modification du code, même sur un serveur, doit être partagée avec la communauté.

## 🗺️ Roadmap

### PoC (Semaines 1-6)
- [x] Setup Laravel + Docker
- [ ] Auth PoC (Breeze) + RBAC
- [ ] Territoires seed (régions/départements FR)
- [ ] Forum (topics/posts) + sanitizer Markdown
- [ ] Votes up/down + scrutin anonyme
- [ ] Budget sliders + contraintes + agrégation
- [ ] Transparence (CSV recettes/dépenses)
- [ ] Modération + sanctions
- [ ] Documents vérifiés (workflow)
- [ ] CI/CD GitLab

### V1 (post-PoC)
- [ ] FranceConnect+ OIDC
- [ ] Vote renforcé (commit-reveal/mixnet)
- [ ] Connecteurs données fiscales officielles
- [ ] Anti-brigading & rate-limit adaptatif
- [ ] Observabilité (métriques publiques)
- [ ] Mobile app (React Native)

## 📞 Support & Contact

- **Issues** : [GitHub Issues](https://github.com/votre-org/civicdash/issues)
- **Discussions** : [GitHub Discussions](https://github.com/votre-org/civicdash/discussions)
- **Email** : contact@civicdash.fr

## 👥 Équipe

Développé avec ❤️ par la communauté CivicDash.

---

**Avertissement** : Ce projet est un PoC (Proof of Concept) à visée éducative et de démonstration. Pour un usage en production, une analyse de sécurité approfondie, une DPIA CNIL et des audits indépendants sont nécessaires.
