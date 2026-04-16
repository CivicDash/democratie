#!/bin/bash

# Script pour setup PostgreSQL en local et importer les données Sénat
# À lancer depuis le répertoire du projet

echo "🔧 Setup PostgreSQL Local pour DemosCratos"
echo "==========================================="
echo ""

# 1. Vérifier si PostgreSQL est installé
echo "📋 1. Vérification PostgreSQL..."
if command -v psql &> /dev/null; then
    echo "✅ PostgreSQL installé : $(psql --version)"
else
    echo "❌ PostgreSQL non installé"
    echo "Installation (Ubuntu/Debian) :"
    echo "  sudo apt update"
    echo "  sudo apt install postgresql postgresql-contrib"
    exit 1
fi
echo ""

# 2. Créer la base de données
echo "📋 2. Création de la base de données..."
sudo -u postgres psql -c "DROP DATABASE IF EXISTS demoscratos_local;" 2>/dev/null
sudo -u postgres psql -c "CREATE DATABASE demoscratos_local;"
DB_LOCAL_PASSWORD="${DB_LOCAL_PASSWORD:-$(openssl rand -base64 16)}"
sudo -u postgres psql -c "CREATE USER demoscratos WITH PASSWORD '${DB_LOCAL_PASSWORD}';" 2>/dev/null || echo "Utilisateur existe déjà"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE demoscratos_local TO demoscratos;"
echo "✅ Base créée : demoscratos_local"
echo ""

# 3. Mettre à jour le .env.local
echo "📋 3. Configuration .env.local..."
cat > .env.local << EOF
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=demoscratos_local
DB_USERNAME=demoscratos
DB_PASSWORD=${DB_LOCAL_PASSWORD}
EOF
echo "✅ Fichier .env.local créé"
echo ""

# 4. Copier les données si elles existent en prod
echo "📋 4. Récupération des dumps SQL depuis prod..."
echo "💡 Si tu as accès à prod, lance ces commandes :"
echo ""
echo "  # En prod :"
echo "  cd /opt/civicdash"
echo "  sudo -u postgres pg_dump demoscratos_prod -t 'senat*' -t 'sen_ameli' > /tmp/senat_dump.sql"
echo "  scp /tmp/senat_dump.sql user@local:/tmp/"
echo ""
echo "  # En local :"
echo "  psql -U demoscratos -d demoscratos_local -h localhost < /tmp/senat_dump.sql"
echo ""

# 5. Ou télécharger depuis data.senat.fr
echo "📋 5. Alternative : Import depuis data.senat.fr..."
echo "💡 Lance la commande d'import Sénat :"
echo "  php artisan import:senat-sql senateurs --fresh"
echo ""

echo "✅ Setup terminé !"
echo ""
echo "📝 Pour utiliser la base locale :"
echo "  export APP_ENV=local"
echo "  php artisan migrate"
echo "  php artisan import:senat-sql senateurs"
echo ""
echo "🔍 Pour tester :"
echo "  php artisan tinker --execute=\"echo 'Connexion OK' . PHP_EOL; DB::select('SELECT 1');\""

