<?php
/**
 * WordPress Configuration File
 *
 * This file contains the following configurations:
 * - Database settings
 * - Security keys and salts
 * - Cache and Redis configuration
 * - Debug settings
 * - Custom WordPress settings
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 * @package WordPress
 */

// ==============================================================================
// HELPER FUNCTIONS
// ==============================================================================

/**
 * Helper function to lookup "env_FILE", "env", then fallback to default
 * Supports Docker secrets and environment variables
 * @link https://github.com/docker-library/wordpress/issues/588
 */
if ( ! function_exists( 'getenv_docker' ) ) {
function getenv_docker( $env, $default ) {
if ( $fileEnv = getenv( $env . '_FILE' ) ) {
return rtrim( file_get_contents( $fileEnv ), "\r\n" );
} elseif ( ( $val = getenv( $env ) ) !== false ) {
return $val;
} else {
return $default;
}
}
}

// ==============================================================================
// DATABASE CONFIGURATION
// ==============================================================================

/** The name of the database for WordPress */
define( 'DB_NAME', getenv_docker( 'WORDPRESS_DB_NAME', '' ) );

/** MySQL database username */
define( 'DB_USER', getenv_docker( 'WORDPRESS_DB_USER', '' ) );

/** MySQL database password */
define( 'DB_PASSWORD', getenv_docker( 'WORDPRESS_DB_PASSWORD', 'wordpress' ) );

/** MySQL hostname */
define( 'DB_HOST', getenv_docker( 'WORDPRESS_DB_HOST', 'mysql' ) );

/** Database charset to use in creating database tables */
define( 'DB_CHARSET', getenv_docker( 'WORDPRESS_DB_CHARSET', 'utf8' ) );

/** The database collate type. Don't change this if in doubt */
define( 'DB_COLLATE', getenv_docker( 'WORDPRESS_DB_COLLATE', '' ) );

/** WordPress database table prefix */
$table_prefix = getenv_docker( 'WORDPRESS_TABLE_PREFIX', 'wp_' );
define( 'WORDPRESS_TABLE_PREFIX', $table_prefix);

// ==============================================================================
// AUTHENTICATION KEYS AND SALTS
// ==============================================================================

/**
 * Authentication unique keys and salts
 * Change these to different unique phrases to invalidate all existing cookies
 * @link https://api.wordpress.org/secret-key/1.1/salt/
 */
define( 'AUTH_KEY',         getenv_docker( 'WORDPRESS_AUTH_KEY',         '8f78595c97c8ebe51f29a46f3039ca53cc5c0332' ) );
define( 'SECURE_AUTH_KEY',  getenv_docker( 'WORDPRESS_SECURE_AUTH_KEY',  '792cada582e82e1dd3d0a764eca292945cb71c50' ) );
define( 'LOGGED_IN_KEY',    getenv_docker( 'WORDPRESS_LOGGED_IN_KEY',    '4d7c68af5bc4b2edb0a0d54ca6b8a1537aed4f5c' ) );
define( 'NONCE_KEY',        getenv_docker( 'WORDPRESS_NONCE_KEY',        '4ca01728bd16895f654db6040ece645526d2cc22' ) );
define( 'AUTH_SALT',        getenv_docker( 'WORDPRESS_AUTH_SALT',        'aa272302742130153c8177759daf0727c789b1af' ) );
define( 'SECURE_AUTH_SALT', getenv_docker( 'WORDPRESS_SECURE_AUTH_SALT', '025358a4b68dc2c30eb4a8c60a5de4e13d5b626c' ) );
define( 'LOGGED_IN_SALT',   getenv_docker( 'WORDPRESS_LOGGED_IN_SALT',   '6a7cb5020362ebcb5db4698e1a568fc451f6fdc5' ) );
define( 'NONCE_SALT',       getenv_docker( 'WORDPRESS_NONCE_SALT',       '0c3c3df70f7e3b7db856683f672d002824443569' ) );

// ==============================================================================
// REDIS AND CACHING CONFIGURATION
// ==============================================================================

/** Redis password and URL for object caching */
define( 'WP_REDIS_PASSWORD', getenv( 'REDIS_PASSWORD' ) );
define( 'WP_REDIS_HOST',      getenv( 'REDIS_URL' ) );

/** Enable WordPress object cache */
define( 'WP_CACHE', true );

/** Redis cache key salt for this site */
define( 'WP_CACHE_KEY_SALT', 'ottersurfboards.co.uk:' );

// ==============================================================================
// DEBUGGING AND DEVELOPMENT SETTINGS
// ==============================================================================

/** WordPress environment type (local, development, staging, production) */
define( 'WP_ENVIRONMENT_TYPE', getenv_docker( 'WP_ENVIRONMENT_TYPE', 'local' ) );

/** Enable WordPress debugging mode */
define( 'WP_DEBUG', ! ! getenv_docker( 'WP_DEBUG', '' ) );

/** Log errors to wp-content/debug.log */
define( 'WP_DEBUG_LOG', ! ! getenv_docker( 'WP_DEBUG_LOG', '' ) );

/** Display errors on screen (disable in production) */
define( 'WP_DEBUG_DISPLAY', ! ! getenv_docker( 'WP_DEBUG_DISPLAY', '' ) );

/** Disable the fatal error handler */
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', ! ! getenv_docker( 'WP_DISABLE_FATAL_ERROR_HANDLER', '' ) );

/** Disable script concatenation for debugging */
define( 'CONCATENATE_SCRIPTS', true );

/** Enable script debugging (loads unminified versions) */
define( 'SCRIPT_DEBUG', false );

// ==============================================================================
// WORDPRESS CUSTOM SETTINGS
// ==============================================================================

/** Increase PHP memory limit for WordPress */
define( 'WP_MEMORY_LIMIT', '1024M' );

/** Set filesystem method to direct (required for Docker) */
define( 'FS_METHOD', 'direct' );

/** Enable WordPress Multisite */
define( 'WP_ALLOW_MULTISITE', ! ! getenv_docker( 'WP_ALLOW_MULTISITE', '' ) );

// ==============================================================================
// THIRD-PARTY SERVICE CONFIGURATION
// ==============================================================================

/** Sentry error tracking - Browser DSN */
define( 'WP_SENTRY_BROWSER_DSN', '' );

/** Sentry error tracking - PHP DSN */
define( 'WP_SENTRY_PHP_DSN', '' );

/** JWT authentication secret key */
define( 'JWT_AUTH_SECRET_KEY', '' );

// ==============================================================================
// REVERSE PROXY AND SSL CONFIGURATION
// ==============================================================================

/**
 * Handle HTTPS behind a reverse proxy
 * Common in container environments (Docker, Kubernetes, etc.)
 * @link https://wordpress.org/support/article/administration-over-ssl/#using-a-reverse-proxy
 */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strpos( $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) !== false ) {
$_SERVER['HTTPS'] = 'on';
}

// ==============================================================================
// DYNAMIC CONFIGURATION
// ==============================================================================

/** Allow additional configuration via environment variable */
if ( $configExtra = getenv_docker( 'WORDPRESS_CONFIG_EXTRA', '' ) ) {
eval( $configExtra );
}

// ==============================================================================
// WORDPRESS BOOTSTRAP
// ==============================================================================

/** Absolute path to the WordPress directory */
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files */
require_once ABSPATH . 'wp-settings.php';
