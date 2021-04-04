#!/bin/sh
./web orm:generate-proxies
service cron start
php-fpm
