# AGENTS.md

Guidance for AI coding agents (and humans) working in this repository. Full documentation is in `docs/`, a wiki published to GitHub Pages. Update the relevant page in the same change as the code. New pages need only a Markdown file with a `# Heading`; the sidebar is generated (see `docs/reference/writing-docs.md`).

## What this is

A WordPress project built on a shared stack: Timber 2 (Twig) classic theme with PSR-4 classes, Tailwind CSS 4 + daisyUI 5 + Alpine.js built by Vite, block editor for **content only** (no full-site editing), Docker for local and production, Composer at the project root.

The project's name appears as the theme directory, PHP namespace, text domain and `<slug>_`/`<slug>/` prefixes. `npm run init` renames all of them; never rename them by hand. Keep documentation project-agnostic: describe how things work, not what the project is, and don't hard-code repository URLs (the docs config derives them).

## Layout

- `wp-content/themes/whalepress/`: the theme.
  - `src/`: PHP, namespace `WhalePress\` (autoloaded by the root `composer.json`). Services implement `Contracts\Hookable` and are registered in `Theme::services()`.
  - `templates/`: Twig. `layouts/base.twig` is the shell, top-level `*.twig` are views, and all reusable UI is in `components/`, rendered with `component('name', { …props })`. Root `*.php` files are thin controllers.
  - `assets/css`, `assets/js`: Vite entries `app.css`, `editor.css`, `app.js`. `theme.json` is the source of design tokens; `assets/css/theme.css` maps them to the daisyUI theme and Tailwind. Alpine components live in `assets/js/components/` and are registered in `app.js`.
- `wp-content/mu-plugins/`: hardening, mail and environment behaviour.
- `wp-config.php`: environment-driven; read new settings with `whalepress_env()` / `whalepress_env_bool()`.
- `local/`: Node scripts behind the npm commands. `tests/php` (PHPUnit), `tests/e2e` (Playwright).
- WordPress core is **not** in the repository; it lives in the container.

## Commands

Run from the repository root:

```sh
npm run lint            # PHPCS, ESLint, Stylelint, Prettier check
npm run analyse         # PHPStan (level 8)
npm run rector          # Rector dry run
npm run test:php        # PHPUnit
npm run test:e2e        # Playwright (needs the stack running: npm run up)
npm run build           # Theme production build
npm run wp -- <args>    # WP-CLI in the container
npm run composer -- <args>
```

PHP tools use host PHP when suitable, otherwise a container (`local/php.js`).

## Conventions

- PHP: WordPress Coding Standards (tabs, `array()`, Yoda conditions), `declare(strict_types=1)`, typed signatures, `array( $this, 'method' )` callbacks, hooks named `whalepress/<name>`, text domain `whalepress`. Keep service constructors side-effect free.
- Never assign `$post` in template files (it overwrites the global); use `$timber_post`.
- Twig: two-space indentation, single quotes, translations via `__('…', 'whalepress')`. No Twig tags inside HTML tags (the formatter can't parse them); use conditional attribute values. Components get only the props passed to them; document each one's props in its header comment.
- CSS: daisyUI classes and Tailwind utilities in templates, written in full so Tailwind finds them; plain CSS with native nesting in `assets/css`. No Sass. Don't add `styles` to `theme.json` (WordPress's unlayered CSS would override Tailwind).
- JS: Alpine.js only. Logic goes in an `Alpine.data()` component; markup references its properties and methods by name (no inline expressions), and state is exposed through ARIA/data attributes styled with Tailwind variants.
- Commits: Conventional Commits (`feat:`, `fix:`, `chore:` …); semantic-release derives versions from them.
- Dependencies: plugins via Composer (wpackagist), never committed. Front-end dependencies in the theme's `package.json`; tooling in the root `package.json`.

## Before finishing a change

Run `npm run lint`, `npm run analyse` and `npm run test:php`. For template, CSS or JS changes, also `npm run build` and `npm run test:e2e` against a running stack.
