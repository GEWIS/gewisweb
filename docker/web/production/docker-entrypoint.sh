#!/bin/sh
printenv | sed 's/^\(.*\)$/export \1/g' | grep -E "^export (APP|DOCKER|SERVER|SMTP|SSH)_" > ./config/bash.env
crond -b
./orm orm:generate-proxies
php-fpm -F -O
