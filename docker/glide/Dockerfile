FROM php:8.1-fpm-alpine as php-target

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        imagemagick-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    && apk add --no-cache --virtual .runtime-deps \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        exif \
        fileinfo \
        gd \
        opcache \
        zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -r /tmp/pear \
    && runtimeDeps="$( \
            scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )" \
    && apk add --virtual .gewisweb-runtime-deps $runtimeDeps \
    && apk del .build-deps

FROM php-target as composer-build
WORKDIR /glide

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

COPY ./composer.json ./composer.lock ./
RUN php composer.phar install -o --no-dev

FROM php-target as glide
WORKDIR /glide

RUN mkdir cache \
    && chown www-data:www-data cache

COPY --chown=www-data:www-data ./php.ini /usr/local/etc/php/conf.d/default.ini
COPY --chown=www-data:www-data ./php-fpm.conf /usr/local/etc/php-fpm.d/zz-glide.conf

COPY --chown=www-data:www-data --from=composer-build /glide/vendor /glide/vendor

COPY --chown=www-data:www-data . /glide

RUN chmod 0775 ./docker-entrypoint.sh

VOLUME ["/glide/public"]

ENTRYPOINT ["/bin/sh", "/glide/docker-entrypoint.sh"]
