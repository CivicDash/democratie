#!/bin/bash
# =============================================================================
# CivicDash Production Deployment Script
# =============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}🚀 CivicDash Production Deployment${NC}"
echo "================================================"

# Check requirements
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker non installé${NC}"
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose non installé${NC}"
    exit 1
fi

# Check .env exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  Fichier .env non trouvé${NC}"
    echo "Création depuis .env.example..."
    
    if [ -f "../.env.production" ]; then
        cp ../.env.production .env
        echo -e "${GREEN}✓ .env créé depuis .env.production${NC}"
    else
        echo -e "${RED}❌ Veuillez créer le fichier .env${NC}"
        echo "   cp .env.example .env && nano .env"
        exit 1
    fi
fi

# Parse arguments
ACTION=${1:-deploy}

case $ACTION in
    deploy)
        echo -e "\n${GREEN}📦 Pulling latest images...${NC}"
        docker compose pull
        
        echo -e "\n${GREEN}🚀 Starting services...${NC}"
        docker compose up -d
        
        echo -e "\n${GREEN}⏳ Waiting for services to be healthy...${NC}"
        sleep 10
        
        echo -e "\n${GREEN}🔄 Running migrations...${NC}"
        docker compose exec -T app php artisan migrate --force
        
        echo -e "\n${GREEN}🗑️  Clearing caches...${NC}"
        docker compose exec -T app php artisan config:cache
        docker compose exec -T app php artisan route:cache
        docker compose exec -T app php artisan view:cache
        
        echo -e "\n${GREEN}🔄 Reloading Octane workers...${NC}"
        docker compose exec -T app php artisan octane:reload || true
        
        echo -e "\n${GREEN}✅ Deployment complete!${NC}"
        ;;
        
    stop)
        echo -e "\n${YELLOW}⏹️  Stopping all services...${NC}"
        docker compose down
        echo -e "${GREEN}✓ Services stopped${NC}"
        ;;
        
    restart)
        echo -e "\n${YELLOW}🔄 Restarting all services...${NC}"
        docker compose restart
        echo -e "${GREEN}✓ Services restarted${NC}"
        ;;
        
    logs)
        docker compose logs -f ${2:-app}
        ;;
        
    status)
        echo -e "\n${GREEN}📊 Service Status${NC}"
        docker compose ps
        
        echo -e "\n${GREEN}📈 Resource Usage${NC}"
        docker stats --no-stream $(docker compose ps -q)
        ;;
        
    backup)
        echo -e "\n${GREEN}💾 Creating backup...${NC}"
        BACKUP_DIR="/opt/backups/civicdash/$(date +%Y%m%d_%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        
        # Database
        docker compose exec -T postgres pg_dump -U civicdash civicdash | gzip > "$BACKUP_DIR/database.sql.gz"
        
        # Uploads
        tar -czf "$BACKUP_DIR/storage.tar.gz" -C ../../ storage/app/public
        
        echo -e "${GREEN}✓ Backup saved to $BACKUP_DIR${NC}"
        ;;
        
    safeline-password)
        echo -e "\n${GREEN}🔑 SafeLine Admin Password${NC}"
        docker logs safeline-mgt 2>&1 | grep -i password || echo "Mot de passe déjà changé"
        ;;
        
    *)
        echo "Usage: $0 {deploy|stop|restart|logs|status|backup|safeline-password}"
        exit 1
        ;;
esac
