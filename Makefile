.PHONY: help runprod rundev updatecomposer updatepackage build buildprod builddev login push pushprod pushdev update all prod dev

help:
		@echo "Makefile commands:"
		@echo "runprod"
		@echo "rundev"
		@echo "updatecomposer"
		@echo "updatepackage"
		@echo "updatecss"
		@echo "updateglide"
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

getvendordir: rundev
		@rm -Rf ./vendor
		@docker cp gewisweb_web_1:/code/vendor ./vendor
		@docker-compose down

replenish: rundev
		@docker cp ./public gewisweb_web_1:/code
		@docker-compose exec web chown -R www-data:www-data /code/public
		@docker cp ./data gewisweb_web_1:/code
		@docker-compose exec web chown -R www-data:www-data /code/data
		@docker-compose exec web php composer.phar dump-autoload --dev
		@docker-compose exec web ./vendor/doctrine/doctrine-module/bin/doctrine-module orm:generate-proxies
		@docker-compose down

update: rundev updatecomposer updatepackage updatecss updateglide
		@docker-compose down

phpstan: rundev
		@docker-compose exec web /code/vendor/bin/phpstan analyse /code/module
		@docker-compose down

phpcs: rundev
		@docker-compose exec web /code/vendor/bin/phpcs -p --standard=PSR1,PSR12 --extensions=php,dist /code/module /code/config
		@docker-compose down

phpcbf: rundev
		@docker-compose exec web /code/vendor/bin/phpcbf -p --standard=PSR1,PSR12 --extensions=php,dist /code/module /code/config
		@docker cp gewisweb_web_1:/code/module ./module
		@docker cp gewisweb_web_1:/code/config ./config
		@docker-compose down

phpcsfix: rundev
		@docker-compose exec web /code/vendor/bin/php-cs-fixer fix --rules=@PSR1,-@PSR12,-@Symfony /code/module
		@docker-compose exec web /code/vendor/bin/php-cs-fixer fix --rules=@PSR1,-@PSR12,-@Symfony /code/config
		@docker cp gewisweb_web_1:/code/module ./module
		@docker cp gewisweb_web_1:/code/config ./config
		@docker-compose down

phpcsfixtypes: rundev
		@docker-compose exec web /code/vendor/bin/php-cs-fixer fix --allow-risky=yes --rules=@PSR1,-@PSR12,-@Symfony,-phpdoc_to_param_type,-phpdoc_to_property_type,-phpdoc_to_return_type /code/module
		@docker-compose exec web /code/vendor/bin/php-cs-fixer fix --allow-risky=yes --rules=@PSR1,-@PSR12,-@Symfony,-phpdoc_to_param_type,-phpdoc_to_property_type,-phpdoc_to_return_type /code/config
		@docker cp gewisweb_web_1:/code/module ./module
		@docker cp gewisweb_web_1:/code/config ./config
		@docker-compose down

updatecomposer:
		@docker-compose exec web php composer.phar selfupdate
		@docker cp gewisweb_web_1:/code/composer.phar ./composer.phar
		@docker-compose exec web php composer.phar update -W
		@docker cp gewisweb_web_1:/code/composer.lock ./composer.lock

updatepackage:
		@docker-compose exec web npm update
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

all: build login push

prod: buildprod login pushprod

dev: builddev login pushdev

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
