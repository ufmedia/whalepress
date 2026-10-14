import js from '@eslint/js';
import globals from 'globals';
import prettier from 'eslint-config-prettier/flat';

export default [
	{ ignores: ['dist/', 'node_modules/'] },
	js.configs.recommended,
	{
		files: ['assets/js/**/*.js'],
		languageOptions: { globals: globals.browser },
	},
	{
		files: ['*.js', 'scripts/**/*.js'],
		languageOptions: { globals: globals.node },
	},
	// Formatting is Prettier's job (run from the project root).
	prettier,
];
