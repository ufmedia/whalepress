import AxeBuilder from '@axe-core/playwright';
import { expect, test as base } from '@playwright/test';

/**
 * Adds an `expectAccessible()` helper that fails on serious or critical
 * axe-core violations (WCAG 2.2 A/AA).
 */
export const test = base.extend({
	expectAccessible: async ({ page }, use) => {
		await use(async () => {
			const results = await new AxeBuilder({ page })
				.withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
				.exclude('#wpadminbar')
				.analyze();

			const blocking = results.violations.filter((violation) =>
				['serious', 'critical'].includes(violation.impact),
			);

			expect(
				blocking.map(({ id, help, nodes }) => `${id}: ${help} (${nodes.length})`),
			).toEqual([]);
		});
	},
});

export { expect };
