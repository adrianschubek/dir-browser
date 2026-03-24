FROM oven/bun:1.3.10 AS deps

WORKDIR /app/next
COPY next/package.json next/bun.lock ./
RUN bun install --frozen-lockfile

FROM deps AS builder

WORKDIR /app/next
COPY next/ ./
RUN bun run build

FROM oven/bun:1.3.10 AS runtime

WORKDIR /app

RUN groupadd --system app && useradd --system --gid app --create-home app

COPY --from=deps /app/next/node_modules ./next/node_modules
COPY --from=builder /app/next/.next ./next/.next
COPY --from=builder /app/next/app ./next/app
COPY --from=builder /app/next/components ./next/components
COPY --from=builder /app/next/hooks ./next/hooks
COPY --from=builder /app/next/lib ./next/lib
COPY --from=builder /app/next/public ./next/public
COPY --from=builder /app/next/server ./next/server
COPY --from=builder /app/next/package.json ./next/package.json
COPY --from=builder /app/next/next.config.mjs ./next/next.config.mjs
COPY --from=builder /app/next/tsconfig.json ./next/tsconfig.json

COPY examples /var/www/html/public
COPY src/init.sh /init.sh

RUN chmod +x /init.sh && mkdir -p /data && chown -R app:app /app /var/www/html /data

USER app

ENV NODE_ENV=production
ENV PORT=8080
ENV NEXT_PORT=3000
ENV NEXT_HOST=127.0.0.1
ENV UI_BASE_PATH=/ui
ENV PUBLIC_FOLDER=/var/www/html/public
ENV COUNTER_SNAPSHOT_PATH=/data/counters.sqlite.bin
ENV COUNTER_FLUSH_INTERVAL_MS=10000

ENV API=true
ENV DOWNLOAD_COUNTER=true
ENV METADATA=true
ENV SEARCH=true
ENV SEARCH_ENGINE=s,g
ENV SEARCH_MAX_DEPTH=25
ENV SEARCH_MAX_RESULTS=100
ENV BATCH_DOWNLOAD=true
ENV BATCH_ZIP_COMPRESS_ALGO=STORE
ENV BATCH_MAX_TOTAL_SIZE=500000
ENV BATCH_MAX_FILE_SIZE=500000
ENV HASH=true
ENV HASH_REQUIRED=false
ENV HASH_ALGO=sha256
ENV HASH_MAX_FILE_SIZE_MB=100
ENV CORS_ALLOW_ANY_ORIGIN=true
ENV AUTH_COOKIE_LIFETIME=2592000
ENV AUTH_COOKIE_HTTPONLY=true
ENV TITLE=dir-browser

VOLUME ["/var/www/html/public", "/data"]

EXPOSE 8080

CMD ["/init.sh"]
