<?php
/**
 * Rector configuration. Preview with `npm run rector`; apply with
 * `npm run composer -- rector:fix`.
 *
 * @package WhalePress
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
	->withPaths(
		array(
			__DIR__ . '/wp-content/themes/whalepress/src',
			__DIR__ . '/wp-content/mu-plugins',
			__DIR__ . '/tests/php',
		)
	)
	->withSkip(
		array(
			__DIR__ . '/wp-content/mu-plugins/*/*',
			// The WordPress Coding Standards require long array syntax.
			LongArrayToShortArrayRector::class,
			// array( $this, 'method' ) callbacks can be unhooked with
			// remove_action(); first-class callables create a new closure
			// each time and cannot.
			ArrayToFirstClassCallableRector::class,
		)
	)
	->withPhpSets( php83: true )
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		typeDeclarations: true,
		earlyReturn: true,
	);
