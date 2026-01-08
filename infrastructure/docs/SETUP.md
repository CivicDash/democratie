# 🔧 Guide d'installation Proxmox VE 9.1

## Prérequis serveur

### Spécifications minimales recommandées

| Composant | Minimum | Recommandé |
|-----------|---------|------------|
| CPU | 4 cores | 8+ cores |
| RAM | 8 GB | 16+ GB |
| Stockage | 100 GB SSD | 250+ GB NVMe |
| Réseau | 100 Mbps | 1 Gbps |

### Répartition des ressources

```
Total: 16 GB RAM / 8 cores
├── Traefik      :  512 MB /  1 core
├── CivicDash    : 4096 MB /  4 cores
├── PostgreSQL   : 2048 MB /  2 cores
├── Redis        :  512 MB /  1 core
├── Meilisearch  : 1024 MB /  2 cores
└── Système PVE  : ~7 GB   /  libre
```

## Installation Proxmox VE 9.1

### 1. Télécharger l'ISO

```bash
# Depuis https://www.proxmox.com/en/downloads
wget https://enterprise.proxmox.com/iso/proxmox-ve_9.1-1.iso
```

### 2. Créer une clé USB bootable

```bash
# Linux
sudo dd if=proxmox-ve_9.1-1.iso of=/dev/sdX bs=4M status=progress

# macOS
sudo dd if=proxmox-ve_9.1-1.iso of=/dev/rdiskX bs=4m
```

### 3. Installation

1. Booter sur la clé USB
2. Sélectionner "Install Proxmox VE"
3. Accepter la licence
4. Choisir le disque d'installation (ZFS recommandé)
5. Configurer :
   - Pays/Timezone
   - Mot de passe root
   - Email admin
6. Configuration réseau :
   - Hostname : `pve.local`
   - IP : Selon votre réseau
   - Gateway : Routeur
   - DNS : 1.1.1.1 ou autre

### 4. Premier accès

```
URL : https://<IP>:8006
User : root
Pass : (celui configuré)
```

## Configuration post-installation

### Exécuter le script de setup

```bash
# Depuis votre machine locale
scp infrastructure/scripts/setup-proxmox.sh root@proxmox:/tmp/
ssh root@proxmox 'bash /tmp/setup-proxmox.sh'
```

### Ou manuellement

#### Désactiver le repo Enterprise

```bash
# Commenter la ligne
sed -i 's/^deb/#deb/' /etc/apt/sources.list.d/pve-enterprise.list

# Ajouter le repo no-subscription
echo "deb http://download.proxmox.com/debian/pve bookworm pve-no-subscription" >> /etc/apt/sources.list
```

#### Mettre à jour

```bash
apt update && apt full-upgrade -y
```

#### Créer le token API

```bash
# Créer l'utilisateur
pveum user add terraform@pam --comment "Terraform automation"

# Créer le rôle avec les bonnes permissions
pveum role add TerraformRole -privs "Datastore.AllocateSpace Datastore.AllocateTemplate Datastore.Audit Pool.Allocate Sys.Audit Sys.Console Sys.Modify SDN.Use VM.Allocate VM.Audit VM.Clone VM.Config.CDROM VM.Config.Cloudinit VM.Config.CPU VM.Config.Disk VM.Config.HWType VM.Config.Memory VM.Config.Network VM.Config.Options VM.Console VM.Migrate VM.Monitor VM.PowerMgmt"

# Assigner le rôle
pveum aclmod / -user terraform@pam -role TerraformRole

# Créer le token
pveum user token add terraform@pam civicdash --privsep=0
```

**⚠️ Notez le token affiché !**

## Configuration réseau

### Architecture réseau

```
Internet
    │
    ▼
┌────────────┐
│  vmbr0     │  ← Bridge public (IP serveur)
│  (bridge)  │
└─────┬──────┘
      │
      ├── CT-100 (Traefik) eth0 ← Seul container exposé
      │
┌─────┴──────┐
│  vmbr1     │  ← Bridge interne (10.10.10.0/24)
│  (bridge)  │
└────────────┘
      │
      ├── CT-100 (Traefik) eth1
      ├── CT-101 (CivicDash)
      ├── CT-102 (PostgreSQL)
      ├── CT-103 (Redis)
      └── CT-104 (Meilisearch)
```

### Créer le bridge interne (automatique via Terraform)

Si vous voulez le créer manuellement :

```bash
# Dans /etc/network/interfaces
auto vmbr1
iface vmbr1 inet static
    address 10.10.10.1/24
    bridge-ports none
    bridge-stp off
    bridge-fd 0
    post-up   echo 1 > /proc/sys/net/ipv4/ip_forward
    post-up   iptables -t nat -A POSTROUTING -s '10.10.10.0/24' -o vmbr0 -j MASQUERADE
    post-down iptables -t nat -D POSTROUTING -s '10.10.10.0/24' -o vmbr0 -j MASQUERADE
```

## Stockage

### Options recommandées

| Type | Usage | Performance |
|------|-------|-------------|
| **ZFS** | Production | ⭐⭐⭐⭐⭐ |
| LVM-thin | Dev/Test | ⭐⭐⭐⭐ |
| Directory | Backup | ⭐⭐⭐ |

### Créer un pool ZFS (si non fait à l'installation)

```bash
# Avec un seul disque
zpool create -f -o ashift=12 rpool /dev/sda

# Avec RAID (mirror)
zpool create -f -o ashift=12 rpool mirror /dev/sda /dev/sdb

# Ajouter à Proxmox
pvesm add zfspool local-zfs -pool rpool/data
```

## Firewall

### Règles de base

Le firewall Proxmox est configuré automatiquement par le script.
Règles appliquées :

- ✅ SSH (22)
- ✅ Proxmox GUI (8006)
- ✅ HTTP (80)
- ✅ HTTPS (443)
- ❌ Tout le reste bloqué

### Vérifier le firewall

```bash
# Voir les règles
pve-firewall status

# Voir les logs
tail -f /var/log/pve-firewall.log
```

## Prochaines étapes

1. ✅ Proxmox installé et configuré
2. ➡️ Copier le token API dans `terraform.tfvars`
3. ➡️ Lancer `./deploy.sh`

---

📚 Documentation officielle : https://pve.proxmox.com/pve-docs/
