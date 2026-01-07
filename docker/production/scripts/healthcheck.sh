#!/bin/bash
# =============================================================================
# CivicDash - Health Check Production
# =============================================================================
# Usage :
#   ./healthcheck.sh              # Check complet
#   ./healthcheck.sh --json       # Sortie JSON (pour monitoring)
#   ./healthcheck.sh --brief      # Résumé rapide
#   ./healthcheck.sh --watch      # Mode surveillance continue
# =============================================================================

set -euo pipefail

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOMAIN="${DOMAIN:-demo.objectif2027.fr}"

# Résultats
declare -A RESULTS
OVERALL_STATUS="healthy"

# =============================================================================
# FONCTIONS DE CHECK
# =============================================================================

check_container() {
    local name=$1
    local container="civicdash_$name"
    
    if docker ps -q -f name="$container" &> /dev/null; then
        local status=$(docker inspect --format='{{.State.Status}}' "$container" 2>/dev/null)
        local health=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "none")
        
        if [ "$status" = "running" ]; then
            if [ "$health" = "healthy" ] || [ "$health" = "none" ]; then
                RESULTS[$name]="healthy"
                return 0
            elif [ "$health" = "starting" ]; then
                RESULTS[$name]="starting"
                return 1
            else
                RESULTS[$name]="unhealthy"
                OVERALL_STATUS="unhealthy"
                return 1
            fi
        fi
    fi
    
    RESULTS[$name]="stopped"
    OVERALL_STATUS="unhealthy"
    return 1
}

check_app_http() {
    local response
    response=$(docker exec civicdash_app curl -sf -o /dev/null -w "%{http_code}" http://localhost/up 2>/dev/null || echo "000")
    
    if [ "$response" = "200" ]; then
        RESULTS[app_http]="healthy"
        return 0
    else
        RESULTS[app_http]="unhealthy ($response)"
        OVERALL_STATUS="unhealthy"
        return 1
    fi
}

check_postgres() {
    if docker exec civicdash_db_primary pg_isready -U civicdash &> /dev/null; then
        # Vérifier aussi les connexions
        local conn_count
        conn_count=$(docker exec civicdash_db_primary psql -U civicdash -t -c \
            "SELECT count(*) FROM pg_stat_activity WHERE state = 'active';" 2>/dev/null | tr -d ' ')
        
        RESULTS[postgres]="healthy (${conn_count:-0} active)"
        return 0
    else
        RESULTS[postgres]="unhealthy"
        OVERALL_STATUS="unhealthy"
        return 1
    fi
}

check_pgbouncer() {
    if docker exec civicdash_pgbouncer pg_isready -h localhost -p 6432 -U civicdash &> /dev/null; then
        # Récupérer les stats du pool
        local pool_info
        pool_info=$(docker exec civicdash_pgbouncer psql -h localhost -p 6432 -U civicdash pgbouncer -t -c \
            "SHOW POOLS;" 2>/dev/null | head -1 | awk '{print "cl:"$3" sv:"$5}' || echo "n/a")
        
        RESULTS[pgbouncer]="healthy ($pool_info)"
        return 0
    else
        RESULTS[pgbouncer]="unhealthy"
        OVERALL_STATUS="degraded"
        return 1
    fi
}

check_redis() {
    if docker exec civicdash_redis redis-cli ping &> /dev/null; then
        # Récupérer l'utilisation mémoire
        local mem_used
        mem_used=$(docker exec civicdash_redis redis-cli info memory 2>/dev/null | grep used_memory_human | cut -d: -f2 | tr -d '\r')
        
        RESULTS[redis]="healthy (${mem_used:-n/a})"
        return 0
    else
        RESULTS[redis]="unhealthy"
        OVERALL_STATUS="unhealthy"
        return 1
    fi
}

check_meilisearch() {
    local response
    response=$(docker exec civicdash_search curl -sf "http://localhost:7700/health" 2>/dev/null || echo '{"status":"error"}')
    
    if echo "$response" | grep -q '"status":"available"'; then
        # Nombre de documents indexés
        local doc_count
        doc_count=$(docker exec civicdash_search curl -sf "http://localhost:7700/stats" \
            -H "Authorization: Bearer ${MEILISEARCH_KEY:-}" 2>/dev/null | \
            grep -o '"numberOfDocuments":[0-9]*' | head -1 | cut -d: -f2 || echo "n/a")
        
        RESULTS[meilisearch]="healthy (${doc_count:-0} docs)"
        return 0
    else
        RESULTS[meilisearch]="unhealthy"
        OVERALL_STATUS="degraded"
        return 1
    fi
}

check_horizon() {
    local status
    status=$(docker exec civicdash_horizon php artisan horizon:status 2>/dev/null || echo "stopped")
    
    if echo "$status" | grep -q "running"; then
        # Jobs en attente
        local pending
        pending=$(docker exec civicdash_redis redis-cli LLEN laravel_database_queues:default 2>/dev/null || echo "0")
        
        RESULTS[horizon]="healthy (${pending:-0} pending)"
        return 0
    else
        RESULTS[horizon]="unhealthy"
        OVERALL_STATUS="degraded"
        return 1
    fi
}

check_traefik() {
    if docker exec civicdash_traefik traefik healthcheck &> /dev/null; then
        RESULTS[traefik]="healthy"
        return 0
    else
        RESULTS[traefik]="unhealthy"
        OVERALL_STATUS="unhealthy"
        return 1
    fi
}

check_disk_space() {
    local usage
    usage=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
    
    if [ "$usage" -lt 80 ]; then
        RESULTS[disk]="healthy (${usage}%)"
        return 0
    elif [ "$usage" -lt 90 ]; then
        RESULTS[disk]="warning (${usage}%)"
        OVERALL_STATUS="degraded"
        return 1
    else
        RESULTS[disk]="critical (${usage}%)"
        OVERALL_STATUS="unhealthy"
        return 1
    fi
}

check_memory() {
    local usage
    usage=$(free | awk '/Mem:/ {printf "%.0f", $3/$2 * 100}')
    
    if [ "$usage" -lt 80 ]; then
        RESULTS[memory]="healthy (${usage}%)"
        return 0
    elif [ "$usage" -lt 95 ]; then
        RESULTS[memory]="warning (${usage}%)"
        OVERALL_STATUS="degraded"
        return 1
    else
        RESULTS[memory]="critical (${usage}%)"
        OVERALL_STATUS="unhealthy"
        return 1
    fi
}

check_ssl() {
    local expiry
    expiry=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | \
        openssl x509 -noout -dates 2>/dev/null | grep notAfter | cut -d= -f2)
    
    if [ -n "$expiry" ]; then
        local expiry_epoch=$(date -d "$expiry" +%s 2>/dev/null || echo 0)
        local now_epoch=$(date +%s)
        local days_left=$(( (expiry_epoch - now_epoch) / 86400 ))
        
        if [ "$days_left" -gt 30 ]; then
            RESULTS[ssl]="healthy (${days_left}d left)"
            return 0
        elif [ "$days_left" -gt 7 ]; then
            RESULTS[ssl]="warning (${days_left}d left)"
            OVERALL_STATUS="degraded"
            return 1
        else
            RESULTS[ssl]="critical (${days_left}d left)"
            OVERALL_STATUS="unhealthy"
            return 1
        fi
    else
        RESULTS[ssl]="unknown"
        return 1
    fi
}

