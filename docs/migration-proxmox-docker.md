# Plan de migration - Proxmox / Docker (serveur dedie)

## 1. Architecture actuelle (serveur VPS mutualisable)

### Serveur
- **OS** : Debian/Ubuntu (VPS)
- **Ressources** : 32 Go RAM, 108 Go disque (58% utilise)
- **IP** : unique, partagee entre tous les services

### Services systeme (hors Docker)
| Service | Version | Role |
|---------|---------|------|
| Nginx | - | Reverse proxy principal (SSL, vhosts) |
| PHP-FPM 8.3 | 8.3 | Dolibarr ERP |
| PHP-FPM 8.4 | 8.4 | Autres sites |
| MariaDB | 11.4.8 | BDD Dolibarr (`doli_civis`) |
| Caddy | - | Reverse proxy secondaire |

### Services Docker - CivicDash (`docker-compose.yml`)
| Container | Image | Port expose | Role |
|-----------|-------|-------------|------|
| `civicdash_app` | custom (Dockerfile) | 8080 -> 80 | App Laravel PHP-FPM + Nginx |
| `civicdash_db` | postgres:15-alpine | 5433 -> 5432 | PostgreSQL |
| `civicdash_redis` | redis:alpine | 6380 -> 6379 | Cache + sessions + queues |
| `civicdash_search` | meilisearch:v1.5 | 7700 | Recherche full-text |
| `civicdash_queue` | custom (Dockerfile) | - | Laravel queue worker |
| `civicdash_scheduler` | custom (Dockerfile) | - | Laravel scheduler (cron) |
| `civicdash_mail` | axllent/mailpit | 1025 / 8025 | Mail dev/test |

### Services Docker - Mailu (mail)
| Container | Image | Ports |
|-----------|-------|-------|
| `mailu-front-1` | mailu/nginx:2024.06 | 25, 110, 143, 465, 587, 993, 995 |
| `mailu-admin-1` | mailu/admin:2024.06 | - |
| `mailu-imap-1` | mailu/dovecot:2024.06 | - |
| `mailu-smtp-1` | mailu/postfix:2024.06 | - |
| `mailu-antispam-1` | mailu/rspamd:2024.06 | - |
| `mailu-webmail-1` | mailu/webmail:2024.06 | - |
| `mailu-redis-1` | redis:alpine | - |
| `mailu-resolver-1` | mailu/unbound:2024.06 | - |

### Sites heberges (vhosts Nginx)
- `demo.objectif2027.fr` - CivicDash (proxy -> Docker :8080)
- `erp.civis-consilium.eu` - Dolibarr ERP (PHP-FPM 8.3)
- `civis-consilium.eu` - Site vitrine
- `objectif2027.fr` - Site principal
- `mail.civis-consilium.eu` - Mailu webmail
- `gitebellevaux.fr` - Site externe
- `le-chevalier-kevin.fr` - Site externe
- `artforai.com` - Site externe

### Donnees a migrer
| Donnee | Emplacement | Taille estimee |
|--------|-------------|----------------|
| PostgreSQL CivicDash | Docker volume `postgres_data` | ~2 Go |
| MariaDB Dolibarr | `/var/lib/mysql/doli_civis` | ~500 Mo |
| Redis | Docker volume `redis_data` | ~100 Mo |
| MeiliSearch | Docker volume `meilisearch_data` | ~500 Mo |
| Storage CivicDash | `/opt/civicdash/storage/app/` | ~1 Go |
| Documents Dolibarr | `/var/www/civis-consilium.eu/erp/documents/` | ~200 Mo |
| Mailu data | volumes Docker Mailu | ~1 Go |
| Config Nginx | `/etc/nginx/conf.d/` | ~50 Ko |
| Certificats SSL | Let's Encrypt / Certbot | auto-renew |

---

## 2. Architecture cible (Proxmox + Docker dedie)

### Infrastructure Proxmox
```
Serveur dedie
└── Proxmox VE (hyperviseur)
    ├── VM 1 : Production (CivicDash + Dolibarr)
    │   ├── Docker Compose principal
    │   ├── Nginx reverse proxy (ou Traefik)
    │   └── Certbot SSL
    ├── VM 2 : Mail (Mailu)
    │   └── Docker Compose Mailu
    └── VM 3 : Backup / Monitoring (optionnel)
        ├── Borgmatic / Restic
        └── Grafana + Prometheus
```

