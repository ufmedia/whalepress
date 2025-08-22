/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		'./**/*.{php,twig,js,scss}',
		'./src/scss/**/*.scss', // important!
		'./safelist.txt',
		'!./node_modules/**',
		'!./vendor/**',
		'!./dist/**',
	],
	theme: {
		extend: {},
	},
	plugins: [],
};
