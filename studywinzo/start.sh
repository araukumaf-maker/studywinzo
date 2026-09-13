#!/usr/bin/env bash
set -euo pipefail

# Replit supplies PORT for the preview workflow. Keep the server in the
# foreground so the workflow can monitor it and restart it when needed.
cd "$(dirname "$0")"
PORT="${PORT:-9000}"

exec php -S "0.0.0.0:${PORT}" -c php.ini
