import { execFileSync } from 'node:child_process';
import { utimesSync } from 'node:fs';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitepress';
import { sidebar } from './sidebar.mjs';

const docs = fileURLToPath(new URL('..', import.meta.url));
const sidebarOptions = { sections: ['getting-started', 'building', 'shipping', 'reference'] };

/**
 * The sidebar is built when the config loads, so reload the dev server when
 * a page is added, removed or retitled. Touching this file triggers
 * VitePress's own reload (Vite's server.restart() leaves search half-built).
 */
const sidebarReload = {
	name: 'docs-sidebar-reload',
	configureServer(server) {
		let current = JSON.stringify(sidebar(docs, sidebarOptions));
		const check = (file) => {
			if (!file.endsWith('.md')) {
				return;
			}
			const next = JSON.stringify(sidebar(docs, sidebarOptions));
			if (next !== current) {
				current = next;
				const now = new Date();
				utimesSync(fileURLToPath(import.meta.url), now, now);
			}
		};
		server.watcher.on('add', check).on('unlink', check).on('change', check);
	},
};

/**
 * The repository's GitHub URL: from Actions, or from the "origin" remote
 * locally. Undefined for a repository that hasn't been pushed yet.
 */
function repositoryUrl() {
	if (process.env.GITHUB_REPOSITORY) {
		return `${process.env.GITHUB_SERVER_URL || 'https://github.com'}/${process.env.GITHUB_REPOSITORY}`;
	}
	try {
		const remote = execFileSync('git', ['remote', 'get-url', 'origin'], {
			encoding: 'utf8',
			stdio: ['ignore', 'pipe', 'ignore'],
		}).trim();
		const match = remote.match(/github\.com[:/](.+?)(?:\.git)?$/);
		return match ? `https://github.com/${match[1]}` : undefined;
	} catch {
		return undefined;
	}
}

const repo = repositoryUrl();

// The Docs workflow sets DOCS_BASE from the Pages URL (/<repository>/, or / on a custom domain).
const base = process.env.DOCS_BASE || '/';

export default defineConfig({
	title: 'WhalePress',
	description: 'Developer documentation.',
	base,
	lang: 'en-GB',
	cleanUrls: true,
	lastUpdated: true,
	// Local service URLs (http://localhost:8025 etc.) are intentional.
	ignoreDeadLinks: [/^https?:\/\/localhost/],
	head: [['link', { rel: 'icon', href: `${base}whalepress.png` }]],
	vite: {
		server: { port: 5174 },
		plugins: [sidebarReload],
	},
	themeConfig: {
		logo: '/whalepress.png',
		// Generated from the files in docs/: see sidebar.mjs.
		sidebar: sidebar(docs, sidebarOptions),
		outline: [2, 3],
		search: { provider: 'local' },
		socialLinks: repo ? [{ icon: 'github', link: repo }] : [],
		editLink: repo
			? { pattern: `${repo}/edit/main/docs/:path`, text: 'Edit this page on GitHub' }
			: undefined,
	},
});
