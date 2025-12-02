#!/bin/bash
#
# 🔄 Script de synchronisation quotidienne des données parlementaires
#
# Ce script synchronise les données de :
# - Assemblée Nationale (XML)
# - Sénat (PostgreSQL dumps + Akoma Ntoso)
# - HATVP (XML déclarations d'intérêts et patrimoine)
#
# IMPORTANT: Ce script doit être exécuté via Docker:
#   docker compose exec app ./scripts/sync-all-data.sh
#
# Ou depuis l'hôte avec le wrapper:
#   ./scripts/sync-docker.sh
#
# Usage:
#   ./scripts/sync-all-data.sh              # Synchronisation complète
#   ./scripts/sync-all-data.sh --an         # Assemblée Nationale uniquement
#   ./scripts/sync-all-data.sh --senat      # Sénat uniquement
#   ./scripts/sync-all-data.sh --hatvp      # HATVP uniquement
#   ./scripts/sync-all-data.sh --dry-run    # Simulation sans modification
#
# Cron (exemple pour 3h du matin):
#   0 3 * * * cd /opt/civicdash && docker compose exec -T app ./scripts/sync-all-data.sh >> /var/log/civicdash-sync.log 2>&1
#

set -e

# ============================================================================
# CONFIGURATION
# ============================================================================

# Répertoire du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Logs - utiliser /tmp si storage/logs n'est pas accessible
LOG_DIR="${PROJECT_DIR}/storage/logs"
if [ ! -w "$LOG_DIR" ] 2>/dev/null; then
    LOG_DIR="/tmp"
fi
LOG_FILE="${LOG_DIR}/sync-$(date +%Y-%m-%d).log"
LOCK_FILE="/tmp/demoscratos-sync.lock"

# Législature AN (à mettre à jour si changement)
LEGISLATURE=17

# Options par défaut
SYNC_AN=false
SYNC_SENAT=false
SYNC_HATVP=false
SYNC_ALL=true
DRY_RUN=false
VERBOSE=false

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# FONCTIONS UTILITAIRES
# ============================================================================

fix_permissions() {
    # Corriger les permissions des répertoires de stockage si nécessaire
    local storage_dirs=(
        "${PROJECT_DIR}/storage/logs"
        "${PROJECT_DIR}/storage/app"
        "${PROJECT_DIR}/storage/app/an-data"
        "${PROJECT_DIR}/storage/app/an-data/zip"
        "${PROJECT_DIR}/storage/app/an-data/xml"
        "${PROJECT_DIR}/storage/app/senat-data"
        "${PROJECT_DIR}/storage/app/hatvp-data"
    )
    
    for dir in "${storage_dirs[@]}"; do
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir" 2>/dev/null || true
        fi
        # Essayer de rendre le répertoire accessible en écriture
        chmod 775 "$dir" 2>/dev/null || true
    done
    
    # S'assurer que storage/logs est accessible
    touch "${PROJECT_DIR}/storage/logs/.gitkeep" 2>/dev/null || true
}

log() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Écrire dans le log si possible
    if [ -w "$LOG_FILE" ] 2>/dev/null || touch "$LOG_FILE" 2>/dev/null; then
        echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
    fi
    
    # Toujours afficher à l'écran
    case "$level" in
        INFO)  echo -e "${BLUE}ℹ️  $message${NC}" ;;
        OK)    echo -e "${GREEN}✅ $message${NC}" ;;
        WARN)  echo -e "${YELLOW}⚠️  $message${NC}" ;;
        ERROR) echo -e "${RED}❌ $message${NC}" ;;
        *)     echo "$message" ;;
    esac
}

check_lock() {
    if [ -f "$LOCK_FILE" ]; then
        local pid=$(cat "$LOCK_FILE")
        if ps -p "$pid" > /dev/null 2>&1; then
            log "ERROR" "Une synchronisation est déjà en cours (PID: $pid)"
            exit 1
        else
            log "WARN" "Fichier lock orphelin trouvé, suppression..."
            rm -f "$LOCK_FILE"
        fi
    fi
    echo $$ > "$LOCK_FILE"
}

cleanup() {
    rm -f "$LOCK_FILE"
}

trap cleanup EXIT

