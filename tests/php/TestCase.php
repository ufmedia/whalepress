<?php
/**
 * Base test case.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Sets up and tears down Brain Monkey around each test.
 */
abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
