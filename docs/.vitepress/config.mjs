import process from 'node:process';
import { defineConfig } from 'vitepress';

const repo = 'https://github.com/ufmedia/whalepress';

// Served from /<repository>/ on GitHub Pages; set DOCS_BASE=/ for a custom domain.
const base = process.env.DOCS_BASE || '/whalepress/';

export default defineConfig({
	title: 'WhalePress',
	description:
		'A WordPress starter: Timber 2, Tailwind CSS 4 and Vite, with immutable Docker images from local to production.',
	base,
	lang: 'en-GB',
	cleanUrls: true,
	lastUpdated: true,
	// Local service URLs (http://localhost:8025 etc.) are intentional.
	ignoreDeadLinks: [/^https?:\/\/localhost/],
	head: [['link', { rel: 'icon', href: `${base}whalepress.png` }]],
	vite: {
		server: { port: 5174 },
	},
	themeConfig: {
		logo: '/whalepress.png',
		nav: [
			{
				text: 'Guide',
				link: '/getting-started/installation',
				activeMatch: '^/(getting-started|building|shipping)/',
			},
			{ text: 'Reference', link: '/reference/troubleshooting', activeMatch: '^/reference/' },
		],
		sidebar: [
			{
				text: 'Getting started',
				items: [
					{ text: 'Prerequisites', link: '/getting-started/prerequisites' },
					{ text: 'Installation', link: '/getting-started/installation' },
					{ text: 'Database', link: '/getting-started/database' },
					{ text: 'Local services', link: '/getting-started/local-services' },
				],
			},
			{
				text: 'Building',
				items: [
					{ text: 'WordPress configuration', link: '/building/wordpress' },
					{ text: 'Theme', link: '/building/theme' },
					{ text: 'Plugins', link: '/building/plugins' },
				],
			},
			{
				text: 'Shipping',
				items: [
					{ text: 'Testing and standards', link: '/shipping/testing' },
					{ text: 'Deployment', link: '/shipping/deployment' },
				],
			},
			{
				text: 'Reference',
				items: [
					{ text: 'Further reading', link: '/reference/further-reading' },
					{ text: 'Troubleshooting', link: '/reference/troubleshooting' },
				],
			},
		],
		outline: [2, 3],
		search: { provider: 'local' },
		socialLinks: [{ icon: 'github', link: repo }],
		editLink: {
			pattern: `${repo}/edit/main/docs/:path`,
			text: 'Edit this page on GitHub',
		},
		footer: {
			message: 'Released under the GPL-2.0-or-later licence.',
		},
	},
});
