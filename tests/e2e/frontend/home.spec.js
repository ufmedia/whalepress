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

	test('has a single top-level heading', async ({ page }) => {
		await page.goto('/');
		await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1);
	});
});

test.describe('colour scheme', () => {
	test('follows the system preference', async ({ page }) => {
		await page.emulateMedia({ colorScheme: 'dark' });
		await page.goto('/');

		await expect(page.locator('html')).not.toHaveAttribute('data-theme');
		await expect(page.getByRole('button', { name: 'Dark theme' })).toHaveAttribute(
			'aria-pressed',
			'true',
		);
	});

	test('the toggle overrides it and persists', async ({ page }) => {
		await page.emulateMedia({ colorScheme: 'light' });
		await page.goto('/');

		const toggle = page.getByRole('button', { name: 'Dark theme' });
		await expect(toggle).toHaveAttribute('aria-pressed', 'false');

		await toggle.click();
		await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
		await expect(toggle).toHaveAttribute('aria-pressed', 'true');

		// Restored before paint by the inline script in <head>.
		await page.reload();
		await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
	});

	test('dark scheme has no serious accessibility violations', async ({
		page,
		expectAccessible,
	}) => {
		await page.emulateMedia({ colorScheme: 'dark' });
		await page.goto('/');
		await expectAccessible();
	});
});
