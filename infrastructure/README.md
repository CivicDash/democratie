# 🏗️ Infrastructure CivicDash - Proxmox 9.1

> Déploiement automatisé sur Proxmox VE 9.1 avec containers LXC depuis images OCI

## 🎯 Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      PROXMOX VE 9.1                             │
│                   (Serveur dédié / VPS)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  CT-100      │  │  CT-101      │  │  CT-102      │          │
│  │  traefik     │  │  civicdash   │  │  postgres    │          │
│  │  :80/:443    │  │  :8000       │  │  :5432       │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│         │                │                  │                   │
│         └────────────────┴──────────────────┘                   │
│                    vmbr1 (10.10.10.0/24)                        │
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  CT-103      │  │  CT-104      │  │  CT-105      │          │
│  │  redis       │  │  meilisearch │  │  backup      │          │
│  │  :6379       │  │  :7700       │  │  cron        │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## 📋 Prérequis

- Serveur avec **Proxmox VE 9.1+** installé
- Accès API Proxmox (token créé)
- **Terraform** >= 1.6 installé localement
- Domaine configuré (DNS A → IP serveur)

## 🚀 Déploiement en une commande

```bash
# 1. Configurer les variables
cp infrastructure/terraform/terraform.tfvars.example infrastructure/terraform/terraform.tfvars
nano infrastructure/terraform/terraform.tfvars

# 2. Déployer !
./infrastructure/scripts/deploy.sh
```

## 📁 Structure

```
infrastructure/
├── README.md                 # Ce fichier
├── terraform/
│   ├── main.tf              # Configuration principale
│   ├── variables.tf         # Variables
│   ├── terraform.tfvars     # Valeurs (à créer, git-ignoré)
│   ├── containers.tf        # Définition des containers
│   ├── network.tf           # Configuration réseau
│   └── outputs.tf           # Sorties
├── scripts/
│   ├── deploy.sh            # Script de déploiement complet
│   ├── setup-proxmox.sh     # Configuration initiale Proxmox
│   └── backup.sh            # Script de backup
└── docs/
    ├── SETUP.md             # Guide d'installation Proxmox
    ├── SCALING.md           # Guide de scaling
    └── TROUBLESHOOTING.md   # Dépannage
```

## 🔧 Configuration

### Variables requises (`terraform.tfvars`)

```hcl
# Proxmox
proxmox_api_url   = "https://proxmox.example.com:8006/api2/json"
proxmox_api_token = "terraform@pam!civicdash=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
proxmox_node      = "pve"

# Réseau
domain            = "objectif2027.fr"
public_ip         = "xxx.xxx.xxx.xxx"

# Application
app_version       = "v1.1.1"
db_password       = "super-secret-password"
redis_password    = "another-secret"
app_key           = "base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"

# Ressources
app_memory        = 4096
app_cores         = 4
db_memory         = 2048
db_cores          = 2
```

## 🐳 Images OCI

Les containers sont créés depuis des images OCI :

| Container | Image | Registry |
|-----------|-------|----------|
| traefik | `docker://traefik:v3.0` | Docker Hub |
| civicdash | `docker://ghcr.io/civicdash/app:latest` | GitHub CR |
| postgres | `docker://postgres:16-alpine` | Docker Hub |
| redis | `docker://redis:7-alpine` | Docker Hub |
| meilisearch | `docker://getmeili/meilisearch:v1.6` | Docker Hub |

## 📦 Build de l'image CivicDash

```bash
# Build et push vers GitHub Container Registry
docker build -t ghcr.io/civicdash/app:v1.1.1 .
docker push ghcr.io/civicdash/app:v1.1.1
```

## 🔄 Workflow CI/CD

```
GitHub Push (tag v*)
       │
       ▼
┌─────────────────┐
│  Build Image    │
│  Push to GHCR   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Terraform      │
│  API Proxmox    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Update LXC     │
│  Restart App    │
└─────────────────┘
```

## 🔒 Sécurité

- ✅ Containers unprivileged (sauf si nécessaire)
- ✅ Réseau interne isolé (vmbr1)
- ✅ Seul Traefik exposé publiquement
- ✅ TLS automatique via Let's Encrypt
- ✅ Firewall Proxmox activé
- ✅ Secrets dans variables d'environnement

## 📈 Scaling

### Horizontal (plusieurs instances app)

```hcl
# Dans containers.tf
variable "app_instances" {
  default = 3
}

resource "proxmox_virtual_environment_container" "civicdash" {
  count     = var.app_instances
  vm_id     = 101 + count.index
  hostname  = "civicdash-${count.index + 1}"
  # ...
}
```

### Vertical (plus de ressources)

```bash
# Modifier terraform.tfvars
app_memory = 8192
app_cores  = 8

# Appliquer
terraform apply
```

## 🔄 Mise à jour

```bash
# Nouvelle version
./infrastructure/scripts/deploy.sh --version v1.2.0

# Ou manuellement
terraform apply -var="app_version=v1.2.0"
```

## 💾 Backup

```bash
# Backup complet de tous les containers
./infrastructure/scripts/backup.sh

# Planifié via cron Proxmox (déjà configuré)
# Tous les jours à 3h00
```

## 🛠️ Commandes utiles

```bash
# Voir l'état de l'infra
terraform show

# Détruire un container spécifique
terraform destroy -target=proxmox_virtual_environment_container.redis

# Recréer l'application
terraform taint proxmox_virtual_environment_container.civicdash
terraform apply

# Logs d'un container
ssh root@proxmox pct exec 101 -- journalctl -f
```

## 📊 Monitoring

- **Proxmox GUI** : https://proxmox.example.com:8006
- **Traefik Dashboard** : https://traefik.objectif2027.fr
- **Application** : https://objectif2027.fr

---

**Version** : 1.0.0  
**Dernière mise à jour** : Janvier 2026  
**Auteur** : CivicDash Team
