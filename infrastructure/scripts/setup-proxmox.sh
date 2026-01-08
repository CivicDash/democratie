#!/bin/bash
# =============================================================================
# CivicDash - Configuration initiale Proxmox
# =============================================================================
# Ce script configure un serveur Proxmox VE 9.1 fraîchement installé
# pour accueillir l'infrastructure CivicDash
# 
# Usage: ssh root@proxmox 'bash -s' < setup-proxmox.sh
# =============================================================================

set -euo pipefail

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  🔧 Configuration Proxmox VE pour CivicDash                   ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# =============================================================================
# 1. Désactiver le dépôt Enterprise (si pas de subscription)
# =============================================================================
echo "→ Configuration des dépôts..."

# Commenter le repo enterprise
if [[ -f /etc/apt/sources.list.d/pve-enterprise.list ]]; then
    sed -i 's/^deb/#deb/' /etc/apt/sources.list.d/pve-enterprise.list
fi

# Ajouter le repo no-subscription
if ! grep -q "pve-no-subscription" /etc/apt/sources.list; then
    echo "deb http://download.proxmox.com/debian/pve bookworm pve-no-subscription" >> /etc/apt/sources.list
fi

echo "✅ Dépôts configurés"

# =============================================================================
# 2. Mise à jour du système
# =============================================================================
echo ""
echo "→ Mise à jour du système..."

apt update && apt full-upgrade -y

echo "✅ Système à jour"

# =============================================================================
# 3. Installation des outils
# =============================================================================
echo ""
echo "→ Installation des outils..."

apt install -y \
    curl \
    wget \
    htop \
    vim \
    git \
    jq \
    unzip

echo "✅ Outils installés"

# =============================================================================
# 4. Configuration du stockage
# =============================================================================
echo ""
echo "→ Vérification du stockage..."

# Afficher les pools de stockage
pvesm status

echo "✅ Stockage vérifié"

# =============================================================================
# 5. Création du token API pour Terraform
# =============================================================================
echo ""
echo "→ Création du token API Terraform..."

# Créer l'utilisateur terraform s'il n'existe pas
if ! pveum user list | grep -q "terraform@pam"; then
    pveum user add terraform@pam --comment "Terraform automation"
    echo "  → Utilisateur terraform@pam créé"
fi

# Créer le rôle terraform s'il n'existe pas
if ! pveum role list | grep -q "TerraformRole"; then
    pveum role add TerraformRole -privs "Datastore.AllocateSpace Datastore.AllocateTemplate Datastore.Audit Pool.Allocate Sys.Audit Sys.Console Sys.Modify SDN.Use VM.Allocate VM.Audit VM.Clone VM.Config.CDROM VM.Config.Cloudinit VM.Config.CPU VM.Config.Disk VM.Config.HWType VM.Config.Memory VM.Config.Network VM.Config.Options VM.Console VM.Migrate VM.Monitor VM.PowerMgmt"
    echo "  → Rôle TerraformRole créé"
fi

# Assigner le rôle à l'utilisateur
pveum aclmod / -user terraform@pam -role TerraformRole

# Créer le token
TOKEN_OUTPUT=$(pveum user token add terraform@pam civicdash --privsep=0 2>&1 || true)

if echo "$TOKEN_OUTPUT" | grep -q "already exists"; then
    echo "  → Token existant, suppression et recréation..."
    pveum user token remove terraform@pam civicdash
    TOKEN_OUTPUT=$(pveum user token add terraform@pam civicdash --privsep=0)
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  🔑 TOKEN API TERRAFORM                                       ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "$TOKEN_OUTPUT"
echo ""
echo "⚠️  IMPORTANT: Notez ce token, il ne sera plus affiché !"
echo ""

# =============================================================================
# 6. Configuration réseau (bridge interne)
# =============================================================================
echo ""
echo "→ Vérification du réseau..."

# Le bridge vmbr1 sera créé par Terraform
# Mais on vérifie que vmbr0 existe
if ! ip link show vmbr0 &> /dev/null; then
    echo "⚠️  vmbr0 non trouvé - vérifiez la configuration réseau"
else
    echo "✅ Bridge vmbr0 présent"
fi

# =============================================================================
# 7. Configuration du firewall
# =============================================================================
echo ""
echo "→ Activation du firewall..."

# Activer le firewall au niveau du datacenter
if [[ -f /etc/pve/firewall/cluster.fw ]]; then
    sed -i 's/enable: 0/enable: 1/' /etc/pve/firewall/cluster.fw 2>/dev/null || true
fi

# Créer la config de base si elle n'existe pas
mkdir -p /etc/pve/firewall
cat > /etc/pve/firewall/cluster.fw << 'EOF'
[OPTIONS]
enable: 1
policy_in: DROP
policy_out: ACCEPT

[RULES]
IN ACCEPT -p tcp -dport 22 # SSH
IN ACCEPT -p tcp -dport 8006 # Proxmox GUI
IN ACCEPT -p tcp -dport 80 # HTTP
IN ACCEPT -p tcp -dport 443 # HTTPS
EOF

echo "✅ Firewall configuré"

# =============================================================================
# 8. Configuration des backups
# =============================================================================
echo ""
echo "→ Configuration des backups..."

# Créer le répertoire de backup s'il n'existe pas
mkdir -p /var/lib/vz/dump

# Créer un job de backup automatique
cat > /etc/pve/jobs.cfg << 'EOF'
vzdump: backup-civicdash
    enabled 1
    schedule 0 3 * * *
    storage local
    vmid 100,101,102,103,104
    mailnotification failure
    mode snapshot
    compress zstd
    prune-backups keep-daily=7,keep-weekly=4
EOF

echo "✅ Backups configurés (tous les jours à 3h)"

# =============================================================================
# 9. Résumé
# =============================================================================
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  ✅ Configuration Proxmox terminée !                          ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "Prochaines étapes :"
echo "  1. Copiez le token API ci-dessus dans terraform.tfvars"
echo "  2. Lancez le déploiement : ./deploy.sh"
echo ""
echo "Accès Proxmox :"
echo "  → https://$(hostname -I | awk '{print $1}'):8006"
echo ""
