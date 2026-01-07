# 🐙 Configuration GitHub - CivicDash

> **Objectif** : Gérer les branches `dev`, `main` et les tags de version sur GitHub  
> **Workflow** : dev → main → tag vX.Y.Z → production

---

## 📋 Table des Matières

1. [Structure des Branches](#structure-des-branches)
2. [Configuration GitHub](#configuration-github)
3. [Protection des Branches](#protection-des-branches)
4. [GitHub Actions (CI/CD)](#github-actions-cicd)
5. [Gestion des Releases](#gestion-des-releases)
6. [Workflow Complet](#workflow-complet)

---

## 🌿 Structure des Branches

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           STRUCTURE GIT                                          │
└─────────────────────────────────────────────────────────────────────────────────┘

                    feature/xxx ─────┐
                    feature/yyy ─────┤
                    fix/zzz ─────────┤
                                     │
                                     ▼
    ┌─────────────────────────────────────────────────────────────────┐
    │                           dev                                    │
    │                                                                  │
    │   • Branche de développement principal                          │
    │   • Reçoit toutes les features et fixes                         │
    │   • Auto-déploiement sur demo.objectif2027.fr                   │
    │   • Tests automatiques (CI)                                     │
    │                                                                  │
    └─────────────────────────────┬───────────────────────────────────┘
                                  │
                                  │ Pull Request (quand stable)
                                  │ Nécessite review
                                  ▼
    ┌─────────────────────────────────────────────────────────────────┐
    │                           main                                   │
    │                                                                  │
    │   • Branche stable / production-ready                           │
    │   • Protégée (pas de push direct)                               │
    │   • Uniquement via PR depuis dev                                │
    │   • Code toujours déployable                                    │
    │                                                                  │
    └─────────────────────────────┬───────────────────────────────────┘
                                  │
                                  │ Tag vX.Y.Z (release)
                                  ▼
    ┌─────────────────────────────────────────────────────────────────┐
    │                    Tags / Releases                               │
    │                                                                  │
    │   v1.0.0  ──────────────────────────────────────▶  PROD         │
    │   v1.1.0  ──────────────────────────────────────▶  PROD         │
    │   v1.2.0  ──────────────────────────────────────▶  PROD         │
    │                                                                  │
    │   • Versions stables pour la production                         │
    │   • Déploiement manuel ou automatique                           │
    │   • Changelog associé                                           │
    │                                                                  │
    └─────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ Configuration GitHub

### 1. Créer la Branche `dev`

```bash
# Si dev n'existe pas encore
git checkout main
git checkout -b dev
git push -u origin dev
```

### 2. Définir `dev` comme Branche par Défaut

Sur GitHub :
1. Aller dans **Settings** → **General**
2. Section **Default branch**
3. Changer de `main` à `dev`
4. Confirmer

> Ainsi, les nouveaux clones et PR cibleront `dev` par défaut.

---

## 🔒 Protection des Branches

### Protéger `main`

Sur GitHub : **Settings** → **Branches** → **Add rule**

```yaml
Branch name pattern: main

Protection rules:
  ✅ Require a pull request before merging
     ✅ Require approvals: 1
     ✅ Dismiss stale pull request approvals when new commits are pushed
  
  ✅ Require status checks to pass before merging
     ✅ Require branches to be up to date before merging
     Status checks: 
       - tests (si CI configuré)
  
  ✅ Require conversation resolution before merging
  
  ✅ Do not allow bypassing the above settings
  
  ❌ Allow force pushes (désactivé)
  ❌ Allow deletions (désactivé)
```

### Protéger `dev` (optionnel, moins strict)

```yaml
Branch name pattern: dev

Protection rules:
  ✅ Require status checks to pass before merging
     Status checks:
       - tests
  
  ❌ Require a pull request (optionnel pour dev)
```

---

## 🤖 GitHub Actions (CI/CD)

### Tests Automatiques

Créer `.github/workflows/tests.yml` :

```yaml
name: Tests

on:
  push:
    branches: [dev, main]
  pull_request:
    branches: [dev, main]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_USER: civicdash
          POSTGRES_PASSWORD: password
          POSTGRES_DB: civicdash_test
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      
      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, redis, gd, zip
          coverage: xdebug
      
      - name: Install Composer dependencies
        run: composer install --no-progress --prefer-dist
      
      - name: Copy .env
        run: cp .env.example .env
      
      - name: Generate key
        run: php artisan key:generate
      
      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_DATABASE: civicdash_test
          DB_USERNAME: civicdash
          DB_PASSWORD: password
      
      - name: Run tests
        run: php artisan test
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_DATABASE: civicdash_test
          DB_USERNAME: civicdash
          DB_PASSWORD: password
```

### Déploiement Automatique sur DEV

Créer `.github/workflows/deploy-dev.yml` :

```yaml
name: Deploy to DEV

on:
  push:
    branches: [dev]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/dev'
    
    steps:
      - name: Deploy to DEV server
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.DEV_SERVER_HOST }}
          username: ${{ secrets.DEV_SERVER_USER }}
          key: ${{ secrets.DEV_SERVER_SSH_KEY }}
          script: |
            cd /opt/civicdash
            git fetch origin dev
            git checkout dev
            git pull origin dev
            docker compose restart app horizon scheduler
            docker exec civicdash_app php artisan migrate --force
            docker exec civicdash_app php artisan optimize
            echo "✅ Deployed to DEV: $(git rev-parse --short HEAD)"
```

### Déploiement sur PROD (via Tag)

Créer `.github/workflows/deploy-prod.yml` :

```yaml
name: Deploy to PROD

on:
  release:
    types: [published]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Get release tag
        id: tag
        run: echo "tag=${GITHUB_REF#refs/tags/}" >> $GITHUB_OUTPUT
      
      - name: Deploy to PROD server
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.PROD_SERVER_HOST }}
          username: ${{ secrets.PROD_SERVER_USER }}
          key: ${{ secrets.PROD_SERVER_SSH_KEY }}
          script: |
            cd /opt/civicdash
            ./docker/production/scripts/deploy-tag.sh ${{ steps.tag.outputs.tag }}
            echo "✅ Deployed to PROD: ${{ steps.tag.outputs.tag }}"
      
      - name: Notify success
        if: success()
        run: |
          echo "🚀 Version ${{ steps.tag.outputs.tag }} deployed to production!"
```

### Secrets GitHub à Configurer

Sur GitHub : **Settings** → **Secrets and variables** → **Actions**

```
DEV_SERVER_HOST     = demo.objectif2027.fr
DEV_SERVER_USER     = civicdash
DEV_SERVER_SSH_KEY  = (clé SSH privée)

PROD_SERVER_HOST    = (IP nouveau serveur)
PROD_SERVER_USER    = civicdash
PROD_SERVER_SSH_KEY = (clé SSH privée)
```

---

## 📦 Gestion des Releases

### Créer une Release sur GitHub

1. **Via l'interface GitHub** :
   - Aller sur **Releases** → **Create a new release**
   - Tag : `v1.2.0`
   - Target : `main`
   - Titre : `v1.2.0 - Nom de la release`
   - Description : Changelog
   - Publier

2. **Via la ligne de commande** :

```bash
# S'assurer que main est à jour
git checkout main
git pull origin main

# Créer et pousser le tag
git tag -a v1.2.0 -m "Release v1.2.0 - Description"
git push origin v1.2.0

# Ou créer une release avec gh CLI
gh release create v1.2.0 --title "v1.2.0" --notes "Changelog..."
```

### Format du Changelog

```markdown
## v1.2.0 (2026-01-15)

### ✨ Nouvelles fonctionnalités
- Ajout de l'API Gouvernement
- Dashboard élu amélioré
- Système de 2FA

### 🐛 Corrections
- Fix du comptage des scrutins
- Correction affichage mobile

### 🔧 Améliorations
- Performance des requêtes SQL
- Optimisation du cache Redis

### ⚠️ Breaking Changes
- (aucun)
```

---

## 🔄 Workflow Complet

### Développement d'une Feature

```bash
# 1. Partir de dev à jour
git checkout dev
git pull origin dev

# 2. Créer une branche feature
git checkout -b feature/nouvelle-fonctionnalite

# 3. Développer...
git add .
git commit -m "feat: ajout de la nouvelle fonctionnalité"

# 4. Pousser et créer une PR vers dev
git push -u origin feature/nouvelle-fonctionnalite
# → Créer PR sur GitHub : feature/xxx → dev
```

### Merge dans Dev

```bash
# Après approbation de la PR (ou directement si pas de PR obligatoire)
git checkout dev
git merge feature/nouvelle-fonctionnalite
git push origin dev

# → GitHub Action déploie automatiquement sur demo.objectif2027.fr

# Supprimer la branche feature
git branch -d feature/nouvelle-fonctionnalite
git push origin --delete feature/nouvelle-fonctionnalite
```

### Créer une Release

```bash
# 1. Quand dev est stable, créer une PR vers main
# Sur GitHub : dev → main (PR)
# Attendre la review et les tests

# 2. Après merge de la PR
git checkout main
git pull origin main

# 3. Créer le tag
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0

# 4. Créer la release sur GitHub (déclenche le déploiement PROD)
gh release create v1.2.0 --title "v1.2.0" --notes-file CHANGELOG.md
```

### Hotfix Production

```bash
# 1. Partir de main
git checkout main
git pull origin main

# 2. Créer le hotfix
git checkout -b hotfix/critical-fix

# 3. Corriger
git commit -m "fix: correction critique"

# 4. Merger dans main ET dev
git checkout main
git merge hotfix/critical-fix
git push origin main

git checkout dev
git merge hotfix/critical-fix
git push origin dev

# 5. Créer un tag patch
git checkout main
git tag -a v1.2.1 -m "Hotfix v1.2.1"
git push origin v1.2.1

# 6. Supprimer le hotfix
git branch -d hotfix/critical-fix
```

---

## 📊 Résumé Visuel

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                                                                 │
│   feature/xxx ──┐                                                               │
│   feature/yyy ──┼──▶  dev  ───────────────▶  main  ───────▶  v1.2.0            │
│   fix/zzz ──────┘      │                      │                │               │
│                        │                      │                │               │
│                  Auto-deploy            PR + Review      Release               │
│                        │                      │                │               │
│                        ▼                      ▼                ▼               │
│                 ┌───────────┐          ┌───────────┐    ┌───────────┐         │
│                 │    DEV    │          │   (prêt   │    │   PROD    │         │
│                 │  demo.    │          │   pour    │    │  (futur   │         │
│                 │  objectif │          │   prod)   │    │   NDD)    │         │
│                 │  2027.fr  │          │           │    │           │         │
│                 └───────────┘          └───────────┘    └───────────┘         │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## ✅ Checklist Configuration GitHub

```markdown
### Branches
- [ ] Branche `dev` créée
- [ ] Branche `dev` définie comme default
- [ ] Protection configurée sur `main`
- [ ] Protection optionnelle sur `dev`

### GitHub Actions
- [ ] Workflow tests.yml créé
- [ ] Workflow deploy-dev.yml créé
- [ ] Workflow deploy-prod.yml créé (pour plus tard)
- [ ] Secrets configurés

### Équipe
- [ ] Collaborateurs ajoutés
- [ ] Règles de review définies

### Releases
- [ ] Template de release créé
- [ ] Convention de versioning documentée
```

---

💙 **CivicDash** - Configuration GitHub
