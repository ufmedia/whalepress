<?php
/**
 * Title: Call to action
 * Slug: whalepress/call-to-action
 * Categories: call-to-action
 * Description: A heading, a short pitch and two buttons on a tinted panel.
 * Viewport Width: 1280
 *
 * @package WhalePress
 */

declare(strict_types=1);

?>
<!-- wp:group {"align":"wide","backgroundColor":"surface","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-surface-background-color has-background"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Ready to get started?', 'whalepress' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-lead","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center is-style-lead"><?php esc_html_e( 'Tell visitors what happens next, and why they should do it now.', 'whalepress' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php esc_html_e( 'Get in touch', 'whalepress' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php esc_html_e( 'Learn more', 'whalepress' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
