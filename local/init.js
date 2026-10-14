/**
 * Turn a fresh copy of the starter into a new project: renames the theme
 * directory, PHP namespace, text domain, hook/function prefixes and file
 * names, then optionally detaches the starter's Git remote.
 *
 * Reads the current identity from the repository, so it can be re-run to
 * rename a project again. Requires a clean working tree, so every change is
 * reviewable with `git diff` and revertable with `git reset --hard`.
 *
 * Non-interactive: npm run init -- --name Acme [--slug acme] [--detach | --no-detach]
 */
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { parseArgs } from 'node:util';
import { confirm, input } from '@inquirer/prompts';
import chalk from 'chalk';
import { banner, envPath, fail, root, step, success, warn } from './lib.js';

// Links to the upstream starter survive the rename (see docs: Starting a new project).
const UPSTREAM = /(github\.com\/ufmedia\/whalepress|ufmedia\.github\.io\/whalepress)/gi;

const git = (...args) => execFileSync('git', args, { cwd: root, encoding: 'utf8' }).trim();
const readJson = (file) => JSON.parse(readFileSync(path.join(root, file), 'utf8'));

function currentIdentity() {
	const slug = readJson('package.json').name;
	const namespace = Object.keys(readJson('composer.json').autoload?.['psr-4'] ?? {})[0];
	if (!slug || !namespace) {
		fail('Could not read the current name from package.json and composer.json.');
	}
	return { slug, name: namespace.replace(/\\+$/, '') };
}

/**
 * Composer's lock file content hash (Locker::getContentHash): md5 of PHP's
 * json_encode() of the relevant composer.json keys, top level sorted.
 *
 * @return {string} Hash.
 */
