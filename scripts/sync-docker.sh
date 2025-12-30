#!/bin/bash
#
# 🐳 Wrapper pour exécuter sync-all-data.sh dans Docker
#
# Usage:
#   ./scripts/sync-docker.sh              # Synchronisation complète
#   ./scripts/sync-docker.sh --an         # Assemblée Nationale uniquement
#   ./scripts/sync-docker.sh --senat      # Sénat uniquement
#   ./scripts/sync-docker.sh --hatvp      # HATVP uniquement
#   ./scripts/sync-docker.sh --dry-run    # Simulation
#
# Cron (exemple pour 3h du matin):
#   0 3 * * * cd /opt/civicdash && ./scripts/sync-docker.sh >> /var/log/civicdash-sync.log 2>&1
#

set -e

# Répertoire du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

# Vérifier que docker compose est disponible
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé"
    exit 1
fi

# Vérifier que le conteneur app est en cours d'exécution
if ! docker compose ps app --status running 2>/dev/null | grep -q "running"; then
    echo "❌ Le conteneur 'app' n'est pas en cours d'exécution"
    echo "   Démarrez-le avec: docker compose up -d"
    exit 1
fi

echo "🐳 Exécution de la synchronisation dans Docker..."
echo ""

# Exécuter le script dans le conteneur
# -T désactive l'allocation de pseudo-TTY (nécessaire pour cron)
docker compose exec -T app ./scripts/sync-all-data.sh "$@"

