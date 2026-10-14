<?php
/**
 * Author archive.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

$context           = Timber::context();
$author            = Timber::get_user( get_queried_object_id() );
$context['author'] = $author;
$context['title']  = $author ? $author->name() : get_the_archive_title();
$context['posts']  = Timber::get_posts();

Timber::render( array( 'author.twig', 'archive.twig', 'index.twig' ), $context );