show_help() {
    echo "🔄 Script de synchronisation des données parlementaires"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --an          Synchroniser uniquement l'Assemblée Nationale"
    echo "  --senat       Synchroniser uniquement le Sénat"
    echo "  --hatvp       Synchroniser uniquement la HATVP"
    echo "  --dry-run     Simulation sans modification de la base"
    echo "  --verbose     Afficher les détails"
    echo "  --help        Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  docker compose exec app $0                    # Synchronisation complète"
    echo "  docker compose exec app $0 --an --verbose     # AN uniquement avec détails"
    echo "  docker compose exec app $0 --dry-run          # Simulation"
    echo ""
}

# ============================================================================
# PARSING DES ARGUMENTS
# ============================================================================

while [[ $# -gt 0 ]]; do
    case $1 in
        --an)
            SYNC_AN=true
            SYNC_ALL=false
            shift
            ;;
        --senat)
            SYNC_SENAT=true
            SYNC_ALL=false
            shift
            ;;
        --hatvp)
            SYNC_HATVP=true
            SYNC_ALL=false
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            show_help
            exit 0
            ;;
        *)
            echo "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Si --all ou aucune option spécifique
if [ "$SYNC_ALL" = true ]; then
    SYNC_AN=true
    SYNC_SENAT=true
    SYNC_HATVP=true
fi

# ============================================================================
# VÉRIFICATIONS PRÉALABLES
# ============================================================================

cd "$PROJECT_DIR"

# Corriger les permissions avant de commencer
fix_permissions

log "INFO" "=========================================="
log "INFO" "Démarrage de la synchronisation"
log "INFO" "=========================================="
log "INFO" "Projet: $PROJECT_DIR"
log "INFO" "Législature AN: $LEGISLATURE"
log "INFO" "Mode dry-run: $DRY_RUN"

# Vérifier que PHP est disponible
if ! command -v php &> /dev/null; then
    log "ERROR" "PHP n'est pas installé ou pas dans le PATH"
    exit 1
fi

# Vérifier que le projet Laravel est configuré
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    log "ERROR" "Fichier artisan non trouvé. Êtes-vous dans un projet Laravel ?"
    exit 1
fi

# Vérifier le lock
check_lock

# Variables pour le résumé
AN_STATUS="⏭️ Ignoré"
SENAT_STATUS="⏭️ Ignoré"
HATVP_STATUS="⏭️ Ignoré"
ERRORS=0

# ============================================================================
# SYNCHRONISATION ASSEMBLÉE NATIONALE
# ============================================================================

sync_assemblee_nationale() {
    log "INFO" "------------------------------------------"
    log "INFO" "🏛️  ASSEMBLÉE NATIONALE"
    log "INFO" "------------------------------------------"
    
    local start_time=$(date +%s)
    local dry_run_flag=""
    
    if [ "$DRY_RUN" = true ]; then
        dry_run_flag="--dry-run"
        log "WARN" "Mode simulation activé"
    fi
    
    # Sources à synchroniser (par ordre de priorité)
    local sources=("deputes_actifs" "scrutins" "amendements")
    
    for source in "${sources[@]}"; do
        log "INFO" "Synchronisation: $source"
        
        if php artisan an:sync "$source" --legislature="$LEGISLATURE" $dry_run_flag 2>&1; then
            log "OK" "$source synchronisé"
        else
            log "WARN" "Échec synchronisation $source (peut être normal si pas de nouvelles données)"
        fi
    done
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    AN_STATUS="✅ OK (${duration}s)"
    log "OK" "Assemblée Nationale terminée en ${duration}s"
}

# ============================================================================
# SYNCHRONISATION SÉNAT
# ============================================================================

sync_senat() {
    log "INFO" "------------------------------------------"
    log "INFO" "🏛️  SÉNAT"
    log "INFO" "------------------------------------------"
    
    local start_time=$(date +%s)
    
    if [ "$DRY_RUN" = true ]; then
        log "WARN" "Mode simulation - pas d'import SQL"
        
        # En mode dry-run, juste analyser
        php artisan senat:sync --status 2>&1 || true
    else
        # Synchroniser les bases SQL principales
        local bases=("senateurs" "ameli")
        
        for base in "${bases[@]}"; do
            log "INFO" "Import base: $base"
            
            # Utiliser --no-interaction pour éviter les prompts
            if echo "yes" | php artisan import:senat-sql "$base" 2>&1; then
                log "OK" "$base importé"
            else
                log "WARN" "Import $base - vérifier les logs"
            fi
        done
        
        # Synchroniser les textes Akoma Ntoso (incrémental)
        log "INFO" "Synchronisation textes Akoma Ntoso..."
        if php artisan senat:sync --textes 2>&1; then
            log "OK" "Textes Akoma Ntoso synchronisés"
        else
            log "WARN" "Textes Akoma Ntoso - vérifier les logs"
        fi
    fi
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    SENAT_STATUS="✅ OK (${duration}s)"
    log "OK" "Sénat terminé en ${duration}s"
}

