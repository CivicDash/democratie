# Mesurer l'audience d'objectif2027.fr sans traceur

## Le principe

Le serveur journalise déjà les requêtes pour fonctionner. On lit ce journal une fois par
jour, on compte, et **on n'écrit que des agrégats**. Résultat :

- **aucun script** chez le visiteur — le site reste à zéro JavaScript sur les pages de contenu ;
- **aucun cookie**, donc aucun bandeau de consentement ;
- **aucun service tiers**, aucune donnée qui sort du serveur ;
- **aucune adresse IP** ni ligne de log conservée en base.

C'est la seule approche compatible avec la promesse affichée du site : « sans compte, sans
traceur ». Un Matomo, même auto-hébergé, supposerait un script côté visiteur, une base
dédiée et des ressources sur une machine déjà chargée.

## 1. Activer la journalisation (à faire une fois)

Caddy sert le site en frontal et **n'écrit aujourd'hui aucun journal d'accès** pour ce
vhost. Ajouter dans le bloc `objectif2027.fr` du `/etc/caddy/Caddyfile` :

```caddy
    log {
        output file /var/log/caddy/objectif2027.log {
            roll_size 20mb
            roll_keep 7
            roll_keep_for 168h
        }
        format json
    }
```

Puis `sudo systemctl reload caddy`. Le format JSON est nécessaire : la commande lit une
ligne JSON par requête.

La rotation garde une semaine de journaux bruts, ce qui suffit largement puisque
l'agrégation tourne chaque nuit. Les données brutes disparaissent d'elles-mêmes.

## 2. Agréger

**Le conteneur applicatif ne voit pas `/var/log/caddy`** : il ne monte que `.env` et
`storage`. Le journal doit donc être déposé dans le storage partagé avant l'agrégation,
ce que fait `audience-sync.sh`, à exécuter **sur l'hôte** :

```bash
/opt/civicdash-prod/audience-sync.sh --dry-run   # afficher sans rien écrire
/opt/civicdash-prod/audience-sync.sh             # écrire les agrégats
```

Le script copie le journal, lance `php artisan audience:agreger` dans le conteneur, puis
**supprime la copie de travail** : les agrégats sont en base, les lignes brutes contiennent
des adresses IP et n'ont plus d'utilité.

Planification, dans la crontab de l'hôte :

```cron
50 4 * * * /opt/civicdash-prod/audience-sync.sh >> /var/log/audience-objectif2027.log 2>&1
```

Ce n'est volontairement **pas** une tâche du scheduler Laravel : elle échouerait chaque
nuit, le conteneur n'ayant pas accès au journal.

## 3. Ce qui est compté, et ce qui ne l'est pas

**Compté** : les réponses `200` sur des pages HTML.

**Ignoré** : les assets (`/_astro/`, `/pagefind/`, `/og/`), les données ouvertes (`/data/`),
le favicon, `robots.txt`, `sitemap.xml`, et tout ce qui n'est pas un `200` — les 404 et les
redirections ne sont pas des lectures.

## 4. Bot ou humain : ce que vaut la distinction

Trois signaux, par ordre de coût :

1. **User-Agent déclaré** — couvre l'écrasante majorité : `bot`, `crawler`, `curl/`,
   `python-requests`, `GPTBot`, `ClaudeBot`, `Bytespider`… Un agent qui s'annonce comme
   robot est cru sur parole. Un User-Agent vide est traité comme un robot : un navigateur
   en envoie toujours un.
2. **Le rythme** (non implémenté) — 200 pages en une minute n'est pas un lecteur.
3. **Le chargement des assets** (non implémenté) — un vrai navigateur demande la page *et*
   son CSS dans `/_astro/`. Un crawler simple ne prend que le HTML. C'est le signal le plus
   fiable sans cookie, et il est déjà disponible dans le journal.

**À dire clairement : le chiffre est un ordre de grandeur, pas une mesure.** Un robot qui
se déclare navigateur sera compté comme humain. C'est vrai de n'importe quel outil
d'audience, y compris ceux qui posent des cookies.

## 5. Les « visiteurs estimés »

Approximation du nombre de personnes distinctes dans la journée. L'empreinte
(adresse IP + User-Agent) est hachée avec un sel quotidien dérivé d'`APP_KEY`, **utilisée en
mémoire pour compter, puis jetée** : seul le décompte est stocké.

Le sel changeant chaque jour, deux journées ne sont pas rapprochables — on ne peut pas
reconstituer un parcours de visiteur dans le temps. C'est volontaire, et c'est ce qui
distingue un comptage d'un traçage.

Limites à garder en tête : plusieurs personnes derrière une même IP (entreprise, partage de
connexion) comptent pour une ; une personne qui change de réseau compte pour deux.
