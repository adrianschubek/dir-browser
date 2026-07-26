#!/bin/bash
# echo colors
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
GREEN_BG_BLACK_TEXT='\033[42;30m'
NC='\033[0m' # reset
MAX_STEPS=5
CONTENT_MOUNT_ROOT=/var/www/html/public
PUBLIC_ROOT="${PUBLIC_ROOT:-$CONTENT_MOUNT_ROOT}"
export PUBLIC_ROOT

dbv=$DIRBROWSER_VERSION
echo -e "${GREEN_BG_BLACK_TEXT} dir-browser v${dbv} by Adrian Schubek${NC}"
echo -e "${CYAN} -> https://dir.adriansoftware.de <- ${NC}"

# PUBLIC_ROOT is inserted into the generated Nginx configuration as a quoted
# string. Keep it absolute, normalized, and within the fixed mount boundary.
if [[ "$PUBLIC_ROOT" != /* ]] \
  || [[ "$PUBLIC_ROOT" =~ [[:cntrl:]] ]] \
  || [[ "$PUBLIC_ROOT" == */ ]] \
  || [[ "$PUBLIC_ROOT" == *//* ]] \
  || [[ "$PUBLIC_ROOT" == *"/./"* || "$PUBLIC_ROOT" == */. ]] \
  || [[ "$PUBLIC_ROOT" == *"/../"* || "$PUBLIC_ROOT" == */.. ]] \
  || [[ "$PUBLIC_ROOT" != "$CONTENT_MOUNT_ROOT" && "$PUBLIC_ROOT" != "$CONTENT_MOUNT_ROOT/"* ]]; then
  echo -e "${RED}[ Error ] PUBLIC_ROOT must be an absolute, normalized path at or below ${CONTENT_MOUNT_ROOT}.${NC}"
  exit 1
fi

ln -sfn -- "$PUBLIC_ROOT" /var/www/html/.dir-browser-public-root

resolved_mount="$(readlink -f -- "$CONTENT_MOUNT_ROOT" 2>/dev/null || true)"
resolved_public_root="$(readlink -f -- "$PUBLIC_ROOT" 2>/dev/null || true)"
if [ -z "$resolved_public_root" ] || [ ! -d "$resolved_public_root" ]; then
  echo -e "${YELLOW}[ Warning ] PUBLIC_ROOT is not currently available: configured=${PUBLIC_ROOT}. Content requests will return 503 until it appears.${NC}"
elif [ -z "$resolved_mount" ] \
  || { [ "$resolved_public_root" != "$resolved_mount" ] && [[ "$resolved_public_root" != "$resolved_mount/"* ]]; }; then
  echo -e "${RED}[ Error ] PUBLIC_ROOT resolves outside the content mount: configured=${PUBLIC_ROOT}, resolved=${resolved_public_root}.${NC}"
elif [ ! -r "$resolved_public_root" ] || [ ! -x "$resolved_public_root" ]; then
  echo -e "${RED}[ Error ] PUBLIC_ROOT is not readable/traversable: configured=${PUBLIC_ROOT}, resolved=${resolved_public_root}. Directories need read permission and execute permission on every ancestor.${NC}"
else
  echo -e "${GREEN}[ Info ] Public root: configured=${PUBLIC_ROOT}, resolved=${resolved_public_root}.${NC}"
fi

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

if [ -n "${PASSWORD_URL_KEY}" ]; then
  echo -e "${GREEN}[ Info ] Global password protection is enabled using URL key auth (?auth=...).${NC}"
fi


echo -e "${YELLOW}[ 1/$MAX_STEPS ] Pre-processing configs using utpp... ${NC}"
# Only preprocess files shipped by the image. /var/www/html/public is a
# user-provided, read-only mount and may itself contain PHP files.
utpp "/etc/nginx/nginx.conf;/etc/nginx/conf.d/default.conf;/etc/php/**;/var/www/html/index.php;/var/www/html/app/*.php;/var/www/html/views/*.php;/var/www/html/views/partials/*.php"

# Folder-auth cookies contain only an opaque session id. Generate a per-container
# secret for credential fingerprints unless the operator supplied one.
if [ -z "${AUTH_SESSION_SECRET}" ] && [ ! -s /var/www/html/tmp/auth-session-secret ]; then
  umask 077
  head -c 32 /dev/urandom | base64 > /var/www/html/tmp/auth-session-secret
fi

echo -e "${YELLOW}[ 2/$MAX_STEPS ] Starting php-fpm... ${NC}"
php-fpm8.5 -F -R &

# skipped in v3.9
# echo -e "${YELLOW}[ 3/$MAX_STEPS ] Starting worker... ${NC}"
# php /var/www/html/worker.php &

echo -e "${YELLOW}[ 3/$MAX_STEPS ] Starting nginx... ${NC}"
nginx -g 'daemon off;' &

echo -e "${YELLOW}[ 4/$MAX_STEPS ] Starting redis... ${NC}"
mkdir -p /run/redis
redis-server /etc/redis/redis.conf --save 60 1 &

echo -e "${GREEN}[ 5/$MAX_STEPS ] All services running!${NC}"
wait -n

echo -e "${RED}[ Error ] Terminating due to a service exiting...${NC}"
exit $?
