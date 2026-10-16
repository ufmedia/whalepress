<?php
/**
 * Front page, whether it shows a static page or the latest posts.
 *
 * The front-page.twig template handles both: a static front page gets its own
 * content plus the three latest posts; a posts front page gets the main
 * query. Delete front-page.twig to fall back to page.twig or index.twig.
 *
 * Editors control the hero through the page itself: its title, excerpt and
 * featured image. The hero button links to the posts page, labelled with that
 * page's title.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context = Timber::context();

$whalepress_posts_page = (int) get_option( 'page_for_posts' );
$whalepress_posts_url  = $whalepress_posts_page ? get_permalink( $whalepress_posts_page ) : false;

$context['posts_url']   = $whalepress_posts_url ? $whalepress_posts_url : null;
$context['posts_title'] = $whalepress_posts_url ? get_the_title( $whalepress_posts_page ) : null;

if ( 'page' === get_option( 'show_on_front' ) ) {
	$context['post']  = Timber::get_post();
	$context['posts'] = Timber::get_posts(
		array(
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	Timber::render( array( 'front-page.twig', 'page.twig' ), $context );
	return;
}

// Timber's context includes the first post of the main query; the template
// uses `post` to tell a static front page from a posts one.
$context['post']  = null;
$context['posts'] = Timber::get_posts();
Timber::render( array( 'front-page.twig', 'home.twig', 'index.twig' ), $context );
