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

Timber::render( array( 'home.twig', 'index.twig' ), $context );
