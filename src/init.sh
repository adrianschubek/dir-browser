#!/bin/bash
# echo colors
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
GRAY='\033[0;37m'
GREEN_BG_BLACK_TEXT='\033[42;30m'
NC='\033[0m' # reset
STEP=0
MAX_STEPS=7
CONFIG=/dbc.yml

step() {
  STEP=$((STEP+1))
  echo -e "${YELLOW}[ $STEP/$MAX_STEPS ] $1${NC}"
}

step_ok() {
  echo -e "${GREEN}[ $STEP/$MAX_STEPS ] $1${NC}"
}

# Function to convert keys to uppercase and replace `.` with `_`
to_env_var() {
    echo "$1" | tr '[:lower:]' '[:upper:]' | sed 's/\./_/g'
}

load_config() {
  # Export each key-value pair
  while IFS= read -r line; do
    # echo "line: $line"

    # Skip empty lines
    if [ -z "$line" ]; then
        continue
    fi

    key=$(echo "$line" | cut -d: -f1 | xargs)
    value=$(yq eval ".${line}" "$CONFIG")
    env_var_name=$(to_env_var "$key")

    # skip if contains new line
    if [[ "$value" == *$'\n'* ]]; then
      continue
    fi

    # Check if the value is an array
    if [[ $(echo "$value" | yq eval 'type' -) == "!!seq" ]]; then
      # Concatenate array elements with ";"
      value=$(echo "$value" | yq eval '.[]' - | paste -sd ";")
    fi

    # Handle empty values
    if [ -z "$value" ]; then
        value="''"
    fi

    # put in quotes if contains spaces or special characters
    if [[ "$value" =~ [[:space:]] || "$value" =~ [[:punct:]] ]]; then
        value="\"$value\""
    fi

    # Export the variable
    export "$env_var_name=$value"
    # echo "Exported: $env_var_name=$value"
  done < <(yq eval '... | path | join(".")' "$CONFIG" | uniq)
}

dbv=$DIRBROWSER_VERSION
echo -e "${GREEN_BG_BLACK_TEXT} dir-browser v${dbv} by Adrian Schubek${NC}"
echo -e "${CYAN} -> https://dir.adriansoftware.de <- ${NC}"

# if -e CONFIG is set then laod environment variables from yaml file. export it

# if CONFIG is set then load otherwie skip step
if [ ! -f "$CONFIG" ]; then
  STEP=$((STEP+1))
  echo -e "${GRAY}[ $STEP/$MAX_STEPS ] Skip loading config... use default${NC}"
else
  step "Loading custom config..."
  load_config
  step_ok "Config loaded!"
fi

step "Pre-processing configs using utpp..."
utpp "/etc/nginx/**;/usr/local/etc/php*/**;/var/www/html/*.php"
step_ok "Pre-processing done!"

step "Starting php-fpm..."
php-fpm -RF &

# wait for php-fpm to be ready
while true; do
  if nc -z localhost 9000; then
    break
  fi
  sleep 1
done
step_ok "php-fpm is ready!"

step "Starting redis..."
redis-server /etc/redis.conf --save 60 1 &

# wait for ready to be ready
while true; do
  redis-cli ping | grep -q "PONG" && break
  sleep 1
done
step_ok "redis is ready!"

step "Starting worker..."
php /var/www/html/worker.php --loop &
step_ok "worker is ready!"

if [ "$WORKER_WATCH" = "true" ]; then
  step "Starting fswatcher..."
  /fswatcher.sh &
  step_ok "fswatcher is ready!"
else 
  STEP=$((STEP+1))
  echo -e "${GRAY}[ $STEP/$MAX_STEPS ] Skip fswatcher... manual scans required${NC}"
fi

step "Starting nginx..."
nginx -g 'daemon off;' &

# wait for nginx to be ready
while true; do
  curl -s http://localhost:80/ > /dev/null && break
  sleep 1
done
step_ok "nginx is ready!"

STEP=$((STEP+1))
echo -e "${GREEN} -> All services running!${NC}"
wait -n

echo -e "${RED}[ Error ] Terminating due to a service exiting...${NC}"
exit $?