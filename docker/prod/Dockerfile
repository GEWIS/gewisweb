FROM node:13-alpine as node-build
WORKDIR /code

COPY ./package.json ./package-lock.json ./
RUN npm install

COPY . /code

RUN npm run scss

FROM php:5.6-fpm as php-target

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libpng-dev \
        libxml2-dev \
        unzip \
        zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        calendar \
        exif \
        gd \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        soap \
        zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

FROM php-target as composer-build
WORKDIR /code

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./composer.json ./composer.lock ./
RUN composer install -o --no-dev

FROM php-target as production-image
WORKDIR /code

COPY . /code

COPY --from=composer-build /code/vendor /code/vendor
COPY --from=node-build /code/public/css/gewis-theme.css /code/public/css/gewis-theme.css
COPY ./docker/prod/php.ini /usr/local/etc/php/conf.d/default.ini

RUN ./genclassmap.sh \
    && ./web orm:generate-proxies

RUN mkdir -p /code/data/logs

VOLUME /code
