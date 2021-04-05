#!/bin/sh
service cron start
./web orm:generate-proxies
php-fpm -F -O
