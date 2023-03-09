# FROM php:8.2-alpine as base

# WORKDIR /app

# COPY ./src /app

# CMD [ "php", "index.php" ]

FROM php:8.2-fpm-alpine AS base

RUN apk update && apk upgrade

RUN docker-php-ext-install opcache

# RUN apk add --no-cache bash

RUN apk add --no-cache nginx

RUN apk add --no-cache supervisor

COPY server/nginx/nginx.conf /etc/nginx/nginx.conf

COPY server/nginx/conf.d /etc/nginx/conf.d

COPY server/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY server/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

COPY server/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# COPY server/php /etc/php

COPY src /var/www/html

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /var/www/html /run /var/lib/nginx /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Add application
COPY --chown=nobody src/ /var/www/html/

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
# HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping