FROM matomo:5.3-fpm-alpine AS gewisweb_matomo

RUN apk add --no-cache --virtual .build-deps \
        tzdata \
    && apk add --no-cache --virtual .runtime-deps \
        gettext \
        unzip \
    && cp /usr/share/zoneinfo/Europe/Amsterdam /etc/localtime \
    && echo 'Europe/Amsterdam' > /etc/timezone \
    && apk del .build-deps

RUN curl -o LogViewer.zip \
      https://plugins.matomo.org/api/2.0/plugins/LogViewer/download/latest \
      && unzip LogViewer.zip \
      && rm LogViewer.zip \
      && mv LogViewer /usr/src/matomo/plugins

RUN curl -o SecurityInfo.zip \
      https://plugins.matomo.org/api/2.0/plugins/SecurityInfo/download/latest \
      && unzip SecurityInfo.zip \
      && rm SecurityInfo.zip \
      && mv SecurityInfo /usr/src/matomo/plugins

RUN curl -L -o AnalyticsOptOut.zip \
      https://github.com/GEWIS/gewisweb-analytics-opt-out/archive/refs/tags/v1.0.3-gewisweb.zip \
      && unzip -j AnalyticsOptOut.zip -d "AnalyticsOptOut" \
      && rm AnalyticsOptOut.zip \
      && mv AnalyticsOptOut /usr/src/matomo/plugins

COPY --chown=www-data:www-data config.ini.php ./config/config.ini.php.template

CMD ["/bin/sh" , "-c" , "envsubst '${MATOMO_DATABASE_HOST} ${MATOMO_DATABASE_PORT} ${MATOMO_DATABASE_USERNAME} ${MATOMO_DATABASE_PASSWORD} ${MATOMO_DATABASE_DBNAME} ${MATOMO_DATABASE_SSL}' < /var/www/html/config/config.ini.php.template > /var/www/html/config/config.ini.php && chown www-data:www-data /var/www/html/config/config.ini.php && php-fpm -F -O"]
