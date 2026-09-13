#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Agrégation quotidienne de l'audience d'objectif2027.fr.
#
# Le conteneur applicatif ne monte que .env et storage : il ne voit pas
# /var/log/caddy. Ce script, exécuté sur l'HÔTE, dépose le journal dans le
# storage partagé puis déclenche l'agrégation dans le conteneur.
#
# Rien n'est envoyé nulle part : on lit un journal local, on écrit des compteurs
# en base, et la copie de travail est supprimée aussitôt.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

LOG=/var/log/caddy/objectif2027.log
DEST_REL=app/audience
STORAGE=/var/lib/docker/volumes/civicdash-prod_app_storage/_data

[ -f "$LOG" ] || { echo "Journal absent : $LOG — la journalisation du vhost est-elle active ?"; exit 1; }

mkdir -p "$STORAGE/$DEST_REL"
cp "$LOG" "$STORAGE/$DEST_REL/objectif2027.log"
chmod 644 "$STORAGE/$DEST_REL/objectif2027.log"

docker exec cd_prod_app php artisan audience:agreger "$@"

# La copie de travail ne doit pas traîner : les agrégats sont en base, les lignes
# brutes n'ont plus d'utilité et contiennent des adresses IP.
rm -f "$STORAGE/$DEST_REL/objectif2027.log"
