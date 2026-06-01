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
PIDFILE="$APP_DIR/storage/worker.pid"

# Already running for this app? Verify the recorded pid is alive AND is our worker
# (pidfile is per-app under its own storage/, so this is app-scoped).
if [ -f "$PIDFILE" ]; then
    pid="$(cat "$PIDFILE" 2>/dev/null || true)"
    if [ -n "${pid:-}" ] && kill -0 "$pid" 2>/dev/null \
       && tr '\0' ' ' < "/proc/$pid/cmdline" 2>/dev/null | grep -q "queue:work"; then
        exit 0
    fi
fi

cd "$APP_DIR" || exit 1
mkdir -p storage/logs
# NOTE: --queue=<name>, NOT a positional arg (positional = connection name).
nohup "$PHP" artisan queue:work --queue="$QUEUE" \
    --sleep=3 --tries=3 --backoff=10 --max-time=3600 --max-jobs=1000 \
    >> storage/logs/worker.log 2>&1 &
echo $! > "$PIDFILE"
echo "[queue-watchdog] started worker pid $! for ${APP_DIR} (queue=${QUEUE})"
