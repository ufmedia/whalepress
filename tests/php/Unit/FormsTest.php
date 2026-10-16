<?php
/**
 * Tests for the WordPress form integration.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use WhalePress\Tests\TestCase;
use WhalePress\Views\Forms;

#[CoversClass( Forms::class )]
final class FormsTest extends TestCase {

	public function test_comment_form_submit_uses_the_button_classes(): void {
		$defaults = ( new Forms() )->comment_form_defaults( array( 'class_submit' => 'submit' ) );

		$this->assertSame( 'btn btn-primary', $defaults['class_submit'] );
	}

	public function test_the_search_form_component_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 3 ) . '/wp-content/themes/whalepress/templates/components/search-form.twig' );
	}
}
