FROM nginx:stable-alpine as gewisweb_nginx

RUN adduser -D -H -u 1000 -s /bin/sh www-data -G www-data

COPY --chown=www-data:www-data . /etc/nginx
COPY --chown=www-data:www-data nginx.conf /etc/nginx/nginx.conf.template

VOLUME ["/etc/nginx/logs"]

CMD ["/bin/sh" , "-c" , "envsubst '${APP_ENV} ${NGINX_REQUIRE_AUTH} ${NONCE_REPLACEMENT_STRING}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'"]
