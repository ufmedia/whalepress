import { expect, test } from '../fixtures.js';

test.describe('primary navigation', () => {
	test('toggles on small screens', async ({ page, isMobile }) => {
		test.skip(!isMobile, 'The toggle is only shown on small screens');

		await page.goto('/');
		const toggle = page.getByRole('button', { name: 'Menu' });
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
		await expect(page.getByRole('button', { name: 'Menu' })).toBeHidden();
	});

	test('submenus open, and close on Escape or an outside click', async ({ page, isMobile }) => {
		await page.goto('/');
		const toggle = page
			.locator('#primary-navigation button[aria-controls^="submenu-"]')
			.first();
		test.skip((await toggle.count()) === 0, 'No menu items with children');

		if (isMobile) {
			await page.getByRole('button', { name: 'Menu' }).click();
		}

		const submenu = page.locator(`#${await toggle.getAttribute('aria-controls')}`);
		await expect(submenu).toBeHidden();

		await toggle.click();
		await expect(submenu).toBeVisible();
		await expect(toggle).toHaveAttribute('aria-expanded', 'true');

		// Escape closes the innermost disclosure only and returns focus to it.
		await page.keyboard.press('Escape');
		await expect(submenu).toBeHidden();
		await expect(toggle).toBeFocused();
		await expect(page.locator('#primary-navigation')).toBeVisible();

		await toggle.click();
		await page.locator('main').click({ position: { x: 5, y: 5 } });
		await expect(submenu).toBeHidden();
	});
});
