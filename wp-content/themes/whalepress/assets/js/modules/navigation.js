/**
 * Disclosure toggle for the primary navigation on small screens.
 *
 * Expects a button with [data-nav-toggle] whose aria-controls points at the
 * navigation element. The nav gets a [data-open] attribute when expanded,
 * which Tailwind targets with the `data-open:` variant.
 *
 * @param {ParentNode} root Element to search within.
 */
export function initNavigation(root = document) {
	const toggle = root.querySelector('[data-nav-toggle]');
	const nav = toggle ? document.getElementById(toggle.getAttribute('aria-controls')) : null;

	if (!toggle || !nav) {
		return;
	}

	const openIcon = toggle.querySelector('[data-nav-icon="open"]');
	const closeIcon = toggle.querySelector('[data-nav-icon="close"]');

	const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

	const setOpen = (open) => {
		toggle.setAttribute('aria-expanded', String(open));
		nav.toggleAttribute('data-open', open);
		openIcon?.toggleAttribute('hidden', open);
		closeIcon?.toggleAttribute('hidden', !open);
	};

	toggle.addEventListener('click', () => setOpen(!isOpen()));

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && isOpen()) {
			setOpen(false);
			toggle.focus();
		}
	});
}
