<?php
/**
 * Tests for the colour scheme service.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use WhalePress\Assets\ColorScheme;
use WhalePress\Tests\TestCase;

#[CoversClass( ColorScheme::class )]
final class ColorSchemeTest extends TestCase {

	public function test_script_reads_the_storage_key(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$script = ( new ColorScheme() )->script();

		$this->assertStringContainsString( 'localStorage.getItem("' . ColorScheme::STORAGE_KEY . '")', $script );
		$this->assertStringContainsString( 'document.documentElement.dataset.theme=s', $script );
	}

	public function test_storage_key_matches_the_toggle_module(): void {
		$module = (string) file_get_contents( dirname( __DIR__, 3 ) . '/wp-content/themes/whalepress/assets/js/components/color-scheme.js' );

		$this->assertStringContainsString( "const STORAGE_KEY = '" . ColorScheme::STORAGE_KEY . "';", $module );
	}
}