### VM 1 - Production : Docker Compose unifie

```yaml
services:
  # Reverse proxy + SSL
  traefik:
    image: traefik:v3
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik_certs:/letsencrypt

  # CivicDash App
  civicdash_app:
    build: ./civicdash
    labels:
      - "traefik.http.routers.civicdash.rule=Host(`demo.objectif2027.fr`)"
      - "traefik.http.routers.civicdash.tls.certresolver=le"

  # CivicDash DB
  civicdash_db:
    image: postgres:15-alpine
    volumes:
      - pg_data:/var/lib/postgresql/data

  # CivicDash Redis
  civicdash_redis:
    image: redis:alpine
    volumes:
      - redis_data:/data

  # CivicDash Search
  civicdash_search:
    image: getmeili/meilisearch:v1.5
    volumes:
      - meili_data:/meili_data

  # CivicDash Queue + Scheduler
  civicdash_queue:
    build: ./civicdash
    command: php artisan queue:work

  civicdash_scheduler:
    build: ./civicdash
    command: sh -c "while true; do php artisan schedule:run; sleep 60; done"

  # Dolibarr ERP
  dolibarr:
    image: tuxgasy/dolibarr:latest
    labels:
      - "traefik.http.routers.dolibarr.rule=Host(`erp.civis-consilium.eu`)"
    volumes:
      - dolibarr_docs:/var/www/documents
      - dolibarr_custom:/var/www/html/custom

  # Dolibarr DB
  dolibarr_db:
    image: mariadb:11.4
    volumes:
      - mariadb_data:/var/lib/mysql
```