function composerContentHash() {
	const keys = [
		'name',
		'version',
		'require',
		'require-dev',
		'conflict',
		'replace',
		'provide',
		'minimum-stability',
		'prefer-stable',
		'repositories',
		'extra',
	];
	const encode = (value) => {
		if (Array.isArray(value)) {
			return `[${value.map(encode).join(',')}]`;
		}
		if (value && typeof value === 'object') {
			const entries = Object.entries(value);
			// PHP decodes objects to arrays, so an empty one encodes as [].
			return entries.length
				? `{${entries.map(([key, item]) => `${encode(key)}:${encode(item)}`).join(',')}}`
				: '[]';
		}
		if (typeof value === 'string') {
			return JSON.stringify(value)
				.replace(/\//g, '\\/')
				.replace(
					/[\u007f-\uffff]/g,
					(c) => `\\u${c.charCodeAt(0).toString(16).padStart(4, '0')}`,
				);
		}
		return JSON.stringify(value);
	};

	const composer = readJson('composer.json');
	const relevant = Object.fromEntries(
		keys.filter((key) => key in composer).map((key) => [key, composer[key]]),
	);
	if (composer.config?.platform) {
		relevant.config = { platform: composer.config.platform };
	}
	const sorted = Object.fromEntries(
		Object.keys(relevant)
			.sort()
			.map((key) => [key, relevant[key]]),
	);
	return createHash('md5').update(encode(sorted)).digest('hex');
}

// Whole identifiers only: `acme_env` and `acme-mail` match, `acmes` doesn't.
const word = (value) => new RegExp(`(?<![A-Za-z0-9])${value}(?![A-Za-z0-9])`, 'g');

function replaceIn(contents, from, to) {
	const kept = [];
	let text = contents.replace(UPSTREAM, (match) => `\u0000${kept.push(match) - 1}\u0000`);
	text = text
		.replace(word(from.slug.toUpperCase()), to.slug.toUpperCase())
		.replace(word(from.name), to.name)
		.replace(word(from.slug), to.slug);
	return text.replace(/\u0000(\d+)\u0000/g, (_, index) => kept[Number(index)]);
}

const { values: args } = parseArgs({
	options: {
		name: { type: 'string' },
		slug: { type: 'string' },
		detach: { type: 'boolean' },
		'no-detach': { type: 'boolean' },
	},
});

const NAME = /^[A-Z][A-Za-z0-9]*$/;
const SLUG = /^[a-z][a-z0-9]*$/;

async function main() {
	banner('New project');

	if (git('status', '--porcelain')) {
		fail('Commit or stash your changes first, so the rename can be reviewed and undone.');
	}

	const from = currentIdentity();
	console.log(`Current name: ${chalk.bold(from.name)} (slug ${chalk.bold(from.slug)})\n`);

	const name =
		args.name ??
		(await input({
			message: 'Project name, in PascalCase (PHP namespace and titles):',
			validate: (value) => NAME.test(value) || 'Letters and digits, starting with a capital.',
		}));
	const slug =
		args.slug ??
		(args.name
			? name.toLowerCase()
			: await input({
					message: 'Slug (theme directory, text domain, prefixes):',
					default: name.toLowerCase(),
					validate: (value) =>
						SLUG.test(value) ||
						'Lowercase letters and digits only (it is also a PHP function prefix).',
				}));
	if (!NAME.test(name) || !SLUG.test(slug)) {
		fail(
			'The name must be PascalCase letters and digits; the slug lowercase letters and digits.',
		);
	}
	const to = { name, slug };

	if (to.name === from.name && to.slug === from.slug) {
		success('Nothing to rename.');
	} else {
		step(`Renaming ${from.name} → ${to.name}, ${from.slug} → ${to.slug}`);

		let changed = 0;
		for (const file of git('ls-files', '-z').split('\0').filter(Boolean)) {
			const full = path.join(root, file);
			if (!existsSync(full)) {
				continue;
			}
			const buffer = readFileSync(full);
			if (buffer.includes(0)) {
				continue; // Binary.
			}
			const contents = buffer.toString('utf8');
			const updated = replaceIn(contents, from, to);
			if (updated !== contents) {
				writeFileSync(full, updated);
				changed++;
			}
		}
		success(`Updated ${changed} files`);

		// The theme directory moves as a whole, untracked node_modules and dist included.
		const themes = path.join('wp-content', 'themes');
		if (existsSync(path.join(root, themes, from.slug))) {
			git('mv', path.join(themes, from.slug), path.join(themes, to.slug));
		}
		let moved = 0;
		for (const file of git('ls-files', '-z').split('\0').filter(Boolean)) {
			const target = file.replace(word(from.slug), to.slug);
			if (target !== file) {
				git('mv', file, target);
				moved++;
			}
		}
		success(`Renamed the theme directory and ${moved} files`);

		// The package name feeds composer.lock's content hash; keep the lock valid.
		const lockPath = path.join(root, 'composer.lock');
		const lock = readFileSync(lockPath, 'utf8');
		writeFileSync(
			lockPath,
			lock.replace(/("content-hash": ")[0-9a-f]{32}/, `$1${composerContentHash()}`),
		);
		success('Updated the composer.lock hash');

		if (existsSync(envPath)) {
			const env = readFileSync(envPath, 'utf8');
			writeFileSync(envPath, env.replace(/^THEME_NAME=.*$/m, `THEME_NAME=${to.slug}`));
			warn(
				'.env: THEME_NAME updated. PROJECT_NAME was left alone, because changing it starts a new, empty database volume.',
			);
		}
	}

	const remotes = git('remote').split('\n').filter(Boolean);
	if (
		remotes.includes('origin') &&
		!args['no-detach'] &&
		(args.detach ||
			(await confirm({
				message: `Remove the "origin" remote (${git('remote', 'get-url', 'origin')})?`,
				default: true,
			})))
	) {
		git('remote', 'remove', 'origin');
		success('Removed origin');
	}

	console.log(`
${chalk.green.bold('Done.')} Review the changes with ${chalk.bold('git status')} and ${chalk.bold('git diff')}, then:

  npm run setup                          ${chalk.gray('# or, if already set up: npm run composer -- dump-autoload && npm run build')}
  npm run lint && npm run test:php
  git add -A && git commit -m "chore: initialise ${to.name}"
  git remote add origin <new repository URL>
  git push -u origin main

Then follow ${chalk.bold('docs/getting-started/new-project.md')} for the one-off repository settings.
`);
}

main().catch((error) => {
	if (error?.name === 'ExitPromptError') {
		process.exit(130);
	}
	fail(error.message);
});
