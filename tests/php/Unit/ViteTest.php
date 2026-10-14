<?php
/**
 * Tests for the Vite asset loader.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests\Unit;

use WhalePress\Assets\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use WhalePress\Tests\TestCase;

#[CoversClass( Vite::class )]
final class ViteTest extends TestCase {

	private string $theme_path;

	protected function setUp(): void {
		parent::setUp();
		$this->theme_path = sys_get_temp_dir() . '/whalepress-vite-' . uniqid();
		mkdir( $this->theme_path . '/dist', 0777, true );
	}

	protected function tearDown(): void {
		@unlink( $this->theme_path . '/' . Vite::HOT_FILE );
		@unlink( $this->theme_path . '/' . Vite::MANIFEST_FILE );
		@rmdir( $this->theme_path . '/dist' );
		@rmdir( $this->theme_path );
		parent::tearDown();
	}

	public function test_resolves_entries_from_the_manifest(): void {
		$this->write_manifest(
			array(
				'assets/js/app.js'   => array(
					'file' => 'assets/app-abc123.js',
					'css'  => array( 'assets/app-def456.css' ),
				),
				'assets/css/app.css' => array( 'file' => 'assets/app-789.css' ),
			)
		);

		$vite = $this->vite( 'production' );

		$this->assertFalse( $vite->is_hot() );
		$this->assertSame( 'https://example.com/theme/dist/assets/app-abc123.js', $vite->url( Vite::ENTRY_SCRIPT ) );
		$this->assertSame( 'https://example.com/theme/dist/assets/app-789.css', $vite->url( Vite::ENTRY_STYLE ) );
		$this->assertSame( array( 'https://example.com/theme/dist/assets/app-def456.css' ), $vite->imported_styles( Vite::ENTRY_SCRIPT ) );
	}

	public function test_returns_null_for_unknown_entries_and_missing_manifest(): void {
		$vite = $this->vite( 'production' );

		$this->assertNull( $vite->url( Vite::ENTRY_SCRIPT ) );
		$this->assertSame( array(), $vite->imported_styles( Vite::ENTRY_SCRIPT ) );
	}

	public function test_uses_the_dev_server_when_hot_locally(): void {
		file_put_contents( $this->theme_path . '/' . Vite::HOT_FILE, "http://localhost:5173/\n" );

		$vite = $this->vite( 'local' );

		$this->assertTrue( $vite->is_hot() );
		$this->assertSame( 'http://localhost:5173', $vite->dev_server_url() );
		$this->assertSame( 'http://localhost:5173/assets/js/app.js', $vite->url( Vite::ENTRY_SCRIPT ) );
		$this->assertSame( array(), $vite->imported_styles( Vite::ENTRY_SCRIPT ) );
	}

	public function test_ignores_a_stale_hot_file_in_production(): void {
		file_put_contents( $this->theme_path . '/' . Vite::HOT_FILE, 'http://localhost:5173' );
		$this->write_manifest( array( 'assets/js/app.js' => array( 'file' => 'assets/app.js' ) ) );

		$vite = $this->vite( 'production' );

		$this->assertFalse( $vite->is_hot() );
		$this->assertSame( 'https://example.com/theme/dist/assets/app.js', $vite->url( Vite::ENTRY_SCRIPT ) );
	}

	private function vite( string $environment ): Vite {
		return new Vite( $this->theme_path, 'https://example.com/theme/', $environment );
	}

	/**
	 * @param array<string, array<string, mixed>> $manifest Manifest contents.
	 */
	private function write_manifest( array $manifest ): void {
		file_put_contents( $this->theme_path . '/' . Vite::MANIFEST_FILE, (string) json_encode( $manifest ) );
	}
}
