#!/bin/bash

# 🚀 Script de démarrage rapide - CivicDash Staging Local (Port 7777)
# Usage: ./start-staging.sh

set -e

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║       🚀 DÉMARRAGE CIVICDASH - STAGING LOCAL (7777)         ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Vérifier que Docker est lancé
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker n'est pas lancé. Veuillez démarrer Docker Desktop."
    exit 1
fi

# Étape 1: Vérifier .env
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env depuis .env.example..."
    cp .env.example .env
    echo "✅ Fichier .env créé"
    echo ""
    echo "⚠️  IMPORTANT: Veuillez configurer le PEPPER dans .env"
    echo "   Générez-le avec: php -r \"echo base64_encode(random_bytes(32)) . PHP_EOL;\""
    echo ""
    read -p "Voulez-vous que je génère le PEPPER maintenant? (o/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        PEPPER=$(php -r "echo base64_encode(random_bytes(32));")
        sed -i "s/PEPPER=/PEPPER=$PEPPER/" .env
        echo "✅ PEPPER généré et ajouté au .env"
    else
        echo "⚠️  N'oubliez pas de générer et ajouter le PEPPER manuellement!"
    fi
else
    echo "✅ Fichier .env détecté"
fi

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "🏗️  ÉTAPE 1/7: Construction des images Docker"
echo "──────────────────────────────────────────────────────────────"
docker-compose build

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "🚀 ÉTAPE 2/7: Démarrage des containers"
echo "──────────────────────────────────────────────────────────────"
docker-compose up -d

echo ""
echo "⏳ Attente du démarrage des services (10 secondes)..."
sleep 10

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "📦 ÉTAPE 3/7: Installation des dépendances"
echo "──────────────────────────────────────────────────────────────"
docker exec -it civicdash-app composer install --no-interaction
docker exec -it civicdash-app npm install --silent

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "🔑 ÉTAPE 4/7: Génération de la clé d'application"
echo "──────────────────────────────────────────────────────────────"
docker exec -it civicdash-app php artisan key:generate --no-interaction

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "🗄️  ÉTAPE 5/7: Migrations & Seeders"
echo "──────────────────────────────────────────────────────────────"
docker exec -it civicdash-app php artisan migrate:fresh --seed --force

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "🎨 ÉTAPE 6/7: Compilation des assets frontend"
echo "──────────────────────────────────────────────────────────────"
docker exec -it civicdash-app npm run build

echo ""
echo "──────────────────────────────────────────────────────────────"
echo "✅ ÉTAPE 7/7: Vérification de l'installation"
echo "──────────────────────────────────────────────────────────────"
echo ""

# Vérifier les containers
echo "📊 Containers actifs:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep civicdash

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                   ✅ INSTALLATION TERMINÉE                   ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "🎉 CivicDash est maintenant accessible !"
echo ""
echo "📍 URLs:"
echo "   • Application:  http://localhost:7777"
echo "   • Horizon:      http://localhost:7777/horizon"
echo "   • Meilisearch:  http://localhost:7700"
echo ""
echo "👥 Comptes de test (password: password):"
echo "   • admin@civicdash.test       (Admin)"
echo "   • moderator@civicdash.test   (Moderator)"
echo "   • journalist@civicdash.test  (Journalist)"
echo "   • citizen@civicdash.test     (Citizen)"
echo "   • state@civicdash.test       (State)"
echo ""
echo "🔧 Commandes utiles:"
echo "   • Logs:       docker-compose logs -f app"
echo "   • Shell:      docker exec -it civicdash-app bash"
echo "   • Tests:      docker exec -it civicdash-app php artisan test"
echo "   • Arrêter:    docker-compose down"
echo "   • Redémarrer: docker-compose restart"
echo ""
echo "📚 Documentation: docs/STAGING_LOCAL.md"
echo ""
echo "💙 Bon développement avec CivicDash !"
echo ""

