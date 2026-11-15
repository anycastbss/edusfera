#!/usr/bin/env bash
# EDUSFERA Zero-Lag Queue Watchdog Worker for Managed VPS (HB.BY)
# Keeps queue listener active for 55s with 0.5s sleep, picking up jobs in < 1 second.
# Protected by flock to prevent multiple overlapping workers.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

LOCK_FILE="/tmp/edusfera_queue_watchdog.lock"

# Execute queue:work with 55s lifetime and 0.5s poll rate
if command -v flock >/dev/null 2>&1; then
    exec flock -n "$LOCK_FILE" php artisan queue:work database --queue=exams,default --sleep=0.5 --max-time=55 --tries=2 > /dev/null 2>&1
else
    exec php artisan queue:work database --queue=exams,default --sleep=0.5 --max-time=55 --tries=2 > /dev/null 2>&1
fi
