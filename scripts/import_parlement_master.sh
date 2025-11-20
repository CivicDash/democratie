#!/bin/bash

###############################################################################
# 🏛️  SCRIPT MASTER - IMPORT COMPLET PARLEMENT (AN + SÉNAT)
# 
# Ce script unique remplace tous les anciens scripts et importe :
#
# ASSEMBLÉE NATIONALE (L17) :
#   1. Acteurs AN (députés)
#   2. Organes AN (groupes, commissions)
#   3. Mandats AN
#   4. Scrutins AN
#   5. Votes Individuels AN
#   6. Dossiers + Textes Législatifs
#   7. Amendements AN
#   8. Wikipedia (députés)
#
# SÉNAT :
#   9. Sénateurs (API data.senat.fr)
#
# Durée totale estimée : 12-16 heures
###############################################################################

set -e  # Arrêter en cas d'erreur

# Déterminer le répertoire racine du projet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
DOCKER_CMD="docker compose exec app"
LOG_DIR="logs/import_parlement_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$LOG_DIR"

###############################################################################
# FONCTIONS UTILITAIRES
###############################################################################

log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')]${NC} $1"
}

log_error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ❌ ERREUR:${NC} $1"
}

log_success() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ SUCCESS:${NC} $1"
}

log_step() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
}

show_banner() {
    echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║                                                               ║${NC}"
    echo -e "${PURPLE}║     🏛️  IMPORT MASTER - PARLEMENT FRANÇAIS COMPLET  🏛️        ║${NC}"
    echo -e "${PURPLE}║                                                               ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

###############################################################################
# MENU INTERACTIF
###############################################################################

show_menu() {
    show_banner
    
    echo -e "${YELLOW}📊 QUE VOULEZ-VOUS IMPORTER ?${NC}"
    echo ""
    echo "  1) 🏛️  ASSEMBLÉE NATIONALE UNIQUEMENT (L17)"
    echo "     └─ 8 étapes • ~12-15h • ~400k enregistrements"
    echo ""
    echo "  2) 🏰 SÉNAT UNIQUEMENT"
    echo "     └─ 1 étape • ~5-10 min • ~8k enregistrements"
    echo ""
    echo "  3) 🇫🇷 PARLEMENT COMPLET (AN + SÉNAT)"
    echo "     └─ 9 étapes • ~12-16h • ~408k enregistrements"
    echo ""
    echo "  4) 🧪 MODE TEST (Limité pour tests)"
    echo "     └─ Toutes étapes avec --limit=10"
    echo ""
    echo "  0) ❌ Annuler"
    echo ""
    read -p "Votre choix (0-4) : " choice
    echo ""
    
    case $choice in
        1) import_assemblee_nationale ;;
        2) import_senat ;;
        3) import_parlement_complet ;;
        4) import_mode_test ;;
        0) log "Import annulé par l'utilisateur" ; exit 0 ;;
        *) log_error "Choix invalide" ; exit 1 ;;
    esac
}

###############################################################################
# VÉRIFICATIONS PRÉALABLES
###############################################################################

