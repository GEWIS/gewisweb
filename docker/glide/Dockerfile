FROM php:7.4-fpm as php-target

RUN apt-get update \
    && apt-get install -y \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        zlib1g-dev \
    && apt-get upgrade -y

RUN docker-php-ext-install \
        fileinfo \
        gd \
        opcache \
        zip

RUN pecl install -o imagick \
    && docker-php-ext-enable imagick

FROM php-target as composer-build
WORKDIR /glide

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');"

COPY ./composer.json ./composer.lock ./
RUN php composer.phar install -o --no-dev

FROM php-target as glide
WORKDIR /glide

COPY ./php.ini /usr/local/etc/php/conf.d/default.ini
COPY ./php-fpm.conf /usr/local/etc/php-fpm.d/php-fpm.conf

COPY --from=composer-build /glide/vendor /glide/vendor

COPY . /glide

RUN chmod 0777 ./docker-entrypoint.sh

RUN chown -R www-data:www-data /glide

ENTRYPOINT ["/bin/sh", "/glide/docker-entrypoint.sh"]
