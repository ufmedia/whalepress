<?php
/**
 * Not found.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Timber\Timber;

Timber::render( '404.twig', Timber::context() );
