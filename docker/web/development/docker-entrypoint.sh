#!/bin/sh
service cron start
php-fpm -F -O
