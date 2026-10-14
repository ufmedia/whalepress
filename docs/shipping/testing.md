# Testing and standards

Everything below runs in CI on every pull request, and the relevant subset runs on staged files before each commit.

```sh
npm run lint        # PHPCS + ESLint + Stylelint + Prettier
npm run analyse     # PHPStan
npm run rector      # Rector (dry run)
npm run test        # PHPUnit, then Playwright
npm run composer -- qa   # All PHP checks in one go
```

## PHP

| Tool                                                                                                                                   | Config              | Notes                                                                                                                                                                                                                                                        |
| -------------------------------------------------------------------------------------------------------------------------------------- | ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) + [WPCS](https://github.com/WordPress/WordPress-Coding-Standards) | `phpcs.xml.dist`    | `WordPress-Extra` and `WordPress-Docs`, with the `whalepress` text domain and prefixes. PSR-4 file names are allowed in `src/`. `npm run composer -- lint:fix` runs PHPCBF.                                                                                  |
| [PHPStan](https://phpstan.org) + [phpstan-wordpress](https://github.com/szepeviktor/phpstan-wordpress)                                 | `phpstan.neon.dist` | Level 8 over the theme and must-use plugins.                                                                                                                                                                                                                 |
| [Rector](https://getrector.com)                                                                                                        | `rector.php`        | PHP 8.3 sets plus dead code, code quality, type declarations and early return. Long array syntax and `array( $this, 'method' )` callbacks are kept, as WordPress conventions (and `remove_action()`) need them. Apply with `npm run composer -- rector:fix`. |
| [PHPUnit](https://phpunit.de) + [Brain Monkey](https://giuseppe-mazzapica.gitbook.io/brain-monkey)                                     | `phpunit.xml.dist`  | Unit tests in `tests/php/Unit`, with no WordPress or database needed. Stub WordPress functions per test.                                                                                                                                                     |

Unit tests extend `WhalePress\Tests\TestCase`, which sets up Brain Monkey. Because services take their dependencies through constructors (paths, URLs, environment), most logic can be tested with plain values. See `ViteTest`.

## Front end

| Tool                                                                                            | Config                                             | Scope                                                                              |
| ----------------------------------------------------------------------------------------------- | -------------------------------------------------- | ---------------------------------------------------------------------------------- |
| [ESLint](https://eslint.org) 10                                                                 | `wp-content/themes/whalepress/eslint.config.js`    | Theme JS. Formatting rules are left to Prettier.                                   |
| [Stylelint](https://stylelint.io)                                                               | `wp-content/themes/whalepress/stylelint.config.js` | `stylelint-config-standard`, aware of Tailwind 4 at-rules.                         |
| [Prettier](https://prettier.io) + [Twig plugin](https://github.com/zackad/prettier-plugin-twig) | `.prettierrc.json`                                 | JS, CSS, Twig, JSON, YAML and Markdown across the repository. PHP is PHPCBF's job. |

The Twig formatter can't parse tags inside an HTML tag. Use conditional attribute values instead:

```twig
{# Not parseable #}
<a href="{{ item.link }}" {% if item.current %}aria-current="page"{% endif %}>

{# Use instead #}
<a href="{{ item.link }}" aria-current="{{ item.current ? 'page' : 'false' }}">
```

## End-to-end: Playwright

```sh
npm run test:e2e               # Against WP_HOME from .env
npm run test:e2e:ui            # Interactive UI mode
BASE_URL=https://staging.example.com npx playwright test --project=frontend
```

`playwright.config.js` defines four projects:

| Project           | What it covers                                                                                       |
| ----------------- | ---------------------------------------------------------------------------------------------------- |
| `frontend`        | Desktop Chrome: layout, assets, skip link, 404s, search, single posts.                               |
| `frontend-mobile` | The same suite on a Pixel 7, plus the mobile navigation toggle.                                      |
| `setup`           | Logs in with `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD` and saves the session.                            |
| `admin`           | Dashboard, the block editor with theme styles in its canvas, and a post round trip via the REST API. |

The `expectAccessible()` fixture (in `tests/e2e/fixtures.js`) runs [axe-core](https://github.com/dequelabs/axe-core) against WCAG 2.2 A/AA and fails on serious or critical violations. Use it in any new page test.

Install browsers once with `npx playwright install chromium`.

## Commits and hooks

[Husky](https://typicode.github.io/husky/) installs two hooks when you run `npm install`:

- **pre-commit:** [lint-staged](https://github.com/lint-staged/lint-staged) runs ESLint, Stylelint, Prettier and PHPCBF/PHPCS on staged files only (`lint-staged.config.js`).
- **commit-msg:** [commitlint](https://commitlint.js.org) enforces [Conventional Commits](https://www.conventionalcommits.org), which semantic-release uses to choose the next version (`fix:` → patch, `feat:` → minor, `feat!:` or `BREAKING CHANGE:` → major).

## Continuous integration

`.github/workflows/ci.yml` runs three jobs in parallel on every pull request and push to `main`:

1. **PHP quality:** PHPCS (annotated inline with cs2pr), PHPStan, Rector, PHPUnit.
2. **Assets and formatting:** commitlint for the PR's commits, ESLint, Stylelint, Prettier, a production build of the theme, and a build of this documentation site (which fails on dead links).
3. **Image and end-to-end tests:** builds the production image, scans it with [Trivy](https://trivy.dev) (fails on fixable critical CVEs), runs it with `docker-compose.test.yml` and runs the full Playwright suite against it. On failure, the Playwright report and container logs are attached.

Pushes to `main` that pass then [release and deploy](./deployment.md).

Two manual workflows test deployed environments: **E2E (deployed site)** runs the front-end Playwright projects, and **OWASP ZAP baseline** runs a passive security scan. Both read `HOST_URL` from the chosen GitHub environment.

To run the CI end-to-end setup locally:

```sh
docker compose -f docker-compose.test.yml up -d --build --wait
docker compose -f docker-compose.test.yml exec -T -u www-data wordpress bash -s < local/bin/install.sh
BASE_URL=http://localhost:8099 WP_ADMIN_USER=admin WP_ADMIN_PASSWORD=password npx playwright test
docker compose -f docker-compose.test.yml down -v
```
