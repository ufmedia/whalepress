<?php
/**
 * Tests for the block style variations.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use WhalePress\Editor\BlockStyles;
use WhalePress\Tests\TestCase;

#[CoversClass( BlockStyles::class )]
final class BlockStylesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	public function test_registers_every_style(): void {
		$registered = array();
		Functions\when( 'register_block_style' )->alias(
			static function ( string $block, array $style ) use ( &$registered ): bool {
				$registered[] = $block . ':' . $style['name'];
				return true;
			}
		);

		( new BlockStyles() )->register_styles();

		$this->assertContains( 'core/group:card', $registered );
		$this->assertContains( 'core/paragraph:lead', $registered );
		$this->assertContains( 'core/list:plain', $registered );
	}

	public function test_styles_are_filterable(): void {
		Filters\expectApplied( 'whalepress/block_styles' )->once()->andReturn( array() );

		$this->assertSame( array(), ( new BlockStyles() )->styles() );
	}

	public function test_every_style_has_css(): void {
		$css = (string) file_get_contents( dirname( __DIR__, 3 ) . '/wp-content/themes/whalepress/assets/css/content.css' );

		foreach ( ( new BlockStyles() )->styles() as $styles ) {
			foreach ( array_keys( $styles ) as $name ) {
				$this->assertStringContainsString( '.is-style-' . $name, $css, "No CSS for the {$name} block style." );
			}
		}
	}
}
