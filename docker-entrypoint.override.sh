#!/bin/sh
pecl install -o xdebug-2.4.1
docker-php-ext-enable xdebug
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar install -o --prefer-source
./genclassmap.sh
cp ./php.override.ini /usr/local/etc/php/conf.d/default.ini
cp ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
cp ./config/autoload/zdt.local.php.dist ./config/autoload/zdt.local.php
php-fpm -F -O
