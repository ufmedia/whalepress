/**
 * Interactive first-run setup: .env, theme assets, containers, Composer and
 * a fresh WordPress install. Safe to re-run.
 */
import { randomBytes } from 'node:crypto';
import { closeSync, existsSync, openSync, readFileSync, writeFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import process from 'node:process';
import { confirm, input } from '@inquirer/prompts';
import chalk from 'chalk';
import {
	banner,
	compose,
	containerUser,
	envExamplePath,
	envPath,
	fail,
	hasCommand,
	readEnv,
	root,
	run,
	step,
	success,
} from './lib.js';

const SALTS = [
	'AUTH_KEY',
	'SECURE_AUTH_KEY',
	'LOGGED_IN_KEY',
	'NONCE_KEY',
	'AUTH_SALT',
	'SECURE_AUTH_SALT',
	'LOGGED_IN_SALT',
	'NONCE_SALT',
].map((key) => `WORDPRESS_${key}`);

// Alphanumeric only: safe in .env files without quoting or $-interpolation.
const secret = (length) =>
	randomBytes(length * 2)
		.toString('base64')
		.replace(/[^A-Za-z0-9]/g, '')
		.slice(0, length);

function checkPrerequisites() {
	step('Checking prerequisites');
	const [major] = process.versions.node.split('.').map(Number);
	if (major < 24) {
		fail(`Node 24 or newer is required (found ${process.versions.node}). Run \`nvm use\`.`);
	}
	if (!hasCommand('docker', ['compose', 'version'])) {
		fail('Docker with the Compose plugin is required: https://docs.docker.com/get-docker/');
	}
	success('Node and Docker Compose found');
}

/**
 * Build .env from .env-example, prompting for lines preceded by "# ?".
 */
async function createEnv() {
	const lines = readFileSync(envExamplePath, 'utf8').split('\n');
	const answers = {};
	const output = [];
	let question = null;

	for (const line of lines) {
		if (line.startsWith('# ?')) {
			question = line.slice(3).trim();
			output.push(line);
			continue;
		}

		const match = line.match(/^([A-Z0-9_]+)=(.*)$/);
		if (!match) {
			output.push(line);
			continue;
		}

		const [, key, example] = match;
		let value = example;

		if (question) {
			let fallback = example;
			if (key === 'WP_HOME') {
				fallback = `http://localhost:${answers.WP_PORT}`;
			}
			value = await input({ message: question, default: fallback });
			question = null;
		} else if (SALTS.includes(key)) {
			value = secret(64);
		} else if (key === 'WP_ADMIN_PASSWORD') {
			value = secret(20);
		} else if (key === 'HOST_UID' && process.getuid) {
			value = String(process.getuid());
		} else if (key === 'HOST_GID' && process.getgid) {
			value = String(process.getgid());
		}

		answers[key] = value;
		output.push(`${key}=${value}`);
	}

	writeFileSync(envPath, output.join('\n'));
	success('.env created');
}

async function main() {
	banner("Let's get things ready…");
	checkPrerequisites();

	step('Environment');
	if (
		!existsSync(envPath) ||
		(await confirm({ message: '.env exists. Replace it?', default: false }))
	) {
		await createEnv();
	} else {
		success('Keeping existing .env');
	}

	const env = readEnv();
	const themeDir = path.join(root, 'wp-content', 'themes', env.THEME_NAME || 'whalepress');
	if (!existsSync(path.join(themeDir, 'package.json'))) {
		fail(`Theme not found at ${themeDir}. Check THEME_NAME in .env.`);
	}

	step('Installing theme dependencies and building assets');
	run('npm', ['ci', '--no-audit', '--no-fund'], { cwd: themeDir });
	run('npm', ['run', 'build'], { cwd: themeDir });

	step('Building and starting containers');
	compose(['build', 'wordpress']);
	compose(['up', '-d', '--wait']);

	step('Installing Composer dependencies');
	compose(['run', '--rm', 'composer', 'install', '--no-interaction']);

	step('Installing WordPress');
	const script = openSync(path.join(root, 'local', 'bin', 'install.sh'), 'r');
	const install = spawnSync(
		'docker',
		['compose', 'exec', '-T', '-u', containerUser(), 'wordpress', 'bash', '-s'],
		{ cwd: root, stdio: [script, 'inherit', 'inherit'] },
	);
	closeSync(script);
	if (install.status !== 0) {
		fail('WordPress install failed. See the output above.');
	}

	console.log(`
${chalk.green.bold('All set!')}

  Site       ${chalk.cyan(env.WP_HOME)}
  Admin      ${chalk.cyan(`${env.WP_HOME}/wp-admin`)}  ${chalk.gray(`(${env.WP_ADMIN_USER} / ${env.WP_ADMIN_PASSWORD})`)}
  Mailpit    ${chalk.cyan(`http://localhost:${env.MAILPIT_PORT}`)}
  Adminer    ${chalk.cyan(`http://localhost:${env.ADMINER_PORT}`)}

Run ${chalk.bold('npm start')} to start developing with hot reloading.
`);
}

main().catch((error) => {
	if (error?.name === 'ExitPromptError') {
		process.exit(130);
	}
	fail(error.message);
});
