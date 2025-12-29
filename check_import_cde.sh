#!/bin/bash
# Vérifier quelle commande d'import existe

echo "🔍 Vérification des commandes disponibles..."
echo ""

cd /opt/civicdash

echo "📋 Liste des commandes 'app:*' :"
docker compose exec app php artisan list | grep "app:"
echo ""

echo "📋 Liste des commandes 'postal*' :"
docker compose exec app php artisan list | grep -i postal
echo ""

echo "📋 Toutes les commandes personnalisées :"
docker compose exec app php artisan list | grep -E "app:|postal"
EOF
chmod +x /home/kevin/www/demoscratos/check_import_command.sh
echo "✅ Script créé"
echo ""
echo "Sur le serveur, exécute :"
echo "cd /opt/civicdash && bash check_import_command.sh"
