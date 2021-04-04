#!/bin/sh
cp ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
php-fpm -F -O
