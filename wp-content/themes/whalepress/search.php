<?php
/**
 * Search results.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context = Timber::context();

/* translators: %s: search query. */
$context['title'] = sprintf( __( 'Search results for “%s”', 'whalepress' ), get_search_query() );
$context['posts'] = Timber::get_posts();

Timber::render( array( 'search.twig', 'archive.twig', 'index.twig' ), $context );
