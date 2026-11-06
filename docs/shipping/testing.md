---
order: 1
---

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

| Project           | What it covers                                                                                                                                             |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `frontend`        | Desktop Chrome: layout, assets, skip link, 404s, search, single posts.                                                                                     |
| `frontend-mobile` | The same suite on a Pixel 7, plus the mobile navigation toggle and submenus.                                                                               |
| `setup`           | Logs in with `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD` and saves the session.                                                                                  |
| `admin`           | Dashboard, the block editor with theme styles in its canvas, a post round trip via the REST API, and theme patterns containing only valid, allowed blocks. |

The `expectAccessible()` fixture (in `tests/e2e/fixtures.js`) runs [axe-core](https://github.com/dequelabs/axe-core) against WCAG 2.2 A/AA and fails on serious or critical violations. Use it in any new page test.

Install browsers once with `npx playwright install chromium`.

## Commits and hooks

[Husky](https://typicode.github.io/husky/) installs two hooks when you run `npm install`:

- **pre-commit:** [lint-staged](https://github.com/lint-staged/lint-staged) runs ESLint, Stylelint, Prettier and PHPCBF/PHPCS on staged files only (`lint-staged.config.js`).
- **commit-msg:** [commitlint](https://commitlint.js.org) enforces [Conventional Commits](https://www.conventionalcommits.org), which semantic-release uses to choose the next version (`fix:` → patch, `feat:` → minor, `feat!:` or `BREAKING CHANGE:` → major).

## Continuous integration

`.github/workflows/ci.yml` runs four jobs in parallel on every pull request and push to `main`. All four are required to merge, so an update that breaks any stage of the pipeline, from local development to the production image, can't be merged (including by [Dependabot auto-merge](./dependency-updates.md#auto-merge)).

1. **PHP quality:** PHPCS (annotated inline with cs2pr), PHPStan, Rector, PHPUnit.
2. **Assets and formatting:** commitlint for the PR's commits, ESLint, Stylelint, Prettier, a production build of the theme, and a build of this documentation site (which fails on dead links).
3. **Image and end-to-end tests:** builds the production image, scans it with [Trivy](https://trivy.dev) (fails on fixable critical CVEs), runs it with `docker-compose.test.yml`, installs WordPress, seeds test content (`tests/e2e/seed.sh`), runs the [stack checks](#stack-checks) and the full Playwright suite, then checks `debug.log`.
4. **Development stack:** runs `npm run setup -- --yes` exactly as a developer would (the `dev` image with bind mounts, Composer in its container, MariaDB, Redis, Mailpit and Adminer from `docker-compose.yml`), then the same seed, stack checks, Playwright suite and `debug.log` check, plus Mailpit and Adminer. It catches updates that break local development but not the production image, such as a MariaDB major or a dev-only plugin.

On failure, the Playwright report and container logs are attached. Pushes to `main` that pass then [release and deploy](./deployment.md).

Two manual workflows test deployed environments: **E2E (deployed site)** runs the front-end Playwright projects, and **OWASP ZAP baseline** runs a passive security scan. Both read `HOST_URL` from the chosen GitHub environment.

To run the CI end-to-end setup locally:

```sh
docker compose -f docker-compose.test.yml up -d --build --wait
docker compose -f docker-compose.test.yml exec -T -u www-data wordpress bash -s < local/bin/install.sh
docker compose -f docker-compose.test.yml exec -T -u www-data wordpress bash -s < tests/e2e/seed.sh
export COMPOSE_FILE=docker-compose.test.yml WP_HOME=http://localhost:8099 WP_CLI_USER=www-data
tests/stack/check.sh services
BASE_URL=$WP_HOME WP_ADMIN_USER=admin WP_ADMIN_PASSWORD=password npx playwright test
tests/stack/check.sh debug-log
docker compose down -v
```

Always finish with `down -v`. The image declares `/var/www/html` as a volume, and Compose reuses it when it recreates the container, so without `-v` a rebuilt image keeps running the previous theme files.

### Stack checks

Healthy containers don't prove the services work together: the WordPress healthcheck only fetches a static file. `tests/stack/check.sh` checks the running stack end to end, from the host:

| Command                          | What it checks                                                                                                                                                                                                                                                                      |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/stack/check.sh services`  | WordPress reaches the database and a write reads back; the Redis object cache is connected and persists between requests; the site and REST API respond over HTTP. With `MAILPIT_URL` set, `wp_mail()` arrives in Mailpit; with `ADMINER_URL` set, Adminer logs in to the database. |
| `tests/stack/check.sh debug-log` | `wp-content/debug.log` has no PHP errors, warnings, notices or deprecations. Run it after the Playwright suite, so every page it visits has been rendered.                                                                                                                          |

It reads `WP_HOME` and the database settings from the environment or `.env`, and runs against the Compose project in the current directory (set `COMPOSE_FILE` for another). To check your local stack:

```sh
MAILPIT_URL=http://localhost:8025 ADMINER_URL=http://localhost:8080 tests/stack/check.sh services
```

`debug-log` reads the whole log, so locally it also reports anything logged before; empty `wp-content/debug.log` first to check from a clean slate.

### Test content

Some tests need content a fresh install doesn't have, and skip without it: the submenu tests need a menu item with children, and the featured image test needs a post with one. `tests/e2e/seed.sh` adds them (a page under the first primary menu item, and a newest post with the theme's screenshot as its featured image, a category and a tag). It is idempotent and meant only for throwaway sites; never run it against a real one. When a new test needs content, seed it there rather than letting the test skip in CI.

The only skips left in CI are deliberate: the mobile navigation toggle test runs on the mobile project only, and the desktop navigation test on the desktop project only.
