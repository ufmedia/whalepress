<?php
/**
 * Theme entry point.
 *
 * Classes live in src/ and are autoloaded by the project's root Composer
 * autoloader, which wp-config.php loads. Add functionality by creating a
 * service in src/ and registering it in WhalePress\Theme::services().
 *
 * @package WhalePress
 */

declare(strict_types=1);

if ( ! class_exists( WhalePress\Theme::class ) || ! class_exists( Timber\Timber::class ) ) {
	// Not translated: this runs before translations can load.
	$whalepress_message = 'WhalePress: Composer dependencies are missing. Run `npm run composer -- install` from the project root.';

	if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		add_action(
			'admin_notices',
			static function () use ( $whalepress_message ): void {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $whalepress_message ) );
			}
		);
		return;
	}

	wp_die( esc_html( $whalepress_message ) );
}

( new WhalePress\Theme() )->boot();
