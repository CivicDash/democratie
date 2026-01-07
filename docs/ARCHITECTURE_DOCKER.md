# 🐳 Architecture Docker - CivicDash Production

> **Version** : 2.0  
> **Dernière mise à jour** : Janvier 2026  
> **Auteur** : CivicDash Team

---

## 📋 Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Architecture Actuelle (Développement)](#architecture-actuelle-développement)
3. [Architecture Production (Docker)](#architecture-production-docker)
4. [Architecture Scalable (Proxmox)](#architecture-scalable-proxmox)
5. [Composants Détaillés](#composants-détaillés)
6. [PgBouncer : Connection Pooling](#pgbouncer--connection-pooling)
7. [Déploiement](#déploiement)
8. [Monitoring](#monitoring)
9. [Backup & Restore](#backup--restore)
10. [Estimation de Charge](#estimation-de-charge)

---

## 🌐 Vue d'Ensemble

CivicDash utilise une architecture Docker conteneurisée, conçue pour être :

- **🚀 Performante** : FrankenPHP + Laravel Octane (workers persistants)
- **📈 Scalable** : Scaling horizontal des workers applicatifs
- **🔒 Sécurisée** : Isolation réseau, SSL automatique, rate limiting
- **💾 Résiliente** : Données persistantes, healthchecks, auto-restart

---

## 🏗️ Architecture Actuelle (Développement)

```
┌─────────────────────────────────────────────────────────────────┐
│                    DOCKER COMPOSE (DEV)                         │
│                                                                 │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐       │
│  │    NGINX    │────▶│  PHP-FPM    │     │   MAILPIT   │       │
│  │   :8080     │     │  (Laravel)  │     │   :8025     │       │
│  └─────────────┘     └──────┬──────┘     └─────────────┘       │
│                             │                                   │
│         ┌───────────────────┼───────────────────┐              │
│         │                   │                   │              │
│         ▼                   ▼                   ▼              │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐       │
│  │ POSTGRESQL  │     │    REDIS    │     │ MEILISEARCH │       │
│  │    :5433    │     │    :6380    │     │    :7700    │       │
│  └─────────────┘     └─────────────┘     └─────────────┘       │
│                                                                 │
│  ┌─────────────┐     ┌─────────────┐                           │
│  │    QUEUE    │     │  SCHEDULER  │                           │
│  │  (Worker)   │     │   (Cron)    │                           │
│  └─────────────┘     └─────────────┘                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

📁 Fichier : docker-compose.yml
```

### Caractéristiques (Dev)

| Composant | Port Externe | Description |
|-----------|--------------|-------------|
| Nginx | 8080 | Reverse proxy + assets statiques |
| PHP-FPM | - | Laravel application |
| PostgreSQL | 5433 | Base de données |
| Redis | 6380 | Cache + Sessions + Queues |
| Meilisearch | 7700 | Recherche full-text |
| Mailpit | 8025 | Capture emails (dev) |

---

## 🚀 Architecture Production (Docker)

```
                              ┌─────────────────┐
                              │    INTERNET     │
                              │                 │
                              │  Utilisateurs   │
                              │  (~5,000+/jour) │
                              └────────┬────────┘
                                       │
                                       │ HTTPS (443)
                                       │ HTTP/3 QUIC
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                              TRAEFIK v3                                       │
│                         (Reverse Proxy + LB)                                 │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐ │
│  │  • Let's Encrypt SSL automatique                                        │ │
│  │  • HTTP → HTTPS redirection                                             │ │
│  │  • Load Balancing (Round Robin)                                         │ │
│  │  • Rate Limiting (100 req/s/IP)                                         │ │
│  │  • Security Headers (HSTS, X-Frame-Options, CSP)                        │ │
│  │  • Compression GZIP/Brotli                                              │ │
│  │  • HTTP/3 QUIC support                                                  │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────┘
                                       │
           ┌───────────────────────────┼───────────────────────────┐
           │                           │                           │
           ▼                           ▼                           ▼
┌─────────────────────┐     ┌─────────────────────┐     ┌─────────────────────┐
│    APP NODE 1       │     │    APP NODE 2       │     │    APP NODE N       │
│   ┌─────────────┐   │     │   ┌─────────────┐   │     │   ┌─────────────┐   │
│   │ FrankenPHP  │   │     │   │ FrankenPHP  │   │     │   │ FrankenPHP  │   │
│   │ + Octane    │   │     │   │ + Octane    │   │     │   │ + Octane    │   │
│   │             │   │     │   │             │   │     │   │             │   │
│   │ • Workers   │   │     │   │ • Workers   │   │     │   │ • Workers   │   │
│   │   auto-scale│   │     │   │   auto-scale│   │     │   │   auto-scale│   │
│   │ • 4 vCPU    │   │     │   │ • 4 vCPU    │   │     │   │ • 4 vCPU    │   │
│   │ • 4 GB RAM  │   │     │   │ • 4 GB RAM  │   │     │   │ • 4 GB RAM  │   │
│   └─────────────┘   │     └─────────────────────┘     └─────────────────────┘
│                     │
│   ┌─────────────┐   │
│   │  HORIZON    │   │     (Queue Workers - 1 instance suffit)
│   │  (Queues)   │   │
│   └─────────────┘   │
│                     │
│   ┌─────────────┐   │
│   │ SCHEDULER   │   │     (Cron - 1 instance suffit)
│   │  (Cron)     │   │
│   └─────────────┘   │
└──────────┬──────────┘
           │
           │  Connexions virtuelles (1000+)
           │
           ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            PGBOUNCER                                          │
│                      (Connection Pooling)                                     │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐ │
│  │  Mode : transaction (libère connexion après COMMIT)                     │ │
│  │  Max clients : 1000                                                     │ │
│  │  Pool size : 50 connexions réelles                                      │ │
│  │  Reserve pool : 10 connexions                                           │ │
│  │                                                                         │ │
│  │  ┌─────────────────────────────────────────────────────────────────┐   │ │
│  │  │   1000 connexions virtuelles  ────▶  50 connexions réelles      │   │ │
│  │  │   (économie 95% de ressources PostgreSQL)                       │   │ │
│  │  └─────────────────────────────────────────────────────────────────┘   │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────┘
           │
           │  50-100 connexions réelles
           ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                         POSTGRESQL 15                                         │
│                        (Primary Server)                                       │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐ │
│  │  • shared_buffers : 2 GB                                                │ │
│  │  • effective_cache_size : 6 GB                                          │ │
│  │  • work_mem : 32 MB                                                     │ │
│  │  • WAL Level : replica (prêt pour réplication)                          │ │
│  │  • Autovacuum optimisé                                                  │ │
│  │  • pg_stat_statements activé                                            │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌─────────────────────┐                   ┌─────────────────────┐          │
│  │   Volume Data       │                   │   Volume Backups    │          │
│  │  (NVMe SSD)         │                   │   (journalier)      │          │
│  └─────────────────────┘                   └─────────────────────┘          │
└──────────────────────────────────────────────────────────────────────────────┘
           │
           │ (Streaming Replication - Futur)
           ▼
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│     POSTGRESQL REPLICA           │  (Optionnel - Phase 2)
│     (Read-Only)                  │
│                                  │
│  • Queries SELECT lourdes        │
│  • Rapports / Analytics          │
│  • Failover automatique          │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘


┌──────────────────────────────────────────────────────────────────────────────┐
│                          SERVICES AUXILIAIRES                                 │
│                                                                              │
│  ┌─────────────────────┐     ┌─────────────────────┐     ┌─────────────────┐│
│  │       REDIS         │     │    MEILISEARCH      │     │     MAILU       ││
│  │    (Cache/Queue)    │     │   (Full-text)       │     │    (Email)      ││
│  │                     │     │                     │     │                 ││
│  │ • maxmemory: 1.5GB  │     │ • Index < 50ms      │     │ • SMTP interne  ││
│  │ • LRU eviction      │     │ • Typo-tolerant     │     │ • DKIM/SPF      ││
│  │ • AOF persistence   │     │ • Filtres avancés   │     │ • Antispam      ││
│  │                     │     │                     │     │                 ││
│  └─────────────────────┘     └─────────────────────┘     └─────────────────┘│
└──────────────────────────────────────────────────────────────────────────────┘


📁 Fichier : docker/production/docker-compose.production.yml
```

### Flux de Données

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                            FLUX D'UNE REQUÊTE                                 │
└──────────────────────────────────────────────────────────────────────────────┘

  Utilisateur                                                          
      │                                                                 
      │ 1. GET /propositions/123                                        
      ▼                                                                 
  ┌───────────┐                                                         
  │  TRAEFIK  │  2. SSL Termination + Headers sécurité                 
  └─────┬─────┘                                                         
        │                                                               
        │ 3. Load Balance vers App Node disponible                      
        ▼                                                               
  ┌───────────┐                                                         
  │    APP    │  4. Laravel Octane traite la requête                   
  └─────┬─────┘                                                         
        │                                                               
        ├──────────────────┬──────────────────┐                        
        │                  │                  │                        
        ▼                  ▼                  ▼                        
  ┌───────────┐     ┌───────────┐     ┌───────────┐                    
  │   REDIS   │     │ PGBOUNCER │     │MEILISEARCH│                    
  │           │     │           │     │           │                    
  │ 5a. Cache │     │ 5b. Pool  │     │ 5c. Search│                    
  │    Hit?   │     │           │     │           │                    
  └─────┬─────┘     └─────┬─────┘     └───────────┘                    
        │                 │                                             
        │                 ▼                                             
        │           ┌───────────┐                                       
        │           │ POSTGRES  │  6. Query SQL si cache miss          
        │           └─────┬─────┘                                       
        │                 │                                             
        ▼                 ▼                                             
  ┌───────────────────────────────────────┐                            
  │          RÉPONSE JSON/HTML             │                            
  │                                        │                            
  │  7. Cache Redis (si applicable)        │                            
  │  8. Retour via Traefik                 │                            
  │  9. Compression GZIP                   │                            
  └────────────────────┬──────────────────┘                            
                       │                                                
                       ▼                                                
                 Utilisateur                                            
                 (~5-50ms total)                                        
```

---

## 🖥️ Architecture Scalable (Proxmox)

Pour un scaling massif (50,000+ utilisateurs), voici l'architecture sur Proxmox :

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           PROXMOX VE HYPERVISOR                                  │
│                    (Bare Metal : 32+ cores, 128+ GB RAM)                         │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                        VM1 : LOAD BALANCER                               │   │
│  │                      (HAProxy ou Traefik)                                │   │
│  │                                                                          │   │
│  │   ┌─────────────────────────────────────────────────────────────────┐   │   │
│  │   │  • 2 vCPU, 4 GB RAM                                              │   │   │
│  │   │  • SSL Termination (Let's Encrypt)                               │   │   │
│  │   │  • Load Balancing (Least Connections)                            │   │   │
│  │   │  • Health Checks toutes les 10s                                  │   │   │
│  │   │  • Rate Limiting par IP                                          │   │   │
│  │   └─────────────────────────────────────────────────────────────────┘   │   │
│  └────────────────────────────────────┬────────────────────────────────────┘   │
│                                       │                                         │
│            ┌──────────────────────────┼──────────────────────────┐             │
│            │                          │                          │             │
│            ▼                          ▼                          ▼             │
│  ┌─────────────────────┐   ┌─────────────────────┐   ┌─────────────────────┐  │
│  │   LXC1 : APP NODE   │   │   LXC2 : APP NODE   │   │   LXC3 : APP NODE   │  │
│  │                     │   │                     │   │                     │  │
│  │  • 4 vCPU, 8 GB     │   │  • 4 vCPU, 8 GB     │   │  • 4 vCPU, 8 GB     │  │
│  │  • FrankenPHP       │   │  • FrankenPHP       │   │  • FrankenPHP       │  │
│  │  • Laravel Octane   │   │  • Laravel Octane   │   │  • Laravel Octane   │  │
│  │  • Horizon          │   │                     │   │                     │  │
│  │  • Scheduler        │   │                     │   │                     │  │
│  └──────────┬──────────┘   └──────────┬──────────┘   └──────────┬──────────┘  │
│             │                         │                         │             │
│             └─────────────────────────┼─────────────────────────┘             │
│                                       │                                        │
│                                       ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────────────┐  │
│  │                    VM2 : DATABASE CLUSTER                                │  │
│  │                                                                          │  │
│  │   ┌─────────────────────────────────────────────────────────────────┐   │  │
│  │   │                        PGBOUNCER                                 │   │  │
│  │   │                    (Connection Pooler)                           │   │  │
│  │   │                      1 vCPU, 512 MB                              │   │  │
│  │   └────────────────────────────┬────────────────────────────────────┘   │  │
│  │                                │                                         │  │
│  │                ┌───────────────┴───────────────┐                        │  │
│  │                │                               │                        │  │
│  │                ▼                               ▼                        │  │
│  │   ┌─────────────────────────┐   ┌─────────────────────────┐            │  │
│  │   │   POSTGRESQL PRIMARY    │   │   POSTGRESQL REPLICA    │            │  │
│  │   │                         │   │                         │            │  │
│  │   │  • 8 vCPU, 32 GB RAM    │   │  • 4 vCPU, 16 GB RAM    │            │  │
│  │   │  • NVMe SSD 500GB       │◀─▶│  • NVMe SSD 500GB       │            │  │
│  │   │  • Read/Write           │   │  • Read Only            │            │  │
│  │   │                         │   │  • Streaming Replication│            │  │
│  │   └─────────────────────────┘   └─────────────────────────┘            │  │
│  │                                                                          │  │
│  │   ┌─────────────────────────┐   ┌─────────────────────────┐            │  │
│  │   │         REDIS           │   │      MEILISEARCH        │            │  │
│  │   │                         │   │                         │            │  │
│  │   │  • 2 vCPU, 8 GB RAM     │   │  • 4 vCPU, 8 GB RAM     │            │  │
│  │   │  • Persistence AOF      │   │  • Full-text index      │            │  │
│  │   └─────────────────────────┘   └─────────────────────────┘            │  │
│  └─────────────────────────────────────────────────────────────────────────┘  │
│                                                                                │
│  ┌─────────────────────────────────────────────────────────────────────────┐  │
│  │                      VM3 : SERVICES ANNEXES                              │  │
│  │                                                                          │  │
│  │   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐   │  │
│  │   │    MAILU    │  │  PROMETHEUS │  │   GRAFANA   │  │   BACKUP    │   │  │
│  │   │   (Email)   │  │ (Metrics)   │  │ (Dashboard) │  │  (restic)   │   │  │
│  │   └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘   │  │
│  │                                                                          │  │
│  │   • 4 vCPU, 8 GB RAM total                                              │  │
│  │   • Backup quotidien vers S3/Backblaze                                  │  │
│  └─────────────────────────────────────────────────────────────────────────┘  │
│                                                                                │
├────────────────────────────────────────────────────────────────────────────────┤
│                              STOCKAGE                                          │
│                                                                                │
│   ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐             │
│   │   ZFS Pool 1    │   │   ZFS Pool 2    │   │  Backup (NAS)   │             │
│   │   (NVMe SSD)    │   │   (HDD RAID)    │   │                 │             │
│   │   VMs + Data    │   │   Archives      │   │   Snapshots     │             │
│   └─────────────────┘   └─────────────────┘   └─────────────────┘             │
└────────────────────────────────────────────────────────────────────────────────┘
```

### Ressources Estimées (Proxmox)

| VM/LXC | vCPU | RAM | Stockage | Rôle |
|--------|------|-----|----------|------|
| VM1 - LB | 2 | 4 GB | 20 GB | Load Balancer Traefik |
| LXC1-3 - App | 4×3 | 8×3 GB | 50×3 GB | Nodes Laravel |
| VM2 - DB | 16 | 64 GB | 500 GB NVMe | PostgreSQL + Redis |
| VM3 - Services | 4 | 8 GB | 100 GB | Monitoring + Backup |
| **TOTAL** | **34** | **100 GB** | **820 GB** | |

---

## 🔧 Composants Détaillés

### FrankenPHP + Laravel Octane

```
┌─────────────────────────────────────────────────────────────────┐
│                      FRANKENPHP + OCTANE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Avantages vs PHP-FPM :                                         │
│  ━━━━━━━━━━━━━━━━━━━━━                                          │
│                                                                 │
│  PHP-FPM (classique) :                                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Requête 1 → Bootstrap Laravel (150ms) → Réponse        │   │
│  │  Requête 2 → Bootstrap Laravel (150ms) → Réponse        │   │
│  │  Requête 3 → Bootstrap Laravel (150ms) → Réponse        │   │
│  │                                                          │   │
│  │  Chaque requête : ~150-300ms de bootstrap                │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  FrankenPHP + Octane :                                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Bootstrap Laravel (1 fois au démarrage)                 │   │
│  │       │                                                  │   │
│  │       ▼                                                  │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐     │   │
│  │  │Worker 1 │  │Worker 2 │  │Worker 3 │  │Worker N │     │   │
│  │  │ (prêt)  │  │ (prêt)  │  │ (prêt)  │  │ (prêt)  │     │   │
│  │  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘     │   │
│  │       │            │            │            │           │   │
│  │  Req 1: 5ms   Req 2: 5ms   Req 3: 5ms   Req 4: 5ms      │   │
│  │                                                          │   │
│  │  Gain : 30-50× plus rapide !                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Configuration :                                                │
│  • Workers : auto (basé sur CPU)                                │
│  • Max requests : 1000 (avant restart)                          │
│  • Memory limit : 512 MB                                        │
│  • HTTP/3 QUIC supporté                                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔀 PgBouncer : Connection Pooling

### Pourquoi PgBouncer ?

```
┌─────────────────────────────────────────────────────────────────┐
│                    PROBLÈME SANS PGBOUNCER                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PostgreSQL crée un processus par connexion (~10 MB RAM)        │
│                                                                 │
│  ┌─────────────┐                                                │
│  │  App Node 1 │──────┐                                         │
│  │ (50 workers)│      │                                         │
│  └─────────────┘      │     ┌─────────────────┐                 │
│                       ├────▶│   POSTGRESQL    │                 │
│  ┌─────────────┐      │     │                 │                 │
│  │  App Node 2 │──────┤     │ 150 connexions  │                 │
│  │ (50 workers)│      │     │ = 150 processus │                 │
│  └─────────────┘      │     │ = 1.5 GB RAM    │                 │
│                       │     │                 │                 │
│  ┌─────────────┐      │     │ ❌ Non scalable │                 │
│  │  App Node 3 │──────┘     └─────────────────┘                 │
│  │ (50 workers)│                                                │
│  └─────────────┘                                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    SOLUTION AVEC PGBOUNCER                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐                                                │
│  │  App Node 1 │──────┐                                         │
│  │ (50 workers)│      │     ┌─────────────────┐                 │
│  └─────────────┘      │     │    PGBOUNCER    │                 │
│                       ├────▶│                 │                 │
│  ┌─────────────┐      │     │ 150 virtuelles  │                 │
│  │  App Node 2 │──────┤     │      ↓          │     ┌────────┐  │
│  │ (50 workers)│      │     │  50 réelles     │────▶│POSTGRES│  │
│  └─────────────┘      │     │                 │     │        │  │
│                       │     │ Mode: transaction│     │50 conn │  │
│  ┌─────────────┐      │     │                 │     │= 500MB │  │
│  │  App Node 3 │──────┘     └─────────────────┘     │        │  │
│  │ (50 workers)│                                    │✅ Scale│  │
│  └─────────────┘                                    └────────┘  │
│                                                                 │
│  Économie : 66% de connexions en moins !                        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Modes de Pooling

| Mode | Description | Usage |
|------|-------------|-------|
| **session** | Connexion dédiée par session client | Transactions longues |
| **transaction** ✅ | Connexion libérée après COMMIT | Laravel (recommandé) |
| **statement** | Connexion libérée après chaque requête | Pas pour Laravel |

### Configuration Recommandée

```ini
[pgbouncer]
pool_mode = transaction
max_client_conn = 1000      # Connexions virtuelles
default_pool_size = 50      # Connexions réelles
min_pool_size = 10          # Minimum maintenu
reserve_pool_size = 10      # En cas de pic
server_reset_query = DISCARD ALL
```

---

## 🚀 Déploiement

### Première Installation

```bash
# 1. Cloner le projet
cd /opt
git clone https://github.com/civicdash/civicdash.git
cd civicdash

# 2. Copier la configuration
cp docker/production/env.production.example .env.production
nano .env.production  # Adapter les variables

# 3. Générer les secrets
php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
php -r "echo 'MEILISEARCH_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL;"
php -r "echo 'DB_PASSWORD=' . bin2hex(random_bytes(24)) . PHP_EOL;"

# 4. Générer le hash PgBouncer
# (Après premier démarrage PostgreSQL)
docker exec civicdash_db_primary psql -U civicdash -c \
  "SELECT 'civicdash' || ' ' || rolpassword FROM pg_authid WHERE rolname='civicdash';"
# Copier le résultat dans docker/production/pgbouncer/userlist.txt

# 5. Démarrer les services
docker compose -f docker/production/docker-compose.production.yml up -d

# 6. Migrations
docker exec civicdash_app php artisan migrate --force
docker exec civicdash_app php artisan db:seed --force

# 7. Indexation Meilisearch
docker exec civicdash_app php artisan scout:import "App\Models\Topic"
docker exec civicdash_app php artisan scout:import "App\Models\ActeurAN"
```

### Mise à Jour

```bash
# 1. Pull des nouvelles images
git pull origin main

# 2. Rebuild
docker compose -f docker/production/docker-compose.production.yml build

# 3. Redéploiement sans downtime
docker compose -f docker/production/docker-compose.production.yml up -d --no-deps app

# 4. Migrations
docker exec civicdash_app php artisan migrate --force

# 5. Clear cache
docker exec civicdash_app php artisan optimize:clear
docker exec civicdash_app php artisan optimize
```

---

## 📊 Monitoring

### Stack Prometheus + Grafana

```
┌─────────────────────────────────────────────────────────────────┐
│                       MONITORING STACK                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐       │
│  │    APP      │     │   REDIS     │     │  POSTGRES   │       │
│  │  /metrics   │     │  exporter   │     │  exporter   │       │
│  └──────┬──────┘     └──────┬──────┘     └──────┬──────┘       │
│         │                   │                   │               │
│         └───────────────────┼───────────────────┘               │
│                             │                                   │
│                             ▼                                   │
│                    ┌─────────────────┐                         │
│                    │   PROMETHEUS    │                         │
│                    │                 │                         │
│                    │ • Scrape 15s    │                         │
│                    │ • Retention 30d │                         │
│                    │ • Alerting      │                         │
│                    └────────┬────────┘                         │
│                             │                                   │
│                             ▼                                   │
│                    ┌─────────────────┐                         │
│                    │    GRAFANA      │                         │
│                    │                 │                         │
│                    │ • Dashboards    │                         │
│                    │ • Alertes Slack │                         │
│                    │ • Visualisation │                         │
│                    └─────────────────┘                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Métriques Clés

| Métrique | Seuil Warning | Seuil Critical |
|----------|---------------|----------------|
| CPU App | > 70% | > 90% |
| RAM App | > 80% | > 95% |
| Latence P95 | > 500ms | > 2s |
| Erreurs 5xx | > 1% | > 5% |
| Connexions PG | > 80% pool | > 95% pool |
| Redis Memory | > 80% | > 95% |

---

## 💾 Backup & Restore

### Stratégie de Backup

```
┌─────────────────────────────────────────────────────────────────┐
│                       BACKUP STRATEGY                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PostgreSQL :                                                   │
│  ━━━━━━━━━━━━                                                   │
│  • pg_dump quotidien (compressé)                                │
│  • WAL archiving continu                                        │
│  • Rétention : 30 jours                                         │
│                                                                 │
│  Redis :                                                        │
│  ━━━━━━                                                         │
│  • RDB snapshot toutes les heures                               │
│  • AOF persistence                                              │
│                                                                 │
│  Fichiers (storage/app) :                                       │
│  ━━━━━━━━━━━━━━━━━━━━━━━                                         │
│  • Restic incremental                                           │
│  • Destination : S3 / Backblaze B2                              │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    SCHEDULE                              │   │
│  │                                                          │   │
│  │  00:00  ────────────▶  Full Backup PostgreSQL           │   │
│  │  */1h   ────────────▶  Incremental Files                │   │
│  │  */6h   ────────────▶  Verification Backup              │   │
│  │  Dimanche ──────────▶  Test Restore (staging)           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Script de Backup

```bash
#!/bin/bash
# docker/production/scripts/backup.sh

BACKUP_DIR="/backups/$(date +%Y-%m-%d)"
mkdir -p $BACKUP_DIR

# PostgreSQL
docker exec civicdash_db_primary pg_dump -U civicdash -Fc civicdash \
  > $BACKUP_DIR/civicdash.dump

# Redis
docker exec civicdash_redis redis-cli BGSAVE

# Upload S3
restic -r s3:s3.amazonaws.com/civicdash-backups backup $BACKUP_DIR

# Cleanup (30 days)
restic forget --keep-daily 7 --keep-weekly 4 --keep-monthly 6
```

---

## 📈 Estimation de Charge

### Capacité par Configuration

```
┌─────────────────────────────────────────────────────────────────┐
│                    ESTIMATION DE CHARGE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Configuration Actuelle (1 VPS) :                               │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━                                 │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Serveur : 4-8 vCPU, 16-32 GB RAM                        │   │
│  │                                                          │   │
│  │  • Utilisateurs simultanés : 500 - 1,000                 │   │
│  │  • Requêtes/seconde : 200 - 500                          │   │
│  │  • Latence P95 : < 100ms                                 │   │
│  │  • Uptime cible : 99.5%                                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Configuration Production (Docker optimisé) :                   │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━                     │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  • Utilisateurs simultanés : 2,000 - 5,000               │   │
│  │  • Requêtes/seconde : 500 - 1,500                        │   │
│  │  • Latence P95 : < 50ms                                  │   │
│  │  • Uptime cible : 99.9%                                  │   │
│  │                                                          │   │
│  │  Gain vs Dev : ~3-5× plus de capacité                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Configuration Proxmox (VM dédiée) :                            │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━                              │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  • Utilisateurs simultanés : 10,000 - 50,000             │   │
│  │  • Requêtes/seconde : 3,000 - 10,000                     │   │
│  │  • Latence P95 : < 30ms                                  │   │
│  │  • Uptime cible : 99.99%                                 │   │
│  │                                                          │   │
│  │  Scaling horizontal : +1 node = +1,500 req/s             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Points de Saturation

| Composant | Signe de Saturation | Solution |
|-----------|---------------------|----------|
| **CPU App** | Latence > 500ms | Ajouter App Nodes |
| **PostgreSQL** | Connexions > 80% | PgBouncer + Replica |
| **Redis** | Évictions fréquentes | Augmenter RAM |
| **Meilisearch** | Indexation lente | Augmenter RAM/CPU |
| **Réseau** | Bandwidth saturé | CDN + Compression |

---

## 📁 Structure des Fichiers

```
docker/production/
├── docker-compose.production.yml    # Orchestration principale
├── env.production.example           # Variables d'environnement
├── postgres/
│   ├── postgresql.conf              # Config PostgreSQL optimisée
│   └── pg_hba.conf                  # Authentification PostgreSQL
├── pgbouncer/
│   ├── pgbouncer.ini                # Config PgBouncer
│   └── userlist.txt                 # Users PgBouncer (généré)
├── monitoring/
│   └── prometheus.yml               # Config Prometheus
└── scripts/
    ├── backup.sh                    # Script backup
    ├── deploy.sh                    # Script déploiement
    └── healthcheck.sh               # Vérification santé
```

---

## 🔗 Liens Utiles

- **Production** : https://demo.objectif2027.fr
- **Traefik Dashboard** : https://traefik.demo.objectif2027.fr
- **Grafana** : https://grafana.demo.objectif2027.fr
- **Horizon** : https://demo.objectif2027.fr/horizon

---

💙 **CivicDash** - Architecture Docker Production

*Document généré en Janvier 2026*
