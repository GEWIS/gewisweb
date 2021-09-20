.PHONY: help runprod rundev update updatecomposer updatepackage updateglide getvendordir phpstan phpcs phpcbf phpcsfix phpcsfixtypes build buildprod builddev login push pushprod pushdev update all prod dev

help:
		@echo "Makefile commands:"
		@echo "runprod"
		@echo "rundev"
		@echo "updatecomposer"
		@echo "updatepackage"
		@echo "updatecss"
		@echo "updateglide"
		@echo "updatedocker"
		@echo "getvendordir"
		@echo "phpstan"
		@echo "phpcs"
		@echo "phpcbf"
		@echo "phpcsfix"
		@echo "phpcsfixtypes"
		@echo "replenish"
		@echo "build"
		@echo "buildprod"
		@echo "builddev"
		@echo "login"
		@echo "push"
		@echo "pushprod"
		@echo "pushdev"
		@echo "update = updatecomposer updatepackage updatecss updateglide"
		@echo "all = build login push"
		@echo "prod = buildprod login pushprod"
		@echo "dev = builddev login pushdev"

.DEFAULT_GOAL := all

runprod:
		@docker-compose -f docker-compose.yml up -d --force-recreate --remove-orphans

runprodtest: buildprod
		@docker-compose -f docker-compose.yml up -d --force-recreate --remove-orphans

rundev: builddev
		@docker-compose up -d --force-recreate --remove-orphans

stop:
		@docker-compose down

getvendordir:
		@rm -Rf ./vendor
		@docker cp gewisweb_web_1:/code/vendor ./vendor

replenish:
		@docker cp ./public gewisweb_web_1:/code
		@docker-compose exec web chown -R www-data:www-data /code/public
		@docker cp ./data gewisweb_web_1:/code
		@docker-compose exec web chown -R www-data:www-data /code/data
		@docker-compose exec web php composer.phar dump-autoload --dev
		@docker-compose exec web ./orm orm:generate-proxies

update: updatecomposer updatepackage updatecss updateglide updatedocker

loadenv:
		@export $$(grep -v '^#' .env | xargs -d '\n')

copyconf:
		cp config/autoload/local.development.php.dist config/autoload/local.php
		cp config/autoload/doctrine.local.development.php.dist config/autoload/doctrine.local.php
		cp config/autoload/laminas-developer-tools.local.php.dist config/autoload/laminas-developer-tools.local.php

phpstan:
		@docker-compose exec web echo "" > phpstan/phpstan-baseline-pr.neon
		@docker-compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G

phpstanpr:
		@git checkout --detach master
		@cp phpstan/phpstan-baseline.neon phpstan/phpstan-baseline-temp.neon
		@echo "" > phpstan/phpstan-baseline.neon
		@echo "" > phpstan/phpstan-baseline-pr.neon
		@make rundev
		@docker-compose exec web vendor/bin/phpstan analyse -c phpstan.neon --generate-baseline phpstan/phpstan-baseline-pr.neon --memory-limit 1G
		@git checkout -
		@cp phpstan/phpstan-baseline-temp.neon phpstan/phpstan-baseline.neon
		@rm phpstan/phpstan-baseline-temp.neon
		@docker cp gewisweb_web_1:/code/phpstan/phpstan-baseline-pr.neon ./phpstan/phpstan-baseline-pr.neon
		@make rundev
		@docker-compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G

phpcs:
		@vendor/bin/phpcs -p --standard=PSR1,PSR12 --extensions=php,dist module config

phpcbf:
		@vendor/bin/phpcbf -p --standard=PSR1,PSR12 --extensions=php,dist --filter=GitModified module config

phpcbfall:
		@vendor/bin/phpcbf -p --standard=PSR1,PSR12 --extensions=php,dist module config

phpcsfix:
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --rules=@PSR1,@PSR12,@DoctrineAnnotation,@PHP80Migration module
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --rules=@PSR1,@PSR12,@DoctrineAnnotation,@PHP80Migration config

phpcsfixtypes:
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --allow-risky=yes --rules=@PSR1,@PSR12,@DoctrineAnnotation,@PHP80Migration:risky module
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --allow-risky=yes --rules=@PSR1,@PSR12,@DoctrineAnnotation,@PHP80Migration:risky config

updatecomposer:
		@docker-compose exec web php composer.phar selfupdate
		@docker cp gewisweb_web_1:/code/composer.phar ./composer.phar
		@docker-compose exec web php composer.phar update -W
		@docker cp gewisweb_web_1:/code/composer.lock ./composer.lock

updatepackage:
		@docker-compose exec web npm update
		@docker-compose exec web npm audit fix
		@docker cp gewisweb_web_1:/code/package-lock.json ./package-lock.json

updatecss:
		@docker-compose exec web npm run scss
		@docker cp gewisweb_web_1:/code/public/css/gewis-theme.css ./public/css/gewis-theme.css

updateglide:
		@docker-compose exec glide php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
		@docker-compose exec glide php composer-setup.php
		@docker-compose exec glide php -r "unlink('composer-setup.php');"
		@docker-compose exec glide php composer.phar selfupdate
		@docker cp gewisweb_glide_1:/glide/composer.phar ./docker/glide/composer.phar
		@docker-compose exec glide php composer.phar update -W
		@docker cp gewisweb_glide_1:/glide/composer.lock ./docker/glide/composer.lock

updatedocker:
		@docker-compose pull
		@docker build --pull --no-cache -t web.docker-registry.gewis.nl/gewisweb_web:production -f docker/web/production/Dockerfile .
		@docker build --pull --no-cache -t web.docker-registry.gewis.nl/gewisweb_web:development -f docker/web/development/Dockerfile .
		@docker build --pull --no-cache -t web.docker-registry.gewis.nl/gewisweb_glide:latest -f docker/glide/Dockerfile docker/glide
		@docker build --pull --no-cache -t web.docker-registry.gewis.nl/gewisweb_matomo:latest -f docker/matomo/Dockerfile docker/matomo
		@docker build --pull --no-cache -t web.docker-registry.gewis.nl/gewisweb_nginx:latest -f docker/nginx/Dockerfile docker/nginx

all: build login push

prod: buildprod login pushprod

dev: builddev login pushdev

webprod: buildwebprod login pushwebprod

webdev: buildwebdev login pushwebdev

build: buildweb buildglide buildmatomo buildnginx

buildprod: buildwebprod buildglide buildmatomo buildnginx

builddev: buildwebdev buildglide buildmatomo buildnginx

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_web:production -f docker/web/production/Dockerfile .

buildwebdev:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_web:development -f docker/web/development/Dockerfile .

buildglide:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_glide:latest -f docker/glide/Dockerfile docker/glide

buildmatomo:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_matomo:latest -f docker/matomo/Dockerfile docker/matomo

buildnginx:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_nginx:latest -f docker/nginx/Dockerfile docker/nginx

login:
		@docker login web.docker-registry.gewis.nl

push: pushweb pushglide pushmatomo pushnginx

pushprod: pushwebprod pushglide pushmatomo pushnginx

pushdev: pushwebdev pushglide pushmatomo pushnginx

pushweb: pushwebprod pushwebdev

pushwebprod:
		@docker push web.docker-registry.gewis.nl/gewisweb_web:production

pushwebdev:
		@docker push web.docker-registry.gewis.nl/gewisweb_web:development

pushglide:
		@docker push web.docker-registry.gewis.nl/gewisweb_glide:latest

pushmatomo:
		@docker push web.docker-registry.gewis.nl/gewisweb_matomo:latest

pushnginx:
		@docker push web.docker-registry.gewis.nl/gewisweb_nginx:latest
