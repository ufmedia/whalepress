<?php
/**
 * Plugin Name: WhalePress Environment
 * Description: Environment-specific behaviour: noindex outside production and an uploads proxy for local development.
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 *
 * @package WhalePress
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/*
 * Discourage search engines everywhere except production, regardless of the
 * "Search engine visibility" setting stored in the database.
 */
if ( 'production' !== wp_get_environment_type() ) {
	add_filter( 'pre_option_blog_public', static fn (): string => '0' );
}

/*
 * Uploads proxy.
 *
 * When UPLOADS_PROXY_URL is set (e.g. https://example.com) in a local or
 * development environment, requests for missing files under the uploads
 * directory are redirected to the same path on that site. This lets you work
 * with a production database without downloading the media library.
 */
$whalepress_uploads_proxy = function_exists( 'whalepress_env' ) ? rtrim( whalepress_env( 'UPLOADS_PROXY_URL' ), '/' ) : '';

if ( '' !== $whalepress_uploads_proxy && in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	add_action(
		'init',
		static function () use ( $whalepress_uploads_proxy ): void {
			$request_path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$uploads_path = (string) wp_parse_url( wp_get_upload_dir()['baseurl'], PHP_URL_PATH );

			if ( '' === $uploads_path || ! str_starts_with( $request_path, $uploads_path . '/' ) ) {
				return;
			}

			// The file exists locally, so Apache would have served it; nothing to do.
			if ( file_exists( ABSPATH . ltrim( $request_path, '/' ) ) ) {
				return;
			}

			wp_redirect( $whalepress_uploads_proxy . $request_path, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Proxy target is trusted configuration.
			exit;
		},
		0
	);
}//end if

unset( $whalepress_uploads_proxy );
