import { expect, test } from '@playwright/test';

test('the dashboard loads', async ({ page }) => {
	await page.goto('/wp-admin/');
	await expect(page.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeVisible();
});

test('the block editor loads with theme styles', async ({ page }) => {
	await page.goto('/wp-admin/post-new.php');

	const canvas = page.frameLocator('iframe[name="editor-canvas"]');
	await expect(canvas.locator('.editor-styles-wrapper')).toBeVisible({ timeout: 30_000 });
	await expect(canvas.locator('link#whalepress-editor-css')).toHaveCount(1);
});

test('a post created via the REST API renders on the front end', async ({ page }) => {
	// Authenticate REST requests with a nonce for the logged-in session.
	const nonceResponse = await page.request.get('/wp-admin/admin-ajax.php?action=rest-nonce');
	expect(nonceResponse.ok()).toBeTruthy();
	const nonce = (await nonceResponse.text()).trim();

	const title = `Playwright post ${Date.now()}`;
	const created = await page.request.post('/?rest_route=/wp/v2/posts', {
		headers: { 'X-WP-Nonce': nonce },
		data: {
			title,
			status: 'publish',
			content: '<!-- wp:paragraph --><p>Created by Playwright.</p><!-- /wp:paragraph -->',
		},
	});
	expect(created.ok()).toBeTruthy();
	const post = await created.json();

	try {
		await page.goto(post.link);
		await expect(page.getByRole('heading', { level: 1 })).toHaveText(title);
		await expect(page.locator('.entry-content')).toContainText('Created by Playwright.');
	} finally {
		await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, {
			headers: { 'X-WP-Nonce': nonce },
		});
	}
});
