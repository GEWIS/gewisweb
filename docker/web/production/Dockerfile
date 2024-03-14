# Compile the language files.
FROM alpine:latest AS gewisweb_translations
WORKDIR /code

RUN apk add --no-cache --virtual .runtime-deps \
        gettext

COPY ./module/Application/language/*.po ./

RUN msgfmt en.po -o en -c --strict -v \
    && msgfmt nl.po -o nl -c --strict -v

# Create a base image with all PHP dependencies.
FROM php:8.3-fpm-alpine as gewisweb_web_production_base
WORKDIR /code

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        freetype-dev \
        icu-dev \
        imagemagick-dev \
        libjpeg-turbo-dev \
        libmemcached-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        tzdata \
    && apk add --no-cache --virtual .runtime-deps \
        git \
        ghostscript \
        icu-data-full \
        imagemagick \
        openssh \
        poppler-utils \
        rsync \
        sshpass \
        unzip \
    && docker-php-ext-configure \
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
        zip \
    && pecl install -o imagick \
    && docker-php-ext-enable imagick \
    && pecl install -o memcached \
    && docker-php-ext-enable memcached \
    && rm -r /tmp/pear \
    && runtimeDeps="$( \
            scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )" \
    && apk add --virtual .gewisweb-runtime-deps $runtimeDeps \
    && cp /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo 'Europe/Amsterdam' > /etc/timezone \
    && apk del .build-deps

RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/g' /etc/ImageMagick-7/policy.xml

# Install composer and through it the application dependencies.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./composer.json ./composer.lock ./

# We need to be able to use the Composer plugins for several patches, however, these are disabled when running non-
# interactively as root. As such, we re-enable this functionality to ensure this works. The alternative is becoming
# www-data while installing the dependencies, however, this would allow for modification of the PHP files at runtime.
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install -o --no-dev

# Compile the SCSS files.
FROM node:lts-alpine as gewisweb_web_styles
WORKDIR /code

COPY ./package.json ./package-lock.json ./
RUN npm install --omit=dev

RUN mkdir public && mkdir public/scss && mkdir public/css

COPY --from=gewisweb_web_production_base /code/vendor ./vendor
COPY ./resources/scss ./resources/scss/

RUN npm run scss

# Create the final image.
FROM gewisweb_web_production_base AS gewisweb_web_production

# Copy patches for dependencies that cannot be patched through composer.
COPY --from=web.docker-registry.gewis.nl/gewisweb_patches:latest . /code

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
COPY --chown=www-data:www-data --from=gewisweb_translations /code/*.mo /code/module/Application/language/
COPY --chown=www-data:www-data --from=gewisweb_web_styles /code/public/css/gewis-theme.css /code/public/css/gewis-theme.css
RUN cp -R ./vendor/fortawesome/font-awesome/sprites/. ./public/sprites/fontawesome
RUN cp -R ./vendor/fortawesome/font-awesome/webfonts/. ./public/fonts/fontawesome

RUN composer dump-autoload -a --no-dev

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

VOLUME ["/code/data", "/code/public"]

ENTRYPOINT ["/bin/sh", "/code/docker-entrypoint.sh"]
