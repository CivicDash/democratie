#!/bin/bash
#
# 📅 Installation du cron de synchronisation
#
# Ce script configure le cron pour la synchronisation quotidienne
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SYNC_SCRIPT="$SCRIPT_DIR/sync-all-data.sh"
LOG_DIR="/var/log/demoscratos"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}📅 Installation du cron de synchronisation${NC}"
echo ""

# Vérifier que le script de sync existe
if [ ! -f "$SYNC_SCRIPT" ]; then
    echo "❌ Script de synchronisation non trouvé: $SYNC_SCRIPT"
    exit 1
fi

# Rendre le script exécutable
chmod +x "$SYNC_SCRIPT"
echo -e "${GREEN}✅ Script rendu exécutable${NC}"

# Créer le répertoire de logs si nécessaire
sudo mkdir -p "$LOG_DIR"
sudo chown $(whoami):$(whoami) "$LOG_DIR"
echo -e "${GREEN}✅ Répertoire de logs créé: $LOG_DIR${NC}"

# Définir les entrées cron
CRON_ENTRIES="
# ============================================================================
# DEMOSCRATOS - Synchronisation des données parlementaires
# ============================================================================

# Synchronisation complète quotidienne à 3h du matin
0 3 * * * $SYNC_SCRIPT >> $LOG_DIR/sync.log 2>&1

# Synchronisation légère des scrutins AN toutes les 6h (pendant les sessions)
# 0 */6 * * * $SYNC_SCRIPT --an >> $LOG_DIR/sync-an.log 2>&1

# Nettoyage des vieux logs (garder 30 jours)
0 4 * * 0 find $LOG_DIR -name '*.log' -mtime +30 -delete

# ============================================================================
"

echo ""
echo -e "${YELLOW}📋 Entrées cron à ajouter:${NC}"
echo "$CRON_ENTRIES"
echo ""

read -p "Voulez-vous installer ces entrées cron ? (y/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Sauvegarder le crontab actuel
    crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || true
    
    # Ajouter les nouvelles entrées (en évitant les doublons)
    (crontab -l 2>/dev/null | grep -v "sync-all-data.sh" | grep -v "DEMOSCRATOS"; echo "$CRON_ENTRIES") | crontab -
    
    echo -e "${GREEN}✅ Cron installé avec succès${NC}"
    echo ""
    echo "Vérification du crontab actuel:"
    crontab -l
else
    echo -e "${YELLOW}⏭️ Installation annulée${NC}"
    echo ""
    echo "Pour installer manuellement, exécutez:"
    echo "  crontab -e"
    echo ""
    echo "Et ajoutez:"
    echo "  0 3 * * * $SYNC_SCRIPT >> $LOG_DIR/sync.log 2>&1"
fi

echo ""
echo -e "${BLUE}💡 Commandes utiles:${NC}"
echo "  - Voir le crontab: crontab -l"
echo "  - Éditer le crontab: crontab -e"
echo "  - Voir les logs: tail -f $LOG_DIR/sync.log"
echo "  - Test manuel: $SYNC_SCRIPT --verbose"
echo "  - Test dry-run: $SYNC_SCRIPT --dry-run --verbose"

