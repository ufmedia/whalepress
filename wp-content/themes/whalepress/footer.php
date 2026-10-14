<?php
/**
 * Compatibility shim for plugins that call get_footer(). See header.php.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$whalepress_context = $GLOBALS['whalepress_plugin_context'] ?? null;

if ( ! is_array( $whalepress_context ) ) {
	return;
}

$whalepress_context['content'] = (string) ob_get_clean();

Timber::render( 'page-plugin.twig', $whalepress_context );
