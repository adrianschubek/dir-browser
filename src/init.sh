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