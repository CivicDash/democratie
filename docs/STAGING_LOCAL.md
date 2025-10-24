# 🚀 Guide de Démarrage Staging Local - CivicDash

## 📋 Prérequis

- Docker & Docker Compose installés
- Git
- Make (optionnel mais recommandé)

## 🎯 Démarrage Rapide (Port 7777)

### 1. Copier le fichier d'environnement

```bash
cp .env.example .env
```

### 2. Configurer les variables essentielles

Éditer `.env` et vérifier/modifier :

```env
APP_NAME=CivicDash
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:7777

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=civicdash
DB_USERNAME=civicdash
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CACHE_DB=1

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=civicdash_cache

# PEPPER (Générer avec: php artisan tinker --execute="echo base64_encode(random_bytes(32));")
PEPPER=CHANGEZ_MOI_AVEC_UNE_VALEUR_ALEATOIRE_32_BYTES_BASE64

# Meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
SCOUT_DRIVER=meilisearch

# Mail (optionnel pour staging local)
MAIL_MAILER=log
```

### 3. Générer le PEPPER de sécurité

```bash
# Option 1: Avec Docker (si déjà lancé)
docker exec -it civicdash-app php artisan tinker --execute="echo base64_encode(random_bytes(32));"

# Option 2: Avec PHP local
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"

# Copier la valeur générée dans .env
# PEPPER=abc123def456...
```

### 4. Construire et lancer les containers

```bash
# Avec Make (recommandé)
make build
make up

# OU avec Docker Compose directement
docker-compose build
docker-compose up -d
```

### 5. Installer les dépendances

```bash
# Avec Make
make install

# OU manuellement
docker exec -it civicdash-app composer install
docker exec -it civicdash-app npm install
```

### 6. Générer la clé d'application Laravel

```bash
# Avec Make
make key

# OU manuellement
docker exec -it civicdash-app php artisan key:generate
```

### 7. Lancer les migrations et seeders

```bash
# Avec Make
make migrate-seed

# OU manuellement
docker exec -it civicdash-app php artisan migrate:fresh --seed
```

### 8. Compiler les assets frontend

```bash
# Avec Make (dev)
make dev

# OU pour production
make build-assets

# OU manuellement
docker exec -it civicdash-app npm run dev
# OU
docker exec -it civicdash-app npm run build
```

### 9. Accéder à l'application

🎉 **L'application est maintenant accessible** :

- **Frontend** : http://localhost:7777
- **Horizon** (queues) : http://localhost:7777/horizon
- **Meilisearch** : http://localhost:7700

## 👥 Comptes de Test

Après le seed, vous disposez de 5 utilisateurs de test :

```
📧 Email                    | 🔑 Password | 👤 Rôle
─────────────────────────────────────────────────────
admin@civicdash.test        | password   | Admin
moderator@civicdash.test    | password   | Moderator
journalist@civicdash.test   | password   | Journalist
citizen@civicdash.test      | password   | Citizen
state@civicdash.test        | password   | State
```

## 🔧 Commandes Make Utiles

```bash
# Démarrer l'environnement
make up

# Arrêter l'environnement
make down

# Voir les logs
make logs

# Accéder au container app
make shell

# Lancer les tests
make test

# Vider le cache
make cache-clear

# Redémarrer complètement
make restart

# Reconstruire tout
make fresh
```

## 📊 Vérifier l'installation

### 1. Vérifier les containers

```bash
docker ps
```

Vous devriez voir 6 containers :
- civicdash-app (port 7777)
- civicdash-queue
- civicdash-horizon
- civicdash-scheduler
- civicdash-db (port 5432)
- civicdash-redis (port 6379)
- civicdash-meilisearch (port 7700)

### 2. Vérifier la base de données

```bash
# Accéder à PostgreSQL
docker exec -it civicdash-db psql -U civicdash -d civicdash

# Lister les tables
\dt

# Quitter
\q
```

Vous devriez voir 16 tables.

### 3. Vérifier Redis

```bash
# Accéder à Redis
docker exec -it civicdash-redis redis-cli

# Voir les clés
KEYS *

# Quitter
exit
```

### 4. Tester l'API

```bash
# Tester un endpoint public
curl http://localhost:7777/api/topics

# Devrait retourner une liste de topics (JSON)
```

## 🧪 Lancer les Tests

```bash
# Tous les tests
make test

# Tests spécifiques
docker exec -it civicdash-app php artisan test --filter=AnonymousVotingTest
docker exec -it civicdash-app php artisan test --filter=BudgetAllocationTest
```

## 🎨 Développement Frontend

### Mode Watch (Hot Reload)

```bash
# Terminal 1: Lancer Vite en mode watch
docker exec -it civicdash-app npm run dev

# Terminal 2: Voir les logs de l'app
make logs app
```

Les changements dans les fichiers Vue seront automatiquement recompilés.

## 🔍 Debugging

### Logs de l'application

```bash
# Tous les logs
make logs

# Logs d'un service spécifique
docker logs -f civicdash-app
docker logs -f civicdash-queue
docker logs -f civicdash-horizon
```

### Logs Laravel

```bash
# Dans le container
docker exec -it civicdash-app tail -f storage/logs/laravel.log
```

### Telescope (Debugging Laravel)

Si vous installez Telescope :

