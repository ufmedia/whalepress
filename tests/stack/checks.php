<?php
/**
 * Database and object cache checks, run inside the WordPress container by
 * tests/stack/check.sh.
 *
 * Usage: wp eval-file - <token> < tests/stack/checks.php
 *
 * Stops with an error on the first failure. Leaves <token> in the object
 * cache so the caller can read it back from a second request.
 *
 * WP-CLI evaluates it as a string, so it can't declare strict_types.
 *
 * @package WhalePress
 */

global $wpdb;

$whalepress_token = $args[0] ?? '';
if ( '' === $whalepress_token ) {
	WP_CLI::error( 'Pass a token: wp eval-file - <token>' );
}

// Database: the server answers, and a write reads back. $wpdb is queried
// directly so the object cache can't stand in for the database.
$whalepress_version = $wpdb->get_var( 'SELECT VERSION()' );
if ( ! $whalepress_version ) {
	WP_CLI::error( 'Database: no connection. ' . $wpdb->last_error );
}

$wpdb->replace(
	$wpdb->options,
	array(
		'option_name'  => 'whalepress_stack_check',
		'option_value' => $whalepress_token,
		'autoload'     => 'off',
	)
);
$whalepress_read = $wpdb->get_var(
	$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'whalepress_stack_check' )
);
$wpdb->delete( $wpdb->options, array( 'option_name' => 'whalepress_stack_check' ) );

if ( $whalepress_read !== $whalepress_token ) {
	WP_CLI::error( 'Database: a write could not be read back. ' . $wpdb->last_error );
}
WP_CLI::success( "Database: connected to {$whalepress_version}; writes read back." );

// Object cache: the Redis drop-in is loaded and connected.
if ( ! wp_using_ext_object_cache() ) {
	WP_CLI::error( 'Redis: no persistent object cache. Is the drop-in installed (wp redis enable)?' );
}

$whalepress_cache = $GLOBALS['wp_object_cache'];
if ( ! method_exists( $whalepress_cache, 'redis_status' ) ) {
	WP_CLI::error( 'Redis: the object cache drop-in isn\'t the Redis Object Cache one.' );
}
if ( ! $whalepress_cache->redis_status() ) {
	WP_CLI::error( 'Redis: the drop-in is installed but not connected. Check REDIS_HOST.' );
}

if ( ! wp_cache_set( $whalepress_token, $whalepress_token, 'whalepress_stack_check', 300 ) ) {
	WP_CLI::error( 'Redis: writing to the cache failed.' );
}
WP_CLI::success( 'Redis: connected to Redis ' . $whalepress_cache->redis_version() . '.' );
