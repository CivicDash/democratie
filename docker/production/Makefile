# =============================================================================
# CivicDash - Makefile Production
# =============================================================================
# Usage :
#   make help          # Affiche l'aide
#   make deploy        # Déploiement complet
#   make up            # Démarrer les services
#   make down          # Arrêter les services
#   make logs          # Voir les logs
# =============================================================================

.PHONY: help up down restart logs deploy deploy-quick backup restore healthcheck shell migrate seed optimize clean

# Variables
COMPOSE_FILE := docker-compose.production.yml
COMPOSE := docker compose -f $(COMPOSE_FILE)
SCRIPTS := ./scripts

# Couleurs
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m

# =============================================================================
# AIDE
# =============================================================================

help: ## Affiche cette aide
	@echo ""
	@echo "$(BLUE)╔════════════════════════════════════════════════════════════╗$(NC)"
	@echo "$(BLUE)║           CivicDash - Commandes Production                  ║$(NC)"
	@echo "$(BLUE)╚════════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo "$(GREEN)Services :$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

# =============================================================================
# SERVICES
# =============================================================================

up: ## Démarrer tous les services
	@echo "$(BLUE)Démarrage des services...$(NC)"
	$(COMPOSE) up -d
	@echo "$(GREEN)✓ Services démarrés$(NC)"

down: ## Arrêter tous les services
	@echo "$(BLUE)Arrêt des services...$(NC)"
	$(COMPOSE) down
	@echo "$(GREEN)✓ Services arrêtés$(NC)"

restart: ## Redémarrer tous les services
	@echo "$(BLUE)Redémarrage des services...$(NC)"
	$(COMPOSE) restart
	@echo "$(GREEN)✓ Services redémarrés$(NC)"

restart-app: ## Redémarrer uniquement l'application
	@echo "$(BLUE)Redémarrage de l'application...$(NC)"
	$(COMPOSE) restart app horizon scheduler
	@echo "$(GREEN)✓ Application redémarrée$(NC)"

logs: ## Voir les logs (tous les services)
	$(COMPOSE) logs -f --tail=100

logs-app: ## Voir les logs de l'application
	$(COMPOSE) logs -f --tail=100 app

logs-db: ## Voir les logs PostgreSQL
	$(COMPOSE) logs -f --tail=100 postgres-primary

logs-horizon: ## Voir les logs Horizon
	$(COMPOSE) logs -f --tail=100 horizon

ps: ## État des services
	$(COMPOSE) ps

# =============================================================================
# DÉPLOIEMENT
# =============================================================================

deploy: ## Déploiement complet (avec rebuild)
	@echo "$(BLUE)Déploiement complet...$(NC)"
	$(SCRIPTS)/deploy.sh
	@echo "$(GREEN)✓ Déploiement terminé$(NC)"

deploy-quick: ## Déploiement rapide (sans rebuild)
	@echo "$(BLUE)Déploiement rapide...$(NC)"
	$(SCRIPTS)/deploy.sh --quick
	@echo "$(GREEN)✓ Déploiement rapide terminé$(NC)"

init: ## Première installation
	@echo "$(BLUE)Première installation...$(NC)"
	$(SCRIPTS)/deploy.sh --init
	@echo "$(GREEN)✓ Installation terminée$(NC)"

rollback: ## Rollback à la version précédente
	@echo "$(YELLOW)Rollback en cours...$(NC)"
	$(SCRIPTS)/deploy.sh --rollback
	@echo "$(GREEN)✓ Rollback terminé$(NC)"

build: ## Construire les images
	@echo "$(BLUE)Construction des images...$(NC)"
	$(COMPOSE) build --no-cache app
	@echo "$(GREEN)✓ Images construites$(NC)"

pull: ## Télécharger les images de base
	@echo "$(BLUE)Téléchargement des images...$(NC)"
	$(COMPOSE) pull
	@echo "$(GREEN)✓ Images téléchargées$(NC)"

# =============================================================================
# BASE DE DONNÉES
# =============================================================================

migrate: ## Exécuter les migrations
	@echo "$(BLUE)Migrations...$(NC)"
	docker exec civicdash_app php artisan migrate --force
	@echo "$(GREEN)✓ Migrations exécutées$(NC)"

migrate-status: ## État des migrations
	docker exec civicdash_app php artisan migrate:status

seed: ## Exécuter les seeders
	@echo "$(BLUE)Seeding...$(NC)"
	docker exec civicdash_app php artisan db:seed --force
	@echo "$(GREEN)✓ Seeders exécutés$(NC)"

db-shell: ## Shell PostgreSQL
	docker exec -it civicdash_db_primary psql -U civicdash civicdash

redis-shell: ## Shell Redis
	docker exec -it civicdash_redis redis-cli

pgbouncer-stats: ## Stats PgBouncer
	docker exec civicdash_pgbouncer psql -h localhost -p 6432 -U civicdash pgbouncer -c "SHOW POOLS;"

# =============================================================================
# APPLICATION
# =============================================================================

shell: ## Shell dans le conteneur app
	docker exec -it civicdash_app bash

tinker: ## Laravel Tinker
	docker exec -it civicdash_app php artisan tinker

