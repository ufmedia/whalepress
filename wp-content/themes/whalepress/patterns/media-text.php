<?php
/**
 * Title: Text beside image
 * Slug: whalepress/media-text
 * Categories: media, featured
 * Description: An image beside an eyebrow, heading, text and a button.
 * Viewport Width: 1280
 *
 * @package WhalePress
 */

declare(strict_types=1);

?>
<!-- wp:media-text {"align":"wide","verticalAlignment":"center"} -->
<div class="wp-block-media-text alignwide is-stacked-on-mobile is-vertically-aligned-center"><figure class="wp-block-media-text__media"></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'Featured', 'whalepress' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Pair a picture with a short story', 'whalepress' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Choose an image, then explain what it shows and why it matters. Use the block toolbar to put the image on the right.', 'whalepress' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php esc_html_e( 'Find out more', 'whalepress' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:media-text -->
