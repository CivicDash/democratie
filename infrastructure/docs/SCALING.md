# 📈 Guide de Scaling CivicDash

## Scaling Vertical (Plus de ressources)

### Modifier les ressources d'un container

```bash
# Éditer terraform.tfvars
app_memory = 8192  # 8 GB au lieu de 4
app_cores  = 8     # 8 cores au lieu de 4

# Appliquer
cd infrastructure/terraform
terraform apply
```

### Via l'interface Proxmox

1. Arrêter le container
2. Hardware → Memory/CPU
3. Modifier les valeurs
4. Redémarrer

### Via CLI Proxmox

```bash
# Modifier la RAM (à chaud possible)
pct set 101 -memory 8192

# Modifier les CPU (requiert redémarrage)
pct set 101 -cores 8
pct reboot 101
```

## Scaling Horizontal (Plusieurs instances)

### Architecture multi-instances

```
                    ┌──────────────┐
                    │   Traefik    │
                    │ Load Balancer│
                    └──────┬───────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│  CivicDash   │   │  CivicDash   │   │  CivicDash   │
│  Instance 1  │   │  Instance 2  │   │  Instance 3  │
│  10.10.10.11 │   │  10.10.10.21 │   │  10.10.10.31 │
└──────────────┘   └──────────────┘   └──────────────┘
        │                  │                  │
        └──────────────────┴──────────────────┘
                           │
                    ┌──────┴───────┐
                    │  Services    │
                    │ PostgreSQL   │
                    │ Redis        │
                    │ Meilisearch  │
                    └──────────────┘
```

### Modifier Terraform pour multi-instances

```hcl
# variables.tf
variable "app_instances" {
  description = "Nombre d'instances de l'application"
  type        = number
  default     = 1
}

# containers.tf
resource "proxmox_virtual_environment_container" "civicdash" {
  count     = var.app_instances
  node_name = var.proxmox_node
  vm_id     = 101 + count.index

  initialization {
    hostname = "civicdash-${count.index + 1}"

    ip_config {
      ipv4 {
        address = "10.10.10.${11 + (count.index * 10)}/24"
        gateway = "10.10.10.1"
      }
    }
  }

  # ... reste de la config
}
```

### Configuration Traefik pour load balancing

```yaml
# traefik/dynamic.yml
http:
  routers:
    civicdash:
      rule: "Host(`objectif2027.fr`)"
      service: civicdash
      tls:
        certResolver: letsencrypt

  services:
    civicdash:
      loadBalancer:
        servers:
          - url: "http://10.10.10.11:8000"
          - url: "http://10.10.10.21:8000"
          - url: "http://10.10.10.31:8000"
        healthCheck:
          path: /health
          interval: 10s
```

## Sessions et Cache

### Configuration Laravel pour multi-instances

```env
# .env - Sessions stockées dans Redis
SESSION_DRIVER=redis
SESSION_CONNECTION=default

# Cache partagé
CACHE_DRIVER=redis

# Queue partagée
QUEUE_CONNECTION=redis
```

### Configuration Redis

```php
// config/database.php
'redis' => [
    'client' => 'phpredis',
    'default' => [
        'host' => env('REDIS_HOST', '10.10.10.13'),
        'password' => env('REDIS_PASSWORD'),
        'port' => 6379,
        'database' => 0,
    ],
    'sessions' => [
        'host' => env('REDIS_HOST', '10.10.10.13'),
        'password' => env('REDIS_PASSWORD'),
        'port' => 6379,
        'database' => 1,
    ],
],
```

## Scaling de la base de données

### Read Replicas PostgreSQL

```
┌──────────────┐
│   Primary    │
│  PostgreSQL  │
│ (read/write) │
└──────┬───────┘
       │ Replication
       │
┌──────┴───────┐
│              │
▼              ▼
┌──────────┐  ┌──────────┐
│ Replica  │  │ Replica  │
│   #1     │  │   #2     │
│ (read)   │  │ (read)   │
└──────────┘  └──────────┘
```

### Configuration Laravel pour replicas

```php
// config/database.php
'pgsql' => [
    'read' => [
        ['host' => '10.10.10.22'],  // Replica 1
        ['host' => '10.10.10.23'],  // Replica 2
    ],
    'write' => [
        ['host' => '10.10.10.12'],  // Primary
    ],
    'sticky' => true,
    // ...
],
```

## Cluster Proxmox (Multi-nodes)

### Architecture haute disponibilité

```
┌─────────────────────────────────────────────────────────────┐
│                    Proxmox Cluster                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐       │
│  │   Node 1    │   │   Node 2    │   │   Node 3    │       │
│  │  (Paris)    │   │  (Lyon)     │   │ (Marseille) │       │
│  └─────────────┘   └─────────────┘   └─────────────┘       │
│         │                │                  │               │
│         └────────────────┴──────────────────┘               │
│                    Ceph Storage                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Créer un cluster

```bash
# Sur le premier node
pvecm create civicdash-cluster

# Sur les autres nodes
pvecm add <IP_NODE_1>

# Vérifier
pvecm status
```

### Migration live

```bash
# Migrer un container vers un autre node
pct migrate 101 node2 --online
```

## Monitoring du scaling

### Métriques à surveiller

| Métrique | Seuil d'alerte | Action |
|----------|----------------|--------|
| CPU > 80% | 5 min | Scale vertical ou horizontal |
| RAM > 85% | 5 min | Scale vertical |
| Disk I/O > 90% | 10 min | Optimiser ou SSD plus rapide |
| Response time > 500ms | 1 min | Scale horizontal |
| Queue jobs > 1000 | - | Ajouter workers |

### Prometheus + Grafana

```yaml
# docker-compose monitoring
services:
  prometheus:
    image: prom/prometheus
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
```

## Coûts estimés

### Scaling vertical

| Config | RAM | CPU | Coût mensuel* |
|--------|-----|-----|---------------|
| Small | 8 GB | 4 cores | ~20€ |
| Medium | 16 GB | 8 cores | ~40€ |
| Large | 32 GB | 16 cores | ~80€ |

### Scaling horizontal

| Instances | RAM total | Coût mensuel* |
|-----------|-----------|---------------|
| 1 | 8 GB | ~20€ |
| 3 | 24 GB | ~50€ |
| 5 | 40 GB | ~80€ |

*Estimations basées sur VPS/dédié EU

---

📚 Plus d'infos : https://pve.proxmox.com/wiki/High_Availability
