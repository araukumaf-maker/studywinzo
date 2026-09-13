#!/usr/bin/env bash
set -euo pipefail
echo "PHP: $(command -v php || echo 'not found')"
echo "PORT: ${PORT:-8080}"
