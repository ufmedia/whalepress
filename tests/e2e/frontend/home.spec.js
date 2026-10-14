import { expect, test } from '../fixtures.js';

test.describe('home page', () => {
	test('renders the layout', async ({ page }) => {
		const response = await page.goto('/');

		expect(response?.status()).toBe(200);
		await expect(page.locator('header').first()).toBeVisible();
		await expect(page.locator('main#main')).toBeVisible();
		await expect(page.locator('footer').first()).toBeVisible();
	});

	test('loads theme assets', async ({ page }) => {
		await page.goto('/');

		const stylesheet = page.locator('link#whalepress-css');
		await expect(stylesheet).toHaveCount(1);
		// Fails if a stale Vite `hot` file points the site at a dev server.
		expect(await stylesheet.getAttribute('href')).not.toContain(':5173');
	});

	test('skip link moves focus to the main content', async ({ page }) => {
		await page.goto('/');
		await page.keyboard.press('Tab');

		const skipLink = page.getByRole('link', { name: 'Skip to content' });
		await expect(skipLink).toBeFocused();
		await skipLink.press('Enter');
		await expect(page).toHaveURL(/#main$/);
	});

	test('has no serious accessibility violations', async ({ page, expectAccessible }) => {
		await page.goto('/');
		await expectAccessible();
	});
});
