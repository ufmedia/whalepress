<?php
/**
 * Single page.
 *
 * Resolves page-{slug}.twig before page.twig, so a page-specific view needs
 * only a Twig file. Add a page-{slug}.php controller when it also needs data.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context     = Timber::context();
$timber_post = Timber::get_post();

if ( null === $timber_post ) {
	Timber::render( '404.twig', $context );
	return;
}

$context['post'] = $timber_post;

Timber::render( array( 'page-' . $timber_post->slug . '.twig', 'page.twig' ), $context );
