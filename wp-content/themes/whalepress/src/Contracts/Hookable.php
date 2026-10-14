<?php
/**
 * Hookable contract.
 *
 * @package WhalePress
 */

declare(strict_types=1);

namespace WhalePress\Contracts;

/**
 * A service that attaches itself to WordPress actions and filters.
 *
 * Services should do nothing in their constructor beyond storing dependencies,
 * so they can be instantiated (and unit tested) without side effects.
 */
interface Hookable {

	/**
	 * Register the service's actions and filters.
	 */
	public function register(): void;
}
