#!/bin/bash

################################################################################
# Import des bases SQL Sénat - Script Automatisé
# 
# Ce script télécharge et importe les 5 bases SQL du Sénat :
# - Sénateurs (profils complets)
# - DOSLEG (dossiers législatifs)
# - AMELI (amendements)
# - Questions (questions au gouvernement)
# - Débats (comptes rendus)
#
# Usage:
#   ./import_senat_sql.sh [--analyze-only] [--essential-only] [--all]
################################################################################

set -e  # Exit on error

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOG_DIR="$PROJECT_ROOT/storage/logs/import_senat_sql"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="$LOG_DIR/import_${TIMESTAMP}.log"

# Créer le dossier de logs
mkdir -p "$LOG_DIR"

# Fonction de logging
log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%H:%M:%S')] ❌ ERREUR:${NC} $1" | tee -a "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[$(date +'%H:%M:%S')] ⚠️  ATTENTION:${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${CYAN}[$(date +'%H:%M:%S')] ℹ️  INFO:${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] ✅ SUCCÈS:${NC} $1" | tee -a "$LOG_FILE"
}

# Fonction pour afficher le header
print_header() {
    echo ""
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                                                               ║${NC}"
    echo -e "${CYAN}║     🏛️  IMPORT BASES SQL SÉNAT - DONNÉES COMPLÈTES  🏛️        ║${NC}"
    echo -e "${CYAN}║                                                               ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

# Fonction pour vérifier les prérequis
check_prerequisites() {
    log "Vérification des prérequis..."
    
    cd "$PROJECT_ROOT"
    
    # Vérifier Docker Compose
    if ! docker compose ps > /dev/null 2>&1; then
        error "Docker Compose n'est pas actif"
        exit 1
    fi
    success "Docker Compose actif"
    
    # Vérifier la connexion PostgreSQL
    if ! docker compose exec -T app php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
        error "Impossible de se connecter à PostgreSQL"
        exit 1
    fi
    success "Connexion PostgreSQL OK"
}

# Fonction pour analyser une base
analyze_database() {
    local db_name=$1
    local db_desc=$2
    
    log "Analyse de ${db_desc}..."
    
    local analysis_file="$LOG_DIR/analysis_${db_name}_${TIMESTAMP}.txt"
    
    if docker compose exec -T app php artisan import:senat-sql "$db_name" --analyze > "$analysis_file" 2>&1; then
        success "Analyse de ${db_desc} terminée"
        info "Résultat : $analysis_file"
        
        # Afficher un résumé
        local table_count=$(grep -c "Table :" "$analysis_file" || echo "0")
        info "  → ${table_count} table(s) trouvée(s)"
    else
        error "Échec de l'analyse de ${db_desc}"
        return 1
    fi
}

# Fonction pour importer une base
import_database() {
    local db_name=$1
    local db_desc=$2
    local fresh_flag=$3
    
    log "Import de ${db_desc}..."
    
    local cmd="docker compose exec -T app php artisan import:senat-sql $db_name"
    
    if [ "$fresh_flag" = "true" ]; then
        cmd="$cmd --fresh"
        warn "Mode --fresh : les tables existantes seront supprimées"
    fi
    
    local start_time=$(date +%s)
    
    if $cmd 2>&1 | tee -a "$LOG_FILE"; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        success "Import de ${db_desc} terminé en ${duration}s"
    else
        error "Échec de l'import de ${db_desc}"
        return 1
    fi
}

# Fonction pour afficher les statistiques finales
display_stats() {
    log "Récupération des statistiques..."
    
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}📊 STATISTIQUES FINALES${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    
    # Utiliser Docker pour exécuter le PHP
    docker compose exec -T app php artisan tinker --execute="
        \$tables = DB::select(\"
            SELECT tablename, 
                   pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
            FROM pg_tables 
            WHERE schemaname = 'public' 
            AND tablename LIKE 'senat_%'
            ORDER BY tablename
        \");
        
        foreach (\$tables as \$table) {
            \$count = DB::table(\$table->tablename)->count();
            echo \"📊 {\$table->tablename} : {\$count} lignes ({\$table->size})\n\";
        }
    " 2>/dev/null || warn "Impossible de récupérer les statistiques"
    
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
}

# Menu principal
show_menu() {
    echo -e "${YELLOW}📋 QUE VOULEZ-VOUS FAIRE ?${NC}"
    echo ""
    echo "  1) 🔍 ANALYSER TOUTES LES BASES (sans import)"
    echo "     └─ 5 analyses • ~5 min • Voir la structure SQL"
    echo ""
    echo "  2) ⭐ IMPORT ESSENTIEL (Sénateurs + AMELI + DOSLEG)"
    echo "     └─ 3 bases • ~30 min • Données critiques"
    echo ""
    echo "  3) 🎯 IMPORT COMPLET (Tout sauf Débats)"
    echo "     └─ 4 bases • ~40 min • Recommandé"
    echo ""
    echo "  4) 🌟 IMPORT INTÉGRAL (5 bases)"
    echo "     └─ 5 bases • ~60-70 min • Tout importer"
    echo ""
    echo "  5) 📦 IMPORT PERSONNALISÉ (choisir les bases)"
    echo ""
    echo "  0) ❌ Annuler"
    echo ""
}

# Fonction pour import personnalisé
custom_import() {
    local databases=()
    
    echo ""
    echo -e "${YELLOW}Sélectionnez les bases à importer :${NC}"
    
    read -p "Sénateurs (profils) ? (o/N) " -n 1 -r
    echo
    [[ $REPLY =~ ^[Oo]$ ]] && databases+=("senateurs|Sénateurs")
    
    read -p "DOSLEG (dossiers législatifs) ? (o/N) " -n 1 -r
    echo
    [[ $REPLY =~ ^[Oo]$ ]] && databases+=("dosleg|DOSLEG")
    
    read -p "AMELI (amendements) ? (o/N) " -n 1 -r
    echo
    [[ $REPLY =~ ^[Oo]$ ]] && databases+=("ameli|AMELI")
    
    read -p "Questions (au gouvernement) ? (o/N) " -n 1 -r
    echo
    [[ $REPLY =~ ^[Oo]$ ]] && databases+=("questions|Questions")
    
    read -p "Débats (comptes rendus) ? (o/N) " -n 1 -r
    echo
    [[ $REPLY =~ ^[Oo]$ ]] && databases+=("debats|Débats")
    
    if [ ${#databases[@]} -eq 0 ]; then
        warn "Aucune base sélectionnée"
        return 1
    fi
    
    read -p "Mode --fresh (supprimer les tables existantes) ? (o/N) " -n 1 -r
    echo
    local fresh="false"
    [[ $REPLY =~ ^[Oo]$ ]] && fresh="true"
    
    for db_info in "${databases[@]}"; do
        IFS='|' read -r db_name db_desc <<< "$db_info"
        import_database "$db_name" "$db_desc" "$fresh"
    done
}

# Script principal
main() {
    print_header
    check_prerequisites
    
    # Parser les arguments
    ANALYZE_ONLY=false
    ESSENTIAL_ONLY=false
    IMPORT_ALL=false
    
    for arg in "$@"; do
        case $arg in
            --analyze-only)
                ANALYZE_ONLY=true
                ;;
            --essential-only)
                ESSENTIAL_ONLY=true
                ;;
            --all)
                IMPORT_ALL=true
                ;;
        esac
    done
    
    # Mode non-interactif
    if [ "$ANALYZE_ONLY" = true ]; then
        log "Mode : Analyse uniquement"
        analyze_database "senateurs" "Sénateurs"
        analyze_database "dosleg" "DOSLEG"
        analyze_database "ameli" "AMELI"
        analyze_database "questions" "Questions"
        analyze_database "debats" "Débats"
        success "Toutes les analyses terminées"
        info "Fichiers d'analyse : $LOG_DIR/analysis_*_${TIMESTAMP}.txt"
        exit 0
    fi
    
    if [ "$ESSENTIAL_ONLY" = true ]; then
        log "Mode : Import essentiel (Sénateurs + AMELI + DOSLEG)"
        import_database "senateurs" "Sénateurs" "true"
        import_database "ameli" "AMELI" "true"
        import_database "dosleg" "DOSLEG" "true"
        display_stats
        exit 0
    fi
    
    if [ "$IMPORT_ALL" = true ]; then
        log "Mode : Import intégral (5 bases)"
        import_database "senateurs" "Sénateurs" "true"
        import_database "dosleg" "DOSLEG" "true"
        import_database "ameli" "AMELI" "true"
        import_database "questions" "Questions" "true"
        import_database "debats" "Débats" "true"
        display_stats
        exit 0
    fi
    
    # Mode interactif
    show_menu
    
    read -p "Votre choix (0-5) : " choice
    
    case $choice in
        1)
            echo ""
            log "Analyse de toutes les bases..."
            analyze_database "senateurs" "Sénateurs"
            analyze_database "dosleg" "DOSLEG"
            analyze_database "ameli" "AMELI"
            analyze_database "questions" "Questions"
            analyze_database "debats" "Débats"
            success "Toutes les analyses terminées"
            info "Fichiers d'analyse : $LOG_DIR/analysis_*_${TIMESTAMP}.txt"
            ;;
        2)
            echo ""
            log "Import essentiel (3 bases)..."
            import_database "senateurs" "Sénateurs" "true"
            import_database "ameli" "AMELI" "true"
            import_database "dosleg" "DOSLEG" "true"
            display_stats
            ;;
        3)
            echo ""
            log "Import complet (4 bases)..."
            import_database "senateurs" "Sénateurs" "true"
            import_database "dosleg" "DOSLEG" "true"
            import_database "ameli" "AMELI" "true"
            import_database "questions" "Questions" "true"
            display_stats
            ;;
        4)
            echo ""
            log "Import intégral (5 bases)..."
            import_database "senateurs" "Sénateurs" "true"
            import_database "dosleg" "DOSLEG" "true"
            import_database "ameli" "AMELI" "true"
            import_database "questions" "Questions" "true"
            import_database "debats" "Débats" "true"
            display_stats
            ;;
        5)
            custom_import
            display_stats
            ;;
        0)
            warn "Annulé par l'utilisateur"
            exit 0
            ;;
        *)
            error "Choix invalide"
            exit 1
            ;;
    esac
    
    echo ""
    success "Import terminé avec succès ! 🎉"
    info "Log complet : $LOG_FILE"
    echo ""
}

# Exécuter le script
main "$@"

