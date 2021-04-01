.PHONY: help build push all

help:
	    @echo "Makefile commands:"
	    @echo "build"
	    @echo "login"
	    @echo "push"
	    @echo "all = build push"

.DEFAULT_GOAL := all

build:
	    @docker build -t web.docker-registry.gewis.nl/gewisweb_web .
	    @docker build -t web.docker-registry.gewis.nl/gewisweb_nginx docker/nginx
	    
login:	
	    @docker login web.docker-registry.gewis.nl

push:
	    @docker push web.docker-registry.gewis.nl/gewisweb_web:latest
	    @docker push web.docker-registry.gewis.nl/gewisweb_nginx:latest

all: build push
