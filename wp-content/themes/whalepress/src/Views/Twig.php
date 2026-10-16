<?php
/**
 * Twig extensions.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Views;

use Twig\Environment;
use Twig\TwigFunction;
use WhalePress\Contracts\Hookable;

/**
 * Adds theme functions and filters to Twig.
 */
final readonly class Twig implements Hookable {

	/**
	 * Where component() looks, relative to the Twig template directory.
	 */
	public const string COMPONENT_DIRECTORY = 'components';

	/**
	 * Constructor.
	 *
	 * @param string $icon_path Absolute path to the SVG icon directory.
	 */
	public function __construct( private string $icon_path ) {}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'timber/twig', array( $this, 'extend' ) );
	}

	/**
	 * Register functions and filters.
	 *
	 * @param Environment $twig Twig environment.
	 */
	public function extend( Environment $twig ): Environment {
		$twig->addFunction( new TwigFunction( 'icon', array( $this, 'icon' ), array( 'is_safe' => array( 'html' ) ) ) );
		$twig->addFunction(
			new TwigFunction(
				'component',
				array( $this, 'component' ),
				array(
					'needs_environment' => true,
					'is_safe'           => array( 'html' ),
				)
			)
		);

		return $twig;
	}

	/**
	 * Render a component from templates/components with only the given props.
	 *
	 * Usage: {{ component('button', { label: 'Read more', url: post.link }) }}.
	 * Pass a list to fall back, e.g. component(['card-' ~ post.post_type, 'card'], …):
	 * the first that exists is used. Components see their props and nothing
	 * else from the calling template, so pass everything they need.
	 *
	 * @param Environment         $env   Twig environment.
	 * @param string|list<string> $name  Component name(s), without the folder or extension.
	 * @param array<string,mixed> $props Variables for the component.
	 */
	public function component( Environment $env, string|array $name, array $props = array() ): string {
		$templates = array();
		foreach ( (array) $name as $candidate ) {
			if ( 1 === preg_match( '/^[a-z0-9-]+$/', $candidate ) ) {
				$templates[] = self::COMPONENT_DIRECTORY . '/' . $candidate . '.twig';
			}
		}

		if ( array() === $templates ) {
			return '';
		}

		return $env->resolveTemplate( $templates )->render( $props );
	}

	/**
	 * Inline an SVG from assets/icons.
	 *
	 * Usage: {{ icon('menu', 'size-6') }} or {{ icon('close', 'size-6', 'Close') }}.
	 * Icons are decorative (aria-hidden) unless a label is given.
	 *
	 * @param string      $name    File name without extension.
	 * @param string      $classes Classes for the <svg> element.
	 * @param string|null $label   Accessible label.
	 */
	public function icon( string $name, string $classes = '', ?string $label = null ): string {
		if ( 1 !== preg_match( '/^[a-z0-9-]+$/', $name ) ) {
			return '';
		}

		$file = $this->icon_path . '/' . $name . '.svg';
		if ( ! is_readable( $file ) ) {
			return '';
		}

		$svg = trim( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.

		$attributes = null === $label
			? ' aria-hidden="true" focusable="false"'
			: sprintf( ' role="img" aria-label="%s"', esc_attr( $label ) );

		if ( '' !== $classes ) {
			$attributes .= sprintf( ' class="%s"', esc_attr( $classes ) );
		}

		return (string) preg_replace( '/<svg\b/', '<svg' . $attributes, $svg, 1 );
	}
}
