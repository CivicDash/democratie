#!/bin/bash
# Rebuild frontend après modifications

echo "🎨 Rebuild frontend..."

cd /opt/civicdash

# 1. Clear les anciens builds
echo "🧹 Nettoyage..."
docker compose exec -u root app rm -rf /var/www/public/build
docker compose exec -u root app rm -f /var/www/public/hot

# 2. Rebuild
echo "🔨 Build Vite..."
docker compose exec -u root app npm run build

# 3. Vérifier que le manifest existe
echo "🔍 Vérification..."
docker compose exec app ls -la /var/www/public/build/manifest.json

# 4. Clear caches Laravel
echo "🧹 Clear caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear

# 5. Redémarrer
echo "🔄 Redémarrage..."
docker compose restart app nginx

echo "✅ Terminé !"
