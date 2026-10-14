/**
 * Database helpers.
 *
 *   npm run db:export [-- <file>]           Dump to local/var/db (gzipped).
 *   npm run db:import -- <file> [--from=<url>]
 *                                            Replace the local database with a
 *                                            .sql or .sql.gz dump, optionally
 *                                            rewriting <url> to WP_HOME.
 *   npm run db:pull                          Import the dump written to stdout
 *                                            by DB_PULL_COMMAND, rewriting
 *                                            DB_PULL_FROM_URL to WP_HOME.
 */
import { createReadStream, createWriteStream, existsSync, mkdirSync } from 'node:fs';
import { spawn } from 'node:child_process';
import path from 'node:path';
import process from 'node:process';
import { pipeline } from 'node:stream/promises';
import { createGunzip, createGzip } from 'node:zlib';
import { fail, requireEnv, root, step, success, warn, wp } from './lib.js';

const env = requireEnv();
const [command, ...args] = process.argv.slice(2);
const flag = (name) =>
	args
		.find((arg) => arg.startsWith(`--${name}=`))
		?.split('=')
		.slice(1)
		.join('=');
const positional = args.filter((arg) => !arg.startsWith('--'));

// Run the client inside the db container so nothing is needed on the host.
const dbExec = (binary, extra = []) => [
	'docker',
	[
		'compose',
		'exec',
		'-T',
		'-e',
		`MYSQL_PWD=${env.DB_ROOT_PASSWORD}`,
		'db',
		binary,
		'-uroot',
		...extra,
		env.WORDPRESS_DB_NAME,
	],
];

function spawnStep([cmd, cmdArgs], stdio) {
	return spawn(cmd, cmdArgs, { cwd: root, stdio });
}

const waitFor = (child) =>
	new Promise((resolve, reject) => {
		child.on('error', reject);
		child.on('close', (code) =>
			code === 0 ? resolve() : reject(new Error(`exited with ${code}`)),
		);
	});

async function exportDb() {
	const dir = path.join(root, 'local', 'var', 'db');
	mkdirSync(dir, { recursive: true });
	const stamp = new Date().toISOString().replace(/[:.]/g, '-');
	const file = positional[0] ?? path.join(dir, `${env.PROJECT_NAME}-${stamp}.sql.gz`);

	step(`Exporting to ${path.relative(root, file)}`);
	const dump = spawnStep(dbExec('mariadb-dump', ['--single-transaction', '--routines']), [
		'ignore',
		'pipe',
		'inherit',
	]);
	await Promise.all([
		pipeline(dump.stdout, createGzip(), createWriteStream(file)),
		waitFor(dump),
	]);
	success('Export complete');
}

/**
 * Reset the database and import SQL from a stream.
 *
 * @param {() => import('node:stream').Readable} createSource Called after the
 *     reset, so a source process isn't started before something is reading it.
 * @param {(source: import('node:stream').Readable) => Promise<void>[]} also Extra
 *     promises to await alongside the import.
 */
async function importStream(createSource, also = () => []) {
	step('Resetting the local database');
	const reset = spawnStep(
		[
			'docker',
			[
				'compose',
				'exec',
				'-T',
				'-e',
				`MYSQL_PWD=${env.DB_ROOT_PASSWORD}`,
				'db',
				'mariadb',
				'-uroot',
				'-e',
				`DROP DATABASE IF EXISTS \`${env.WORDPRESS_DB_NAME}\`; CREATE DATABASE \`${env.WORDPRESS_DB_NAME}\`; GRANT ALL ON \`${env.WORDPRESS_DB_NAME}\`.* TO '${env.WORDPRESS_DB_USER}'@'%';`,
			],
		],
		'inherit',
	);
	await waitFor(reset);

	step('Importing');
	const client = spawnStep(dbExec('mariadb'), ['pipe', 'inherit', 'inherit']);
	const source = createSource();
	await Promise.all([pipeline(source, client.stdin), waitFor(client), ...also(source)]);
	success('Import complete');
}

function searchReplace(from) {
	if (!from) {
		warn('No source URL given; skipping search-replace.');
		return;
	}
	const to = env.WP_HOME.replace(/\/$/, '');
	step(`Replacing ${from} with ${to}`);
	wp([
		'search-replace',
		from.replace(/\/$/, ''),
		to,
		'--all-tables-with-prefix',
		'--precise',
		'--skip-columns=guid',
		'--report-changed-only',
	]);
	wp(['cache', 'flush'], { allowFailure: true });
	wp(['rewrite', 'flush'], { allowFailure: true });
}

async function importDb() {
	const file = positional[0];
	if (!file || !existsSync(file)) {
		fail('Usage: npm run db:import -- <file.sql|file.sql.gz> [--from=https://example.com]');
	}
	await importStream(() => {
		const stream = createReadStream(file);
		return file.endsWith('.gz') ? stream.pipe(createGunzip()) : stream;
	});
	searchReplace(flag('from'));
}

async function pullDb() {
	if (!env.DB_PULL_COMMAND) {
		fail(
			'Set DB_PULL_COMMAND (and DB_PULL_FROM_URL) in .env. See docs/getting-started/database.md.',
		);
	}
	let remote;
	await importStream(
		() => {
			step('Running DB_PULL_COMMAND');
			remote = spawn(env.DB_PULL_COMMAND, {
				cwd: root,
				shell: true,
				stdio: ['ignore', 'pipe', 'inherit'],
			});
			return remote.stdout;
		},
		() => [waitFor(remote)],
	);
	searchReplace(env.DB_PULL_FROM_URL);
}

const commands = { export: exportDb, import: importDb, pull: pullDb };

if (!commands[command]) {
	fail(`Unknown command "${command ?? ''}". Use export, import or pull.`);
}

commands[command]().catch((error) => fail(error.message));
