# 🌍 Architecture Multi-Environnements - CivicDash

> **Stratégie** : Serveur actuel → Dev/Staging | Nouveau serveur Proxmox → Production  
> **Dernière mise à jour** : Janvier 2026

---

## 📋 Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Architecture Cible](#architecture-cible)
3. [Configuration des Environnements](#configuration-des-environnements)
4. [Workflow de Déploiement](#workflow-de-déploiement)
5. [Migration vers Production](#migration-vers-production)

---

## 🌐 Vue d'Ensemble

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           WORKFLOW COMPLET                                       │
└─────────────────────────────────────────────────────────────────────────────────┘

                              DÉVELOPPEUR LOCAL
                                     │
                                     │ git push
                                     ▼
                             ┌───────────────┐
                             │    GITLAB     │
                             │   (ou GitHub) │
                             │               │
                             │  ┌─────────┐  │
                             │  │   dev   │  │ ◄─── Développement continu
                             │  └────┬────┘  │
                             │       │       │
                             │       │ merge │
                             │       ▼       │
                             │  ┌─────────┐  │
                             │  │  main   │  │ ◄─── Versions stables
                             │  └────┬────┘  │
                             │       │       │
                             │       │ tag   │
                             │       ▼       │
                             │  ┌─────────┐  │
                             │  │ v1.2.0  │  │ ◄─── Releases
                             │  └─────────┘  │
                             └───────┬───────┘
                                     │
                    ┌────────────────┼────────────────┐
                    │                │                │
                    ▼                ▼                ▼
         ┌─────────────────┐  ┌─────────────┐  ┌─────────────────┐
         │   SERVEUR DEV   │  │   STAGING   │  │   SERVEUR PROD  │
         │    (Actuel)     │  │  (Optionnel)│  │   (Proxmox)     │
         │                 │  │             │  │                 │
         │  8 CPU, 32GB    │  │ VM sur Dev  │  │  32+ CPU        │
         │  Branche: dev   │  │ Tag: latest │  │  128+ GB RAM    │
         │                 │  │             │  │  Tag: vX.Y.Z    │
         │ dev.objectif    │  │ staging.    │  │ demo.objectif   │
         │ 2027.fr         │  │ objectif    │  │ 2027.fr         │
         │                 │  │ 2027.fr     │  │                 │
         └─────────────────┘  └─────────────┘  └─────────────────┘
                │                                      │
                │     ┌────────────────────────┐      │
                └────▶│  BASE DE DONNÉES PROD  │◀─────┘
                      │  (Réplication possible) │
                      └────────────────────────┘
```

---

## 🏗️ Architecture Cible

### Serveur Actuel → DEV/STAGING

```
┌─────────────────────────────────────────────────────────────────┐
│              SERVEUR ACTUEL (8 CPU, 32 GB RAM)                  │
│                    dev.objectif2027.fr                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │                    ENVIRONNEMENT DEV                       │ │
│  │                                                            │ │
│  │   • Branche : dev (ou feature/*)                          │ │
│  │   • Auto-déploiement sur push dev                         │ │
│  │   • Base de données : données de test                     │ │
│  │   • Email : Mailpit (pas de vrais emails)                 │ │
│  │   • Debug : activé                                        │ │
│  │   • Telescope/Horizon : accessibles                       │ │
│  │                                                            │ │
│  │   Ports :                                                  │ │
│  │     • 8080 : Application                                  │ │
│  │     • 8025 : Mailpit                                      │ │
│  │     • 5433 : PostgreSQL                                   │ │
│  │                                                            │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │                 ENVIRONNEMENT STAGING                      │ │
│  │                     (Optionnel)                            │ │
│  │                                                            │ │
│  │   • Tag : dernier tag créé                                │ │
│  │   • Miroir de la production                               │ │
│  │   • Base de données : copie anonymisée de prod            │ │
│  │   • Email : Mailpit ou test                               │ │
│  │   • Debug : désactivé                                     │ │
│  │                                                            │ │
│  │   Ports :                                                  │ │
│  │     • 8180 : Application                                  │ │
│  │                                                            │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Nouveau Serveur Proxmox → PRODUCTION

```
┌─────────────────────────────────────────────────────────────────┐
│              NOUVEAU SERVEUR PROXMOX                            │
│                  demo.objectif2027.fr                           │
│              (32+ CPU, 128+ GB RAM, NVMe SSD)                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │                   VM : LOAD BALANCER                       │ │
│  │                    (Traefik / HAProxy)                     │ │
│  │   • SSL Let's Encrypt automatique                         │ │
│  │   • HTTP/3 QUIC                                           │ │
│  │   • Rate Limiting                                         │ │
│  └───────────────────────────────────────────────────────────┘ │
│                              │                                  │
│              ┌───────────────┼───────────────┐                 │
│              ▼               ▼               ▼                 │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐  │
│  │   LXC : APP 1   │ │   LXC : APP 2   │ │   LXC : APP N   │  │
│  │   FrankenPHP    │ │   FrankenPHP    │ │   FrankenPHP    │  │
│  │   + Horizon     │ │                 │ │                 │  │
│  └─────────────────┘ └─────────────────┘ └─────────────────┘  │
│              │               │               │                 │
│              └───────────────┼───────────────┘                 │
│                              ▼                                  │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │                    VM : DATABASE                           │ │
│  │   • PostgreSQL Primary + Replica                          │ │
│  │   • PgBouncer                                             │ │
│  │   • Redis Cluster                                         │ │
│  │   • Meilisearch                                           │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │                    VM : SERVICES                           │ │
│  │   • Mailu (Email)                                         │ │
│  │   • Prometheus + Grafana                                  │ │
│  │   • Backup (vers S3)                                      │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  Configuration :                                                │
│   • Tag : uniquement des tags versionnés (v1.0.0, v1.1.0...)  │
│   • Debug : DÉSACTIVÉ                                          │
│   • Logs : niveau warning uniquement                           │
│   • Backups : quotidiens vers stockage externe                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ Configuration des Environnements

### Variables d'Environnement par Serveur

#### Serveur DEV (`.env.dev`)

```bash
# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=https://dev.objectif2027.fr

# Base de données (données de test)
DB_DATABASE=civicdash_dev
DB_HOST=postgres

# Logs verbeux
LOG_LEVEL=debug
LOG_CHANNEL=daily

# Email (Mailpit - pas de vrais emails)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Telescope activé
TELESCOPE_ENABLED=true

# Pas besoin de cache agressif
CACHE_DRIVER=redis
SESSION_DRIVER=file
```

#### Serveur PROD (`.env.production`)

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.objectif2027.fr

# Base de données (via PgBouncer)
DB_DATABASE=civicdash
DB_HOST=pgbouncer
DB_PORT=6432

# Logs minimaux
LOG_LEVEL=warning
LOG_CHANNEL=daily

# Vrais emails (Mailu)
MAIL_MAILER=smtp
MAIL_HOST=mailu-front-1
MAIL_PORT=25
MAIL_URL=smtp://mailu-front-1:25?verify_peer=0

# Telescope désactivé en prod
TELESCOPE_ENABLED=false

# Cache agressif
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
```

---

## 🔄 Workflow de Déploiement

### Cycle de Développement Complet

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        CYCLE DE DÉVELOPPEMENT                                    │
└─────────────────────────────────────────────────────────────────────────────────┘

  ÉTAPE 1 : Développement
  ━━━━━━━━━━━━━━━━━━━━━━━
  
    Développeur           Git                    Serveur DEV
        │                  │                         │
        │  git push        │                         │
        │  feature/xxx ───▶│                         │
        │                  │                         │
        │  Pull Request    │                         │
        │  → dev      ────▶│                         │
        │                  │  webhook / cron         │
        │                  │────────────────────────▶│
        │                  │                         │ git pull dev
        │                  │                         │ docker restart
        │                  │                         │
        │                  │                    Tests sur DEV
        │                  │                         │
        
  ÉTAPE 2 : Validation Staging (optionnel)
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  
        │                  │                    Serveur DEV
        │                  │                    (port 8180)
        │  Créer tag       │                         │
        │  v1.2.0-rc1 ────▶│                         │
        │                  │────────────────────────▶│
        │                  │                         │ deploy-tag.sh v1.2.0-rc1
        │                  │                         │
        │                  │                    Tests staging
        │                  │                    (QA, PO, etc.)
        │                  │                         │
        
  ÉTAPE 3 : Release Production
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  
        │                  │                    Serveur PROD
        │  Merger          │                    (Proxmox)
        │  dev → main ────▶│                         │
        │                  │                         │
        │  Créer tag       │                         │
        │  v1.2.0     ────▶│                         │
        │                  │                         │
        │                  │   Manuel ou webhook     │
        │                  │────────────────────────▶│
        │                  │                         │ deploy-tag.sh v1.2.0
        │                  │                         │
        │                  │                    ✅ EN PRODUCTION
```

### Commandes par Environnement

#### Sur le Serveur DEV

```bash
# Auto-déploiement de la branche dev (cron toutes les 5 min ou webhook)
cd /opt/civicdash
git fetch origin dev
git checkout dev
git pull origin dev
docker compose restart app horizon scheduler
php artisan migrate --force
php artisan optimize

# Ou déployer un tag pour tester avant prod
./scripts/deploy-tag.sh v1.2.0-rc1
```

#### Sur le Serveur PROD (Proxmox)

```bash
# UNIQUEMENT des tags versionnés !
cd /opt/civicdash

# Lister les tags disponibles
./scripts/deploy-tag.sh --list

# Déployer une version spécifique
./scripts/deploy-tag.sh v1.2.0

# Rollback si problème
./scripts/deploy-tag.sh --rollback
```

---

## 🚀 Migration vers Production

### Phase 1 : Préparer le Nouveau Serveur Proxmox

```bash
# 1. Créer les VMs/LXC sur Proxmox
#    - VM Load Balancer (2 vCPU, 4 GB)
#    - LXC App (4 vCPU, 8 GB) x N
#    - VM Database (8-16 vCPU, 32-64 GB)
#    - VM Services (4 vCPU, 8 GB)

# 2. Cloner le projet
git clone https://github.com/votre-org/civicdash.git /opt/civicdash
cd /opt/civicdash

# 3. Configurer l'environnement production
cp docker/production/env.production.example .env
nano .env  # Configurer les variables

# 4. Déployer le dernier tag stable
./scripts/deploy-tag.sh --latest
```

### Phase 2 : Migrer les Données

```bash
# Option A : Base vierge (nouveau départ)
docker exec civicdash_app php artisan migrate --force
docker exec civicdash_app php artisan db:seed --force

# Option B : Migrer les données existantes
# Sur le serveur DEV (source)
docker exec civicdash_db pg_dump -U civicdash -Fc civicdash > /tmp/civicdash_prod.dump
scp /tmp/civicdash_prod.dump user@nouveau-serveur:/tmp/

# Sur le serveur PROD (destination)
docker exec -i civicdash_db pg_restore -U civicdash -d civicdash -c < /tmp/civicdash_prod.dump
docker exec civicdash_app php artisan migrate --force  # Appliquer les nouvelles migrations
```

### Phase 3 : Basculer le DNS

```bash
# 1. Tester le nouveau serveur avec un sous-domaine temporaire
#    new.objectif2027.fr → IP nouveau serveur

# 2. Quand tout est OK, basculer le DNS principal
#    demo.objectif2027.fr → IP nouveau serveur

# 3. Mettre à jour le serveur actuel
#    dev.objectif2027.fr → IP serveur actuel (DEV)
```

### Phase 4 : Configurer le Serveur DEV

```bash
# Sur le serveur actuel (devient DEV)
cd /opt/civicdash

# Basculer sur la branche dev
git checkout dev

# Créer un .env spécifique dev
cp .env .env.backup
nano .env  # Configurer pour environnement dev

# Redémarrer avec la config dev
docker compose -f docker-compose.yml up -d

# Optionnel : Créer des données de test
docker exec civicdash_app php artisan db:seed --class=DemoSeeder
```

---

## 📊 Résumé des Environnements

| Aspect | Serveur DEV | Serveur PROD |
|--------|-------------|--------------|
| **URL** | dev.objectif2027.fr | demo.objectif2027.fr |
| **Branche/Tag** | `dev` | Tags `vX.Y.Z` |
| **APP_DEBUG** | true | false |
| **LOG_LEVEL** | debug | warning |
| **Email** | Mailpit (test) | Mailu (réel) |
| **Telescope** | ✅ Activé | ❌ Désactivé |
| **Données** | Test/Demo | Production |
| **Backups** | Optionnel | Quotidien S3 |
| **Monitoring** | Basique | Prometheus+Grafana |
| **SSL** | Let's Encrypt | Let's Encrypt |

---

## 🛡️ Sécurité

### Serveur DEV

- Accès restreint (VPN ou IP whitelist)
- Données de test uniquement
- Telescope/Horizon accessibles pour debug

### Serveur PROD

- Accès public pour l'application
- Admin protégé par 2FA
- Logs d'audit
- Backups chiffrés
- Rate limiting
- WAF (Cloudflare ou similaire)

---

## 📅 Checklist Migration

```markdown
### Avant la migration
- [ ] Nouveau serveur Proxmox configuré
- [ ] VMs/LXC créées
- [ ] Docker installé
- [ ] Projet cloné
- [ ] .env configuré
- [ ] SSL configuré

### Migration
- [ ] Export des données de prod
- [ ] Import sur nouveau serveur
- [ ] Migrations appliquées
- [ ] Tests smoke (pages principales)
- [ ] Tests fonctionnels (login, votes, etc.)

### Bascule
- [ ] DNS mis à jour
- [ ] TTL DNS vérifié (faible avant bascule)
- [ ] Ancien serveur reconfiguré en DEV
- [ ] Monitoring activé sur PROD
- [ ] Backups automatiques configurés

### Post-migration
- [ ] Vérifier les logs d'erreurs
- [ ] Vérifier les performances
- [ ] Configurer les alertes
- [ ] Documenter les changements
```

---

💙 **CivicDash** - Architecture Multi-Environnements
