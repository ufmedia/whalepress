<?php
/**
 * Theme supports, menus and image sizes.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Setup;

use WhalePress\Contracts\Hookable;
use WhalePress\Theme;

/**
 * Declares what the theme supports.
 */
final class ThemeSupport implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'add_theme_supports' ) );
		add_action( 'after_setup_theme', array( $this, 'add_image_sizes' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_menus' ) );
		add_action( 'widgets_init', array( $this, 'register_widget_areas' ) );
	}

	/**
	 * Declare theme supports.
	 *
	 * Full site editing is intentionally not supported: templates are Twig,
	 * and the block editor is used for post content only.
	 */
	public function add_theme_supports(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );

		// The front page hero shows the page's excerpt as its lead text.
		add_post_type_support( 'page', 'excerpt' );

		// Opts out of core's default editor typography; assets/css/editor.css replaces it.
		add_theme_support( 'editor-styles' );
		add_theme_support(
			'html5',
			array(
				'caption',
				'comment-form',
				'comment-list',
				'gallery',
				'navigation-widgets',
				'script',
				'search-form',
				'style',
			)
		);

		remove_theme_support( 'block-templates' );
		remove_theme_support( 'block-template-parts' );
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Register custom image sizes.
	 *
	 * Add sizes here, then reference them in Twig with
	 * `post.thumbnail.src('whalepress-hero')`.
	 */
	public function add_image_sizes(): void {
		add_image_size( 'whalepress-hero', 1920, 800, true );
	}

	/**
	 * Load theme translations from /languages.
	 */
	public function load_textdomain(): void {
		load_theme_textdomain( Theme::TEXT_DOMAIN, get_template_directory() . '/languages' );
	}

	/**
	 * Register navigation menu locations.
	 */
	public function register_menus(): void {
		register_nav_menus(
			array(
				'primary' => __( 'Primary navigation', 'whalepress' ),
				'footer'  => __( 'Footer navigation', 'whalepress' ),
			)
		);
	}

	/**
	 * Register widget areas.
	 *
	 * Widgets are edited with the block editor (Appearance > Widgets), limited
	 * to the same blocks as post content. Render one in Twig with
	 * `{{ function('dynamic_sidebar', 'footer') }}`.
	 */
	public function register_widget_areas(): void {
		register_sidebar(
			array(
				'id'            => 'footer',
				'name'          => __( 'Footer', 'whalepress' ),
				'description'   => __( 'Shown in the site footer, beside the site name. Use it for an address, opening hours or social links.', 'whalepress' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
}
