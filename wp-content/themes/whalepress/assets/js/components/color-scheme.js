/**
 * Light/dark toggle (components/theme-toggle.twig).
 *
 * The site follows the system colour scheme by default (see base.css). This
 * lets visitors override it: the choice is stored in localStorage and applied
 * as data-theme on <html>, which an inline script in <head> restores on the
 * next page load (WhalePress\Assets\ColorScheme) to avoid a flash.
 */

// Keep in sync with WhalePress\Assets\ColorScheme::STORAGE_KEY.
const STORAGE_KEY = 'whalepress-color-scheme';

const systemDark = window.matchMedia('(prefers-color-scheme: dark)');

const storage = {
	get() {
		try {
			return localStorage.getItem(STORAGE_KEY);
		} catch {
			return null;
		}
	},
	set(value) {
		try {
			localStorage.setItem(STORAGE_KEY, value);
		} catch {
			// Storage blocked: the choice still applies to this page.
		}
	},
};

const currentScheme = () =>
	document.documentElement.dataset.theme ?? (systemDark.matches ? 'dark' : 'light');

export default () => ({
	dark: currentScheme() === 'dark',

	init() {
		// Follow system changes until the visitor makes a choice.
		systemDark.addEventListener('change', () => {
			if (!storage.get()) {
				this.dark = currentScheme() === 'dark';
			}
		});
	},

	toggle() {
		const next = this.dark ? 'light' : 'dark';
		document.documentElement.dataset.theme = next;
		storage.set(next);
		this.dark = next === 'dark';
	},
});