optimize: ## Optimiser l'application
	@echo "$(BLUE)Optimisation...$(NC)"
	docker exec civicdash_app php artisan optimize:clear
	docker exec civicdash_app php artisan optimize
	docker exec civicdash_app php artisan config:cache
	docker exec civicdash_app php artisan route:cache
	docker exec civicdash_app php artisan view:cache
	docker exec civicdash_app php artisan event:cache
	@echo "$(GREEN)✓ Application optimisée$(NC)"

cache-clear: ## Vider le cache
	@echo "$(BLUE)Vidage du cache...$(NC)"
	docker exec civicdash_app php artisan cache:clear
	docker exec civicdash_app php artisan config:clear
	docker exec civicdash_app php artisan route:clear
	docker exec civicdash_app php artisan view:clear
	@echo "$(GREEN)✓ Cache vidé$(NC)"

horizon-restart: ## Redémarrer Horizon
	docker exec civicdash_horizon php artisan horizon:terminate
	$(COMPOSE) restart horizon

scout-import: ## Réindexer Meilisearch
	@echo "$(BLUE)Réindexation Meilisearch...$(NC)"
	docker exec civicdash_app php artisan scout:import "App\Models\Topic"
	docker exec civicdash_app php artisan scout:import "App\Models\ActeurAN"
	docker exec civicdash_app php artisan scout:import "App\Models\Senateur"
	@echo "$(GREEN)✓ Indexation terminée$(NC)"

# =============================================================================
# BACKUP & RESTORE
# =============================================================================

backup: ## Créer un backup complet
	@echo "$(BLUE)Backup en cours...$(NC)"
	$(SCRIPTS)/backup.sh
	@echo "$(GREEN)✓ Backup terminé$(NC)"

backup-db: ## Backup PostgreSQL uniquement
	$(SCRIPTS)/backup.sh --db-only

backup-status: ## État des backups
	$(SCRIPTS)/backup.sh --status

restore: ## Restaurer un backup
	$(SCRIPTS)/backup.sh --restore

# =============================================================================
# MONITORING
# =============================================================================

healthcheck: ## Vérifier la santé des services
	$(SCRIPTS)/healthcheck.sh

healthcheck-json: ## Healthcheck en JSON
	$(SCRIPTS)/healthcheck.sh --json

healthcheck-watch: ## Surveillance continue
	$(SCRIPTS)/healthcheck.sh --watch

stats: ## Statistiques des conteneurs
	docker stats --no-stream civicdash_app civicdash_db_primary civicdash_redis civicdash_search

monitoring-up: ## Démarrer Prometheus + Grafana
	$(COMPOSE) --profile monitoring up -d prometheus grafana

monitoring-down: ## Arrêter Prometheus + Grafana
	$(COMPOSE) --profile monitoring down

# =============================================================================
# NETTOYAGE
# =============================================================================

clean: ## Nettoyer les ressources Docker inutilisées
	@echo "$(YELLOW)Nettoyage Docker...$(NC)"
	docker system prune -f
	docker volume prune -f
	@echo "$(GREEN)✓ Nettoyage terminé$(NC)"

clean-all: ## Nettoyer TOUT (images, volumes, etc.)
	@echo "$(YELLOW)⚠ Nettoyage complet (toutes les données seront perdues)$(NC)"
	@read -p "Êtes-vous sûr ? (oui/non) " confirm && [ "$$confirm" = "oui" ]
	$(COMPOSE) down -v --rmi all
	docker system prune -af
	@echo "$(GREEN)✓ Nettoyage complet terminé$(NC)"

# =============================================================================
# GIT & VERSIONING
# =============================================================================

list-tags: ## Lister les tags disponibles
	$(SCRIPTS)/deploy-tag.sh --list

deploy-tag: ## Déployer un tag (usage: make deploy-tag TAG=v1.2.0)
	@if [ -z "$(TAG)" ]; then echo "Usage: make deploy-tag TAG=v1.2.0"; exit 1; fi
	$(SCRIPTS)/deploy-tag.sh $(TAG)

deploy-latest: ## Déployer le dernier tag
	$(SCRIPTS)/deploy-tag.sh --latest

version-status: ## Afficher la version déployée
	$(SCRIPTS)/deploy-tag.sh --status

# =============================================================================
# ESPACE DISQUE
# =============================================================================

disk: ## Analyser l'espace disque
	$(SCRIPTS)/disk-cleanup.sh

disk-clean: ## Nettoyage complet de l'espace disque
	$(SCRIPTS)/disk-cleanup.sh --clean

disk-auto: ## Nettoyage automatique (pour cron)
	$(SCRIPTS)/disk-cleanup.sh --auto

# =============================================================================
# DIVERS
# =============================================================================

env-check: ## Vérifier les variables d'environnement
	@echo "$(BLUE)Variables d'environnement :$(NC)"
	@echo "  DOMAIN = $(DOMAIN)"
	@echo "  DB_DATABASE = $(DB_DATABASE)"
	@echo "  DB_HOST = $(DB_HOST)"
	@echo ""

version: ## Afficher les versions
	@echo "$(BLUE)Versions :$(NC)"
	@echo "  Docker: $(shell docker --version)"
	@echo "  Compose: $(shell docker compose version)"
	@echo ""
	@docker exec civicdash_app php artisan --version 2>/dev/null || true
	@docker exec civicdash_db_primary psql --version 2>/dev/null || true
