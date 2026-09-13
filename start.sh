#!/usr/bin/env bash
set -Eeuo pipefail

# Wasmer may run the start command from the repository root, while the PHP
# application lives in the studywinzo directory.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec bash "$ROOT/studywinzo/start.sh"