check_prerequisites() {
    log "Vérification des prérequis..."
    
    # Afficher le répertoire de travail
    log "Répertoire de travail: $(pwd)"
    
    # Docker
    if ! docker compose ps | grep -q "Up"; then
        log_error "Docker Compose n'est pas démarré. Lancer: docker compose up -d"
        exit 1
    fi
    log_success "Docker Compose actif"
    
    # Données source AN
    if [ ! -d "public/data/acteur" ]; then
        log_error "Dossier public/data/acteur introuvable dans $(pwd)"
        log "Contenu de public/data/:"
        ls -la public/data/ 2>&1 || echo "  Dossier public/data/ introuvable"
        exit 1
    fi
    
    # Compter les fichiers JSON
    ACTEUR_COUNT=$(ls -1 public/data/acteur/*.json 2>/dev/null | wc -l)
    log_success "Données source AN présentes ($ACTEUR_COUNT fichiers acteur)"
    
    # Vérifier scrutins
    if [ -d "public/data/scrutins" ]; then
        SCRUTIN_COUNT=$(ls -1 public/data/scrutins/*.json 2>/dev/null | wc -l)
        log_success "Données scrutins présentes ($SCRUTIN_COUNT fichiers)"
    fi
    
    # Vérifier amendements
    if [ -d "public/data/amendements" ]; then
        AMENDEMENT_COUNT=$(ls -1 public/data/amendements/*.json 2>/dev/null | wc -l)
        log_success "Données amendements présentes ($AMENDEMENT_COUNT fichiers)"
    fi
}

###############################################################################
# IMPORT ASSEMBLÉE NATIONALE
###############################################################################

import_assemblee_nationale() {
    log_step "🏛️  ASSEMBLÉE NATIONALE - Législature 17"
    
    echo -e "${YELLOW}⚠️  ATTENTION:${NC}"
    echo "   - Durée : 12-15 heures"
    echo "   - ~400 000 enregistrements"
    echo "   - Données existantes écrasées (--fresh)"
    echo ""
    read -p "Confirmer l'import AN ? (oui/non) : " confirm
    
    if [[ "$confirm" != "oui" ]]; then
        log "Import AN annulé"
        return
    fi
    
    START_TIME=$(date +%s)
    
    # Étape 1 : Acteurs
    log_step "📊 1/8 - Import Acteurs AN (Députés)"
    log "Durée estimée : 5-10 minutes"
    $DOCKER_CMD php artisan import:acteurs-an --fresh 2>&1 | tee "$LOG_DIR/01_acteurs_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Acteurs importés" || { log_error "Échec acteurs" ; exit 1; }
    
    # Étape 2 : Organes
    log_step "📊 2/8 - Import Organes AN"
    log "Durée estimée : 2-5 minutes"
    $DOCKER_CMD php artisan import:organes-an --fresh 2>&1 | tee "$LOG_DIR/02_organes_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Organes importés" || { log_error "Échec organes" ; exit 1; }
    
    # Étape 3 : Mandats
    log_step "📊 3/8 - Import Mandats AN"
    log "Durée estimée : 10-15 minutes"
    $DOCKER_CMD php artisan import:mandats-an --fresh 2>&1 | tee "$LOG_DIR/03_mandats_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Mandats importés" || { log_error "Échec mandats" ; exit 1; }
    
    # Étape 4 : Scrutins
    log_step "📊 4/8 - Import Scrutins AN"
    log "Durée estimée : 1-2 heures"
    $DOCKER_CMD php artisan import:scrutins-an --fresh 2>&1 | tee "$LOG_DIR/04_scrutins_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Scrutins importés" || { log_error "Échec scrutins" ; exit 1; }
    
    # Étape 5 : Votes Individuels
    log_step "📊 5/8 - Extraction Votes Individuels"
    log "Durée estimée : 2-3 heures"
    $DOCKER_CMD php artisan extract:votes-individuels-an --fresh 2>&1 | tee "$LOG_DIR/05_votes_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Votes extraits" || { log_error "Échec votes" ; exit 1; }
    
    # Étape 6 : Dossiers + Textes
    log_step "📊 6/8 - Import Dossiers & Textes"
    log "Durée estimée : 2-3 heures"
    $DOCKER_CMD php artisan import:dossiers-textes-an --fresh 2>&1 | tee "$LOG_DIR/06_dossiers_textes_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Dossiers & Textes importés" || { log_error "Échec dossiers" ; exit 1; }
    
    # Étape 7 : Amendements
    log_step "📊 7/8 - Import Amendements AN"
    log "Durée estimée : 4-6 heures"
    $DOCKER_CMD php artisan import:amendements-an --fresh 2>&1 | tee "$LOG_DIR/07_amendements_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Amendements importés" || { log_error "Échec amendements" ; exit 1; }
    
    # Étape 8 : Wikipedia
    log_step "📊 8/8 - Enrichissement Wikipedia"
    log "Durée estimée : 10-15 minutes"
    $DOCKER_CMD php artisan import:deputes-wikipedia --force 2>&1 | tee "$LOG_DIR/08_wikipedia_an.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Wikipedia importé" || log_error "Échec Wikipedia (non bloquant)"
    
    show_stats_an "$START_TIME"
}

###############################################################################
# IMPORT SÉNAT
###############################################################################

import_senat() {
    log_step "🏰 SÉNAT - Import Complet"
    
    echo -e "${YELLOW}⚠️  ATTENTION:${NC}"
    echo "   - Durée : 5-10 minutes"
    echo "   - ~8 000 enregistrements"
    echo "   - Source : data.senat.fr (API REST)"
    echo ""
    read -p "Confirmer l'import SÉNAT ? (oui/non) : " confirm
    
    if [[ "$confirm" != "oui" ]]; then
        log "Import Sénat annulé"
        return
    fi
    
    START_TIME=$(date +%s)
    
    log_step "📊 Import Sénateurs (API REST)"
    log "Durée estimée : 5-10 minutes"
    $DOCKER_CMD php artisan import:senateurs-complet 2>&1 | tee "$LOG_DIR/09_senateurs.log"
    [ ${PIPESTATUS[0]} -eq 0 ] && log_success "Sénateurs importés" || { log_error "Échec sénateurs" ; exit 1; }
    
    show_stats_senat "$START_TIME"
}

###############################################################################
# IMPORT COMPLET (AN + SÉNAT)
###############################################################################

import_parlement_complet() {
    log_step "🇫🇷 PARLEMENT COMPLET - AN + SÉNAT"
    
    echo -e "${YELLOW}⚠️  ATTENTION:${NC}"
    echo "   - Durée TOTALE : 12-16 heures"
    echo "   - ~408 000 enregistrements"
    echo "   - AN (8 étapes) + Sénat (1 étape)"
    echo ""
    read -p "Confirmer l'import COMPLET ? (oui/non) : " confirm
    
    if [[ "$confirm" != "oui" ]]; then
        log "Import complet annulé"
        exit 0
    fi
    
    GLOBAL_START=$(date +%s)
    
    # 1. Assemblée Nationale
    import_assemblee_nationale
    
    # 2. Sénat
    import_senat
    
    # Résumé global
    show_stats_global "$GLOBAL_START"
}

###############################################################################
# MODE TEST
###############################################################################

import_mode_test() {
    log_step "🧪 MODE TEST - Toutes étapes avec --limit=10"
    
    START_TIME=$(date +%s)
    
    log "Test 1/9 - Acteurs AN (10)"
    $DOCKER_CMD php artisan import:acteurs-an --fresh --limit=10
    
    log "Test 2/9 - Organes AN (10)"
    $DOCKER_CMD php artisan import:organes-an --fresh --limit=10
    
    log "Test 3/9 - Mandats AN (10)"
    $DOCKER_CMD php artisan import:mandats-an --fresh --limit=10
    
    log "Test 4/9 - Scrutins AN (10)"
    $DOCKER_CMD php artisan import:scrutins-an --fresh --limit=10
    
    log "Test 5/9 - Votes Individuels"
    $DOCKER_CMD php artisan extract:votes-individuels-an --fresh --limit=10
    
    log "Test 6/9 - Dossiers + Textes (10)"
    $DOCKER_CMD php artisan import:dossiers-textes-an --fresh --limit=10
    
    log "Test 7/9 - Amendements (10)"
    $DOCKER_CMD php artisan import:amendements-an --fresh --limit=10
    
    log "Test 8/9 - Wikipedia (10)"
    $DOCKER_CMD php artisan import:deputes-wikipedia --limit=10
    
    log "Test 9/9 - Sénateurs"
    $DOCKER_CMD php artisan import:senateurs-complet
    
    show_stats_test "$START_TIME"
}

###############################################################################
# STATISTIQUES
###############################################################################

show_stats_an() {
    local start_time=$1
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    local hours=$((duration / 3600))
    local minutes=$(((duration % 3600) / 60))
    
    echo ""
    echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║          ✅  IMPORT ASSEMBLÉE NATIONALE TERMINÉ !  ✅          ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    log_success "Durée AN: ${hours}h ${minutes}m"
    
    log_step "📈 STATISTIQUES ASSEMBLÉE NATIONALE"
    $DOCKER_CMD php artisan tinker --execute="
echo '✅ Acteurs AN: ' . \App\Models\ActeurAN::count();
echo '✅ Organes AN: ' . \App\Models\OrganeAN::count();
echo '✅ Mandats AN: ' . \App\Models\MandatAN::count();
echo '✅ Scrutins AN: ' . \App\Models\ScrutinAN::count();
echo '✅ Votes Individuels: ' . \App\Models\VoteIndividuelAN::count();
echo '✅ Dossiers: ' . \App\Models\DossierLegislatifAN::count();
echo '✅ Textes: ' . \App\Models\TexteLegislatifAN::count();
echo '✅ Amendements AN: ' . \App\Models\AmendementAN::count();
echo '✅ Députés avec Wikipedia: ' . \App\Models\ActeurAN::whereNotNull('wikipedia_url')->count();
"
}

show_stats_senat() {
    local start_time=$1
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    local minutes=$((duration / 60))
    
    echo ""
    echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║              ✅  IMPORT SÉNAT TERMINÉ !  ✅                    ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    log_success "Durée Sénat: ${minutes} minutes"
    
    log_step "📈 STATISTIQUES SÉNAT"
    $DOCKER_CMD php artisan tinker --execute="
echo '✅ Sénateurs: ' . \App\Models\Senateur::count();
echo '✅ Sénateurs actifs: ' . \App\Models\Senateur::where('etat', 'ACTIF')->count();
echo '✅ Groupes (historique): ' . \App\Models\SenateurHistoriqueGroupe::count();
echo '✅ Commissions: ' . \App\Models\SenateurCommission::count();
echo '✅ Mandats: ' . \App\Models\SenateurMandat::count();
"
}

show_stats_global() {
    local start_time=$1
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    local hours=$((duration / 3600))
    local minutes=$(((duration % 3600) / 60))
    
    echo ""
    echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║        ✅  IMPORT PARLEMENT COMPLET TERMINÉ !  ✅             ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    log_success "Durée TOTALE: ${hours}h ${minutes}m"
    log_success "Logs: $LOG_DIR"
    
    log_step "📈 STATISTIQUES GLOBALES"
    echo -e "${CYAN}🏛️  ASSEMBLÉE NATIONALE${NC}"
    show_stats_an "$start_time" 2>/dev/null || echo "Stats AN déjà affichées"
    
    echo ""
    echo -e "${CYAN}🏰 SÉNAT${NC}"
    show_stats_senat "$start_time" 2>/dev/null || echo "Stats Sénat déjà affichées"
    
    echo ""
    log_success "🎉 Plateforme CivicDash prête !"
    log "URL: https://demo.objectif2027.fr/representants/deputes"
}

show_stats_test() {
    echo ""
    log_success "✅ Tests terminés avec succès !"
    log "Les comptages sont volontairement limités (--limit=10)"
}

###############################################################################
# MAIN
###############################################################################

# Vérifications
check_prerequisites

# Menu principal
show_menu

