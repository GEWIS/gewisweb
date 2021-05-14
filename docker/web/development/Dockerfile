FROM php:5.6-fpm as gewisweb_web_development

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
        unzip \
        zlib1g-dev \
    && apt-get upgrade -y

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install \
        calendar \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pgsql \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        soap \
        zip

RUN pecl install imagick \
    && docker-php-ext-enable imagick \
    && pecl install memcached-2.2.0 \
    && docker-php-ext-enable memcached \
    && pecl install xdebug-2.4.1 \
    && docker-php-ext-enable xdebug

WORKDIR /code

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

COPY ./composer.json ./composer.lock ./
RUN php composer.phar install

RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
RUN apt-get install -y npm

COPY ./package.json ./package-lock.json ./
RUN npm install

COPY ./public/scss ./public/scss/
RUN npm run scss

COPY ./docker/web/development/php.ini /usr/local/etc/php/conf.d/default.ini
COPY ./docker/web/development/php-fpm.conf /usr/local/etc/php-fpm.d/php-fpm.conf
COPY ./config/autoload/local.development.php.dist ./config/autoload/local.php
COPY ./config/autoload/doctrine.local.development.php.dist ./config/autoload/doctrine.local.php
COPY ./config/autoload/gewisdb.local.php.dist ./config/autoload/gewisdb.local.php
COPY ./config/autoload/zdt.local.php.dist ./config/autoload/zdt.local.php

COPY ./docker/web/development/crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && crontab /etc/cron.d/crontab

COPY ./docker/web/development/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod 0777 ./docker-entrypoint.sh

COPY . /code

RUN chown -R www-data:www-data /code

VOLUME ["/code/data", "/code/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
