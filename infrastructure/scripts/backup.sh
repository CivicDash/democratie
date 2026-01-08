#!/bin/bash
# =============================================================================
# CivicDash - Script de backup manuel
# =============================================================================
# Usage: ./backup.sh [--remote user@host:/path]
# =============================================================================

set -euo pipefail

REMOTE_DEST=""
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/lib/vz/dump"

# Parsing arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --remote)
            REMOTE_DEST="$2"
            shift 2
            ;;
        *)
            shift
            ;;
    esac
done

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  💾 CivicDash - Backup Manuel                                 ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "Timestamp: $TIMESTAMP"
echo ""

# Containers à sauvegarder
CONTAINERS="100 101 102 103 104"

for VMID in $CONTAINERS; do
    echo "→ Backup du container $VMID..."
    
    # Créer le backup avec compression zstd
    vzdump $VMID \
        --storage local \
        --mode snapshot \
        --compress zstd \
        --notes "Backup manuel - $TIMESTAMP"
    
    echo "  ✅ Container $VMID sauvegardé"
done

echo ""
echo "Backups créés dans: $BACKUP_DIR"
ls -lah $BACKUP_DIR/*$TIMESTAMP* 2>/dev/null || echo "  (fichiers en cours de création)"

# Sync vers destination distante si spécifié
if [[ -n "$REMOTE_DEST" ]]; then
    echo ""
    echo "→ Synchronisation vers $REMOTE_DEST..."
    
    rsync -avz --progress \
        "$BACKUP_DIR/"*$TIMESTAMP* \
        "$REMOTE_DEST"
    
    echo "  ✅ Synchronisation terminée"
fi

echo ""
echo "✅ Backup terminé !"
