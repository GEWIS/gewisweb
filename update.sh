#!/usr/bin/env sh

git pull
php composer.phar instal -o
./genclassmap.sh
./web orm:schema-tool:update
./web orm:generate-proxies
