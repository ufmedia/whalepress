<?php
/**
 * Block style variations.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Editor;

use WhalePress\Contracts\Hookable;

/**
 * Registers extra styles for core blocks (the "Styles" panel in the editor).
 *
 * Each style adds an `is-style-{name}` class to the block. The CSS lives in
 * assets/css/content.css, so it applies in the editor and on the front end.
 * Add, rename or remove styles here and in the stylesheet together.
 */
final class BlockStyles implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_styles' ) );
	}

	/**
	 * Register every style.
	 */
	public function register_styles(): void {
		foreach ( $this->styles() as $block => $styles ) {
			foreach ( $styles as $name => $label ) {
				register_block_style(
					$block,
					array(
						'name'  => $name,
						'label' => $label,
					)
				);
			}
		}
	}

	/**
	 * Styles, keyed by block name and then style name.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function styles(): array {
		/**
		 * Filters the block styles the theme registers.
		 *
		 * @param array<string, array<string, string>> $styles Style labels, keyed by block name and style name.
		 */
		return apply_filters(
			'whalepress/block_styles',
			array(
				'core/group'     => array(
					'card' => __( 'Card', 'whalepress' ),
				),
				'core/column'    => array(
					'card' => __( 'Card', 'whalepress' ),
				),
				'core/paragraph' => array(
					'lead'    => __( 'Lead', 'whalepress' ),
					'eyebrow' => __( 'Eyebrow', 'whalepress' ),
				),
				'core/list'      => array(
					'plain' => __( 'Plain', 'whalepress' ),
				),
			)
		);
	}
}
