import { expect, test } from '../fixtures.js';

test('unknown URLs return a 404 page', async ({ page, expectAccessible }) => {
	const response = await page.goto('/this-page-does-not-exist-' + Date.now());

	expect(response?.status()).toBe(404);
	await expect(page.getByRole('heading', { level: 1 })).toHaveText('Page not found');
	await expectAccessible();
});

test('search results render', async ({ page }) => {
	const response = await page.goto('/?s=hello');

	expect(response?.status()).toBe(200);
	await expect(page.getByRole('heading', { level: 1 })).toContainText('Search results for');
});

test('a single post renders', async ({ page, expectAccessible }) => {
	await page.goto('/');
	const firstPost = page.locator('main article :is(h2, h3) a').first();
	test.skip((await firstPost.count()) === 0, 'No posts on the front page');

	await firstPost.click();
	await expect(page.locator('main article h1')).toBeVisible();
	await expect(page.locator('main article > .entry-content')).toBeVisible();
	await expect(page.locator('#comments')).toBeVisible();
	await expectAccessible();
});

test('a single post shows its featured image centred', async ({ page }) => {
	await page.goto('/');
	const firstPost = page.locator('main article :is(h2, h3) a').first();
	test.skip((await firstPost.count()) === 0, 'No posts on the front page');

	await firstPost.click();
	const image = page.locator('main article > div:not(.entry-content) img').first();
	test.skip((await image.count()) === 0, 'The post has no featured image');

	const box = await image.boundingBox();
	const viewport = page.viewportSize();
	const left = box.x;
	const right = viewport.width - (box.x + box.width);
	// Allow for a scrollbar on one side.
	expect(Math.abs(left - right)).toBeLessThan(20);
});
