<?php
/**
 * Front page, whether it shows a static page or the latest posts.
 *
 * Add templates/front-page.twig for a bespoke homepage; otherwise a static
 * front page uses page.twig and a posts front page uses home.twig/index.twig.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context = Timber::context();

if ( 'page' === get_option( 'show_on_front' ) ) {
	$context['post'] = Timber::get_post();
	Timber::render( array( 'front-page.twig', 'page.twig' ), $context );
	return;
}

$context['posts'] = Timber::get_posts();
Timber::render( array( 'front-page.twig', 'home.twig', 'index.twig' ), $context );
