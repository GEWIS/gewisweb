.PHONY: help runprod rundev updatecomposer updatepackage build buildprod builddev login push pushprod pushdev update all prod dev

help:
		@echo "Makefile commands:"
		@echo "runprod"
		@echo "rundev"
		@echo "updatecomposer"
		@echo "updatepackage"
		@echo "build"
		@echo "buildprod"
		@echo "builddev"
		@echo "login"
		@echo "push"
		@echo "pushprod"
		@echo "pushdev"
		@echo "update = updatecomposer updatepackage"
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

update: rundev updatecomposer updatepackage updatecss
		@docker-compose down

updatecomposer:
		@docker-compose exec web php composer.phar selfupdate
		@docker-compose exec -T web cat composer.phar > composer.phar
		@docker-compose exec web php composer.phar update
		@docker-compose exec -T web cat composer.lock > composer.lock

updatepackage:
		@docker-compose exec web npm update
		@docker-compose exec -T web cat package-lock.json > package-lock.json

updatecss:
		@docker-compose exec web npm run scss
		@docker-compose exec -T web cat public/css/gewis-theme.css > public/css/gewis-theme.css

all: build login push

prod: buildprod login pushprod

dev: builddev login pushdev

build: buildweb buildnginx

buildprod: buildwebprod buildnginx

builddev: buildwebdev buildnginx

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_web:production -f docker/web/production/Dockerfile .

buildwebdev:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_web:development -f docker/web/development/Dockerfile .

buildnginx:
		@docker build -t web.docker-registry.gewis.nl/gewisweb_nginx:latest -f docker/nginx/Dockerfile docker/nginx

login:
		@docker login web.docker-registry.gewis.nl

push: pushweb pushnginx

pushprod: pushwebprod pushnginx

pushdev: pushwebdev pushnginx

pushweb: pushwebprod pushwebdev

pushwebprod:
		@docker push web.docker-registry.gewis.nl/gewisweb_web:production

pushwebdev:
		@docker push web.docker-registry.gewis.nl/gewisweb_web:development

pushnginx:
		@docker push web.docker-registry.gewis.nl/gewisweb_nginx:latest
