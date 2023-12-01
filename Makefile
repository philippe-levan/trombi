.PHONY: assets tests

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

bash: ## [host] Ouvre un bash dans le conteneur web (en tant que root)
	docker compose exec web bash
bash-node: ## [host] Ouvre un bash dans le conteneur web (en tant que root)
	docker compose exec node sh

permissions-dev: ## [host] Configure les permissions de dev
	sudo setfacl -R  -m u:$(USER):rwX ./
	sudo setfacl -dR -m u:$(USER):rwX ./

_permissions: ## [container] Configure les permissions de l'app
	chmod -R a+rwX var

permissions: ## [host] Configure les permissions de l'app
	docker-compose run --rm -v $$(pwd):/var/www web make _permissions

_database:
	bin/console doctrine:database:create --if-not-exists --no-interaction
	bin/console doctrine:migration:migrate --no-interaction

database:
	docker-compose exec web make _database

dev-reinit-db: ## [host] Réinitialise la base de données
	docker-compose exec web php bin/console doctrine:database:drop --if-exists --force
	docker-compose exec web php bin/console doctrine:database:create
	docker-compose exec web php bin/console doctrine:migrations:migrate --no-interaction

deps: ## [host] Installe les dépendances
	docker-compose run --rm -v $$(pwd):/var/www web make _deps

_deps:
	composer install --no-interaction
	yarn install --dev --force # le "force" est pour SF UX

assets-dev: ## [host] Compile les assets en dev
	docker-compose exec web ./bin/console sass:build --watch

assets-prod: ## [host] Compile les assets en prod
	docker-compose exec web ./bin/console sass:build

tests: ## [host] Lance les tests
	docker-compose run --rm -v $$(pwd):/var/www web make _tests

_tests: ## [container] Lance les tests
	make _database-for-test
	php vendor/bin/phpunit

_database-for-test: ## [container] Initialise une base de test
	bin/console doctrine:database:drop --if-exists --no-interaction --force --env=test
	bin/console doctrine:database:create --if-not-exists --no-interaction --env=test
	bin/console doctrine:migration:migrate --no-interaction --quiet --env=test
	bin/console doctrine:fixtures:load --no-interaction  --append --quiet --env=test

fixtures: ## [container] Charge les fixtures
	docker-compose exec web bin/console doctrine:database:drop --if-exists --force --no-interaction
	docker-compose exec web bin/console doctrine:database:create --if-not-exists --no-interaction
# les migrations ne fonctionnent from scratch docker-compose exec web bin/console doctrine:fixtures:load --no-interaction  --append --quiet
	docker-compose exec web bin/console doctrine:schema:update --force --complete
	docker-compose exec web bin/console doctrine:fixture:load --no-interaction

xdebug: ## [host] lance xdebug en mode debug
	./bin/activate-xdebug.sh --mode debug
