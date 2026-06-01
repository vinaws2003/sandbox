#!/bin/sh
# scripts/queue-watchdog.sh — ensure exactly one Laravel queue worker is running
# for THIS app. Idempotent: safe to run from cron every few minutes; it also
# covers boot (the first tick after reboot starts the worker). Run as `http`
# (the php-fpm user) so anything the worker writes under storage/ stays http-owned.
#
# The worker self-recycles via --max-time so deployed code is picked up without
# a separate `queue:restart`. One worker per node; multiple nodes pulling the
# shared DB queue coordinate via row locking, so running it on >1 node is safe.
set -eu

APP_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
PHP=/usr/local/bin/php84
QUEUE=default

# Already running for this app? (match the app dir to avoid colliding with other apps)
if pgrep -f "artisan queue:work .*${APP_DIR}/" >/dev/null 2>&1; then
    exit 0
fi

cd "$APP_DIR" || exit 1
mkdir -p storage/logs
nohup "$PHP" artisan queue:work "$QUEUE" \
    --sleep=3 --tries=3 --backoff=10 --max-time=3600 --max-jobs=1000 \
    >> storage/logs/worker.log 2>&1 &

echo "[queue-watchdog] started worker for ${APP_DIR} (queue=${QUEUE})"
