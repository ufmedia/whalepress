#!/usr/bin/env bash
# Runs once inside the devcontainer after it is created.
set -euo pipefail
cd /workspace

# Work as the host user so files stay writable outside the container.
as_host() { setpriv --reuid="${HOST_UID:-1000}" --regid="${HOST_GID:-1000}" --clear-groups env HOME=/tmp "$@"; }

as_host composer install --no-interaction
as_host npm ci
as_host npm ci --prefix wp-content/themes/whalepress
as_host npm run build

# A local-only fallback password if .env didn't set one.
export WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-password}"
as_host bash local/bin/install.sh

echo "WordPress admin: ${WP_ADMIN_USER:-admin} / ${WP_ADMIN_PASSWORD}"
