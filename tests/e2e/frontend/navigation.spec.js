import { expect, test } from '../fixtures.js';

test.describe('primary navigation', () => {
	test('toggles on small screens', async ({ page, isMobile }) => {
		test.skip(!isMobile, 'The toggle is only shown on small screens');

		await page.goto('/');
		const toggle = page.locator('[data-nav-toggle]');
		test.skip((await toggle.count()) === 0, 'No primary menu assigned');

		const nav = page.locator('#primary-navigation');
		await expect(nav).toBeHidden();
		await expect(toggle).toHaveAttribute('aria-expanded', 'false');

		await toggle.click();
		await expect(nav).toBeVisible();
		await expect(toggle).toHaveAttribute('aria-expanded', 'true');

		await page.keyboard.press('Escape');
		await expect(nav).toBeHidden();
		await expect(toggle).toBeFocused();
	});

	test('is visible without a toggle on large screens', async ({ page, isMobile }) => {
		test.skip(isMobile, 'Desktop only');

		await page.goto('/');
		const nav = page.locator('#primary-navigation');
		test.skip((await nav.count()) === 0, 'No primary menu assigned');

		await expect(nav).toBeVisible();
		await expect(page.locator('[data-nav-toggle]')).toBeHidden();
	});
});
