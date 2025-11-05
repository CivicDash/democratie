.PHONY: help up down build install migrate seed fresh test lint pint phpstan logs shell db-shell

help: ## Affiche l'aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Démarre les conteneurs Docker
	docker-compose up -d

down: ## Arrête les conteneurs Docker
	docker-compose down

build: ## Construit les images Docker
	docker-compose build

rebuild: ## Reconstruit les images Docker sans cache
	docker-compose build --no-cache

install: ## Installe les dépendances PHP et Node
	docker-compose exec app composer install
	docker-compose exec app npm install

migrate: ## Lance les migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Recrée la base avec les seeds
	docker-compose exec app php artisan migrate:fresh --seed

seed: ## Lance les seeders
	docker-compose exec app php artisan db:seed

fresh: ## Réinitialise complètement (migrations + seeds)
	docker-compose exec app php artisan migrate:fresh --seed

test: ## Lance les tests Pest
	docker-compose exec app php artisan test

test-coverage: ## Lance les tests avec coverage
	docker-compose exec app ./vendor/bin/pest --coverage --min=70

lint: pint phpstan ## Lance tous les linters

pint: ## Lance Laravel Pint (formatter)
	docker-compose exec app ./vendor/bin/pint

pint-test: ## Test Pint sans modifier
	docker-compose exec app ./vendor/bin/pint --test

phpstan: ## Lance PHPStan (analyse statique)
	docker-compose exec app ./vendor/bin/phpstan analyse app

logs: ## Affiche les logs
	docker-compose logs -f app

logs-horizon: ## Affiche les logs Horizon
	docker-compose logs -f horizon

shell: ## Ouvre un shell dans le conteneur app
	docker-compose exec app sh

db-shell: ## Ouvre psql dans la base
	docker-compose exec db psql -U civicdash civicdash

redis-cli: ## Ouvre redis-cli
	docker-compose exec redis redis-cli

npm-dev: ## Lance Vite en mode dev
	docker-compose exec app npm run dev

npm-build: ## Build les assets frontend
	docker-compose exec app npm run build

key-generate: ## Génère la clé APP_KEY
	docker-compose exec app php artisan key:generate

optimize: ## Optimise l'application
	docker-compose exec app php artisan optimize

cache-clear: ## Vide tous les caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

pepper: ## Génère un PEPPER pour .env
	@echo "Copiez cette valeur dans votre .env à PEPPER="
	docker-compose exec app php artisan tinker --execute="echo base64_encode(random_bytes(32));"

demo: ## Configure CivicDash en mode démonstration
	docker-compose exec app php artisan demo:setup --fresh --force
	@echo ""
	@echo "🎬 Mode démo activé !"
	@echo "🔐 Comptes de test :"
	@echo "   - admin@civicdash.fr / password"
	@echo "   - citoyen1@demo.civicdash.fr / demo2025"
	@echo "   - depute1@demo.assemblee-nationale.fr / demo2025"
	@echo ""
	@echo "📚 Documentation : docs/DEMO_MODE.md"

demo-data: ## Génère uniquement les données de démo (sans reset)
	docker-compose exec app php artisan db:seed --class=DemoDataSeeder

setup: build up install key-generate migrate ## Setup complet du projet
	@echo "✅ Projet CivicDash installé !"
	@echo "📝 N'oubliez pas de configurer PEPPER dans .env avec: make pepper"
	@echo "🌐 Application : http://localhost:8000"
	@echo "🔭 Telescope : http://localhost:8000/telescope"
	@echo "⚡ Horizon : http://localhost:8000/horizon"
	@echo ""
	@echo "🎬 Pour activer le mode démo : make demo"