### Avantages de Traefik vs Nginx reverse proxy
- SSL automatique (Let's Encrypt) via labels Docker
- Detection automatique des containers
- Zero config Nginx a maintenir
- Dashboard de monitoring integre

---

## 3. Strategie de migration

### Phase 1 : Preparation (J-7)
- [ ] Commander/configurer le serveur dedie
- [ ] Installer Proxmox VE
- [ ] Creer les VMs (CPU, RAM, stockage)
- [ ] Configurer le reseau (bridge, IP publique)
- [ ] Installer Docker + Docker Compose sur chaque VM
- [ ] Preparer les Docker Compose cibles
- [ ] Tester le build de l'image CivicDash sur la cible

### Phase 2 : Pre-migration (J-2)
- [ ] Dump complet PostgreSQL : `pg_dumpall > civicdash_full.sql`
- [ ] Dump complet MariaDB : `mysqldump --all-databases > dolibarr_full.sql`
- [ ] Rsync des fichiers storage CivicDash
- [ ] Rsync des documents Dolibarr
- [ ] Rsync de la config Mailu
- [ ] Transferer les dumps et fichiers vers le nouveau serveur
- [ ] Restaurer les BDD sur le nouveau serveur
- [ ] Deployer CivicDash + Dolibarr + Mailu sur le nouveau serveur
- [ ] Tester toutes les applications en accès interne (via /etc/hosts)

### Phase 3 : Migration (Jour J) - Downtime estime : 30-60 min
- [ ] Afficher page de maintenance sur l'ancien serveur
- [ ] Arreter les queue workers et schedulers
- [ ] Faire un dump incremental final des BDD
- [ ] Rsync incremental final des fichiers
- [ ] Restaurer les dumps incrementaux sur la cible
- [ ] Basculer les enregistrements DNS vers la nouvelle IP
- [ ] Verifier la propagation DNS (dig, nslookup)
- [ ] Activer les services sur le nouveau serveur
- [ ] Verifier SSL (certificats Let's Encrypt)
- [ ] Tester chaque service : CivicDash, Dolibarr, Mail, sites

### Phase 4 : Post-migration (J+1 a J+7)
- [ ] Surveiller les logs d'erreurs
- [ ] Verifier les crons/queues
- [ ] Verifier l'envoi/reception d'emails
- [ ] Verifier les webhooks PayPal
- [ ] Mettre a jour les URLs dans la config PayPal si necessaire
- [ ] Conserver l'ancien serveur 7 jours en fallback
- [ ] Supprimer l'ancien serveur apres validation

---

## 4. Commandes de backup/restore

### PostgreSQL (CivicDash)
```bash
# Backup
docker exec civicdash_db pg_dump -U civicdash civicdash > civicdash_$(date +%Y%m%d).sql

# Restore
docker exec -i civicdash_db psql -U civicdash civicdash < civicdash_20260221.sql
```

### MariaDB (Dolibarr)
```bash
# Backup
mysqldump -u doli_civis -p doli_civis > dolibarr_$(date +%Y%m%d).sql

# Restore
mysql -u doli_civis -p doli_civis < dolibarr_20260221.sql
```

### Fichiers storage
```bash
# Sync CivicDash storage
rsync -avz --progress /opt/civicdash/storage/app/ user@new-server:/opt/civicdash/storage/app/

# Sync Dolibarr documents
rsync -avz --progress /var/www/civis-consilium.eu/erp/documents/ user@new-server:/opt/dolibarr/documents/

# Sync Mailu data
rsync -avz --progress /path/to/mailu/data/ user@new-server:/opt/mailu/data/
```

### MeiliSearch
```bash
# Export (via API)
curl -s http://localhost:7700/dumps -X POST -H "Authorization: Bearer $MEILI_KEY"
# Les dumps sont dans le volume meili_data/dumps/

# Ou plus simple : rsync le volume entier
docker cp civicdash_search:/meili_data ./meili_backup/
```

---

## 5. DNS et certificats SSL

### Enregistrements DNS a modifier
| Domaine | Type | Valeur actuelle | Nouvelle valeur |
|---------|------|-----------------|-----------------|
| demo.objectif2027.fr | A | IP_ANCIEN | IP_NOUVEAU |
| objectif2027.fr | A | IP_ANCIEN | IP_NOUVEAU |
| erp.civis-consilium.eu | A | IP_ANCIEN | IP_NOUVEAU |
| civis-consilium.eu | A/MX | IP_ANCIEN | IP_NOUVEAU |
| mail.civis-consilium.eu | A | IP_ANCIEN | IP_NOUVEAU |

### Certificats SSL
- **Option A (Traefik)** : Automatique via le certresolver Let's Encrypt
- **Option B (Certbot)** : `certbot certonly --nginx -d demo.objectif2027.fr -d erp.civis-consilium.eu`
- **TTL DNS** : Reduire a 300s (5 min) une semaine avant la migration, puis remettre a 3600s apres

---

## 6. Rollback

En cas de probleme majeur :
1. Rebasculer les DNS vers l'ancien serveur (propagation ~5 min avec TTL court)
2. Redemarrer les services sur l'ancien serveur
3. Les donnees creees entre la migration et le rollback seront perdues (fenetre courte)

Pour minimiser ce risque :
- Garder l'ancien serveur operationnel pendant 7 jours
- Planifier la migration en heure creuse (dimanche matin)
- Avoir un script de rollback DNS pret

---

## 7. Checklist pre/post migration

### Pre-migration
- [ ] Tous les dumps testables restaures et verifies sur la cible
- [ ] Tests fonctionnels passes : login, inscription, paiement PayPal, Dolibarr API
- [ ] Emails sortants fonctionnels (SPF, DKIM, DMARC configures)
- [ ] Monitoring en place (uptime check)
- [ ] Sauvegardes automatiques configurees sur le nouveau serveur
- [ ] TTL DNS reduit a 300s

### Post-migration
- [ ] Tous les sites repondent en HTTPS
- [ ] Login/register CivicDash fonctionne
- [ ] Verification membre Dolibarr fonctionne (API)
- [ ] Paiement PayPal fonctionne (webhook)
- [ ] Envoi/reception emails fonctionne
- [ ] Queue workers traitent les jobs
- [ ] Crons/scheduler executent les taches
- [ ] MeiliSearch repond (recherche)
- [ ] Pas d'erreurs 500 dans les logs
- [ ] Sauvegardes automatiques executees avec succes
- [ ] Ancien serveur eteint apres 7 jours de validation
