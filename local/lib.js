/**
 * Shared helpers for the local tooling scripts.
 */
import { spawnSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import chalk from 'chalk';

export const root = fileURLToPath(new URL('..', import.meta.url));
export const envPath = path.join(root, '.env');
export const envExamplePath = path.join(root, '.env-example');

export function banner(subtitle) {
	console.log(
		chalk.blue(`
        .
       ":"
     ___:____     |"\\/""|
   ,'        \`.    \\  /
   |  ${chalk.yellow('O')}        \\___/  |
 ~^~^~^~^~^~^~^~^~^~^~^~^~
 ------- ${chalk.yellow('WhalePress')} --------
`),
	);
	if (subtitle) {
		console.log(chalk.bold(subtitle), '\n');
	}
}

export const step = (message) => console.log(`\n${chalk.cyan('›')} ${message}`);
export const success = (message) => console.log(chalk.green(`✔ ${message}`));
export const warn = (message) => console.log(chalk.yellow(`! ${message}`));
export const fail = (message) => {
	console.error(chalk.red(`✖ ${message}`));
	process.exit(1);
};

/**
 * Parse a dotenv file. Supports `KEY=value`, quoted values and inline
 * `# comments` (preceded by whitespace), matching Docker Compose.
 *
 * @param {string} contents File contents.
 * @return {Record<string, string>} Parsed variables.
 */
export function parseEnv(contents) {
	const vars = {};
	for (const line of contents.split(/\r?\n/)) {
		const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);
		if (!match) {
			continue;
		}
		let value = match[2].trim();
		const quoted = value.match(/^(['"])(.*)\1$/);
		value = quoted ? quoted[2] : value.replace(/\s+#.*$/, '');
		vars[match[1]] = value;
	}
	return vars;
}

/** @return {Record<string, string>} Variables from .env (empty if missing). */
export function readEnv() {
	return existsSync(envPath) ? parseEnv(readFileSync(envPath, 'utf8')) : {};
}

export function requireEnv() {
	if (!existsSync(envPath)) {
		fail('No .env file found. Run `npm run setup` first.');
	}
	return readEnv();
}

/**
 * Run a command, inheriting stdio. Exits the process on failure unless
 * `allowFailure` is set.
 *
 * @param {string}   command Executable.
 * @param {string[]} args    Arguments.
 * @param {object}   options spawnSync options plus `allowFailure`.
 * @return {number} Exit status.
 */
export function run(command, args = [], { allowFailure = false, ...options } = {}) {
	const result = spawnSync(command, args, { cwd: root, stdio: 'inherit', ...options });
	if (result.error) {
		fail(`${command}: ${result.error.message}`);
	}
	if (result.status !== 0 && !allowFailure) {
		fail(`\`${[command, ...args].join(' ')}\` exited with status ${result.status}`);
	}
	return result.status ?? 1;
}

/** Whether an executable is available on PATH. */
export function hasCommand(command, args = ['--version']) {
	return spawnSync(command, args, { stdio: 'ignore' }).status === 0;
}

/** Run `docker compose <args>`. */
export const compose = (args, options) => run('docker', ['compose', ...args], options);

/** The container user that owns bind-mounted files (see docker-compose.yml). */
export function containerUser() {
	const env = readEnv();
	return `${env.HOST_UID || 1000}:${env.HOST_GID || 1000}`;
}

/**
 * Run WP-CLI in the wordpress container as the Apache user.
 *
 * @param {string[]} args    WP-CLI arguments.
 * @param {object}   options run() options plus `tty` for interactive commands.
 * @return {number} Exit status.
 */
export const wp = (args, { tty = false, ...options } = {}) =>
	compose(
		['exec', ...(tty ? [] : ['-T']), '-u', containerUser(), 'wordpress', 'wp', ...args],
		options,
	);
