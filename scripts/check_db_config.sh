#!/bin/bash

# Script pour afficher la config DB actuelle
cd /opt/civicdash || exit 1

echo "🔍 Configuration Base de Données"
echo "=================================="
echo ""

echo "📋 Variables .env :"
grep "^DB_" .env 2>/dev/null || echo "❌ Fichier .env non trouvé"
echo ""

echo "💡 Instructions :"
echo "  1. Vérifier DB_HOST dans .env"
echo "  2. Si DB_HOST=postgres, changer pour:"
echo "     - DB_HOST=localhost (si PostgreSQL local)"
echo "     - DB_HOST=127.0.0.1 (alternative)"
echo "     - DB_HOST=IP_DU_SERVEUR (si distant)"
echo ""
echo "  3. Après modification, relancer:"
echo "     php artisan config:clear"
echo "     php artisan cache:clear"
echo ""
echo "  4. Tester la connexion:"
echo "     php artisan tinker --execute=\"echo 'Connexion OK' . PHP_EOL; DB::select('SELECT 1');\""

