.PHONY: help build push all

help:
	    @echo "Makefile commands:"
	    @echo "build"
	    @echo "push"
	    @echo "all"

.DEFAULT_GOAL := all

build:
	    @docker build -t koen1999/gewisweb_web .
	    @docker build -t koen1999/gewisweb_nginx docker/nginx

push:
	    @docker push koen1999/gewisweb_web
	    @docker push koen1999/gewisweb_nginx

all: build push
