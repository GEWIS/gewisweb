FROM matomo:latest as gewisweb_matomo

RUN apt update && apt install -y \
    gettext-base \
    unzip

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

COPY --chown=www-data:www-data config.ini.php /var/www/html/config/config.ini.php.template

CMD ["/bin/sh" , "-c" , "envsubst '${MATOMO_DATABASE_HOST} ${MATOMO_DATABASE_PORT} ${MATOMO_DATABASE_USERNAME} ${MATOMO_DATABASE_PASSWORD} ${MATOMO_DATABASE_DBNAME}' < /var/www/html/config/config.ini.php.template > /var/www/html/config/config.ini.php && chown www-data:www-data /var/www/html/config/config.ini.php && exec apache2-foreground"]
