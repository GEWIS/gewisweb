FROM php:5.6-fpm

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng12-dev \
        libxml2-dev \
        libicu-dev \
        g++ \
        zlib1g-dev \
        git \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install pdo \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install exif \
    && docker-php-ext-install soap \
    && docker-php-ext-install zip \
    && docker-php-ext-install calendar

RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends
RUN pecl install imagick

RUN pecl install -o -f xdebug-2.4.1 && rm -rf /tmp/pear

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /code
