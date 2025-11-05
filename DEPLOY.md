# 🚀 Guide de Déploiement CivicDash (Docker)

Guide complet pour déployer CivicDash en production sur Debian 13 avec Docker.

## 📋 Prérequis

- Serveur Debian 13
- Docker & Docker Compose installés
- Utilisateur `civicdash` créé
- Accès root/sudo
- Domaines configurés (DNS)

---

## 🔧 Installation

### 1️⃣ Copier les fichiers Docker

```bash
# En tant que debian (ou ton user admin)
cd /home/kevin/www/demoscratos

# Copier tous les fichiers vers /opt/civicdash
sudo cp -r * /opt/civicdash/
sudo cp -r .* /opt/civicdash/ 2>/dev/null || true

# Donner les permissions à civicdash
sudo chown -R civicdash:civicdash /opt/civicdash
```

### 2️⃣ Configuration de l'environnement

```bash
# Switcher sur l'utilisateur civicdash
sudo su - civicdash
cd /opt/civicdash

# Copier le fichier d'environnement Docker
cp env.docker.example .env

# Éditer la configuration
nano .env
```

**Variables CRITIQUES à modifier** :

```bash
# Générer APP_KEY (sera fait par artisan)
APP_KEY=

# Base de données
DB_PASSWORD=VotreMdpTresFort123!

# Meilisearch
MEILISEARCH_KEY=$(openssl rand -base64 32)

# Security PEPPER
PEPPER=$(openssl rand -base64 32)

# URL de production
APP_URL=https://api.civicdash.fr
```

### 3️⃣ Build et lancement des conteneurs

```bash
# Build l'image Docker (première fois, ~5-10 min)
docker-compose build

# Lancer tous les services
docker-compose up -d

# Vérifier que tout tourne
docker-compose ps
```

**Services attendus** :
- ✅ `civicdash_app` (PHP-FPM)
- ✅ `civicdash_nginx` (Web server)
- ✅ `civicdash_db` (PostgreSQL)
- ✅ `civicdash_redis` (Cache)
- ✅ `civicdash_search` (Meilisearch)
- ✅ `civicdash_queue` (Queue worker)
- ✅ `civicdash_scheduler` (Cron)

### 4️⃣ Installation de Laravel

```bash
# Installer les dépendances PHP
docker-compose exec app composer install --no-dev --optimize-autoloader

# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Créer le lien symbolique pour le storage
docker-compose exec app php artisan storage:link

# Optimiser pour la production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### 5️⃣ Base de données

```bash
# Lancer les migrations
docker-compose exec app php artisan migrate --force

# Option A : Mode DÉMO (recommandé pour tester)
docker-compose exec app php artisan demo:setup --fresh

# Option B : Seeders de base uniquement
docker-compose exec app php artisan db:seed --class=DatabaseSeeder
```

### 6️⃣ Build des assets frontend

```bash
# Installer les dépendances Node.js
docker-compose exec app npm install

# Build pour la production
docker-compose exec app npm run build
```

### 7️⃣ Permissions finales

```bash
# Sortir du conteneur et revenir à debian
exit

# Ajuster les permissions
sudo chown -R civicdash:civicdash /opt/civicdash
sudo chmod -R 775 /opt/civicdash/storage
sudo chmod -R 775 /opt/civicdash/bootstrap/cache
```

---

## 🌐 Configuration Nginx (Reverse Proxy)

### Installer Nginx sur l'hôte

```bash
sudo apt update
sudo apt install nginx certbot python3-certbot-nginx
```

### Configuration pour l'API CivicDash

Créer `/etc/nginx/sites-available/civicdash` :

```nginx
# API CivicDash
server {
    listen 80;
    server_name api.civicdash.fr;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
        proxy_request_buffering off;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}

