/**
 * Disclosure: a button that shows and hides a region, e.g. the mobile
 * navigation or a submenu (see components/site-header.twig and menu.twig).
 *
 *   <div x-data="disclosure" x-on:keydown.escape="dismiss" x-on:click.outside="close">
 *     <button type="button" x-ref="toggle" x-on:click="toggle"
 *       aria-controls="panel" x-bind:aria-expanded="open">Menu</button>
 *     <div id="panel" x-show="open">…</div>
 *   </div>
 *
 * Escape closes it and returns focus to the toggle; clicking outside closes
 * it without moving focus.
 */
export default () => ({
	open: false,

	toggle() {
		this.open = !this.open;
	},

	close() {
		this.open = false;
	},

	dismiss(event) {
		if (!this.open) {
			return;
		}

		// Nested disclosures: only the innermost open one handles Escape.
		event?.stopPropagation();
		this.open = false;
		this.$refs.toggle?.focus();
	},
});
