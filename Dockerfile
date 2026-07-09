#syntax=docker/dockerfile:1
ARG PHP_VERSION=8.5
ARG FRANKENPHP_VERSION=1.12

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS frankenphp_upstream

# GEWISWEB Base Image
FROM frankenphp_upstream AS gewisweb_web_base

# Prevents having to use `set -eux` in every RUN command
SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app
VOLUME /app/data/
VOLUME /app/var/

# The `pcntl` extension is required for Symfony Messenger to perform graceful shutdowns
RUN <<-EOF
    apt-get update
    apt-get install -y --no-install-recommends \
        ca-certificates \
        file \
        git \
        libicu-dev
    install-php-extensions \
        @composer \
        amqp \
        apcu \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        redis \
        zip
    rm -rf /var/lib/apt/lists/*
EOF

# Builders for AssetMapper. The binaries are arch-specific, so select them based on the build target (set by BuildKit)
# to avoid pulling linux-x64 binaries that only run through (failing) emulation on arm64 hosts (e.g. Apple Silicon).
ARG TARGETARCH
ARG SASS_VERSION=1.101.0
ARG SWC_VERSION=v1.15.43

RUN <<-EOF
    case "$TARGETARCH" in
        amd64) SASS_ARCH=linux-x64; SWC_ARCH=linux-x64-gnu ;;
        arm64) SASS_ARCH=linux-arm64; SWC_ARCH=linux-arm64-gnu ;;
        *) echo "Unsupported architecture: ${TARGETARCH}" >&2; exit 1 ;;
    esac
    curl -OL --no-progress-meter "https://github.com/sass/dart-sass/releases/download/${SASS_VERSION}/dart-sass-${SASS_VERSION}-${SASS_ARCH}.tar.gz"
    tar -xzf "dart-sass-${SASS_VERSION}-${SASS_ARCH}.tar.gz" -C /usr/local/bin --strip-components=1
    rm -f "dart-sass-${SASS_VERSION}-${SASS_ARCH}.tar.gz"
    curl -OL --no-progress-meter "https://github.com/swc-project/swc/releases/download/${SWC_VERSION}/swc-${SWC_ARCH}"
    mv "swc-${SWC_ARCH}" /usr/local/bin/swc
    chmod +x /usr/local/bin/swc
EOF

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

ARG GIT_COMMIT
ENV GIT_COMMIT=${GIT_COMMIT}

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

###> recipes ###
###> doctrine/doctrine-bundle ###
###< doctrine/doctrine-bundle ###
###< recipes ###

COPY --link docker/web/frankenphp/conf.d/10-gewisweb.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 docker/web/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/web/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]

# GEWISWEB Development Image (local)
FROM gewisweb_web_base AS gewisweb_web_development

# Match the host user's UID/GID so files written through the bind mount are not root-owned.
ARG USER_UID=1000
ARG USER_GID=1000

ENV APP_ENV=dev
ENV XDEBUG_MODE=off
ENV FRANKENPHP_WORKER_CONFIG=watch

RUN <<-EOF
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
    apt-get update
    apt-get install -y --no-install-recommends \
        aggregate \
        curl \
        dnsmasq \
        dnsutils \
        inotify-tools \
        iproute2 \
        ipset \
        iptables \
        jq \
        sudo
    install-php-extensions xdebug
    rm -rf /var/lib/apt/lists/*
    # On macOS `id -g` returns 20 (staff), which already exists in the base image; reuse the existing group in that case
    # instead of failing on a duplicate GID.
    if ! getent group "$USER_GID" >/dev/null; then
        groupadd -g "$USER_GID" nonroot
    fi
    useradd -m -u "$USER_UID" -g "$USER_GID" -s /bin/bash nonroot
    echo "nonroot ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/nonroot
    chown -R "$USER_UID:$USER_GID" /data/caddy /config/caddy
    git config --system --add safe.directory /app
EOF

COPY --link docker/web/frankenphp/conf.d/20-gewisweb.dev.ini $PHP_INI_DIR/app.conf.d/

USER nonroot

CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--watch" ]

# GEWISWEB Development Image (remote)
FROM gewisweb_web_base AS gewisweb_web_test

ENV FRANKENPHP_WORKER_CONFIG=""

COPY --link . ./
RUN rm -Rf docker/

RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative; \
    chmod +x bin/console; sync;

# GEWISWEB Production Base Image
FROM gewisweb_web_base AS gewisweb_web_prod_builder

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link docker/web/frankenphp/conf.d/20-gewisweb.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --link composer.* symfony.* ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link --exclude=docker/ . ./

RUN <<-EOF
    mkdir -p var/cache var/log var/share
    composer dump-autoload --classmap-authoritative --no-dev
    composer dump-env prod
    composer run-script --no-dev post-install-cmd
    php bin/console sass:build
    php bin/console importmap:install
    php bin/console asset-map:compile
    chmod +x bin/console
    chmod -R g=u var
    sync
EOF

# Collect shared libraries needed by FrankenPHP and PHP extensions
RUN <<-'EOF'
    apt-get update
    apt-get install -y --no-install-recommends libtree
    mkdir -p /tmp/libs
    BINARIES=(frankenphp php file)
    for target in $(printf '%s\n' "${BINARIES[@]}" | xargs -I{} which {}) \
        $(find "$(php -r 'echo ini_get("extension_dir");')" -maxdepth 2 -name "*.so"); do
        libtree -pv "$target" 2>/dev/null | grep -oP '(?:── )\K/\S+(?= \[)' | while IFS= read -r lib; do
            [ -f "$lib" ] && cp -n "$lib" /tmp/libs/
        done
    done
    sed -i 's/opcache.preload_user = root/opcache.preload_user = www-data/' "$PHP_INI_DIR/app.conf.d/20-gewisweb.prod.ini"
    rm -rf /var/lib/apt/lists/*
EOF

# GEWISWEB Production Base Image
FROM debian:13-slim AS gewisweb_web_production

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

ENV APP_ENV=prod
ENV PHP_INI_SCAN_DIR=":/usr/local/etc/php/app.conf.d"

COPY --from=gewisweb_web_prod_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
COPY --from=gewisweb_web_prod_builder /usr/local/bin/php /usr/local/bin/php
COPY --from=gewisweb_web_prod_builder /usr/local/bin/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
COPY --from=gewisweb_web_prod_builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=gewisweb_web_prod_builder /tmp/libs /usr/lib

COPY --from=gewisweb_web_prod_builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=gewisweb_web_prod_builder /usr/local/etc/php/php.ini /usr/local/etc/php/php.ini
COPY --from=gewisweb_web_prod_builder /usr/local/etc/php/app.conf.d /usr/local/etc/php/app.conf.d

COPY --from=gewisweb_web_prod_builder /etc/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# CA certificates for TLS, file/libmagic for Symfony MIME type detection
COPY --from=gewisweb_web_prod_builder /usr/share/ca-certificates /usr/share/ca-certificates
COPY --from=gewisweb_web_prod_builder /etc/ssl/certs /etc/ssl/certs
COPY --from=gewisweb_web_prod_builder /etc/ssl/openssl.cnf /etc/ssl/openssl.cnf
COPY --from=gewisweb_web_prod_builder /usr/bin/file /usr/bin/file
COPY --from=gewisweb_web_prod_builder /usr/lib/file/magic.mgc /usr/lib/file/magic.mgc

ENV XDG_CONFIG_HOME=/config XDG_DATA_HOME=/data

RUN <<-EOF
    mkdir -p /data/caddy /config/caddy
    chown -R www-data:www-data /data /config
    find / -perm /6000 -type f -exec chmod a-s {} + 2>/dev/null || true
EOF

COPY --link --exclude=var --from=gewisweb_web_prod_builder /app /app
COPY --chown=www-data:0 --from=gewisweb_web_prod_builder /app/var /app/var
RUN chmod g=u /app/var

COPY --link --chmod=755 docker/web/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

USER www-data

WORKDIR /app

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'
CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]
