<?php
/**
 * WordPress-generated forms.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Views;

use Timber\Timber;
use WhalePress\Contracts\Hookable;

/**
 * Brings forms that WordPress renders itself into the component library.
 *
 * The search form that core and plugins print with get_search_form() is the
 * search-form component. The comment form keeps core's markup, which
 * assets/css/components.css styles; only its submit button gets classes here.
 */
final class Forms implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'get_search_form', array( $this, 'search_form' ) );
		add_filter( 'comment_form_defaults', array( $this, 'comment_form_defaults' ) );
	}

	/**
	 * Replace the core search form with the search-form component.
	 *
	 * @param string $form Core markup, returned unchanged if the component can't render.
	 */
	public function search_form( string $form ): string {
		$html = Timber::compile(
			'components/search-form.twig',
			array(
				'action' => home_url( '/' ),
				'query'  => get_search_query(),
			)
		);

		return is_string( $html ) ? $html : $form;
	}

	/**
	 * Give the comment form's submit button the button classes.
	 *
	 * @param array<string, mixed> $defaults comment_form() arguments.
	 * @return array<string, mixed>
	 */
	public function comment_form_defaults( array $defaults ): array {
		$defaults['class_submit'] = 'btn btn-primary';

		return $defaults;
	}
}
