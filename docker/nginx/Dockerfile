FROM nginx:latest as gewisweb_nginx

COPY . /etc/nginx
COPY nginx.conf /etc/nginx/nginx.conf.template

CMD ["/bin/sh" , "-c" , "envsubst '${NGINX_REQUIRE_AUTH} ${NGINX_REQUIRE_AUTH}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'"]
