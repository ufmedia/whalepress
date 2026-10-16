<?php
/**
 * Minimal stand-in for WordPress's WP_Block_Editor_Context, which unit tests
 * can't load. Mirrors the one property the theme reads.
 *
 * @package WhalePress
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_Block_Editor_Context' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, Generic.Files.OneObjectStructurePerFile.MultipleFound -- Stub of a core class.
	final class WP_Block_Editor_Context {

		/**
		 * Constructor.
		 *
		 * @param string $name Editor name, e.g. core/edit-post or core/edit-widgets.
		 */
		public function __construct( public string $name = 'core/edit-post' ) {}
	}
}
