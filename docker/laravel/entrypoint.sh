#!/bin/sh
set -eu

case "${PORT:-8080}" in
    ''|*[!0-9]*)
        echo "CIVICLEAR startup refused: PORT must be numeric." >&2
        exit 1
        ;;
esac

if [ "${PORT:-8080}" -lt 1024 ] || [ "${PORT:-8080}" -gt 65535 ]; then
    echo "CIVICLEAR startup refused: PORT is outside the non-root range." >&2
    exit 1
fi

export APACHE_PORT="${PORT:-8080}"

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    /tmp/civiclear

# Deployment never performs migrations or secret generation during container startup.
php artisan config:cache --no-ansi >/dev/null
php artisan route:cache --no-ansi >/dev/null

exec "$@"