```bash
docker exec -it civicdash-app composer require laravel/telescope --dev
docker exec -it civicdash-app php artisan telescope:install
docker exec -it civicdash-app php artisan migrate
```

Accès : http://localhost:7777/telescope

## 💾 Cache Management

```bash
# Vider tout le cache
make cache-clear

# OU spécifiquement
docker exec -it civicdash-app php artisan cache:clear
docker exec -it civicdash-app php artisan config:clear
docker exec -it civicdash-app php artisan route:clear
docker exec -it civicdash-app php artisan view:clear

# Cache CivicDash (vote, budget, etc.)
docker exec -it civicdash-app php artisan cache:clear-civicdash --force
```

## 🗄️ Base de Données

### Réinitialiser la DB

```bash
# Tout supprimer et recréer
make fresh

# OU
docker exec -it civicdash-app php artisan migrate:fresh --seed
```

### Backup de la DB

```bash
# Exporter
docker exec -it civicdash-db pg_dump -U civicdash civicdash > backup.sql

# Importer
docker exec -i civicdash-db psql -U civicdash civicdash < backup.sql
```

## 🚨 Dépannage

### Port 7777 déjà utilisé

```bash
# Trouver le processus
lsof -i :7777
# OU
sudo netstat -tulpn | grep 7777

# Tuer le processus
kill -9 <PID>
```

### Problème de permissions

```bash
# Fixer les permissions
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache
```

### Containers qui ne démarrent pas

```bash
# Voir les logs
docker-compose logs

# Reconstruire
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Erreur "PEPPER not set"

Générer un PEPPER et l'ajouter dans `.env` :

```bash
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

### Cache qui ne fonctionne pas

```bash
# Vérifier que Redis est actif
docker exec -it civicdash-redis redis-cli PING
# Devrait retourner: PONG

# Vider le cache Redis
docker exec -it civicdash-redis redis-cli FLUSHDB
```

## 🌐 FranceConnect+ (Optionnel)

Pour activer FranceConnect+ :

1. S'inscrire sur https://partenaires.franceconnect.gouv.fr/
2. Créer une application en intégration
3. Obtenir CLIENT_ID et CLIENT_SECRET
4. Configurer dans `.env` :

```env
FRANCECONNECT_CLIENT_ID=your_client_id
FRANCECONNECT_CLIENT_SECRET=your_client_secret
FRANCECONNECT_REDIRECT_URI=http://localhost:7777/auth/franceconnect/callback

# Environnement intégration
FRANCECONNECT_AUTHORIZE_URL=https://fcp.integ01.dev-franceconnect.fr/api/v1/authorize
FRANCECONNECT_TOKEN_URL=https://fcp.integ01.dev-franceconnect.fr/api/v1/token
FRANCECONNECT_USERINFO_URL=https://fcp.integ01.dev-franceconnect.fr/api/v1/userinfo
FRANCECONNECT_LOGOUT_URL=https://fcp.integ01.dev-franceconnect.fr/api/v1/logout
```

5. Ajouter dans `config/services.php` :

```php
'franceconnect' => [
    'client_id' => env('FRANCECONNECT_CLIENT_ID'),
    'client_secret' => env('FRANCECONNECT_CLIENT_SECRET'),
    'redirect' => env('FRANCECONNECT_REDIRECT_URI'),
    'authorize_url' => env('FRANCECONNECT_AUTHORIZE_URL'),
    'token_url' => env('FRANCECONNECT_TOKEN_URL'),
    'userinfo_url' => env('FRANCECONNECT_USERINFO_URL'),
    'logout_url' => env('FRANCECONNECT_LOGOUT_URL'),
],
```

6. Lancer la migration :

```bash
docker exec -it civicdash-app php artisan migrate
```

## 📚 Documentation

Consultez la documentation complète dans le dossier `docs/` :

- `docs/SETUP.md` - Installation détaillée
- `docs/DATABASE.md` - Structure de la base
- `docs/API_RESOURCES.md` - API documentation
- `docs/FRONTEND.md` - Guide frontend
- `docs/CACHE_REDIS.md` - Cache Redis
- `docs/RECAP_PROJET.md` - Récapitulatif complet

## 🎯 Checklist de Validation Staging

- [ ] Application accessible sur http://localhost:7777
- [ ] Login avec compte de test fonctionnel
- [ ] Création de topic
- [ ] Vote anonyme (token + vote)
- [ ] Allocation budget (100% contrainte)
- [ ] Upload de document
- [ ] Signalement (report)
- [ ] Cache Redis actif (vérifier performance)
- [ ] Rate limiting actif (tester 5 login failed)
- [ ] Tests Pest passent (122 tests)
- [ ] Horizon actif
- [ ] Logs propres (pas d'erreurs)

## 🚀 Commandes Rapides

```bash
# Setup complet from scratch
make build && make up && make install && make key && make migrate-seed && make dev

# Redémarrer proprement
make restart

# Tout reconstruire
make fresh

# Tests
make test

# Accéder au shell
make shell

# Voir les logs
make logs
```

## 🎉 C'est Prêt !

Votre environnement de staging local est maintenant configuré sur **http://localhost:7777** ! 🚀

Connectez-vous avec un compte de test et explorez CivicDash ! 💙

---

**Version** : 1.0.0-alpha-staging  
**Port** : 7777  
**Environnement** : Local Staging

