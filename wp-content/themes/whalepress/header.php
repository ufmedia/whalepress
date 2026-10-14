<?php
/**
 * Compatibility shim for plugins that call get_header().
 *
 * Some plugins (WooCommerce, The Events Calendar, etc.) render their own
 * templates and call get_header()/get_footer(). This buffers their output so
 * footer.php can render it inside templates/page-plugin.twig. Delete this
 * file and footer.php if you don't use such plugins.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$GLOBALS['whalepress_plugin_context'] = Timber::context();

ob_start();
