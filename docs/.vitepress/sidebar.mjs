/**
 * Builds the sidebar from the files in docs/, so adding a page is just adding
 * a Markdown file:
 *
 * - Each page is listed under its first `# Heading` (or a `title` in front matter).
 * - Each folder is a section, named after its index.md heading if it has one,
 *   otherwise after the folder ("getting-started" → "Getting started").
 * - Pages are sorted by an optional `order: <number>` in front matter, then by title.
 *   Sections are sorted by their index.md's `order`, then the `sections` option,
 *   then by name.
 * - `sidebar: false` in front matter leaves a page out (it is still published).
 */
import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';

const IGNORED = new Set(['public', 'node_modules']);

/**
 * Parse the few front matter keys the sidebar uses.
 *
 * @param {string} source Markdown source.
 * @return {{title?: string, order?: number, sidebar?: boolean}} Values found.
 */
function frontMatter(source) {
	const block = source.match(/^---\r?\n([\s\S]*?)\r?\n---/);
	const values = {};
	for (const line of block ? block[1].split(/\r?\n/) : []) {
		const match = line.match(/^(title|order|sidebar):\s*(.+?)\s*$/);
		if (!match) {
			continue;
		}
		const [, key, raw] = match;
		const value = raw.replace(/^(['"])(.*)\1$/, '$2');
		if (key === 'order') {
			values.order = Number(value);
		} else if (key === 'sidebar') {
			values.sidebar = value !== 'false';
		} else {
			values.title = value;
		}
	}
	return values;
}

const humanise = (name) => {
	const words = name.replace(/[-_]+/g, ' ').trim();
	return words.charAt(0).toUpperCase() + words.slice(1);
};

/**
 * @param {string} file Absolute path to a Markdown file.
 * @return {{title: string, order?: number, hidden: boolean}} Page details.
 */
function page(file) {
	const source = readFileSync(file, 'utf8');
	const meta = frontMatter(source);
	// The first level-one heading outside fenced code blocks.
	const heading = source
		.replace(/^(```|~~~)[\s\S]*?^\1/gm, '')
		.match(/^#\s+(.+?)\s*#*\s*$/m)?.[1];
	return {
		title: meta.title ?? heading?.replace(/[`*_]/g, '') ?? humanise(path.basename(file, '.md')),
		order: Number.isFinite(meta.order) ? meta.order : undefined,
		hidden: meta.sidebar === false,
	};
}

const byOrder = (a, b) => a.order - b.order || a.text.localeCompare(b.text);

/**
 * @param {string}   root      Absolute path to docs/.
 * @param {string}   directory Directory to list, relative to root.
 * @param {string[]} sections  Preferred order of folders, by path relative to root.
 * @return {Array<object>} Sidebar entries for that directory.
 */
function entries(root, directory, sections) {
	const items = [];
	for (const entry of readdirSync(path.join(root, directory), { withFileTypes: true })) {
		const relative = path.posix.join(directory, entry.name);
		if (entry.name.startsWith('.') || IGNORED.has(entry.name)) {
			continue;
		}

		if (entry.isDirectory()) {
			const children = entries(root, relative, sections);
			let index;
			try {
				index = page(path.join(root, relative, 'index.md'));
			} catch {
				index = undefined;
			}
			if (children.length || index) {
				items.push({
					text: index?.title ?? humanise(entry.name),
					order:
						index?.order ??
						(sections.includes(relative)
							? sections.indexOf(relative)
							: Number.POSITIVE_INFINITY),
					link: index ? `/${relative}/` : undefined,
					collapsed: false,
					items: children,
				});
			}
			continue;
		}

		if (!entry.name.endsWith('.md') || (directory && entry.name === 'index.md')) {
			continue;
		}
		const details = page(path.join(root, relative));
		if (!details.hidden) {
			items.push({
				text: details.title,
				order:
					entry.name === 'index.md'
						? Number.NEGATIVE_INFINITY
						: (details.order ?? Number.POSITIVE_INFINITY),
				link: `/${relative.replace(/(^|\/)index\.md$/, '$1').replace(/\.md$/, '')}`,
			});
		}
	}

	// Pages before sections at each level, then by order and title.
	return items
		.sort((a, b) => Number(Boolean(a.items)) - Number(Boolean(b.items)) || byOrder(a, b))
		.map(({ order, ...item }) => item);
}

/**
 * @param {string}   root               Absolute path to docs/.
 * @param {object}   [options]
 * @param {string[]} [options.sections] Preferred order of folders; others follow by name.
 * @return {Array<object>} VitePress sidebar.
 */
export function sidebar(root, { sections = [] } = {}) {
	return entries(root, '', sections);
}