# Site vitrine Civis-Consilium
server {
    listen 80;
    server_name civis-consilium.eu www.civis-consilium.eu;
    root /var/www/civis-consilium.eu;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# Landing page Objectif2027
server {
    listen 80;
    server_name objectif2027.fr www.objectif2027.fr;
    root /var/www/objectif2027.fr;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Activer la configuration

```bash
# Créer le lien symbolique
sudo ln -s /etc/nginx/sites-available/civicdash /etc/nginx/sites-enabled/

# Tester la configuration
sudo nginx -t

# Recharger Nginx
sudo systemctl reload nginx
```

### SSL avec Let's Encrypt

```bash
# Obtenir les certificats SSL
sudo certbot --nginx -d api.civicdash.fr
sudo certbot --nginx -d civis-consilium.eu -d www.civis-consilium.eu
sudo certbot --nginx -d objectif2027.fr -d www.objectif2027.fr

# Renouvellement automatique (déjà configuré par certbot)
sudo certbot renew --dry-run
```

---

## 📁 Déployer les sites statiques

```bash
# Créer les répertoires
sudo mkdir -p /var/www/civis-consilium.eu
sudo mkdir -p /var/www/objectif2027.fr

# Copier les fichiers
sudo cp -r /opt/civicdash/civis-consilium/* /var/www/civis-consilium.eu/
sudo cp -r /opt/civicdash/objectif2027/* /var/www/objectif2027.fr/

# Permissions
sudo chown -R www-data:www-data /var/www/civis-consilium.eu
sudo chown -R www-data:www-data /var/www/objectif2027.fr
sudo chmod -R 755 /var/www/civis-consilium.eu
sudo chmod -R 755 /var/www/objectif2027.fr
```

---

## 🔒 Sécurité

### Firewall (UFW)

```bash
# Autoriser SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Activer le firewall
sudo ufw enable

# Vérifier le statut
sudo ufw status
```

### Fail2Ban (optionnel)

```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

## 📊 Monitoring & Logs

### Logs Docker

```bash
# Tous les services
docker-compose logs -f

# Service spécifique
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres

# Dernières 100 lignes
docker-compose logs --tail=100 app
```

### Logs Laravel

```bash
# Logs Laravel (dans le conteneur)
docker-compose exec app tail -f storage/logs/laravel.log
```

### Logs Nginx

```bash
# Logs Nginx hôte
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# Logs Nginx Docker
docker-compose exec nginx tail -f /var/log/nginx/access.log
```

### Statut des services

```bash
# Docker
docker-compose ps

# Nginx hôte
sudo systemctl status nginx

# Utilisation ressources
docker stats
```

---

## 🔄 Maintenance

### Mise à jour du code

```bash
sudo su - civicdash
cd /opt/civicdash

# Pull les dernières modifications
git pull origin main

# Rebuild si nécessaire
docker-compose build

# Redémarrer les services
docker-compose down
docker-compose up -d

# Migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear

# Rebuild caches
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Rebuild assets si modifiés
docker-compose exec app npm run build
```

### Backup de la base de données

```bash
# Backup manuel
docker-compose exec postgres pg_dump -U civicdash civicdash > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurer un backup
docker-compose exec -T postgres psql -U civicdash civicdash < backup_20250105_120000.sql
```

### Redémarrer les services

```bash
# Tous les services
docker-compose restart

# Service spécifique
docker-compose restart app
docker-compose restart nginx
docker-compose restart queue
```

---

## 🧪 Tests

### Vérifier l'API

```bash
# Depuis le serveur
curl http://localhost:8080
curl https://api.civicdash.fr

# Healthcheck
curl https://api.civicdash.fr/api/health
```

### Vérifier les sites statiques

```bash
curl https://civis-consilium.eu
curl https://objectif2027.fr
```

---

## 🎯 Comptes de test (Mode Démo)

Si vous avez lancé `php artisan demo:setup --fresh` :

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Admin** | admin@civicdash.fr | password |
| **Modérateur** | moderator@civicdash.fr | password |
| **Journaliste** | journalist@civicdash.fr | password |
| **Citoyen** | citizen@civicdash.fr | password |
| **Député** | deputy@civicdash.fr | password |

⚠️ **IMPORTANT** : Changer ces mots de passe en production !

---

## 🆘 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Voir les logs
docker-compose logs

# Reconstruire tout
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Erreur de permissions

```bash
sudo chown -R civicdash:civicdash /opt/civicdash
sudo chmod -R 775 /opt/civicdash/storage
sudo chmod -R 775 /opt/civicdash/bootstrap/cache
```

### Base de données inaccessible

```bash
# Vérifier que PostgreSQL tourne
docker-compose ps postgres

# Tester la connexion
docker-compose exec postgres psql -U civicdash -d civicdash -c "SELECT 1;"
```

### Nginx 502 Bad Gateway

```bash
# Vérifier que le conteneur app tourne
docker-compose ps app

# Vérifier les logs
docker-compose logs app
docker-compose logs nginx
```

---

## 📚 Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Docker](https://docs.docker.com)
- [Documentation PostgreSQL](https://www.postgresql.org/docs/)
- [Documentation Meilisearch](https://docs.meilisearch.com)

---

## ✅ Checklist de déploiement

- [ ] Docker installé et fonctionnel
- [ ] Utilisateur `civicdash` créé
- [ ] Projet cloné dans `/opt/civicdash`
- [ ] `.env` configuré (DB_PASSWORD, PEPPER, MEILISEARCH_KEY)
- [ ] Conteneurs Docker lancés (`docker-compose up -d`)
- [ ] Dépendances installées (`composer install`)
- [ ] Clé générée (`php artisan key:generate`)
- [ ] Migrations exécutées (`php artisan migrate`)
- [ ] Assets buildés (`npm run build`)
- [ ] Nginx configuré en reverse proxy
- [ ] SSL activé avec Let's Encrypt
- [ ] Sites statiques déployés
- [ ] Firewall configuré
- [ ] Tests de connexion OK
- [ ] Mots de passe par défaut changés

---

**🎉 CivicDash est maintenant en production !**

