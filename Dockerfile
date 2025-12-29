FROM debian:trixie-slim AS base

ENV DIRBROWSER_VERSION=4.3.2

ENV DEBIAN_FRONTEND=noninteractive

RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    apt-transport-https \
    unzip \
    bash \
    nginx \
    redis-server; \
  rm -rf /var/lib/apt/lists/*

# Install PHP 8.5 from Sury (https://packages.sury.org/php/README.txt)
RUN set -eux; \
  curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/trusted.gpg.d/php.gpg; \
  echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    php8.5-cli \
    php8.5-fpm \
    php8.5-mbstring \
    php8.5-zip \
    php8.5-redis; \
  ln -sf /usr/bin/php8.5 /usr/local/bin/php; \
  rm -rf /var/lib/apt/lists/*

RUN set -eux; \
  curl -fSsL https://github.com/adrianschubek/utpp/releases/download/0.5.0/utpp-linux -o /usr/local/bin/utpp; \
  chmod +x /usr/local/bin/utpp

RUN set -eux; \
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php; \
  php8.5 /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer; \
  rm -f /tmp/composer-setup.php

WORKDIR /var/www/html

RUN composer require "league/commonmark:^2.8"

RUN composer require "maennchen/zipstream-php:^3.2"

RUN mkdir -p /data/nginx/cache
# for batch downloads
RUN mkdir -p /var/www/html/tmp

COPY server/nginx/nginx.conf /etc/nginx/nginx.conf

COPY server/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

COPY server/php/fpm-pool.conf /etc/php/8.5/fpm/pool.d/www.conf

COPY server/php/php.ini /etc/php/8.5/fpm/conf.d/99-custom.ini
COPY server/php/php.ini /etc/php/8.5/cli/conf.d/99-custom.ini

COPY server/redis/redis.conf /etc/redis/redis.conf

COPY src/index.php /var/www/html

# skipped in v3.9
# COPY src/worker.php /var/www/html

COPY src/init.sh /init.sh

RUN chmod +x /init.sh

ENV THEME=default

ENV DATE_FORMAT=relative

ENV HASH=true

ENV HASH_MAX_FILE_SIZE_MB=100

ENV TRANSITION=false

ENV HASH_FOLDER=false	

ENV HASH_REQUIRED=false

ENV HASH_ALGO=sha256

ENV API=true

# basic,popup
ENV LAYOUT=popup
# TODO: show files in tree on hover
# ENV PREVIEW=false

ENV README_NAME=readme.md;readme.txt;readme.html;readme;read.me;read\ me;liesmich.md;liesmich.txt;liesmich;lies\ mich;index.html;index.htm;index.txt;license

ENV README_FIRST=false

ENV README_META=true

ENV DOWNLOAD_COUNTER=true

ENV README_RENDER=true

ENV OPEN_NEW_TAB=false

ENV HIGHLIGHT_UPDATED=true

ENV METADATA=true

ENV SEARCH=true

# s=simple,g=glob,r=regex
ENV SEARCH_ENGINE=s,g
# regex only:
ENV SEARCH_MAX_DEPTH=25

ENV SEARCH_MAX_RESULTS=100

# multi select batch file download
ENV BATCH_DOWNLOAD=true
# TODO: add more: https://www.php.net/manual/en/book.zlib.php
ENV BATCH_TYPE=zip
# https://www.php.net/manual/en/zip.constants.php#ziparchive.constants.cm-default. keep STORE highly recommended for performance
ENV BATCH_ZIP_COMPRESS_ALGO=STORE
# MB (not strictly necessary anymore due to streaming, but still good to have a limit) 500 GB
ENV BATCH_MAX_TOTAL_SIZE=500000
# MB per file (not strictly necessary anymore due to streaming, but still good to have a limit) 500 GB
ENV BATCH_MAX_FILE_SIZE=500000
# MB, how much system disk space to keep free at all times
ENV BATCH_MIN_SYSTEM_FREE_DISK=500
# watch filesystem
ENV WORKER_WATCH=true
# seconds re-scan
ENV WORKER_SCAN_INTERVAL=60

ENV WORKER_FORCE_RESCAN=

ENV PAGINATION_PER_PAGE=100

ENV PREFETCH_FOLDERS=true

ENV PREFETCH_FILES=false

ENV CORS_ALLOW_ANY_ORIGIN=true

# seconds. default 60 * 60 * 24 * 30
ENV AUTH_COOKIE_LIFETIME=2592000

ENV AUTH_COOKIE_HTTPONLY=true

ENV TITLE="dir-browser"

ENV MEM_LIMIT=-1

ENV MAX_EXEC_TIME=600

ENV DISPLAY_ERRORS=Off

EXPOSE 8080

CMD ["/init.sh"]