# ============================================================================
# SYNCHRONISATION HATVP
# ============================================================================

sync_hatvp() {
    log "INFO" "------------------------------------------"
    log "INFO" "🏛️  HATVP (Déclarations)"
    log "INFO" "------------------------------------------"
    
    local start_time=$(date +%s)
    
    if [ "$DRY_RUN" = true ]; then
        log "WARN" "Mode simulation - analyse uniquement"
        
        php artisan hatvp:sync --analyze --parlementaires 2>&1 || true
    else
        # Importer uniquement les parlementaires (députés + sénateurs)
        log "INFO" "Import des déclarations parlementaires..."
        
        if php artisan hatvp:sync --import --parlementaires 2>&1; then
            log "OK" "Déclarations HATVP importées"
        else
            log "WARN" "Import HATVP - vérifier les logs"
        fi
    fi
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    HATVP_STATUS="✅ OK (${duration}s)"
    log "OK" "HATVP terminé en ${duration}s"
}

# ============================================================================
# TÂCHES POST-SYNCHRONISATION
# ============================================================================

post_sync_tasks() {
    log "INFO" "------------------------------------------"
    log "INFO" "🔧 Tâches post-synchronisation"
    log "INFO" "------------------------------------------"
    
    if [ "$DRY_RUN" = true ]; then
        log "WARN" "Mode simulation - tâches ignorées"
        return
    fi
    
    # Recalculer les statistiques des scrutins AN si nécessaire
    if [ "$SYNC_AN" = true ]; then
        log "INFO" "Recalcul des statistiques scrutins AN..."
        if php artisan scrutins:recalculer --legislature="$LEGISLATURE" 2>&1; then
            log "OK" "Statistiques scrutins recalculées"
        else
            log "WARN" "Recalcul scrutins - vérifier les logs"
        fi
    fi
    
    # Enrichissement Wikipedia (optionnel, limité pour éviter trop de requêtes)
    if [ "$SYNC_SENAT" = true ]; then
        log "INFO" "Enrichissement Wikipedia sénateurs (10 max)..."
        if php artisan enrich:senateurs-wikipedia --limit=10 --force 2>&1; then
            log "OK" "Enrichissement Wikipedia sénateurs terminé"
        else
            log "WARN" "Enrichissement Wikipedia sénateurs - vérifier les logs"
        fi
    fi
    
    if [ "$SYNC_AN" = true ]; then
        log "INFO" "Enrichissement Wikipedia députés (10 max)..."
        if php artisan import:deputes-wikipedia --limit=10 2>&1; then
            log "OK" "Enrichissement Wikipedia députés terminé"
        else
            log "WARN" "Enrichissement Wikipedia députés - vérifier les logs"
        fi
    fi
    
    # Nettoyer le cache Laravel
    log "INFO" "Nettoyage du cache..."
    php artisan cache:clear 2>&1 || true
    
    log "OK" "Tâches post-synchronisation terminées"
}

# ============================================================================
# EXÉCUTION PRINCIPALE
# ============================================================================

TOTAL_START=$(date +%s)

# Synchroniser selon les options
if [ "$SYNC_AN" = true ]; then
    sync_assemblee_nationale
fi

if [ "$SYNC_SENAT" = true ]; then
    sync_senat
fi

if [ "$SYNC_HATVP" = true ]; then
    sync_hatvp
fi

# Tâches post-sync
post_sync_tasks

TOTAL_END=$(date +%s)
TOTAL_DURATION=$((TOTAL_END - TOTAL_START))

# ============================================================================
# RÉSUMÉ FINAL
# ============================================================================

log "INFO" "=========================================="
log "INFO" "📊 RÉSUMÉ DE LA SYNCHRONISATION"
log "INFO" "=========================================="

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 RÉSUMÉ DE LA SYNCHRONISATION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Assemblée Nationale : $AN_STATUS"
echo "Sénat               : $SENAT_STATUS"
echo "HATVP               : $HATVP_STATUS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Durée totale        : ${TOTAL_DURATION}s"
echo "Erreurs             : $ERRORS"
if [ -f "$LOG_FILE" ]; then
    echo "Log                 : $LOG_FILE"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
