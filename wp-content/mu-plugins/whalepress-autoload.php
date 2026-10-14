<?php
/**
 * Plugin Name: WhalePress Autoload
 * Description: Loads Composer's autoloader on hosts whose wp-config.php doesn't (e.g. Hostinger).
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 *
 * The project's wp-config.php already loads vendor/autoload.php; this is a
 * fallback for managed hosts that use their own wp-config.php. vendor/ must
 * sit beside wp-content (as in the repository) for the theme's PSR-4 paths to
 * resolve. Must-use plugins load before themes, so the theme can rely on it.
 *
 * @package WhalePress
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( is_readable( ABSPATH . 'vendor/autoload.php' ) ) {
	require_once ABSPATH . 'vendor/autoload.php';
}
