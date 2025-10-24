# Guide de Setup Initial - CivicDash

Ce document détaille les étapes d'initialisation post-installation.

## 🔧 Configuration initiale

### 1. Copier l'environnement

```bash
cp .env.example .env
```

### 2. Générer le PEPPER (obligatoire)

Le PEPPER est utilisé pour hasher les références citoyennes de manière sécurisée.

```bash
make pepper
# ou
docker-compose exec app php artisan tinker --execute="echo base64_encode(random_bytes(32));"
```

Copiez la valeur générée dans `.env` :
```
PEPPER=votre_valeur_generee_ici
```

### 3. Initialiser Breeze (Auth UI)

```bash
docker-compose exec app php artisan breeze:install vue --ssr
```

Choisir :
- Stack : **Vue avec Inertia**
- SSR : **Oui** (recommandé)
- TypeScript : **Oui** (recommandé)
- Pest : **Oui**

### 4. Publier les configurations des packages

```bash
# Spatie Permission (RBAC)
docker-compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Horizon (Queues)
docker-compose exec app php artisan vendor:publish --tag=horizon-assets
docker-compose exec app php artisan horizon:install

# Telescope (Debug)
docker-compose exec app php artisan telescope:install

# Scout (Search)
docker-compose exec app php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

### 5. Créer les migrations Horizon & Telescope

```bash
docker-compose exec app php artisan migrate
```

## 🎨 Configuration Frontend

### Configuration Tailwind

Mettre à jour `tailwind.config.js` :

```javascript
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Palette CivicDash
        'civic-blue': '#0055a4',
        'civic-red': '#ef4135',
        'civic-white': '#ffffff',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

### Configuration Vite

Vérifier `vite.config.js` :

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
```

## 🗄️ Configuration Database

### PostgreSQL

Le `docker-compose.yml` configure automatiquement PostgreSQL. Vérifiez dans `.env` :

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=civicdash
DB_USERNAME=civicdash
DB_PASSWORD=secret
```

### Redis

Configuration dans `.env` :

```env
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

## 🔍 Configuration Meilisearch

Dans `.env` :

```env
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
```

## 🛡️ Configuration Sécurité

### Horizon

Dans `config/horizon.php`, définir les autorisations :

```php
'middleware' => ['web', 'auth', 'role:admin'],
```

### Telescope

Dans `config/telescope.php` :

```php
'enabled' => env('TELESCOPE_ENABLED', false),
'middleware' => ['web', 'auth', 'role:admin'],
```

### Spatie Permission

Publier et configurer :

```bash
docker-compose exec app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Dans `config/permission.php`, vérifier :

```php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role' => Spatie\Permission\Models\Role::class,
],

'table_names' => [
    'roles' => 'roles',
    'permissions' => 'permissions',
    'model_has_permissions' => 'model_has_permissions',
    'model_has_roles' => 'model_has_roles',
    'role_has_permissions' => 'role_has_permissions',
],
```

## 📋 Prochaines étapes

1. ✅ Setup de base terminé
2. 🔄 Créer les migrations des tables métier (users, topics, posts, etc.)
3. 🎭 Créer les seeders des rôles et territoires
4. 🧪 Écrire les premiers tests
5. 🎨 Créer les composants Vue de base

## 🐛 Troubleshooting

### Erreur "could not find driver"

Si vous voyez cette erreur, c'est que SQLite n'est pas configuré. Vérifiez que `.env` utilise bien PostgreSQL :

```env
DB_CONNECTION=pgsql
```

### Port déjà utilisé

Si le port 8000 est déjà utilisé, modifiez `docker-compose.yml` :

```yaml
ports:
  - "8080:8000"  # Utiliser 8080 au lieu de 8000
```

### Permission denied sur storage/

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R civicdash:civicdash storage bootstrap/cache
```

### Horizon ne démarre pas

Vérifiez que Redis est bien lancé :

```bash
docker-compose ps
docker-compose logs redis
```

## 📚 Ressources

- [Laravel Breeze](https://laravel.com/docs/11.x/starter-kits#breeze-and-inertia)
- [Spatie Permission](https://spatie.be/docs/laravel-permission/v6)
- [Laravel Horizon](https://laravel.com/docs/11.x/horizon)
- [Laravel Scout](https://laravel.com/docs/11.x/scout)
- [Meilisearch](https://www.meilisearch.com/docs)

