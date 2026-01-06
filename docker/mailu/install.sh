#!/bin/bash
# Installation Mailu pour civis-consilium.eu
# Usage: sudo bash install.sh

set -e

MAILU_DIR="/opt/mailu"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "🔧 Installation Mailu pour civis-consilium.eu"
echo "=============================================="

# Vérifier root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en root (sudo)"
    exit 1
fi

# Arrêter postfix existant s'il tourne
echo "📦 Arrêt de Postfix existant..."
systemctl stop postfix 2>/dev/null || true
systemctl disable postfix 2>/dev/null || true

# Créer les répertoires
echo "📁 Création des répertoires..."
mkdir -p $MAILU_DIR/{certs,data,dkim,mail,mailqueue,filter,webmail,redis}
mkdir -p $MAILU_DIR/overrides/{nginx,postfix,dovecot,rspamd,roundcube}

# Copier les fichiers
echo "📋 Copie des fichiers de configuration..."
cp "$SCRIPT_DIR/docker-compose.yml" "$MAILU_DIR/"
cp "$SCRIPT_DIR/mailu.env.example" "$MAILU_DIR/mailu.env"

# Générer la clé secrète
echo "🔐 Génération de la clé secrète..."
SECRET_KEY=$(openssl rand -hex 32)
sed -i "s/CHANGER_CETTE_CLE_SECRETE/$SECRET_KEY/" "$MAILU_DIR/mailu.env"

# Permissions
echo "🔒 Configuration des permissions..."
chown -R root:root $MAILU_DIR
chmod 600 "$MAILU_DIR/mailu.env"

echo ""
echo "✅ Installation préparée !"
echo ""
echo "📝 Prochaines étapes :"
echo ""
echo "1. Éditer la configuration :"
echo "   sudo nano $MAILU_DIR/mailu.env"
echo "   → Changer INITIAL_ADMIN_PW"
echo ""
echo "2. Configurer les DNS pour civis-consilium.eu :"
echo "   MX     10 mail.civis-consilium.eu."
echo "   A      mail.civis-consilium.eu → IP_SERVEUR"
echo "   TXT    \"v=spf1 mx a -all\""
echo ""
echo "3. Configurer nginx reverse proxy (voir nginx-mailu.conf)"
echo ""
echo "4. Démarrer Mailu :"
echo "   cd $MAILU_DIR && docker compose up -d"
echo ""
echo "5. Accéder à l'admin :"
echo "   https://mail.civis-consilium.eu/admin"
echo "   Login: admin@civis-consilium.eu"
echo ""
