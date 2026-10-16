<?php
/**
 * Tests for the block editor configuration.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests\Unit;

use Brain\Monkey\Filters;
use WhalePress\Editor\BlockEditor;
use PHPUnit\Framework\Attributes\CoversClass;
use WhalePress\Tests\TestCase;

#[CoversClass( BlockEditor::class )]
final class BlockEditorTest extends TestCase {

	public function test_limits_the_inserter_to_content_blocks(): void {
		$allowed = ( new BlockEditor() )->allowed_block_types( true );

		$this->assertIsArray( $allowed );
		$this->assertContains( 'core/paragraph', $allowed );
		$this->assertNotContains( 'core/template-part', $allowed );
		$this->assertNotContains( 'core/navigation', $allowed );
	}

	public function test_allowed_blocks_are_filterable(): void {
		Filters\expectApplied( 'whalepress/allowed_blocks' )->once()->andReturn( array( 'core/paragraph' ) );

		$this->assertSame( array( 'core/paragraph' ), ( new BlockEditor() )->allowed_block_types( true ) );
	}

	public function test_adds_widget_area_blocks_in_the_widget_editor_only(): void {
		$editor = new BlockEditor();

		$widgets = $editor->allowed_block_types( true, new \WP_Block_Editor_Context( 'core/edit-widgets' ) );
		$posts   = $editor->allowed_block_types( true, new \WP_Block_Editor_Context( 'core/edit-post' ) );

		$this->assertContains( 'core/widget-area', $widgets );
		$this->assertContains( 'core/paragraph', $widgets );
		$this->assertNotContains( 'core/widget-area', $posts );
	}

	public function test_disables_the_font_library_and_openverse(): void {
		$settings = ( new BlockEditor() )->editor_settings( array( 'fontLibraryEnabled' => true ) );

		$this->assertFalse( $settings['fontLibraryEnabled'] );
		$this->assertFalse( $settings['enableOpenverseMediaCategory'] );
	}
}
