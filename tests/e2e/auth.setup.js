import process from 'node:process';
import { expect, test as setup } from '@playwright/test';
import { authFile } from '../../playwright.config.js';

setup('log in as admin', async ({ page }) => {
	const user = process.env.WP_ADMIN_USER;
	const password = process.env.WP_ADMIN_PASSWORD;
	setup.skip(!user || !password, 'WP_ADMIN_USER and WP_ADMIN_PASSWORD are not set');

	await page.goto('/wp-login.php');
	await page.getByLabel('Username or Email Address').fill(user);
	await page.getByLabel('Password', { exact: true }).fill(password);
	await page.getByRole('button', { name: 'Log In' }).click();

	await expect(page).toHaveURL(/wp-admin/);
	await page.context().storageState({ path: authFile });
});
