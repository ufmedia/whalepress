<?php
/**
 * Title: Feature columns
 * Slug: whalepress/feature-columns
 * Categories: featured, columns
 * Description: A heading and introduction above three feature cards.
 * Viewport Width: 1280
 *
 * @package WhalePress
 */

declare(strict_types=1);

?>
<!-- wp:paragraph {"className":"is-style-eyebrow","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Why choose us', 'whalepress' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Three reasons to work with us', 'whalepress' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-lead","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center is-style-lead"><?php esc_html_e( 'A short introduction to the features below. Keep it to a sentence or two.', 'whalepress' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'First feature', 'whalepress' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Describe the benefit in plain words. One or two sentences is plenty.', 'whalepress' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Second feature', 'whalepress' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Describe the benefit in plain words. One or two sentences is plenty.', 'whalepress' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"is-style-card"} -->
<div class="wp-block-column is-style-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Third feature', 'whalepress' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Describe the benefit in plain words. One or two sentences is plenty.', 'whalepress' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
