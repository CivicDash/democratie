# 🚀 CivicDash - Infrastructure de Production

> Stack Docker sécurisée avec WAF SafeLine + Matomo Analytics

## 📋 Architecture

```
                    Internet
                        │
                        ▼
┌───────────────────────────────────────────────────────────┐
│                   🛡️ SafeLine WAF                         │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │
│   │  tengine    │  │  detector   │  │    mgt      │      │
│   │  :80/:443   │  │   (ML)      │  │   :9443     │      │
│   └──────┬──────┘  └─────────────┘  └─────────────┘      │
│          │                                                │
└──────────┼────────────────────────────────────────────────┘
           │
           ▼
┌───────────────────────────────────────────────────────────┐
│                   🌐 Réseau interne                       │
│                                                           │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │
│   │   nginx     │  │    app      │  │   queue     │      │
│   │  (proxy)    │──│  (octane)   │  │  (worker)   │      │
│   └─────────────┘  └──────┬──────┘  └─────────────┘      │
│                           │                               │
│   ┌─────────────┐  ┌──────┴──────┐  ┌─────────────┐      │
│   │  postgres   │  │   redis     │  │ meilisearch │      │
│   │   :5432     │  │   :6379     │  │   :7700     │      │
│   └─────────────┘  └─────────────┘  └─────────────┘      │
│                                                           │
│   ┌─────────────┐  ┌─────────────┐                       │
│   │  scheduler  │  │   matomo    │                       │
│   │   (cron)    │  │  analytics  │                       │
│   └─────────────┘  └─────────────┘                       │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

## 🛡️ SafeLine WAF

[SafeLine](https://github.com/chaitin/SafeLine) est un WAF open source basé sur le ML.

### Fonctionnalités incluses

- ✅ **Protection OWASP Top 10** (SQLi, XSS, CSRF, etc.)
- ✅ **Anti-bot** avec challenge JavaScript/Captcha
- ✅ **Rate limiting** par IP/URL
- ✅ **Blocage géographique** (optionnel)
- ✅ **Détection ML** des attaques zero-day
- ✅ **Dashboard web** sur le port 9443

### Accès admin

```
URL: https://your-server:9443
User: admin
Password: (généré au premier lancement, voir les logs)
```

## 📊 Matomo Analytics

Analytics RGPD-compliant auto-hébergé.

### Configuration dans Laravel

```php
// Dans resources/views/layouts/app.blade.php
@if(config('app.env') === 'production')
<script>
  var _paq = window._paq = window._paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="{{ config('services.matomo.url') }}/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '{{ config('services.matomo.site_id') }}']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
@endif
```

## 🚀 Déploiement

### 1. Préparer les secrets

```bash
cd infrastructure/production
cp .env.example .env
nano .env  # Configurer les mots de passe
```

### 2. Créer les certificats SSL

```bash
# Option A: Certbot (Let's Encrypt)
mkdir -p ssl
certbot certonly --standalone -d objectif2027.fr -d www.objectif2027.fr
cp /etc/letsencrypt/live/objectif2027.fr/fullchain.pem ssl/
cp /etc/letsencrypt/live/objectif2027.fr/privkey.pem ssl/

# Option B: Cloudflare Origin Certificates
# Télécharger depuis le dashboard Cloudflare
```

### 3. Lancer la stack

```bash
docker compose up -d
```

### 4. Configurer SafeLine

1. Accéder à https://your-server:9443
2. Récupérer le mot de passe initial: `docker logs safeline-mgt 2>&1 | grep password`
3. Ajouter un site protégé:
   - **Upstream**: `http://nginx:80`
   - **Domain**: `objectif2027.fr`
   - **Port**: 443
   - **SSL**: Activer et uploader les certificats

### 5. Configurer Matomo

1. Accéder via SafeLine: https://analytics.objectif2027.fr
2. Suivre l'assistant d'installation
3. Créer un site et noter le `SITE_ID`

## 📈 Monitoring

### Logs SafeLine

```bash
docker logs -f safeline-tengine
docker logs -f safeline-detector
```

### Logs Application

```bash
docker logs -f civicdash_app
docker exec civicdash_app tail -f /var/www/storage/logs/laravel.log
```

### Métriques

- **SafeLine Dashboard**: https://your-server:9443
- **Matomo**: https://analytics.objectif2027.fr

## 🔧 Maintenance

### Backup

```bash
./scripts/backup.sh
```

### Mise à jour

```bash
# Application
docker compose pull app queue scheduler
docker compose up -d app queue scheduler

# SafeLine
docker compose pull safeline-mgt safeline-tengine safeline-detector
docker compose up -d safeline-mgt safeline-tengine safeline-detector
```

### Restart

```bash
docker compose restart
```

## 🆘 Dépannage

### SafeLine bloque des requêtes légitimes

1. Dashboard SafeLine → Logs → Trouver la requête bloquée
2. Créer une règle de whitelist

### Erreur 502

```bash
# Vérifier que l'app répond
docker exec civicdash_nginx curl -I http://app:8000/health

# Reloader Octane
docker exec civicdash_app php artisan octane:reload
```

### Base de données Matomo

```bash
# Créer la DB manuellement si nécessaire
docker exec civicdash_db psql -U civicdash -c "CREATE DATABASE matomo;"
```

## 📋 Ressources requises

| Service | RAM | CPU | Stockage |
|---------|-----|-----|----------|
| SafeLine (total) | 1GB | 2 cores | 10GB |
| App (Octane) | 2GB | 2 cores | - |
| PostgreSQL | 4GB | 2 cores | 50GB+ |
| Redis | 512MB | 1 core | 1GB |
| Meilisearch | 1GB | 1 core | 10GB |
| Matomo | 512MB | 1 core | 5GB |

**Total recommandé**: 8GB RAM, 4 cores, 100GB SSD

---

**Version**: 1.0.0  
**Dernière mise à jour**: Janvier 2026
