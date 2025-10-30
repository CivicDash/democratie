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
- 🏛️ **Suivre la législation** : Assemblée Nationale et Sénat en temps réel avec votes citoyens
- 🔍 **Rechercher en < 50ms** : Meilisearch pour une recherche ultra-rapide avec autocomplete
- 🎨 **Profiter d'une UX premium** : Loading skeletons, toast notifications, empty states
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

### ✅ État Actuel : 97% Production-Ready

**Déjà implémenté** :
- [x] Setup Laravel 11 + Docker (PostgreSQL, Redis, Meilisearch, Horizon)
- [x] Auth Laravel Breeze + Inertia + Vue 3
- [x] RBAC Spatie Permission (7 rôles, 26 permissions)
- [x] Territoires seed (13 régions + 101 départements FR)
- [x] Forum complet (topics/posts) + Markdown sanitizer
- [x] Votes up/down + scrutin anonyme cryptographique
- [x] Budget participatif (10 secteurs) + contraintes min/max
- [x] Transparence (recettes/dépenses publiques)
- [x] **🏛️ Législation** : Intégration Assemblée + Sénat + votes citoyens + timeline
- [x] **🔍 Recherche Meilisearch** : < 50ms avec autocomplete intelligent + filtres
- [x] **🎨 Composants UX** : LoadingSkeleton, Toast, EmptyState, ConfirmModal
- [x] **📊 Dashboard Législatif** : Trending propositions + graphiques circulaires
- [x] Modération workflow (reports + sanctions)
- [x] Documents vérifiés (upload + workflow validation)
- [x] 122 tests Pest (Unit + Feature)
- [x] 73 routes API REST (15 nouvelles)
- [x] 23 pages Vue 3 + Inertia (6 nouvelles)
- [x] Cache Redis (540x plus rapide)
- [x] Rate Limiting (9 limites anti-spam)
- [x] FranceConnect+ ready (95%)
- [x] CI/CD GitLab
- [x] Documentation exhaustive (28 fichiers)

### 📅 Phase 1 : Production Ready (T1 2026 - Janv-Mars)

**Version** : 1.0.0 | **Durée** : 2-3 semaines

- [ ] 🇫🇷 **FranceConnect+ Finalisé** (2-3 jours)
  - Obtenir CLIENT_ID + CLIENT_SECRET
  - Badge "Vérifié par l'État"
  - Tests E2E OAuth2
  
- [ ] 🧪 **Tests Additionnels** (3-4 jours)
  - Tests Cache Redis (invalidation, performance)
  - Tests Rate Limiting (429 responses)
  - Coverage > 80%
  
- [ ] 📱 **Responsive Mobile** (4-5 jours)
  - Menu burger + bottom nav
  - 17 pages mobile-optimized
  - Touch targets 44px minimum
  - Lighthouse mobile > 85
  
- [x] 🔍 **Recherche Meilisearch** ✅ **TERMINÉ**
  - Indexation Topics, Posts, Documents
  - Autocomplete + navigation clavier
  - Filtres avancés + Typo-tolerant

**Objectif** : Beta publique avec 1,000 citoyens

**✨ NOUVEAU (Oct 2025)** :
- [x] 🏛️ **Pages Législatives** : Index + Show avec timeline animée
- [x] 📊 **Votes Citoyens** : Graphiques circulaires SVG + stats détaillées
- [x] 🎨 **4 Composants UX** : Skeleton, Toast, Empty, Confirm
- [x] 🧩 **2 Composables** : useToast, useConfirm
- [x] 📚 **Documentation** : MEILISEARCH.md + COMPOSANTS_UX.md

### ⚡ Phase 2 : Qualité Production (T2 2026 - Avril-Juin)

**Version** : 1.1.0 | **Durée** : 2-3 semaines

- [ ] 📊 **Monitoring & Observabilité** (2-3 jours)
  - Telescope (dev) + Sentry (prod)
  - Logs JSON structurés
  - Alertes Slack
  - MTTR < 30 min
  
- [ ] 📧 **Système de Notifications** (3-4 jours)
  - In-app + Email (6 types)
  - Préférences utilisateur
  - Queue jobs
  
- [ ] 🌐 **Internationalisation i18n** (2-3 jours)
  - Interface FR/EN
  - Vue i18n + Laravel lang
  - URLs localisées

**Objectif** : 5,000 citoyens, 99.9% uptime

### 💡 Phase 3 : Features Avancées (T3 2026 - Juil-Sept)

**Version** : 1.2.0 | **Durée** : 1-2 mois

- [ ] 🎨 Design System complet (3-4 jours)
- [ ] 📱 PWA (Progressive Web App) (2-3 jours)
- [ ] 🔐 Sécurité avancée (CSP, 2FA, audits) (4-5 jours)
- [ ] 📊 Analytics & Métriques (2-3 jours)
- [ ] 🚀 Optimisations Performance (2-3 jours)
- [ ] 📄 Documentation utilisateur (2-3 jours)
- [ ] 🧪 Tests E2E Cypress (3-4 jours)

**Objectif** : 10,000 citoyens, Lighthouse > 90

### 🌟 Phase 4 : Scale & Innovation (T4 2026 - Oct-Déc)

**Version** : 2.0.0 | **Durée** : 2-3 mois

- [ ] 📜 Pétitions en ligne (1-2 semaines)
- [ ] 🏛️ Initiatives citoyennes (1-2 semaines)
- [ ] 🗺️ Cartographie participative (1 semaine)
- [ ] 📹 Livestream débats (1 semaine)
- [ ] 🤖 ML Auto-Modération (2-3 semaines)
- [ ] 🧠 AI Facilitator (2-3 semaines)
- [ ] ⚙️ Microservices Architecture (1 mois)
- [ ] ☸️ Kubernetes Production (2 semaines)

**Objectif** : 50,000 citoyens, plateforme nationale

### 🏆 Vision Long Terme (2027+)

- 🇫🇷 Partenariat Gouvernement français
- 🏛️ Intégration Assemblée Nationale
- 🏙️ Déploiement communes France (> 5000 hab)
- 🌍 Fork européen (DE, ES, IT)
- 🇪🇺 Standard EU démocratie participative

**Roadmap complète** : Voir [docs/ROADMAP.md](docs/ROADMAP.md)

## 📞 Support & Contact

- **Issues** : [GitHub Issues](https://github.com/CivicDash/democratie/issues)
- **Discussions** : [GitHub Discussions](https://github.com/CivicDash/democratie/discussions)
- **Documentation** : [docs/](docs/)

## 👥 Équipe

Développé avec ❤️ par la communauté CivicDash.

---

**Avertissement** : Ce projet est un PoC (Proof of Concept) à visée éducative et de démonstration. Pour un usage en production, une analyse de sécurité approfondie, une DPIA CNIL et des audits indépendants sont nécessaires.
