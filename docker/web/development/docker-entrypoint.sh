#!/bin/sh
service cron start
./orm orm:generate-proxies
php-fpm -F -O
