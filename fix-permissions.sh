#!/bin/bash
# Script pour corriger les permissions Docker

echo "🔧 Correction des permissions..."

# Recréer les répertoires nécessaires
docker compose exec -u root app mkdir -p /var/www/bootstrap/cache
docker compose exec -u root app mkdir -p /var/www/storage/logs
docker compose exec -u root app mkdir -p /var/www/storage/framework/cache/data
docker compose exec -u root app mkdir -p /var/www/storage/framework/sessions
docker compose exec -u root app mkdir -p /var/www/storage/framework/views

# Changer le propriétaire (UID 1000 = civicdash user dans le container)
docker compose exec -u root app chown -R 1000:1000 /var/www/storage
docker compose exec -u root app chown -R 1000:1000 /var/www/bootstrap/cache

# Permissions d'écriture
docker compose exec -u root app chmod -R 775 /var/www/storage
docker compose exec -u root app chmod -R 775 /var/www/bootstrap/cache

echo "✅ Permissions corrigées !"
