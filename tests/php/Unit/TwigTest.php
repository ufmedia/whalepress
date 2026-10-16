<?php
/**
 * Tests for the Twig extensions.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use WhalePress\Tests\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use WhalePress\Views\Twig;

#[CoversClass( Twig::class )]
final class TwigTest extends TestCase {

	private Twig $twig;

	protected function setUp(): void {
		parent::setUp();
		Functions\stubEscapeFunctions();
		$this->twig = new Twig( dirname( __DIR__, 3 ) . '/wp-content/themes/whalepress/assets/icons' );
	}

	public function test_icons_are_decorative_by_default(): void {
		$svg = $this->twig->icon( 'menu', 'size-6' );

		$this->assertStringStartsWith( '<svg aria-hidden="true" focusable="false" class="size-6"', $svg );
	}

	public function test_icons_with_a_label_are_announced(): void {
		$svg = $this->twig->icon( 'close', '', 'Close menu' );

		$this->assertStringStartsWith( '<svg role="img" aria-label="Close menu"', $svg );
	}

	public function test_rejects_path_traversal_and_unknown_icons(): void {
		$this->assertSame( '', $this->twig->icon( '../../../wp-config' ) );
		$this->assertSame( '', $this->twig->icon( 'does-not-exist' ) );
	}

	public function test_components_render_with_only_their_props(): void {
		$env = $this->environment(
			array(
				'page.twig'             => '{% set secret = 1 %}{{ component("badge", { label: "New" }) }}',
				'components/badge.twig' => '<b>{{ label }}{{ secret ?? "" }}</b>',
			)
		);

		$this->assertSame( '<b>New</b>', $env->render( 'page.twig' ) );
	}

	public function test_components_fall_back_through_a_list(): void {
		$env = $this->environment( array( 'components/card.twig' => 'card' ) );

		$this->assertSame( 'card', $this->twig->component( $env, array( 'card-project', 'card' ) ) );
	}

	public function test_component_names_cannot_leave_the_folder(): void {
		$env = $this->environment( array( 'secret.twig' => 'secret' ) );

		$this->assertSame( '', $this->twig->component( $env, '../secret' ) );
	}

	/**
	 * A Twig environment with in-memory templates and the theme's functions.
	 *
	 * @param array<string, string> $templates Template sources keyed by name.
	 */
	private function environment( array $templates ): Environment {
		return $this->twig->extend( new Environment( new ArrayLoader( $templates ), array( 'strict_variables' => false ) ) );
	}
}
