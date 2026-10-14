/**
 * Start the containers and the Vite dev server.
 *
 * Usage: npm start [-- --no-vite]
 */
import path from 'node:path';
import process from 'node:process';
import chalk from 'chalk';
import { banner, compose, requireEnv, root, run, step } from './lib.js';

const env = requireEnv();
const withVite = !process.argv.includes('--no-vite');

banner('Time to build something awesome…');

step('Starting containers');
compose(['up', '-d', '--wait']);

console.table({
	Site: env.WP_HOME,
	Admin: `${env.WP_HOME}/wp-admin`,
	Mailpit: `http://localhost:${env.MAILPIT_PORT}`,
	Adminer: `http://localhost:${env.ADMINER_PORT}`,
	...(withVite && { Vite: `http://localhost:${env.VITE_PORT}` }),
});

if (withVite) {
	step(
		`Starting Vite ${chalk.gray('(Ctrl+C stops Vite; containers keep running, `npm stop` stops them)')}`,
	);
	const themeDir = path.join(root, 'wp-content', 'themes', env.THEME_NAME || 'whalepress');
	run('npm', ['run', 'dev'], { cwd: themeDir, allowFailure: true });
}
