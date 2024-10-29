# includes redis

FROM php:8.3-fpm-alpine AS base

RUN apk update && apk upgrade

RUN docker-php-ext-install opcache

RUN apk add --no-cache libzip-dev \
  && docker-php-ext-configure zip \
  && docker-php-ext-install zip

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
# for batch downloads
RUN mkdir -p /var/www/html/tmp

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

ENV TRANSITION=false

ENV HASH_FOLDER=false	

ENV HASH_REQUIRED=false

ENV HASH_ALGO=sha256

ENV API=true

# TODO: full = popup fullscreen
# basic,popup,full
ENV LAYOUT=basic
# TODO: show files in tree on hover
ENV PREVIEW=false

ENV README_NAME=readme.md;readme.txt;readme.html;readme;read.me;read\ me;liesmich.md;liesmich.txt;liesmich;lies\ mich;index.html;index.htm;index.txt;license

ENV README_FIRST=false

ENV README_META=true

ENV DOWNLOAD_COUNTER=true

ENV README_RENDER=true

ENV OPEN_NEW_TAB=false

ENV HIGHLIGHT_UPDATED=true

ENV METADATA=true

# multi select batch file download
ENV BATCH_DOWNLOAD=true
# TODO: add more: https://www.php.net/manual/en/book.zlib.php
ENV BATCH_TYPE=zip
# https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.cm-default
ENV BATCH_ZIP_COMPRESS_ALGO=ZSTD
# MB
ENV BATCH_MAX_TOTAL_SIZE=500
# MB per file
ENV BATCH_MAX_FILE_SIZE=100
# MB, how much system disk space to keep free at all times
ENV BATCH_MIN_SYSTEM_FREE_DISK=500

# TODO Ratelimiting?

RUN chmod +x /init.sh

EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/init.sh"]
