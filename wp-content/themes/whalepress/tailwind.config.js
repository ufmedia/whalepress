/** @type {import('tailwindcss').Config} */
module.exports = {
	mode: 'jit',
	important: true,
	content: [
		'./*/*.{twig,php}',
		'./**/*.{twig,php}',
		'./public/src/js/*.js',
		'./safelist.txt',
		'!./node_modules/**',
		'!./vendor/**',
		'!./public/build/**',
	],
	theme: {
		extend: {},
	},
	plugins: [],
};
