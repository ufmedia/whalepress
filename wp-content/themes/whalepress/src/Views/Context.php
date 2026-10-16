<?php
/**
 * Global Timber context.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Views;

use Timber\Timber;
use WhalePress\Contracts\Hookable;

/**
 * Adds data available to every Twig template.
 *
 * Keep this lean: anything added here is computed on every request. Data for
 * a single template belongs in that template's PHP controller.
 */
final class Context implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
	}

	/**
	 * Add global data to the context.
	 *
	 * Timber already provides `site`, `user`, `body_class`, `request` and
	 * `theme` (see https://timber.github.io/docs/v2/guides/context/).
	 *
	 * @param array<string, mixed> $context Timber context.
	 * @return array<string, mixed>
	 */
	public function add_to_context( array $context ): array {
		$context['menus'] = array(
			'primary' => Timber::get_menu( 'primary' ),
			'footer'  => Timber::get_menu( 'footer' ),
		);

		// Rendered widget areas, keyed by ID; empty when an area has no widgets.
		$context['widgets'] = array(
			'footer' => is_active_sidebar( 'footer' ) ? (string) Timber::get_widgets( 'footer' ) : '',
		);

		$context['environment'] = wp_get_environment_type();

		return $context;
	}
}
