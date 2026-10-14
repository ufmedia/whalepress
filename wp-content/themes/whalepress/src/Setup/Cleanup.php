<?php
/**
 * Front-end output cleanup.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Setup;

use WhalePress\Contracts\Hookable;

/**
 * Removes legacy and unnecessary markup from wp_head.
 *
 * Security-related cleanup (generator tag, XML-RPC, etc.) lives in the
 * whalepress-hardening must-use plugin so it applies regardless of theme.
 */
final class Cleanup implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'remove_emoji' ) );

		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}

	/**
	 * Remove the emoji detection script and styles.
	 */
	public function remove_emoji(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}
}
