<?php
/**
 * WordPress configuration.
 *
 * Every value comes from the environment so the same file works locally (via
 * .env and Docker Compose) and in production images. Each variable can also
 * be supplied as a Docker secret by pointing VARIABLE_FILE at a file path.
 *
 * See docs/building/wordpress.md for the full list of variables.
 *
 * @package WhalePress
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- $table_prefix is a core global.

/**
 * Read an environment variable, preferring a *_FILE secret. Empty values are
 * treated as unset.
 *
 * @link https://github.com/docker-library/wordpress/issues/588
 *
 * @param string $name     Variable name.
 * @param string $fallback Value used when the variable is not set.
 */
function whalepress_env( string $name, string $fallback = '' ): string {
	$file = getenv( $name . '_FILE' );
	if ( false !== $file && is_readable( $file ) ) {
		return rtrim( (string) file_get_contents( $file ), "\r\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	$value = getenv( $name );

	return false === $value || '' === $value ? $fallback : $value;
}

/**
 * Read a boolean environment variable ("1", "true", "yes", "on").
 *
 * @param string $name     Variable name.
 * @param bool   $fallback Value used when the variable is not set.
 */
function whalepress_env_bool( string $name, bool $fallback = false ): bool {
	$value = whalepress_env( $name );

	return '' === $value ? $fallback : filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

// ==============================================================================
// COMPOSER
// ==============================================================================

/*
 * vendor/ sits beside wp-content, as in the repository, so the autoloader's
 * relative paths to the theme resolve. Apache denies HTTP access to it.
 */
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// ==============================================================================
// ENVIRONMENT
// ==============================================================================

/** One of: local, development, staging, production. */
define( 'WP_ENVIRONMENT_TYPE', whalepress_env( 'WP_ENVIRONMENT_TYPE', 'production' ) );

$whalepress_is_dev = in_array( WP_ENVIRONMENT_TYPE, array( 'local', 'development' ), true );

/*
 * 'theme' stops WordPress caching the theme's patterns folder and theme.json,
 * so changes show up without flushing the object cache.
 */
define( 'WP_DEVELOPMENT_MODE', whalepress_env( 'WP_DEVELOPMENT_MODE', $whalepress_is_dev ? 'theme' : '' ) );

// ==============================================================================
// DATABASE
// ==============================================================================

define( 'DB_NAME', whalepress_env( 'WORDPRESS_DB_NAME', 'wordpress' ) );
define( 'DB_USER', whalepress_env( 'WORDPRESS_DB_USER', 'wordpress' ) );
define( 'DB_PASSWORD', whalepress_env( 'WORDPRESS_DB_PASSWORD' ) );
define( 'DB_HOST', whalepress_env( 'WORDPRESS_DB_HOST', 'db' ) );
define( 'DB_CHARSET', whalepress_env( 'WORDPRESS_DB_CHARSET', 'utf8mb4' ) );
define( 'DB_COLLATE', whalepress_env( 'WORDPRESS_DB_COLLATE' ) );

$table_prefix = whalepress_env( 'WORDPRESS_TABLE_PREFIX', 'wp_' );

// ==============================================================================
// AUTHENTICATION KEYS AND SALTS
// ==============================================================================

/*
 * Set these in the environment (`npm run setup` generates them locally). If
 * any are left empty, WordPress generates random values and stores them in
 * the database instead, so there is never a shared, committed fallback.
 */
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $whalepress_key ) {
	define( $whalepress_key, whalepress_env( 'WORDPRESS_' . $whalepress_key ) );
}

// ==============================================================================
// URLS
// ==============================================================================

/*
 * Behind a TLS-terminating proxy (Traefik, a load balancer, App Platform),
 * trust X-Forwarded-Proto so WordPress generates https URLs.
 */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && str_contains( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$_SERVER['HTTPS'] = 'on';
}

if ( '' !== whalepress_env( 'WP_HOME' ) ) {
	define( 'WP_HOME', rtrim( whalepress_env( 'WP_HOME' ), '/' ) );
	define( 'WP_SITEURL', WP_HOME );
}

// ==============================================================================
// DEBUGGING
// ==============================================================================

define( 'WP_DEBUG', whalepress_env_bool( 'WP_DEBUG' ) );
define( 'WP_DEBUG_LOG', whalepress_env_bool( 'WP_DEBUG_LOG' ) );
define( 'WP_DEBUG_DISPLAY', whalepress_env_bool( 'WP_DEBUG_DISPLAY' ) );
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', whalepress_env_bool( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) );
define( 'SCRIPT_DEBUG', whalepress_env_bool( 'SCRIPT_DEBUG' ) );

// ==============================================================================
// OBJECT CACHE (REDIS)
// ==============================================================================

/*
 * Used by the Redis Object Cache plugin. Leave REDIS_HOST empty to disable
 * persistent object caching.
 */
define( 'WP_REDIS_HOST', whalepress_env( 'REDIS_HOST' ) );
define( 'WP_REDIS_PORT', (int) whalepress_env( 'REDIS_PORT', '6379' ) );
define( 'WP_REDIS_PASSWORD', whalepress_env( 'REDIS_PASSWORD' ) ?: null ); // phpcs:ignore Universal.Operators.DisallowShortTernary
define( 'WP_REDIS_PREFIX', whalepress_env( 'REDIS_PREFIX', DB_NAME . '_' . $table_prefix ) );
define( 'WP_REDIS_DISABLED', '' === WP_REDIS_HOST );

// ==============================================================================
// UPDATES, FILE MODIFICATION AND CRON
// ==============================================================================

/*
 * Production images are immutable: core comes from the base image and
 * plugins from Composer, so updates and installs from wp-admin are disabled
 * outside local and development environments.
 */
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', whalepress_env_bool( 'DISALLOW_FILE_MODS', ! $whalepress_is_dev ) );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'FS_METHOD', 'direct' );

/** Set to true when a system cron runs `wp cron event run --due-now`. */
define( 'DISABLE_WP_CRON', whalepress_env_bool( 'DISABLE_WP_CRON' ) );

// ==============================================================================
// PERFORMANCE AND CONTENT
// ==============================================================================

define( 'WP_MEMORY_LIMIT', whalepress_env( 'WP_MEMORY_LIMIT', '256M' ) );
define( 'WP_MAX_MEMORY_LIMIT', whalepress_env( 'WP_MAX_MEMORY_LIMIT', '512M' ) );
define( 'WP_POST_REVISIONS', (int) whalepress_env( 'WP_POST_REVISIONS', '10' ) );
define( 'WP_ALLOW_MULTISITE', whalepress_env_bool( 'WP_ALLOW_MULTISITE' ) );

/** Set to true only if a page cache plugin provides advanced-cache.php. */
define( 'WP_CACHE', whalepress_env_bool( 'WP_CACHE' ) );

unset( $whalepress_is_dev, $whalepress_key );

// ==============================================================================
// BOOTSTRAP
// ==============================================================================

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
