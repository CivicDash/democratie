# 🚀 Setup Rapide CivicDash

## Démarrage en 3 commandes

```bash
# 1. Cloner et entrer dans le projet
git clone https://github.com/TON_USERNAME/civicdash.git
cd civicdash

# 2. Lancer le script d'installation automatique
chmod +x start-staging.sh
./start-staging.sh

# 3. Accéder à l'application
# → http://localhost:7777
```

## Comptes de test

| Email                    | Password | Rôle       |
|--------------------------|----------|------------|
| admin@civicdash.fr       | password | Admin      |
| moderator@civicdash.fr   | password | Moderator  |
| legislator@civicdash.fr  | password | Legislator |
| journalist@civicdash.fr  | password | Journalist |
| citizen@civicdash.fr     | password | Citizen    |

## Problème Frontend Vue ne s'affiche pas ?

Si tu vois une page blanche ou la page Laravel par défaut :

```bash
# 1. Vérifier que Vite tourne
docker exec civicdash-app ps aux | grep vite

# 2. Relancer Vite si nécessaire
docker exec civicdash-app pkill -f vite
docker exec -d civicdash-app sh -c 'cd /var/www && npm run dev > /tmp/vite.log 2>&1'

# 3. Vérifier les logs Vite
docker exec civicdash-app cat /tmp/vite.log

# 4. Tester les assets Vite
curl http://localhost:5173/@vite/client
# Doit retourner du JavaScript, pas 404

# 5. Vider le cache navigateur (Ctrl+Shift+Suppr)
# ou essayer en navigation privée
```

## URLs importantes

- **App** : http://localhost:7777
- **Horizon** : http://localhost:7777/horizon
- **Telescope** : http://localhost:7777/telescope (si activé)
- **Vite Dev** : http://localhost:5173
- **Meilisearch** : http://localhost:7700

## Ports utilisés

- **7777** : Laravel (app)
- **5173** : Vite dev server
- **5433** : PostgreSQL (externe, interne 5432)
- **6380** : Redis (externe, interne 6379)
- **7700** : Meilisearch

## Commandes Docker utiles

```bash
# Voir les logs
docker logs -f civicdash-app

# Shell dans le container
docker exec -it civicdash-app bash

# Artisan
docker exec civicdash-app php artisan [commande]

# Lancer les tests
docker exec civicdash-app php artisan test

# Redémarrer les services
docker-compose restart
```

## En cas de problème

1. **Port déjà utilisé** : Modifie les ports dans `docker-compose.yml`
2. **Assets ne se chargent pas** : Vérifier que Vite tourne (voir ci-dessus)
3. **Erreurs migrations** : `docker exec civicdash-app php artisan migrate:fresh --seed`
4. **Problème cache** : `docker exec civicdash-app php artisan cache:clear && php artisan config:clear`

## Documentation complète

- `README.md` : Vue d'ensemble
- `docs/SETUP.md` : Installation détaillée
- `docs/STAGING_LOCAL.md` : Environnement local
- `docs/ROADMAP.md` : Roadmap 2026
- `docs/MOBILE_GUIDE.md` : Guide développement mobile
- `docs/RECAP_PROJET.md` : Récapitulatif complet

---

💙 CivicDash - Plateforme Démocratique Participative
