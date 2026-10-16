<?php
/**
 * Theme bootstrap.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress;

use Timber\Timber;
use WhalePress\Assets\ColorScheme;
use WhalePress\Assets\Vite;
use WhalePress\Contracts\Hookable;

/**
 * Boots Timber and registers every theme service.
 *
 * To add functionality, create a class implementing Hookable and add it to
 * services(). Keep each service focused on one concern.
 */
final class Theme {

	public const string TEXT_DOMAIN = 'whalepress';

	/**
	 * Boot the theme.
	 */
	public function boot(): void {
		Timber::init();
		Timber::$dirname = array( 'templates' );

		foreach ( $this->services() as $service ) {
			$service->register();
		}
	}

	/**
	 * The services that make up the theme.
	 *
	 * @return list<Hookable>
	 */
	private function services(): array {
		$services = array(
			new Setup\ThemeSupport(),
			new Setup\Cleanup(),
			new Vite( get_theme_file_path(), get_theme_file_uri(), wp_get_environment_type() ),
			new ColorScheme(),
			new Editor\BlockEditor(),
			new Editor\BlockStyles(),
			new Content\PostTypes(),
			new Content\Taxonomies(),
			new Views\Context(),
			new Views\Twig( get_theme_file_path( 'assets/icons' ) ),
			new Views\Forms(),
		);

		/**
		 * Filters the theme services before they are registered.
		 *
		 * @param list<Hookable> $services Service instances.
		 */
		return apply_filters( 'whalepress/services', $services );
	}
}
