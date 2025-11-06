#!/bin/bash
# Fix DÉFINITIF des permissions - À exécuter EN TANT QUE ROOT

echo "🔧 Fix permissions DÉFINITIF..."

cd /opt/civicdash

# 1. Arrêter les services pour éviter la recréation du fichier
echo "🛑 Arrêt des services..."
docker compose stop app queue

# 2. Fixer les permissions sur l'HÔTE
echo "📁 Fix permissions sur l'hôte..."
chown -R 33:33 /opt/civicdash/storage  # 33 = www-data
chmod -R 775 /opt/civicdash/storage
find /opt/civicdash/storage -type f -exec chmod 664 {} +
find /opt/civicdash/storage -type d -exec chmod 775 {} +

chown -R 33:33 /opt/civicdash/bootstrap/cache
chmod -R 775 /opt/civicdash/bootstrap/cache

# 3. Redémarrer les services
echo "🔄 Redémarrage des services..."
docker compose start app queue

# 4. Attendre que les services soient prêts
echo "⏳ Attente des services..."
sleep 5

# 5. Ajouter la permission manquante
echo "🔑 Ajout de la permission 'posts.create'..."
docker compose exec app php artisan tinker --execute="
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Créer la permission si elle n'existe pas
\$permission = Permission::firstOrCreate(['name' => 'posts.create', 'guard_name' => 'web']);
echo 'Permission posts.create créée\n';

// L'assigner aux rôles qui en ont besoin
\$roles = ['citizen', 'moderator', 'journalist', 'ong', 'legislator', 'state', 'public_figure'];
foreach (\$roles as \$roleName) {
    \$role = Role::findByName(\$roleName, 'web');
    if (!\$role->hasPermissionTo('posts.create')) {
        \$role->givePermissionTo('posts.create');
        echo \"Permission assignée à \$roleName\n\";
    }
}
"

# 6. Clear caches
echo "🧹 Clear caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan permission:cache-reset

# 7. Vérifier les permissions du fichier log
echo "🔍 Vérification des permissions..."
docker compose exec app ls -la /var/www/storage/logs/laravel.log

echo "✅ Terminé !"
