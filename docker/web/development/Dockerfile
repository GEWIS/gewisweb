FROM php:7.4-fpm as gewisweb_web_development

RUN apt-get update \
    && apt-get install -y \
        cron \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libmemcached-dev \
        libpng-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        nano \
        rsync \
        sshpass \
        unzip \
        zip \
    && apt-get upgrade -y

RUN docker-php-ext-configure \
        gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        calendar \
        exif \
        gd \
        intl \
        opcache \
        pgsql \
        pdo_mysql \
        pdo_pgsql \
        soap \
        zip

RUN pecl install imagick \
    && docker-php-ext-enable imagick \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

WORKDIR /code

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get update \
    && apt-get install -y \
        nodejs

COPY --chown=www-data:www-data ./composer.json ./
RUN php composer.phar install

COPY --chown=www-data:www-data ./package.json ./package-lock.json ./
RUN npm install

COPY --chown=www-data:www-data ./public/scss ./public/scss/
RUN npm run scss

COPY --chown=www-data:www-data ./docker/web/development/php.ini /usr/local/etc/php/conf.d/default.ini
COPY --chown=www-data:www-data ./docker/web/development/php-fpm.conf /usr/local/etc/php-fpm.d/php-fpm.conf
COPY --chown=www-data:www-data ./config/autoload/local.development.php.dist ./config/autoload/local.php
COPY --chown=www-data:www-data ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
COPY --chown=www-data:www-data ./config/autoload/gewisdb.local.php.dist ./config/autoload/gewisdb.local.php
COPY --chown=www-data:www-data ./config/autoload/laminas-developer-tools.local.php.dist ./config/autoload/laminas-developer-tools.local.php

COPY --chown=www-data:www-data ./docker/web/development/crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && crontab /etc/cron.d/crontab

COPY --chown=www-data:www-data ./docker/web/development/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod 0775 ./docker-entrypoint.sh

COPY --chown=www-data:www-data . /code

ENV PHP_IDE_CONFIG="serverName=gewis.nl"

RUN php composer.phar dump-autoload

VOLUME ["/code/data", "/code/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
