#!/usr/bin/env bash
# orion_import_sku.sh — mirrors lipseys_import_sku.sh
set -euo pipefail

: "${SKU:?Usage: SKU=XXXXX OR: ./orion_import_sku.sh ORION-ABC123}"
if [ -z "${SKU:-}" ] && [ -n "${1:-}" ]; then SKU="$1"; fi

WP_CONTAINER="${WP_CONTAINER:-$(docker ps --format '{{.Names}}\t{{.Image}}' | awk '/wordpress/{print $1; exit}')}"
[ -z "$WP_CONTAINER" ] && { echo "WP container not found"; exit 2; }

docker exec -it "$WP_CONTAINER" sh -lc       "ORION_API_BASE=\${ORION_API_BASE:-$ORION_API_BASE}        ORION_API_KEY=\${ORION_API_KEY:-$ORION_API_KEY}        ORION_API_SECRET=\${ORION_API_SECRET:-$ORION_API_SECRET}        wp --allow-root neefeco import $SKU --source=orion --featured"
