/** @type {import('tailwindcss').Config} */
module.exports = {
	mode: 'jit',
	content: [
		'./*/*.{twig,php}',
		'./**/*.{twig,php}',
		//'./public/src/js/*.js',
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
