FROM node:13-alpine as node-build

COPY ./package.json ./package-lock.json ./
WORKDIR /code
COPY . /code

RUN npm install

# TODO: run scss

FROM php:5.6-fpm as php-target

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxml2-dev \
        libicu-dev \
        g++ \
        zlib1g-dev \
        git \
        unzip \
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
RUN docker-php-ext-enable imagick

# prod does not need xdebug
#RUN pecl install -o -f xdebug-2.4.1 && rm -rf /tmp/pear

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /code
COPY ./composer.json ./composer.lock ./
RUN composer install -o --no-dev
COPY . /code
RUN ./genclassmap.sh

VOLUME /code

CMD bash
