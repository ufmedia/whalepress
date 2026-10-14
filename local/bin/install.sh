#!/usr/bin/env bash
#
# Idempotent WordPress install and configuration. Runs inside the wordpress
# container as the Apache user:
#
#   docker compose exec -T -u "$HOST_UID:$HOST_GID" wordpress bash -s < local/bin/install.sh
#
# Reads WP_HOME, WP_ADMIN_USER, WP_ADMIN_PASSWORD, WP_ADMIN_EMAIL, THEME_NAME
# and REDIS_HOST from the container environment.

set -euo pipefail

: "${WP_HOME:?WP_HOME is required}"
: "${WP_ADMIN_PASSWORD:?WP_ADMIN_PASSWORD is required}"

if wp core is-installed 2>/dev/null; then
	echo "WordPress is already installed; skipping install."
else
	wp core install \
		--url="$WP_HOME" \
		--title="${SITE_TITLE:-WhalePress}" \
		--admin_user="${WP_ADMIN_USER:-admin}" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="${WP_ADMIN_EMAIL:-admin@example.com}" \
		--skip-email

	wp rewrite structure '/%postname%/'
	wp theme activate "${THEME_NAME:-whalepress}"
fi

# Starter navigation so the header has something to render.
if ! wp menu list --fields=locations --format=csv | grep -qw primary; then
	wp menu create 'Primary' 2>/dev/null || true
	wp menu location assign primary primary
	wp menu item add-custom primary 'Home' "$WP_HOME/"
	about_id=$(wp post create --post_type=page --post_status=publish --post_title='About' --porcelain)
	wp menu item add-post primary "$about_id"
fi

# Activate everything Composer installed (includes dev-only plugins locally).
# The image's entrypoint seeds Akismet and Hello Dolly into wp-content; skip them.
wp plugin activate --all --exclude=akismet,hello

if [ -n "${REDIS_HOST:-}" ] && wp plugin is-active redis-cache; then
	wp redis enable --force || echo "Could not enable the Redis object cache."
fi

wp rewrite flush
echo "WordPress is ready at $WP_HOME"
