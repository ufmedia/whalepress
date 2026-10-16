<?php
/**
 * Archives: categories, tags, taxonomies, dates and post type archives.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context                = Timber::context();
$context['title']       = get_the_archive_title();
$context['description'] = get_the_archive_description();
$context['posts']       = Timber::get_posts();

// On category archives, the same category links as the blog.
if ( is_category() ) {
	$whalepress_posts_page = (int) get_option( 'page_for_posts' );

	$context['terms']   = Timber::get_terms( array( 'taxonomy' => 'category' ) );
	$context['current'] = get_queried_object_id();
	$context['all_url'] = $whalepress_posts_page ? get_permalink( $whalepress_posts_page ) : home_url( '/' );
}

$templates = array( 'archive.twig', 'index.twig' );

if ( is_post_type_archive() ) {
	array_unshift( $templates, 'archive-' . get_post_type() . '.twig' );
} elseif ( is_category() || is_tag() || is_tax() ) {
	$queried_term = get_queried_object();
	if ( $queried_term instanceof WP_Term ) {
		array_unshift( $templates, 'archive-' . $queried_term->taxonomy . '.twig' );
	}
}

Timber::render( $templates, $context );
