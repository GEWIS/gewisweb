#!/bin/sh
service cron start
./web orm:generate-proxies
memcached -m 256 -p 11211 -U 11211 -u www-data -d
php-fpm -F -O
