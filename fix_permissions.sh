#!/bin/bash
# Script pour fixer les permissions Docker

echo "🔧 Fixation des permissions pour CivicDash..."

# Sur l'hôte, fixer les permissions
sudo chown -R civicdash:civicdash /opt/civicdash
sudo chmod -R 755 /opt/civicdash
sudo chmod -R 775 /opt/civicdash/storage
sudo chmod -R 775 /opt/civicdash/bootstrap/cache

# Fixer node_modules
sudo chown -R civicdash:civicdash /opt/civicdash/node_modules
sudo chmod -R 755 /opt/civicdash/node_modules

echo "✅ Permissions fixées sur l'hôte"
echo ""
echo "📝 Maintenant, exécute ces commandes sur le serveur :"
echo ""
echo "# En tant que root sur le serveur"
echo "cd /opt/civicdash"
echo "bash fix_permissions.sh"
echo ""
echo "# Puis rebuild le frontend"
echo "docker compose exec -u root app npm run build"
echo ""
echo "# Refixer les permissions dans le conteneur"
echo "docker compose exec -u root app chown -R www-data:www-data /var/www/storage"
echo "docker compose exec -u root app chmod -R 775 /var/www/storage"
echo ""
echo "# Redémarrer"
echo "docker compose restart app nginx"
