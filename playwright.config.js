import { existsSync } from 'node:fs';
import process from 'node:process';
import { defineConfig, devices } from '@playwright/test';

// Pick up WP_HOME and admin credentials from .env when running locally.
if (existsSync('.env')) {
	process.loadEnvFile('.env');
}

const baseURL = process.env.BASE_URL || process.env.WP_HOME || 'http://localhost:8088';
const authFile = 'tests/e2e/.auth/admin.json';

export default defineConfig({
	testDir: 'tests/e2e',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
	use: {
		baseURL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'frontend',
			testDir: 'tests/e2e/frontend',
			use: { ...devices['Desktop Chrome'] },
		},
		{
			name: 'frontend-mobile',
			testDir: 'tests/e2e/frontend',
			use: { ...devices['Pixel 7'] },
		},
		{
			name: 'setup',
			testMatch: 'auth.setup.js',
		},
		{
			name: 'admin',
			testDir: 'tests/e2e/admin',
			dependencies: ['setup'],
			use: { ...devices['Desktop Chrome'], storageState: authFile },
		},
	],
});

export { authFile };
