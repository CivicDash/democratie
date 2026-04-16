# 🗳️ CivicDash

> *"Votre voix compte. Faites-la entendre."*

[![License: AGPL-3.0](https://img.shields.io/badge/License-AGPL%203.0-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-purple.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/Tests-122%20Pest-green.svg)](https://pestphp.com)

**[🌐 civicdash.fr](https://civicdash.fr)** · **[🎯 objectif2027.fr](https://objectif2027.fr)** · **[🏛️ civis-consilium.eu](https://civis-consilium.eu)**

---

Plateforme open-source de démocratie participative pour les citoyens français. Forum sans starification, vote anonyme chiffré, suivi parlementaire temps réel (AN + Sénat), hub communal pour 36 000 communes, transparence publique complète.

Portée par l'association **[Civis-Consilium](https://civis-consilium.eu)**, déployée en production sur **[civicdash.fr](https://civicdash.fr)**.

| | |
|---|---|
| Production | https://civicdash.fr |
| Projet civique | https://objectif2027.fr |
| Association | https://civis-consilium.eu |
| Documentation | [docs/](docs/) |
| Issues | [GitHub Issues](https://github.com/CivicDash/democratie/issues) |

---

## Fonctionnalités

### 🏛️ Parlement & Législation

- Suivi temps réel AN + Sénat — 577 députés, 348 sénateurs, profils complets
- 12 000+ lois avec cycle de vie complet (dépôt → JO)
- 200 000+ amendements, 30 000+ votes individuels (AN), 3 000+ votes Sénat
- Hémicycle SVG interactif, votes agrégés par groupe parlementaire
- **Légifrance API** — contexte juridique et jurisprudence automatiques ⭐
- Calendrier unifié AN + Sénat + Élysée
- Débats Sénat, questions au gouvernement, dossiers législatifs

### 👤 Participation Citoyenne

- Forum sans starification — pas de followers, DM, avatars ni karma
- Vote anonyme chiffré (AES-256, séparation identité/bulletin par jeton usage unique)
- Résultats révélés uniquement après échéance
- Budget participatif — simulation d'affectation sur 10 secteurs avec planchers/plafonds
- Idées citoyennes, interpellations aux élus avec suivi des réponses

### 🏙️ Commune Hub — 36 000 communes

Chaque commune dispose d'une page dédiée (`{commune}.civicdash.fr`) :

**Citoyens (sans compte)**
- Actualités municipales, événements, calendrier mensuel
- Budget communal sur 10 ans (source DGFIP)
- Résultats élections municipales T1/T2 avec listes et participation
- Élus de la commune : maire, député(s), sénateur(s)
- FAQ auto-générée, communes voisines et similaires

**Citoyens (connectés)**
- Voter aux consultations (sondages mono/multi-choix, résultats anonymes)
- S'inscrire aux événements (liste d'attente automatique si complet)
- Poster sur le forum local (9 catégories : urbanisme, sécurité, environnement…)
- Commenter et réagir (articles, événements, consultations)
- S'abonner aux notifications (actualités, événements, forum, email)
- Signaler les contenus inappropriés

**Mairies (tableau de bord admin)**
- Publier actualités, événements, consultations, galerie photo
- Exporter les inscriptions événements (CSV)
- Gérer le forum local, épingler les discussions importantes
- Diffuser des notifications à tous les abonnés
- Personnaliser la page (logo, couleurs, réseaux sociaux)
- Déléguer l'administration (4 rôles : maire, adjoint, délégué, communication)
- Activer le hub via vérification en 3 niveaux (email officiel / domaine / document)

### 📊 Transparence & Données Publiques

- **HATVP** — déclarations de patrimoine des élus
- **Affaires judiciaires** — base vérifiée en 4 niveaux de statut (condamné / en appel / mis en examen / procédure), disclaimer présomption d'innocence automatique
- **KPI nationaux** — démographie, économie, emploi, éducation, santé, environnement, sécurité (sources : INSEE, France Travail, DREES, ADEME, SSMSI)
- **Statistiques régionales et départementales** (18 régions, 101 départements)
- **Budget national** PLF/LFI avec comparaison préférences citoyennes
- **Gouvernement** — ministres, ministères, 16 domaines, historique depuis 1958

### 🗳️ Élections Municipales 2026

- Résultats par commune (T1/T2), participation, sièges par liste
- Profils candidats et listes électorales publics
- **Espace candidat** — dépôt de liste, programme, logo, documents
- Suivi des transitions maires (avant/après, parité, réélection)

### 🔍 Recherche & Découverte

- Recherche full-text < 50ms via Meilisearch (typo-tolérante, autocomplete, filtres)
- "Mes représentants" — député, sénateur et maire par code postal
- Communes voisines (< 30 km) et similaires (même département, population ±30%)

### 🎮 Engagement & Compte Utilisateur

- Gamification — 24 badges, XP, niveaux, streaks, leaderboard
- Suivi des élus avec notifications personnalisées
- 2FA (OTP via Google Authenticator / Authy)
- FranceConnect+ intégré (95% — finalisation en cours)
- Modération communautaire — signalements, sanctions, audit immuable

---

## Stack Technique

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 11, PHP 8.3+ |
| Base de données | PostgreSQL 15 |
| Cache & Queues | Redis 7 + Laravel Horizon |
| Recherche | Meilisearch |
| Frontend | Vue 3 + Inertia.js + Tailwind CSS |
| Authentification | Laravel Breeze + 2FA + FranceConnect+ |
| Tests | Pest (122 tests, Feature + Unit) |
| DevOps | Docker Compose + Proxmox + Terraform |
| Qualité | Pint (PSR-12) + PHPStan niveau 8 |

---

## Sources de Données

| Source | Données intégrées |
|--------|-------------------|
| Assemblée Nationale API | 577 députés, 30 000+ votes, 200 000+ amendements |
| Sénat API | 348 sénateurs, votes, débats, commissions |
| Légifrance | 12 000+ lois, textes JO, jurisprudence |
| INSEE / BDM | Démographie, économie, emploi (commune → national) |
| DGFIP / data.gouv.fr | Budgets communaux sur 10 ans |
| HATVP | Déclarations de patrimoine, conflits d'intérêts |
| Wikipedia / Wikidata | Biographies et photos des élus |
| France Travail, DREES, ADEME | KPI emploi, santé, environnement |

---

## Démarrage Rapide

### Prérequis

- Docker & Docker Compose
- Git

### Installation

```bash
# 1. Cloner le projet
git clone https://github.com/CivicDash/democratie.git
cd democratie

# 2. Configurer l'environnement
cp .env.example .env

# 3. Setup complet (build, dépendances, clé, migrations)
make setup

# 4. Générer le PEPPER (copier la valeur dans .env)
make pepper

# 5. Lancer le mode démo avec données réalistes
make demo
```

→ Application disponible sur **http://localhost:8000**

### Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@civicdash.fr | password |
| Citoyen | citoyen1@civicdash.fr | demo2025 |
| Élu (Député) | demo-elu@civicdash.fr | DemoElu2026! |

Documentation complète : [docs/DEMO_MODE.md](docs/DEMO_MODE.md)

### Commandes utiles

```bash
make test           # Lancer la suite de tests Pest
make lint           # Pint + PHPStan
make migrate-fresh  # Réinitialiser la base avec seeds
make optimize       # Cache de production
```

---

## CivicDash · Objectif 2027 · Civis-Consilium

Ce projet articule trois entités complémentaires :

- **CivicDash** — la plateforme technique open-source (AGPL-3.0), ce dépôt
- **[Objectif 2027](https://objectif2027.fr)** — le projet civique et politique porté par la communauté
- **[Civis-Consilium](https://civis-consilium.eu)** — l'association porteuse, gouvernance et financement

---

## Roadmap

| Phase | Période | Statut | Objectif principal |
|-------|---------|--------|--------------------|
| 1 — Production Ready | T1 2026 | 🔄 En cours | FranceConnect+ finalisé, couverture tests > 80% |
| 2 — Qualité | T2 2026 | 📅 Planifié | Monitoring Sentry, notifications email, i18n FR/EN |
| 3 — Features Avancées | T3 2026 | 📅 Planifié | PWA, design system, E2E Cypress, Lighthouse > 90 |
| 4 — Scale & Innovation | T4 2026 | 📅 Planifié | Pétitions, ML modération, microservices, Kubernetes |

**Vision 2027+** : partenariat gouvernement français, déploiement communes > 5 000 habitants, fork européen (DE, ES, IT).

Roadmap détaillée : [docs/ROADMAP.md](docs/ROADMAP.md)

---

## Contribution

Les contributions sont les bienvenues.

```bash
git checkout -b feature/ma-feature
# ... développer ...
git commit -m "feat: description"  # Conventional Commits
git push origin feature/ma-feature
# Ouvrir une Pull Request
```

**Conventions** : [Conventional Commits](https://www.conventionalcommits.org/), Pint (PSR-12), PHPStan niveau 8, Pest avec couverture > 80% pour les nouvelles features.

Voir [CONTRIBUTING.md](CONTRIBUTING.md) pour les guidelines complètes.

---

## Licence & Avertissement

Ce projet est sous licence **[AGPL-3.0](LICENSE)** — toute modification déployée sur un serveur doit être partagée avec la communauté.

> ⚠️ **PoC à visée éducative et de démonstration.** Pour un usage en production impliquant des données personnelles, une DPIA CNIL complète et un audit de sécurité indépendant sont requis.
