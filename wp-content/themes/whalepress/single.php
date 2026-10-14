<?php
/**
 * Single post of any post type.
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

if ( post_password_required( $timber_post->ID ) ) {
	Timber::render( 'single-password.twig', $context );
	return;
}

Timber::render(
	array(
		'single-' . $timber_post->post_type . '-' . $timber_post->slug . '.twig',
		'single-' . $timber_post->post_type . '.twig',
		'single.twig',
	),
	$context
);
