FROM node:lts-alpine as node-build
WORKDIR /code

COPY ./package.json ./package-lock.json ./
RUN npm install --production

RUN mkdir public && mkdir public/scss && mkdir public/css

COPY ./public/scss ./public/scss/
RUN npm run scss

FROM php:8.1-fpm-alpine as php-target

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    curl-dev \
    freetype-dev \
    git \
    ghostscript \
    icu-dev \
    imagemagick-dev \
    imagemagick \
    libjpeg-turbo-dev \
    libmemcached-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    openssh \
    poppler-utils \
    rsync \
    sshpass \
    unzip

RUN docker-php-ext-configure \
        gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
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

RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/g' /etc/ImageMagick-7/policy.xml

FROM php-target as gewisweb_web_production
WORKDIR /code

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

COPY ./composer.json ./composer.lock ./
RUN php composer.phar install -o --no-dev

COPY --chown=www-data:www-data ./docker/web/production/php.ini /usr/local/etc/php/conf.d/default.ini
COPY --chown=www-data:www-data ./docker/web/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-gewisweb.conf
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
