#!/bin/bash
#
# ✅ Diagnostic rapide du scheduler Laravel
#
# Usage:
#   ./scripts/check-scheduler.sh
#
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="${PROJECT_DIR}/storage/logs/scheduler.log"

cd "$PROJECT_DIR"

echo "🧭 Scheduler Laravel - statut"
echo "----------------------------------------"

echo "🔹 schedule:list"
docker compose exec -T app php artisan schedule:list || true

echo ""
echo "🔹 Dernières lignes log scheduler"
if [ -f "$LOG_FILE" ]; then
    tail -n 200 "$LOG_FILE"
else
    echo "Log introuvable: $LOG_FILE"
fi
