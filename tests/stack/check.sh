#!/usr/bin/env bash
#
# Checks that a running stack works end to end, beyond its containers being
# healthy. Runs on the host against the Compose project in the current
# directory (set COMPOSE_FILE for another one, e.g. docker-compose.test.yml).
#
#   tests/stack/check.sh services    WordPress, database, Redis, plus Mailpit
#                                    and Adminer when their URLs are set
#   tests/stack/check.sh debug-log   Fails on PHP errors, warnings, notices or
#                                    deprecations in wp-content/debug.log
#
# Reads from the environment, falling back to .env:
#   WP_HOME       Site URL.
#   WP_CLI_USER   Container user for WP-CLI (default: HOST_UID:HOST_GID, or
#                 www-data when those aren't set).
#   MAILPIT_URL   Mailpit's web UI, e.g. http://localhost:8025 (optional).
#   ADMINER_URL   Adminer, e.g. http://localhost:8080 (optional), logged in
#                 with WORDPRESS_DB_USER / WORDPRESS_DB_PASSWORD.

set -euo pipefail

cd "$(dirname "$0")/../.."

# Values already in the environment win over .env.
if [ -f .env ]; then
	while IFS='=' read -r key value; do
		if [[ $key =~ ^[A-Z0-9_]+$ ]] && [ -z "${!key:-}" ]; then
			export "$key=$value"
		fi
	done < .env
fi

: "${WP_HOME:?WP_HOME is required}"
if [ -z "${WP_CLI_USER:-}" ]; then
	if [ -n "${HOST_UID:-}" ]; then
		WP_CLI_USER="$HOST_UID:${HOST_GID:-$HOST_UID}"
	else
		WP_CLI_USER=www-data
	fi
fi

pass() { printf '\033[32m✔\033[0m %s\n' "$1"; }
fail() {
	printf '\033[31m✘\033[0m %s\n' "$1" >&2
	exit 1
}
wp() { docker compose exec -T -u "$WP_CLI_USER" wordpress wp "$@"; }

check_services() {
	local token
	token="stack-check-$(date +%s)-$RANDOM"

	# Database and Redis, checked from inside WordPress (tests/stack/checks.php).
	wp eval-file - "$token" < tests/stack/checks.php || fail 'WordPress checks failed (see above).'

	# A value cached by the previous WP-CLI process is only still there if
	# Redis is persisting the cache across requests.
	[ "$(wp cache get "$token" whalepress_stack_check 2>/dev/null)" = "$token" ] \
		|| fail 'Redis: a value cached by one request was gone in the next.'
	wp cache delete "$token" whalepress_stack_check > /dev/null
	pass 'Redis: the cache persists across requests.'

	# The site and REST API over HTTP, as a browser would reach them.
	local body
	body=$(curl -fsS "$WP_HOME/") || fail "HTTP: $WP_HOME/ didn't return a successful response."
	grep -q '</html>' <<< "$body" || fail "HTTP: $WP_HOME/ returned an incomplete page."
	curl -fsS "$WP_HOME/wp-json/" | jq -e '.name' > /dev/null \
		|| fail "HTTP: the REST API at $WP_HOME/wp-json/ didn't respond."
	pass "HTTP: the site and REST API respond at $WP_HOME."

	if [ -n "${MAILPIT_URL:-}" ]; then
		wp eval "wp_mail( 'stack-check@example.test', '$token', 'Sent by tests/stack/check.sh.' ) || exit( 1 );" \
			|| fail 'Mail: wp_mail() failed. Check SMTP_HOST and SMTP_PORT.'
		local found=0
		for _ in $(seq 10); do
			found=$(curl -fsS "$MAILPIT_URL/api/v1/search?query=subject:$token" | jq '.messages_count')
			[ "$found" -gt 0 ] && break
			sleep 1
		done
		[ "$found" -gt 0 ] || fail "Mail: the message never reached Mailpit at $MAILPIT_URL."
		curl -fsS -X DELETE "$MAILPIT_URL/api/v1/search?query=subject:$token" > /dev/null || true
		pass 'Mail: wp_mail() delivers to Mailpit.'
	fi

	if [ -n "${ADMINER_URL:-}" ]; then
		local jar page csrf
		jar=$(mktemp)
		page=$(curl -fsS -c "$jar" -b "$jar" "$ADMINER_URL/") || fail "Adminer: no response at $ADMINER_URL."
		# The login form's CSRF token (the first hidden token on the page).
		csrf=$(grep -oE "name='token' value='[^']+'" <<< "$page" | head -n 1 | cut -d"'" -f4)
		page=$(curl -fsS -L -c "$jar" -b "$jar" "$ADMINER_URL/" \
			--data-urlencode "token=$csrf" \
			--data-urlencode 'auth[driver]=server' \
			--data-urlencode 'auth[server]=db' \
			--data-urlencode "auth[username]=${WORDPRESS_DB_USER:-wordpress}" \
			--data-urlencode "auth[password]=${WORDPRESS_DB_PASSWORD:-wordpress}" \
			--data-urlencode "auth[db]=${WORDPRESS_DB_NAME:-wordpress}")
		rm -f "$jar"
		grep -q "${WORDPRESS_TABLE_PREFIX:-wp_}options" <<< "$page" \
			|| fail 'Adminer: logging in to the database failed, or WordPress tables were missing.'
		pass 'Adminer: logs in to the database and lists the WordPress tables.'
	fi
}

check_debug_log() {
	local problems
	problems=$(docker compose exec -T wordpress sh -c 'cat wp-content/debug.log 2> /dev/null || true' \
		| grep -E 'PHP (Fatal error|Parse error|Warning|Notice|Deprecated)' || true)
	if [ -n "$problems" ]; then
		printf '%s\n' "$problems" >&2
		fail "debug.log: $(wc -l <<< "$problems" | tr -d ' ') PHP error(s), warning(s), notice(s) or deprecation(s)."
	fi
	pass 'debug.log: no PHP errors, warnings, notices or deprecations.'
}

case "${1:-}" in
	services) check_services ;;
	debug-log) check_debug_log ;;
	*)
		echo "Usage: $0 services|debug-log" >&2
		exit 2
		;;
esac
