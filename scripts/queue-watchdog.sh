#!/bin/sh
# scripts/queue-watchdog.sh — drain THIS app's queue once, then exit.
# Run every minute from cron as `http`.
#
# A persistent `queue:work` daemon does not stay resident in this environment
# (it exits cleanly after a few seconds), so instead of babysitting a daemon we
# drain on a schedule: process all queued jobs, then exit when the queue is
# empty (--stop-when-empty). This is robust on a NAS — no orphaned daemons, code
# changes are picked up every run, and a crash just means the next tick retries.
#
#   flock -n   : if a previous drain is still running, skip this tick (no stacking)
#   --max-time : bound a long drain so it finishes before the next minute
#   --sleep=1  : exit quickly when the queue is already empty
set -eu

APP_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
PHP=/usr/local/bin/php84
QUEUE=default
LOCK="$APP_DIR/storage/queue-drain.lock"

cd "$APP_DIR" || exit 1
mkdir -p storage/logs

exec /usr/bin/flock -n "$LOCK" "$PHP" artisan queue:work --queue="$QUEUE" \
    --stop-when-empty --max-time=55 --sleep=1 --tries=3 --backoff=10 \
    >> storage/logs/worker.log 2>&1
