<?php
/**
 * Fallback template, also used for the blog posts index.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context          = Timber::context();
$context['posts'] = Timber::get_posts();

if ( is_home() && ! is_front_page() ) {
	$context['title'] = single_post_title( '', false );
}

// Category links above the blog. Remove to drop the filter.
if ( is_home() ) {
	$context['terms']   = Timber::get_terms( array( 'taxonomy' => 'category' ) );
	$context['all_url'] = get_pagenum_link( 1 );
}

Timber::render( array( 'home.twig', 'index.twig' ), $context );
