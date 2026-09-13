#!/usr/bin/env bash
set -Eeuo pipefail

# Wasmer supplies PORT. Bind to 0.0.0.0 so the deployment can reach PHP.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOST="${HOST:-0.0.0.0}"
PORT="${PORT:-8080}"

command -v php >/dev/null 2>&1 || {
    echo "PHP is required but was not found in PATH." >&2
    exit 1
}

exec php -c "$ROOT/php.ini" -S "$HOST:$PORT" -t "$ROOT"
