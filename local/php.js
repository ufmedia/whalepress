/**
 * Run a PHP tool with the host's PHP when it is suitable, otherwise inside
 * the Composer container, so every PHP quality script works without a local
 * PHP install. Set WHALEPRESS_PHP=container to always use the container.
 *
 *   node local/php.js composer lint
 *   node local/php.js vendor/bin/phpcs path/to/file.php
 */
import path from 'node:path';
import process from 'node:process';
import { compose, hasCommand, root, run } from './lib.js';

const [tool, ...args] = process.argv.slice(2);

// Paths must be relative so they resolve the same on the host and in /app.
const relativeArgs = args.map((arg) => (path.isAbsolute(arg) ? path.relative(root, arg) : arg));

const REQUIRED_EXTENSIONS = ['dom', 'mbstring', 'tokenizer', 'xmlwriter'];

function hostPhpIsSuitable() {
	if (process.env.WHALEPRESS_PHP === 'container') {
		return false;
	}
	const check = `exit(PHP_VERSION_ID >= 80300 && array_product(array_map('extension_loaded', ${JSON.stringify(
		REQUIRED_EXTENSIONS,
	)})) ? 0 : 1);`;
	return hasCommand('php', ['-r', check]) && (tool !== 'composer' || hasCommand('composer'));
}

const command =
	tool === 'composer' ? ['composer', ...relativeArgs] : ['php', tool, ...relativeArgs];

const status = hostPhpIsSuitable()
	? run(command[0], command.slice(1), { allowFailure: true })
	: compose(['run', '--rm', '-T', 'composer', ...command], { allowFailure: true });

process.exit(status);
