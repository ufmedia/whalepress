<?php
/**
 * Custom taxonomies.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Content;

use WhalePress\Contracts\Hookable;

/**
 * Registers custom taxonomies.
 *
 * Add definitions to definitions(), keyed by taxonomy name. For example:
 *
 *     'project_type' => array(
 *         'object_type' => array( 'project' ),
 *         'args'        => array(
 *             'labels'            => array(
 *                 'name'          => __( 'Project types', 'whalepress' ),
 *                 'singular_name' => __( 'Project type', 'whalepress' ),
 *             ),
 *             'hierarchical'      => true,
 *             'show_in_rest'      => true,
 *             'show_admin_column' => true,
 *         ),
 *     ),
 */
final class Taxonomies implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register every defined taxonomy.
	 */
	public function register_taxonomies(): void {
		foreach ( $this->definitions() as $taxonomy => $definition ) {
			register_taxonomy( $taxonomy, $definition['object_type'], $definition['args'] );
		}
	}

	/**
	 * Taxonomy definitions.
	 *
	 * @return array<non-empty-lowercase-string, array{object_type: list<string>, args: array<string, mixed>}>
	 */
	private function definitions(): array {
		return array();
	}
}
