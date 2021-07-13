#!/bin/sh
service cron start
./vendor/doctrine/doctrine-module/bin/doctrine-module orm:generate-proxies
php-fpm -F -O
