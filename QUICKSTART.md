# 🚀 Quick Start - CivicDash

## Démarrage rapide (5 minutes)

### 1️⃣ Cloner et configurer

```bash
git clone <votre-repo> civicdash
cd civicdash
cp .env.example .env
```

### 2️⃣ Générer les secrets

```bash
# Générer APP_KEY et PEPPER
make setup
```

**OU** manuellement avec Docker :

```bash
docker-compose build
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate

# Générer PEPPER
docker-compose exec app php artisan tinker --execute="echo base64_encode(random_bytes(32));"
# Copier la valeur dans .env à PEPPER=
```

### 3️⃣ Initialiser Breeze

```bash
docker-compose exec app php artisan breeze:install vue --ssr
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 4️⃣ Configurer les packages

```bash
docker-compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
docker-compose exec app php artisan horizon:install
docker-compose exec app php artisan telescope:install
docker-compose exec app php artisan migrate
```

### 5️⃣ Accéder à l'application

🌐 **Application** : http://localhost:8000
🔭 **Telescope** : http://localhost:8000/telescope
⚡ **Horizon** : http://localhost:8000/horizon

## 📝 Commandes essentielles

### Développement

```bash
# Démarrer
make up

# Voir les logs
make logs

# Shell dans le conteneur
make shell

# Arrêter
make down
```

### Base de données

```bash
# Migrations
make migrate

# Reset + seed
make fresh

# Accès psql
make db-shell
```

### Tests

```bash
# Lancer tous les tests
make test

# Tests avec coverage
make test-coverage

# Linter
make lint
```

### Frontend

```bash
# Mode dev (hot reload)
docker-compose exec app npm run dev

# Build production
make npm-build
```

## 🐛 Troubleshooting

### Port 8000 déjà utilisé ?

Modifiez `docker-compose.yml` :

```yaml
ports:
  - "8080:8000"  # Changer 8000 en 8080
```

### Permissions denied ?

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Base de données non accessible ?

```bash
docker-compose down
docker-compose up -d
docker-compose logs db
```

### Redis/Horizon ne fonctionne pas ?

```bash
docker-compose restart redis
docker-compose restart horizon
make logs-horizon
```

## 📚 Documentation complète

- **Setup détaillé** : [docs/SETUP.md](docs/SETUP.md)
- **Progression** : [docs/PROGRESS.md](docs/PROGRESS.md)
- **Contribution** : [CONTRIBUTING.md](CONTRIBUTING.md)
- **Sécurité** : [SECURITY.md](SECURITY.md)

## ✨ Prêt à coder !

Le projet est maintenant configuré. Prochaine étape :

1. Créer les migrations des tables métier
2. Configurer les rôles (citizen, moderator, admin, etc.)
3. Implémenter le forum (topics/posts)
4. Développer le système de vote anonyme

Consultez [docs/PROGRESS.md](docs/PROGRESS.md) pour la roadmap complète.

---

**Besoin d'aide ?** Ouvrez une issue ou consultez les discussions GitHub.

