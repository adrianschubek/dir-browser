#!/usr/bin/env bash

set -euo pipefail

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
GREEN_BG_BLACK_TEXT='\033[42;30m'
NC='\033[0m'

echo -e "${GREEN_BG_BLACK_TEXT} dir-browser (Next.js + Bun)${NC}"
echo -e "${CYAN} Bun API on :${PORT:-8080}, Next.js on :${NEXT_PORT:-3000}${NC}"

if [[ -n "${PASSWORD_USER:-}" && -z "${PASSWORD_RAW:-}" && -z "${PASSWORD_HASH:-}" ]]; then
  echo -e "${RED}[ Error ] PASSWORD_USER is set but neither PASSWORD_RAW nor PASSWORD_HASH is set.${NC}"
  exit 1
fi

mkdir -p "$(dirname "${COUNTER_SNAPSHOT_PATH:-/data/counters.sqlite.bin}")"

cd /app/next

echo -e "${YELLOW}[1/3] Starting Next.js server...${NC}"
bun run start -- -H 0.0.0.0 -p "${NEXT_PORT:-3000}" &
NEXT_PID=$!

echo -e "${YELLOW}[2/3] Starting Bun API server...${NC}"
PORT="${PORT:-8080}" NEXT_PORT="${NEXT_PORT:-3000}" bun run start:api &
API_PID=$!

cleanup() {
  echo -e "${YELLOW}[3/3] Stopping services...${NC}"
  kill "$NEXT_PID" "$API_PID" >/dev/null 2>&1 || true
  wait "$NEXT_PID" "$API_PID" >/dev/null 2>&1 || true
}

trap cleanup SIGINT SIGTERM

wait -n "$NEXT_PID" "$API_PID"
EXIT_CODE=$?

echo -e "${RED}[ Error ] One process exited. Shutting down...${NC}"
cleanup
exit "$EXIT_CODE"