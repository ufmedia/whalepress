/**
 * Generates Utopia-style fluid type and space scales into theme.json.
 *
 * theme.json is the single source of truth for design tokens: WordPress turns
 * each preset into a CSS custom property (e.g. --wp--preset--font-size--step-0)
 * for both the front end and the block editor, and assets/css/theme.css maps
 * those properties onto Tailwind's theme.
 *
 * Edit the config below, then run `npm run tokens`.
 *
 * @see https://utopia.fyi
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const config = {
	viewport: { min: 640, max: 1536 },
	type: {
		min: { size: 16, scale: 1.2 },
		max: { size: 20, scale: 1.25 },
		negativeSteps: 2,
		positiveSteps: 6,
	},
	// Multipliers of the step-0 type size.
	space: {
		sizes: {
			'3-xs': 0.25,
			'2-xs': 0.5,
			xs: 0.75,
			s: 1,
			m: 1.5,
			l: 2,
			xl: 3,
			'2-xl': 4,
			'3-xl': 6,
		},
		// One-up and custom pairs that interpolate between two sizes.
		pairs: [
			['s', 'm'],
			['m', 'l'],
			['l', 'xl'],
			['s', 'l'],
		],
	},
};

const REM = 16;
const round = (value) => Number(value.toFixed(4));

/**
 * A clamp() that scales linearly from `min` px to `max` px across the viewport.
 *
 * @param {number} min Size at the minimum viewport, in px.
 * @param {number} max Size at the maximum viewport, in px.
 * @return {string} CSS clamp() expression.
 */
export function clamp(min, max) {
	const { min: vMin, max: vMax } = config.viewport;
	const slope = (max - min) / (vMax - vMin);
	const intercept = min - slope * vMin;
	const lower = Math.min(min, max);
	const upper = Math.max(min, max);

	return `clamp(${round(lower / REM)}rem, ${round(intercept / REM)}rem + ${round(
		slope * 100,
	)}vw, ${round(upper / REM)}rem)`;
}

function typeScale() {
	const { min, max, negativeSteps, positiveSteps } = config.type;
	const sizes = [];

	for (let step = -negativeSteps; step <= positiveSteps; step++) {
		const slug = step < 0 ? `step-minus-${Math.abs(step)}` : `step-${step}`;
		sizes.push({
			slug,
			name: `Step ${step}`,
			size: clamp(min.size * min.scale ** step, max.size * max.scale ** step),
			fluid: false,
		});
	}

	return sizes;
}

function spaceScale() {
	const { sizes, pairs } = config.space;
	const minBase = config.type.min.size;
	const maxBase = config.type.max.size;

	const single = Object.entries(sizes).map(([slug, multiplier]) => ({
		slug,
		name: slug.toUpperCase(),
		size: clamp(minBase * multiplier, maxBase * multiplier),
	}));

	const paired = pairs.map(([from, to]) => ({
		slug: `${from}-${to}`,
		name: `${from.toUpperCase()} → ${to.toUpperCase()}`,
		size: clamp(minBase * sizes[from], maxBase * sizes[to]),
	}));

	return [...single, ...paired];
}

const themeJsonPath = fileURLToPath(new URL('../theme.json', import.meta.url));
const themeJson = JSON.parse(readFileSync(themeJsonPath, 'utf8'));

themeJson.settings.typography.fontSizes = typeScale();
themeJson.settings.spacing.spacingSizes = spaceScale();

writeFileSync(themeJsonPath, `${JSON.stringify(themeJson, null, '\t')}\n`);

console.log('Updated fontSizes and spacingSizes in theme.json');
