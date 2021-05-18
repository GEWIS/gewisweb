FROM nginx:latest as gewisweb_nginx

COPY --chown=www-data:www-data . /etc/nginx
COPY --chown=www-data:www-data nginx.conf /etc/nginx/nginx.conf.template

VOLUME ["/etc/nginx/logs"]

CMD ["/bin/sh" , "-c" , "envsubst '${NGINX_REQUIRE_AUTH} ${NGINX_REQUIRE_AUTH}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'"]
