#!/bin/sh
cp ./php.override.ini /usr/local/etc/php/conf.d/default.ini
cp ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
php-fpm -F -O
