#!/bin/sh
set -eu

case "${PORT:-8080}" in
    ''|*[!0-9]*)
        echo "CIVICLEAR FastAPI startup refused: PORT must be numeric." >&2
        exit 1
        ;;
esac

if [ "${PORT:-8080}" -lt 1024 ] || [ "${PORT:-8080}" -gt 65535 ]; then
    echo "CIVICLEAR FastAPI startup refused: PORT is outside the non-root range." >&2
    exit 1
fi

exec uvicorn main:app \
    --host 0.0.0.0 \
    --port "${PORT:-8080}" \
    --workers 1 \
    --no-server-header
