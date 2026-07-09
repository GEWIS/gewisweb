# Executables (local)
DOCKER = docker
# In dev we explicitly pass --env-file twice so compose substitution reads both `.env` (committed defaults) and
# `.env.local` (developer overrides). Without this, compose only reads `.env` and values in `.env.local` never reach the
# container, meaning Caddy and Symfony see different secrets.
DOCKER_COMP      = $(DOCKER) compose --env-file=.env --env-file=.env.local
DOCKER_COMP_PROD = $(DOCKER) compose -f compose.yaml -f compose.production.yaml

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec web

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL   = help
.PHONY          : help seed translations lint lint-fix lint-twig psalm psalm-all phpstan test test-prepare start startprod down logs bash sf cc
LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD 2>/dev/null || echo abcabcabc)
HOST_UID        := $(shell id -u)
HOST_GID        := $(shell id -g)

buildwebdev:
	@$(DOCKER) build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" --build-arg USER_UID="$(HOST_UID)" --build-arg USER_GID="$(HOST_GID)" --target gewisweb_web_development -t abc.docker-registry.gewis.nl/web/gewisweb/web:development .

buildwebtest:
	@$(DOCKER) build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" --target gewisweb_web_test -t abc.docker-registry.gewis.nl/web/gewisweb/web:test .

buildwebprod:
	@$(DOCKER_COMP_PROD) build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)"

buildmatomo:
	@$(DOCKER) build -t abc.docker-registry.gewis.nl/web/gewisweb/matomo:latest -f docker/matomo/Dockerfile docker/matomo

## —— GEWISWEB —————————————————————————————————————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

seed: ## Seed the database with test data (run after `make start`)
	@$(SYMFONY) app:fixtures:load

translations: ## Extract untranslated text to the XLIFF files (also removes entries no longer referenced in source)
	@$(SYMFONY) translation:extract en --format=xlf --sort=asc --no-fill --force --clean
	@$(SYMFONY) translation:extract nl --format=xlf --sort=asc --no-fill --force --clean

igor: ## Run Igor (static linter to validate Symfony project for the persistent memory model of FrankenPHP)
	@$(PHP) ./vendor/bin/igor-php .

lint: ## Linter using PHP_CodeSniffer
	@$(PHP) ./vendor/bin/phpcs -p

lint-fix: ## Lint fix using phpcbf
	@$(PHP) ./vendor/bin/phpcbf -p

lint-twig: ## Validate Twig templates
	@$(SYMFONY) lint:twig templates

psalm: ## Static analysis using Psalm
	@$(PHP) ./vendor/bin/psalm --no-cache --no-diff

psalm-all: ## Static analysis using Psalm (ignores baseline)
	@$(PHP) ./vendor/bin/psalm --no-cache --no-diff --ignore-baseline

phpstan: ## Static analysis using PHPStan
	@$(PHP) ./vendor/bin/phpstan analyse -c phpstan.dist.neon

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test web bin/phpunit $(c)

test-prepare: ## Prepare the isolated test database: (re)build its schema and load the fixtures. Run once, and after schema/fixture changes (the integration tests roll back their own writes, so the seed persists between runs).
	@$(SYMFONY) doctrine:schema:drop --force --full-database --env=test
	@$(SYMFONY) doctrine:schema:create --env=test
	@$(SYMFONY) doctrine:fixtures:load --no-interaction --env=test

## —— Docker ———————————————————————————————————————————————————————————————————
builddev: buildwebdev buildmatomo ## Builds the development Docker images

buildprod: buildwebprod buildmatomo ## Builds the production Docker images

setuplocalenv:
	@if [ ! -f .env.local ]; then \
		cp .env.local.dist .env.local; \
		echo ".env.local created from .env.local.dist; alter it to your needs"; \
	fi

up: setuplocalenv ## Start the development Docker images in detached mode (no logs)
	@# Create var/ as the host user first; otherwise Docker creates the bind-mount source as root and the non-root
	@# container cannot write var/cache.
	@mkdir -p var
	@$(DOCKER_COMP) up --detach

upprod: ## Start the production Docker images in detached mode (no logs)
	@$(DOCKER_COMP_PROD) up --detach

start: builddev up ## Build and start the development Docker containers

startprod: buildprod upprod ## Build and start the production Docker images

stop: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

bash: ## Connect to the FrankenPHP container
	@$(PHP_CONT) bash

## —— Composer —————————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

## —— Symfony ——————————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf
