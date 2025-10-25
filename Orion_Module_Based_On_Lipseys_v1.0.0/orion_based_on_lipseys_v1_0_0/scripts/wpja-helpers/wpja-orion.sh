#!/usr/bin/env bash
# wpja-orion.sh — mirrors wpja-lipseys.sh, just pins --source=orion
set -euo pipefail
WP_CONTAINER="${WP_CONTAINER:-$(docker ps --format '{{.Names}}\t{{.Image}}' | awk '/wordpress/{print $1; exit}')}"
[ -z "${WP_CONTAINER}" ] && { echo "WP container not found"; exit 1; }

cmd="$*"
docker exec -it "$WP_CONTAINER" sh -lc "wp --allow-root ${cmd} --source=orion"
