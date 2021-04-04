#!/bin/sh
service cron startx
./web orm:generate-proxies
php-fpm -F -O