# =============================================================================
# AFFICHAGE
# =============================================================================

print_status() {
    local name=$1
    local status=${RESULTS[$name]:-unknown}
    
    local color=$GREEN
    local icon="✓"
    
    if [[ "$status" == *"unhealthy"* ]] || [[ "$status" == *"critical"* ]] || [[ "$status" == *"stopped"* ]]; then
        color=$RED
        icon="✗"
    elif [[ "$status" == *"warning"* ]] || [[ "$status" == *"starting"* ]] || [[ "$status" == *"degraded"* ]]; then
        color=$YELLOW
        icon="⚠"
    fi
    
    printf "  ${color}[${icon}]${NC} %-15s : %s\n" "$name" "$status"
}

print_report() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║              CivicDash - Health Check Report                ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Date :${NC} $(date '+%Y-%m-%d %H:%M:%S')"
    echo ""
    
    echo -e "${BLUE}Services Docker :${NC}"
    print_status "app"
    print_status "horizon"
    print_status "scheduler"
    print_status "traefik"
    echo ""
    
    echo -e "${BLUE}Base de données :${NC}"
    print_status "postgres"
    print_status "pgbouncer"
    print_status "redis"
    print_status "meilisearch"
    echo ""
    
    echo -e "${BLUE}Application :${NC}"
    print_status "app_http"
    print_status "ssl"
    echo ""
    
    echo -e "${BLUE}Système :${NC}"
    print_status "disk"
    print_status "memory"
    echo ""
    
    # Status global
    local global_color=$GREEN
    local global_icon="✓"
    if [ "$OVERALL_STATUS" = "unhealthy" ]; then
        global_color=$RED
        global_icon="✗"
    elif [ "$OVERALL_STATUS" = "degraded" ]; then
        global_color=$YELLOW
        global_icon="⚠"
    fi
    
    echo -e "${BLUE}Status Global :${NC} ${global_color}${global_icon} ${OVERALL_STATUS}${NC}"
    echo ""
}

print_json() {
    echo "{"
    echo "  \"timestamp\": \"$(date -Iseconds)\","
    echo "  \"overall_status\": \"$OVERALL_STATUS\","
    echo "  \"services\": {"
    
    local first=true
    for key in "${!RESULTS[@]}"; do
        if [ "$first" = true ]; then
            first=false
        else
            echo ","
        fi
        echo -n "    \"$key\": \"${RESULTS[$key]}\""
    done
    
    echo ""
    echo "  }"
    echo "}"
}

print_brief() {
    local status_char="✓"
    local exit_code=0
    
    if [ "$OVERALL_STATUS" = "unhealthy" ]; then
        status_char="✗"
        exit_code=2
    elif [ "$OVERALL_STATUS" = "degraded" ]; then
        status_char="⚠"
        exit_code=1
    fi
    
    echo "$status_char CivicDash: $OVERALL_STATUS"
    exit $exit_code
}

# =============================================================================
# MAIN
# =============================================================================

run_all_checks() {
    # Containers
    check_container "app" || true
    check_container "horizon" || true
    check_container "scheduler" || true
    check_container "traefik" || true
    
    # Services
    check_postgres || true
    check_pgbouncer || true
    check_redis || true
    check_meilisearch || true
    
    # Application
    check_app_http || true
    check_horizon || true
    check_ssl || true
    
    # Système
    check_disk_space || true
    check_memory || true
}

main() {
    case "${1:-}" in
        --json)
            run_all_checks
            print_json
            ;;
        --brief)
            run_all_checks
            print_brief
            ;;
        --watch)
            while true; do
                clear
                run_all_checks
                print_report
                echo "Actualisation dans 30 secondes... (Ctrl+C pour quitter)"
                sleep 30
            done
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --json    Sortie JSON (pour monitoring)"
            echo "  --brief   Résumé sur une ligne"
            echo "  --watch   Mode surveillance continue"
            echo "  --help    Affiche cette aide"
            echo ""
            ;;
        *)
            run_all_checks
            print_report
            ;;
    esac
}

main "$@"
