<?php
/**
 * Custom post types.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Content;

use WhalePress\Contracts\Hookable;

/**
 * Registers custom post types.
 *
 * Add definitions to definitions(), keyed by post type name. For example:
 *
 *     'project' => array(
 *         'labels'       => array(
 *             'name'          => __( 'Projects', 'whalepress' ),
 *             'singular_name' => __( 'Project', 'whalepress' ),
 *         ),
 *         'public'       => true,
 *         'has_archive'  => true,
 *         'show_in_rest' => true, // Required for the block editor.
 *         'menu_icon'    => 'dashicons-portfolio',
 *         'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
 *         'rewrite'      => array( 'slug' => 'projects' ),
 *     ),
 *
 * Templates then resolve as single-project.twig and archive-project.twig.
 * Flush permalinks after adding a type: `npm run wp -- rewrite flush`.
 */
final class PostTypes implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	/**
	 * Register every defined post type.
	 */
	public function register_post_types(): void {
		foreach ( $this->definitions() as $post_type => $args ) {
			register_post_type( $post_type, $args );
		}
	}

	/**
	 * Post type definitions.
	 *
	 * @return array<non-empty-lowercase-string, array<string, mixed>>
	 */
	private function definitions(): array {
		return array();
	}
}
