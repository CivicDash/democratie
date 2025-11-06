#!/bin/bash
# Solution ULTIME pour les permissions logs

echo "🔧 Fix ULTIME des permissions logs..."

cd /opt/civicdash

# 1. Désactiver Telescope dans .env
echo "🔕 Désactivation de Telescope..."
docker compose exec app sed -i 's/TELESCOPE_ENABLED=true/TELESCOPE_ENABLED=false/' /var/www/.env || true

# 2. Fixer le fichier log PENDANT que l'app tourne
echo "📝 Fix du fichier log..."
docker compose exec -u root app bash -c '
# Supprimer et recréer
rm -f /var/www/storage/logs/laravel.log
touch /var/www/storage/logs/laravel.log
chown www-data:www-data /var/www/storage/logs/laravel.log
chmod 666 /var/www/storage/logs/laravel.log

# Fixer tout le dossier logs
chown -R www-data:www-data /var/www/storage/logs
chmod -R 777 /var/www/storage/logs
find /var/www/storage/logs -type f -exec chmod 666 {} +
'

# 3. Fixer sur l'hôte aussi
echo "📁 Fix sur l'hôte..."
chmod -R 777 /opt/civicdash/storage/logs
find /opt/civicdash/storage/logs -type f -exec chmod 666 {} +

# 4. Redémarrer
echo "🔄 Redémarrage..."
docker compose restart app queue

echo "✅ Terminé ! Teste l'application maintenant."
