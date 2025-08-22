<?php
/**
 * This file contains the Init class.
 *
 * @package WhalePress
 * @since   1.0.0
 */

namespace WhalePressTheme;

/**
 * Init class.
 *
 * This class is used to initialize the theme. For example, it is used to enqueue scripts and styles.
 *
 * @package WhalePress
 */
class Init {

	/**
	 * Construct
	 *
	 * Add hooks for theme initialization.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'whalepress_enqueue_scripts' ) );
		add_action( 'init', array( $this, 'whalepress_register_menus' ) );
				add_action( 'init', array( $this, 'whalepress_blocks' ) );
	}

	/**
	 * Enqueue theme assets.
	 *
	 * @return void
	 */
	public function whalepress_enqueue_scripts(): void {

		$app_version = getenv( 'APP_VERSION' ) ? getenv( 'APP_VERSION' ) : 'dev';

		if ( 'dev' !== $app_version ) {

			wp_enqueue_style( 'whalepress-styles', $this->whalepress_asset( 'index.css' ), null, $this->theme->get( 'Version' ) ); // Theme styles.
			wp_enqueue_script( 'whalepress-scripts', $this->whalepress_asset( 'index.js' ), null, $this->theme->get( 'Version' ), true ); // Theme scripts.

		}
	}

	/**
	 * Register Menus.
	 *
	 * @return void
	 */
	public function whalepress_register_menus(): void {
		register_nav_menu( 'main-menu', __( 'Main Menu' ) );
	}

	/**
	 * Get asset path.
	 *
	 * @param  string $path Path to asset.
	 * @return string
	 */
	protected function whalepress_asset( $path ) {
		if ( wp_get_environment_type() === 'production' ) {
			return get_stylesheet_directory_uri() . '/public/build/' . $path;
		}

		return add_query_arg( 'time', time(), get_stylesheet_directory_uri() . '/public/build/' . $path );
	}

	/**
	 * Register blocks.
	 *
	 * @return void
	 */
	public function whalepress_blocks(): void {
	}
}

new Init();
