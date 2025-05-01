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
		extend: {fontSize: {
        // note: keys must be valid class names, so we use `step--2` rather than `--step--2`
        'step--2': 'var(--step--2)',
        'step--1': 'var(--step--1)',
        'step-0' : 'var(--step-0)',
        'step-1' : 'var(--step-1)',
        'step-2' : 'var(--step-2)',
        'step-3' : 'var(--step-3)',
        'step-4' : 'var(--step-4)',
        'step-5' : 'var(--step-5)',
      },},
	},
	plugins: [],
};
