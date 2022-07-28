FROM php:8.1-fpm as php-target

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
    && apt-get upgrade -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        exif \
        fileinfo \
        gd \
        opcache \
        zip

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
