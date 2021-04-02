FROM node:14-alpine as node-build
WORKDIR /code

COPY package.json package-lock.json ./
RUN npm install

RUN mkdir public
RUN mkdir public/scss
RUN mkdir public/css

COPY public/scss/ ./public/scss/
RUN npm run scss

FROM php:5.6-fpm as php-target

RUN apt-get update
RUN apt-get upgrade -y

RUN apt-get install -y \
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
        zlib1g-dev

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        calendar \
        exif \
        gd \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        soap \
        zip \
    && pecl install -o imagick \
    && docker-php-ext-enable imagick \
    && pecl install -o memcached-2.2.0 \
    && docker-php-ext-enable memcached

FROM php-target as composer-build
WORKDIR /code

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./composer.json ./composer.lock ./
RUN composer install -o --no-dev

FROM php-target as gewisweb_web
WORKDIR /code

RUN ln -s /code/data /data
RUN ln -s /code/public /public

COPY ./php.ini /usr/local/etc/php/conf.d/default.ini
COPY ./config/autoload/local.php.dist ./config/autoload/local.php
COPY ./config/autoload/doctrine.local.production.php.dist ./config/autoload/doctrine.local.php
COPY ./config/autoload/gewisdb.local.php.dist ./config/autoload/gewisdb.local.php

COPY ./crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab

COPY --from=composer-build /code/vendor /code/vendor
COPY --from=node-build /code/public/css/gewis-theme.css /code/public/css/gewis-theme.css

COPY . /code

RUN ./genclassmap.sh

VOLUME ["/code", "/data", "/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
