<?php
/**
 * Plugin Name: WhalePress Hardening
 * Description: Security defaults that apply regardless of the active theme.
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 *
 * @package WhalePress
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/*
 * Remove version disclosure.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/*
 * Disable XML-RPC. Remove these lines if you rely on Jetpack or the mobile apps.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

/*
 * Hide the users REST endpoints from anonymous visitors to stop username
 * enumeration. Logged-in editors (and the block editor) are unaffected.
 */
add_filter(
	'rest_endpoints',
	static function ( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		foreach ( array_keys( $endpoints ) as $route ) {
			if ( str_starts_with( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}
);

/*
 * Block ?author=N enumeration for anonymous visitors.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_user_logged_in() || ! isset( $_GET['author'] ) || ! is_numeric( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	},
	1
);

/*
 * Don't reveal whether a username exists on failed logins.
 */
add_filter(
	'login_errors',
	static fn (): string => __( 'Invalid username or password.', 'whalepress' )
);

/*
 * Baseline security headers. HSTS and a Content-Security-Policy are
 * deliberately left out: set HSTS at the proxy, and add a CSP via the
 * filter once you know which third parties the site uses.
 */
add_action(
	'send_headers',
	static function (): void {
		$headers = array(
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options'        => 'SAMEORIGIN',
			'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
		);

		/**
		 * Filters the security headers sent with every front-end response.
		 *
		 * @param array<string, string> $headers Header name => value.
		 */
		$headers = apply_filters( 'whalepress/security_headers', $headers );

		foreach ( $headers as $name => $value ) {
			header( $name . ': ' . $value );
		}
	}
);
