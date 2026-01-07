# 🔀 Workflow Git - CivicDash

> **Stratégie** : GitFlow simplifié avec déploiement par tags  
> **Objectif** : Développer sur `dev`, tester, puis publier via tags `vX.Y.Z`

---

## 📋 Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Branches](#branches)
3. [Workflow de Développement](#workflow-de-développement)
4. [Versioning (SemVer)](#versioning-semver)
5. [Déploiement](#déploiement)
6. [Optimisation Espace Disque](#optimisation-espace-disque)

---

## 🌐 Vue d'Ensemble

```
                                    DÉVELOPPEMENT
                                         │
     ┌───────────────────────────────────┼───────────────────────────────────┐
     │                                   │                                   │
     ▼                                   ▼                                   ▼
┌─────────┐                        ┌─────────┐                        ┌─────────┐
│feature/ │                        │  dev    │                        │  main   │
│xxx      │───────────────────────▶│         │───────────────────────▶│         │
│         │     Pull Request       │ (tests) │     Tag vX.Y.Z         │ (prod)  │
└─────────┘                        └─────────┘                        └─────────┘
                                                                            │
                                                                            │
                                                                            ▼
                                                                   ┌─────────────┐
                                                                   │  SERVEUR    │
                                                                   │ PRODUCTION  │
                                                                   │             │
                                                                   │ Déploie     │
                                                                   │ uniquement  │
                                                                   │ les TAGS    │
                                                                   └─────────────┘
```

---

## 🌿 Branches

### Branches Permanentes

| Branche | Rôle | Déploiement |
|---------|------|-------------|
| `main` | Production stable | Auto sur tag `vX.Y.Z` |
| `dev` | Développement intégré | Manuel (staging optionnel) |

### Branches Temporaires

| Préfixe | Exemple | Usage |
|---------|---------|-------|
| `feature/` | `feature/gouvernement-api` | Nouvelles fonctionnalités |
| `fix/` | `fix/scrutins-count` | Corrections de bugs |
| `hotfix/` | `hotfix/security-patch` | Urgences production |
| `refactor/` | `refactor/services-layer` | Refactoring |

---

## 🔄 Workflow de Développement

### 1. Créer une Feature

```bash
# Se positionner sur dev à jour
git checkout dev
git pull origin dev

# Créer la branche feature
git checkout -b feature/ma-nouvelle-feature

# Développer...
git add .
git commit -m "feat: ajout de la fonctionnalité X"

# Pousser la branche
git push -u origin feature/ma-nouvelle-feature
```

### 2. Intégrer dans Dev

```bash
# Mettre à jour dev
git checkout dev
git pull origin dev

# Merger la feature
git merge feature/ma-nouvelle-feature

# Résoudre les conflits si nécessaire
git push origin dev

# Supprimer la branche feature
git branch -d feature/ma-nouvelle-feature
git push origin --delete feature/ma-nouvelle-feature
```

### 3. Préparer une Release

```bash
# S'assurer que dev est stable
git checkout dev
git pull origin dev

# Merger dans main
git checkout main
git pull origin main
git merge dev

# Créer le tag de version
git tag -a v1.2.0 -m "Release v1.2.0 - Gouvernement API + corrections"

# Pousser main et le tag
git push origin main
git push origin v1.2.0
```

### 4. Hotfix Production

```bash
# Partir de main
git checkout main
git pull origin main

# Créer le hotfix
git checkout -b hotfix/critical-security-fix

# Corriger...
git commit -m "fix: correction critique sécurité"

# Merger dans main ET dev
git checkout main
git merge hotfix/critical-security-fix
git tag -a v1.2.1 -m "Hotfix v1.2.1 - Security patch"
git push origin main v1.2.1

git checkout dev
git merge hotfix/critical-security-fix
git push origin dev

# Supprimer le hotfix
git branch -d hotfix/critical-security-fix
```

---

## 📦 Versioning (SemVer)

Format : `vMAJOR.MINOR.PATCH`

| Type | Quand l'utiliser | Exemple |
|------|------------------|---------|
| **MAJOR** | Changements incompatibles, refonte | v1.0.0 → v2.0.0 |
| **MINOR** | Nouvelles fonctionnalités rétro-compatibles | v1.0.0 → v1.1.0 |
| **PATCH** | Corrections de bugs | v1.0.0 → v1.0.1 |

### Exemples

```
v1.0.0  - Première release stable
v1.1.0  - Ajout API Gouvernement
v1.1.1  - Fix bug scrutins
v1.2.0  - Ajout 2FA
v2.0.0  - Refonte architecture (breaking changes)
```

---

## 🚀 Déploiement

### Principe Clé

> **Le serveur de production ne déploie QUE les tags versionnés**
> 
> Pas de déploiement direct depuis une branche !

### Workflow Serveur

```
┌─────────────────────────────────────────────────────────────────┐
│                    SERVEUR PRODUCTION                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Récupérer le tag spécifié                                   │
│     git fetch --tags                                            │
│     git checkout v1.2.0                                         │
│                                                                 │
│  2. Conserver UNE SEULE version déployée                        │
│     (pas d'historique sur le serveur)                           │
│                                                                 │
│  3. Migrations INCRÉMENTALES                                    │
│     (pas de réimport BDD !)                                     │
│     php artisan migrate --force                                 │
│                                                                 │
│  4. Rollback si problème                                        │
│     git checkout v1.1.0                                         │
│     php artisan migrate:rollback                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 💾 Optimisation Espace Disque

### Problème

Avec un serveur limité en espace disque, il faut éviter :
- ❌ Multiples versions du code sur le serveur
- ❌ Réimport complet de la BDD à chaque version
- ❌ Conservation de toutes les images Docker
- ❌ Backups volumineux sur le serveur

### Solutions

#### 1. Une Seule Version sur le Serveur

```bash
# Le serveur ne garde QUE la version active
# Pas de dossiers v1.0, v1.1, v1.2...

# Simplement :
git checkout v1.2.0
```

#### 2. Migrations Incrémentales

```
┌─────────────────────────────────────────────────────────────────┐
│                    STRATÉGIE MIGRATIONS                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ❌ MAUVAIS : Réimporter la BDD à chaque version                │
│     pg_restore full_dump.sql  (500MB+ à chaque fois)            │
│                                                                 │
│  ✅ BON : Migrations incrémentales                               │
│     php artisan migrate --force                                 │
│     (Seules les NOUVELLES migrations sont exécutées)            │
│                                                                 │
│  Exemple :                                                      │
│  v1.0.0 : migrations 001-050 (déjà appliquées)                  │
│  v1.1.0 : migrations 051-055 (nouvelles, ~5 fichiers)           │
│  v1.2.0 : migrations 056-060 (nouvelles, ~5 fichiers)           │
│                                                                 │
│  Laravel sait quelles migrations ont déjà été exécutées         │
│  grâce à la table `migrations` !                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 3. Nettoyage Docker Agressif

```bash
# Après chaque déploiement, nettoyer :
docker system prune -af --volumes

# Résultat :
# - Supprime les anciennes images
# - Supprime les conteneurs arrêtés
# - Supprime les volumes orphelins
# - Économie : 5-20 GB potentiellement
```

#### 4. Backups Externalisés

```bash
# NE PAS stocker les backups sur le serveur !
# Envoyer directement vers un stockage externe :

# Option 1 : S3 / Backblaze B2 (très peu cher)
# Option 2 : Serveur de backup dédié
# Option 3 : Google Drive / Dropbox via rclone

# Garder localement : dernier backup uniquement (pour rollback rapide)
```

---

## 📊 Estimation Espace Disque

### Avec Optimisations

| Élément | Taille | Notes |
|---------|--------|-------|
| Code source | ~500 MB | Une seule version |
| PostgreSQL | ~2-10 GB | Dépend des données |
| Redis | ~100-500 MB | Cache volatile |
| Meilisearch | ~500 MB - 2 GB | Index de recherche |
| Images Docker | ~2-3 GB | Après prune |
| Logs | ~500 MB | Rotation activée |
| **TOTAL** | **~6-15 GB** | Marge confortable |

### ⚠️ À Éviter

| Piège | Taille Gaspillée |
|-------|------------------|
| Garder anciennes images Docker | +5-10 GB |
| Backups locaux multiples | +10-50 GB |
| Logs non rotatés | +5-20 GB |
| node_modules en production | +500 MB |

---

## 🔧 Configuration Recommandée

### `.gitignore` Production

```gitignore
# Ne pas versionner
node_modules/
vendor/
.env
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
public/build/
*.log
```

### Rotation des Logs

```php
// config/logging.php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => 'warning',  // Pas de debug en prod
    'days' => 7,           // Garder 7 jours max
],
```

---

## 📝 Checklist Release

```markdown
## Release vX.Y.Z

### Avant la release
- [ ] Tous les tests passent sur `dev`
- [ ] Code review effectué
- [ ] CHANGELOG.md mis à jour
- [ ] Documentation à jour

### Création du tag
- [ ] Merger `dev` → `main`
- [ ] Créer le tag `vX.Y.Z`
- [ ] Pousser tag vers origin

### Déploiement
- [ ] Connexion serveur
- [ ] `git fetch --tags && git checkout vX.Y.Z`
- [ ] `php artisan migrate --force`
- [ ] `php artisan optimize`
- [ ] Test smoke (pages principales)

### Post-déploiement
- [ ] `docker system prune -af`
- [ ] Vérifier espace disque
- [ ] Vérifier logs erreurs
```

---

💙 **CivicDash** - Workflow Git Optimisé
