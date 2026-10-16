<?php
/**
 * Light and dark colour schemes.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Assets;

use WhalePress\Contracts\Hookable;

/**
 * Applies the visitor's saved colour scheme before the page paints.
 *
 * The palette in theme.json is made of light-dark() pairs, so the site
 * follows the system preference with no JavaScript at all. When a visitor
 * picks a scheme with the toggle (assets/js/components/color-scheme.js), it is
 * saved to localStorage; this inline script reapplies it as data-theme on
 * <html> early in <head>, avoiding a flash of the other scheme.
 */
final class ColorScheme implements Hookable {

	/**
	 * Key in localStorage. Keep in sync with assets/js/components/color-scheme.js.
	 */
	public const string STORAGE_KEY = 'whalepress-color-scheme';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'print_meta' ), 1 );
		add_action( 'wp_head', array( $this, 'print_script' ), 1 );
	}

	/**
	 * Tell the browser both schemes are supported, so its own UI (scrollbars,
	 * form controls) matches before the stylesheet loads.
	 */
	public function print_meta(): void {
		echo '<meta name="color-scheme" content="light dark" />' . "\n";
	}

	/**
	 * Print the inline script that restores a saved choice.
	 *
	 * Printed with wp_print_inline_script_tag() so a Content-Security-Policy
	 * nonce can be added through the wp_inline_script_attributes filter.
	 */
	public function print_script(): void {
		wp_print_inline_script_tag( $this->script() );
	}

	/**
	 * The inline script.
	 */
	public function script(): string {
		return sprintf(
			'try{var s=localStorage.getItem(%s);if(s==="light"||s==="dark")document.documentElement.dataset.theme=s}catch(e){}',
			(string) wp_json_encode( self::STORAGE_KEY )
		);
	}
}
