/** @type {import('stylelint').Config} */
export default {
	extends: ['stylelint-config-standard'],
	ignoreFiles: ['dist/**', 'node_modules/**'],
	rules: {
		// Tailwind CSS v4 directives.
		'at-rule-no-unknown': [
			true,
			{
				ignoreAtRules: [
					'theme',
					'source',
					'utility',
					'variant',
					'custom-variant',
					'plugin',
					'apply',
					'reference',
					'config',
				],
			},
		],
		'at-rule-no-deprecated': [true, { ignoreAtRules: ['apply'] }],
		// @apply takes Tailwind and daisyUI class names, not CSS.
		'at-rule-prelude-no-invalid': [true, { ignoreAtRules: ['apply'] }],
		'import-notation': 'string',
		// Tailwind's --text-*--line-height and WordPress's --wp--preset--* names.
		'custom-property-pattern': null,
		// Blank lines group related tokens in theme.css.
		'custom-property-empty-line-before': null,
		// WordPress block classes (wp-block-*, has-*-color, etc.) aren't ours to rename.
		'selector-class-pattern': null,
	},
};
