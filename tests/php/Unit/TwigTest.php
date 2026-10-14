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
}
