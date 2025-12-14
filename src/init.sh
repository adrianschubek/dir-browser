#!/bin/bash
# echo colors
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
GREEN_BG_BLACK_TEXT='\033[42;30m'
NC='\033[0m' # reset
MAX_STEPS=5

dbv=$DIRBROWSER_VERSION
echo -e "${GREEN_BG_BLACK_TEXT} dir-browser v${dbv} by Adrian Schubek${NC}"
echo -e "${CYAN} -> https://dir.adriansoftware.de <- ${NC}"

# crash if PASSWORD_USER is set but neither PASSWORD_RAW nor PASSWORD_HASH is set
if [ -n "${PASSWORD_USER}" ] && [ -z "${PASSWORD_RAW}" ] && [ -z "${PASSWORD_HASH}" ]; then
  echo -e "${RED}[ Error ] PASSWORD_USER is set but neither PASSWORD_RAW nor PASSWORD_HASH is set. Exiting.${NC}"
  exit 1
fi

# print if password protection is enabled and which method is used
if [ -n "${PASSWORD_USER}" ] && ( [ -n "${PASSWORD_RAW}" ] || [ -n "${PASSWORD_HASH}" ] ); then
  if [ -n "${PASSWORD_HASH}" ]; then
    echo -e "${GREEN}[ Info ] Global password protection is enabled using hashed password.${NC}"
  else
    echo -e "${GREEN}[ Info ] Global password protection is enabled using raw password.${NC}"
  fi
fi


echo -e "${YELLOW}[ 1/$MAX_STEPS ] Pre-processing configs using utpp... ${NC}"
utpp "/etc/nginx/**;/usr/local/etc/php*/**;/var/www/html/*.php"

echo -e "${YELLOW}[ 2/$MAX_STEPS ] Starting php-fpm... ${NC}"
php-fpm -RF &

# skipped in v3.9
# echo -e "${YELLOW}[ 3/$MAX_STEPS ] Starting worker... ${NC}"
# php /var/www/html/worker.php &

echo -e "${YELLOW}[ 3/$MAX_STEPS ] Starting nginx... ${NC}"
nginx -g 'daemon off;' &

echo -e "${YELLOW}[ 4/$MAX_STEPS ] Starting redis... ${NC}"
redis-server /etc/redis.conf --save 60 1 &

echo -e "${GREEN}[ 5/$MAX_STEPS ] All services running!${NC}"
wait -n

echo -e "${RED}[ Error ] Terminating due to a service exiting...${NC}"
exit $?