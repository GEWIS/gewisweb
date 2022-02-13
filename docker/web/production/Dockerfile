FROM node:14-alpine as node-build
WORKDIR /code

COPY ./package.json ./package-lock.json ./
RUN npm install --production

RUN mkdir public && mkdir public/scss && mkdir public/css

COPY ./public/scss ./public/scss/
RUN npm run scss

FROM php:8.1-fpm as php-target

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
        git \
        ghostscript \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libmemcached-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        poppler-utils \
        rsync \
        ssh \
        sshpass \
        unzip \
    && apt-get upgrade -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure \
        gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        calendar \
        curl \
        exif \
        gd \
        intl \
        opcache \
        pgsql \
        pdo_mysql \
        pdo_pgsql \
        zip

RUN pecl install -o imagick \
    && docker-php-ext-enable imagick \
    && pecl install -o memcached \
    && docker-php-ext-enable memcached

RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/g' /etc/ImageMagick-6/policy.xml

FROM php-target as gewisweb_web_production
WORKDIR /code

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

COPY ./composer.json ./composer.lock ./
RUN php composer.phar install -o --no-dev

COPY --chown=www-data:www-data ./docker/web/production/php.ini /usr/local/etc/php/conf.d/default.ini
COPY --chown=www-data:www-data ./docker/web/production/php-fpm.conf /usr/local/etc/php-fpm.d/php-fpm.conf
COPY --chown=www-data:www-data ./config/autoload/local.production.php.dist ./config/autoload/local.php
COPY --chown=www-data:www-data ./config/autoload/doctrine.local.production.php.dist ./config/autoload/doctrine.local.php
COPY --chown=www-data:www-data ./config/autoload/gewisdb.local.php.dist ./config/autoload/gewisdb.local.php

COPY --chown=www-data:www-data ./docker/web/production/crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab && crontab /etc/cron.d/crontab

COPY --chown=www-data:www-data ./docker/web/production/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod 0775 ./docker-entrypoint.sh

COPY --chown=www-data:www-data . /code

RUN php composer.phar dump-autoload -a --no-dev

COPY --chown=www-data:www-data --from=node-build /code/public/css/gewis-theme.css /code/public/css/gewis-theme.css

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

VOLUME ["/code/data", "/code/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
