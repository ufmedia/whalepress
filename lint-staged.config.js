/**
 * Pre-commit checks on staged files only.
 *
 * Theme assets are linted with the theme's own ESLint/Stylelint install; PHP
 * runs through local/php.js so it works with or without PHP on the host.
 */
const theme = 'wp-content/themes/whalepress';
const quote = (files) => files.map((file) => JSON.stringify(file)).join(' ');

export default {
	[`${theme}/**/*.js`]: (files) => [
		`npm --prefix ${theme} exec -- eslint --fix ${quote(files)}`,
		`prettier --write ${quote(files)}`,
	],
	[`${theme}/assets/**/*.css`]: (files) => [
		`npm --prefix ${theme} exec -- stylelint --fix ${quote(files)}`,
		`prettier --write ${quote(files)}`,
	],
	'**/*.php': (files) => [
		`node local/php.js vendor/bin/phpcbf ${quote(files)} || true`,
		`node local/php.js vendor/bin/phpcs ${quote(files)}`,
	],
	'**/*.{twig,json,yml,yaml,md}': 'prettier --write --ignore-unknown',
	'local/**/*.js': 'prettier --write',
	'tests/e2e/**/*.js': 'prettier --write',
};
