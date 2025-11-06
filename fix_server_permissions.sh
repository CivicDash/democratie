#!/bin/bash
# À exécuter EN TANT QUE ROOT sur le serveur

echo "🔧 Fixation des permissions pour CivicDash sur le serveur..."

# 1. Fixer les permissions sur l'hôte
echo "📁 Permissions hôte..."
chown -R civicdash:civicdash /opt/civicdash
chmod -R 755 /opt/civicdash
chmod -R 775 /opt/civicdash/storage
chmod -R 775 /opt/civicdash/bootstrap/cache

# 2. Fixer les permissions dans le conteneur
echo "🐳 Permissions conteneur..."
docker compose exec -u root app chown -R www-data:www-data /var/www/storage
docker compose exec -u root app chown -R www-data:www-data /var/www/bootstrap/cache
docker compose exec -u root app chmod -R 775 /var/www/storage
docker compose exec -u root app chmod -R 775 /var/www/bootstrap/cache

# 3. Clear les caches
echo "🧹 Clear caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# 4. Rebuild frontend
echo "🎨 Rebuild frontend..."
docker compose exec -u root app npm run build

# 5. Redémarrer
echo "🔄 Redémarrage..."
docker compose restart app nginx

echo "✅ Terminé !"
