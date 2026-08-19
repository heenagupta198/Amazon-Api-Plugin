#!/usr/bin/env bash
set -euo pipefail

# Per-boot readiness check: confirm install artifacts exist before terminals start.
cd "$(dirname "$0")/.."

if [[ ! -d node_modules ]]; then
  echo "node_modules missing; run install first" >&2
  exit 1
fi

echo "Amazon API plugin environment ready"
