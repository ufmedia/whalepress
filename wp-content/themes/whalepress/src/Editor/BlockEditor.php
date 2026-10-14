<?php
/**
 * Block editor configuration.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Editor;

use WhalePress\Contracts\Hookable;

/**
 * Configures the block editor for editing post content only.
 *
 * Layout and templates belong to Twig, so the editor is limited to a curated
 * set of content blocks and design presets come from theme.json.
 */
final class BlockEditor implements Hookable {

	/**
	 * Blocks available to editors. Filter with `whalepress/allowed_blocks`.
	 */
	public const array ALLOWED_BLOCKS = array(
		'core/audio',
		'core/button',
		'core/buttons',
		'core/code',
		'core/column',
		'core/columns',
		'core/details',
		'core/embed',
		'core/file',
		'core/footnotes',
		'core/gallery',
		'core/group',
		'core/heading',
		'core/html',
		'core/image',
		'core/list',
		'core/list-item',
		'core/media-text',
		'core/paragraph',
		'core/preformatted',
		'core/pullquote',
		'core/quote',
		'core/separator',
		'core/shortcode',
		'core/spacer',
		'core/table',
		'core/video',
	);

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'allowed_block_types_all', array( $this, 'allowed_block_types' ) );
		add_filter( 'block_editor_settings_all', array( $this, 'editor_settings' ) );
		add_filter( 'should_load_remote_block_patterns', '__return_false' );

		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}

	/**
	 * Restrict the inserter to content blocks.
	 *
	 * @param bool|list<string> $allowed Blocks allowed so far (ignored: the list is authoritative).
	 * @return list<string>
	 */
	public function allowed_block_types( bool|array $allowed ): array {
		/**
		 * Filters the blocks available in the editor.
		 *
		 * @param list<string> $blocks Block names.
		 */
		return apply_filters( 'whalepress/allowed_blocks', self::ALLOWED_BLOCKS );
	}

	/**
	 * Turn off editor features that conflict with a Twig-driven layout.
	 *
	 * @param array<string, mixed> $settings Editor settings.
	 * @return array<string, mixed>
	 */
	public function editor_settings( array $settings ): array {
		$settings['fontLibraryEnabled']           = false;
		$settings['enableOpenverseMediaCategory'] = false;

		return $settings;
	}
}
