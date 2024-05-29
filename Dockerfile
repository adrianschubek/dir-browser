# includes redis

FROM php:8.3-fpm-alpine AS base

RUN apk update && apk upgrade

RUN docker-php-ext-install opcache

RUN apk add --no-cache autoconf build-base \
  && pecl install -o -f redis \
  && rm -rf /tmp/pear \
  && docker-php-ext-enable redis \
  && apk del autoconf build-base

RUN apk add --no-cache redis 

RUN apk add --no-cache nginx

RUN apk add --no-cache supervisor

RUN apk add --no-cache curl \
  && curl -fSsL https://github.com/adrianschubek/utpp/releases/download/0.5.0/utpp-alpine -o /usr/local/bin/utpp && chmod +x /usr/local/bin/utpp\
  && apk del curl

RUN apk add --no-cache composer

WORKDIR /var/www/html

RUN composer require "league/commonmark:^2.4"

RUN mkdir -p /data/nginx/cache

COPY server/nginx/nginx.conf /etc/nginx/nginx.conf

COPY server/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

COPY server/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY server/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

COPY server/php/php.ini /usr/local/etc/php/conf.d/custom.ini

COPY src/index.php /var/www/html

COPY src/init.sh /init.sh

ENV THEME=default

ENV DATE_FORMAT=relative

ENV HASH=true

RUN chmod +x /init.sh

EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/init.sh"]
