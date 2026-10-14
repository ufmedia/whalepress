## Installation

```sh
gh repo clone ufmedia/WhalePress my-site
cd my-site
git remote remove origin   # Starting a new project
nvm use
npm install
npm run setup
```

`npm run setup` is interactive and safe to re-run. It:

1. Checks for Node 24 and Docker Compose.
2. Creates `.env` from `.env-example`, asking for the project name, theme, port and URL, and generating salts and an admin password.
3. Installs the theme's npm dependencies and builds its assets.
4. Builds the `dev` image and starts the stack, waiting for health checks.
5. Runs `composer install` (in a container).
6. Installs WordPress, activates the theme and plugins, creates a starter menu and enables the Redis object cache.

When it finishes it prints the site URL and admin credentials (also stored in `.env` as `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD`).

## Daily use

```sh
npm start          # Start containers and the Vite dev server (HMR)
npm start -- --no-vite
npm stop           # Stop containers
npm run logs       # Tail WordPress/Apache logs
```

`npm start` leaves the containers running when you stop Vite with Ctrl+C. Without the dev server, the site uses the last production build in `dist/`.

## Command reference

| Command                                       | What it does                                                                  |
| --------------------------------------------- | ----------------------------------------------------------------------------- |
| `npm run setup`                               | First-run setup (see above).                                                  |
| `npm start` / `npm stop` / `npm run up`       | Start everything / stop / start containers only.                              |
| `npm run dev` / `npm run build`               | Vite dev server / production build of the theme.                              |
| `npm run wp -- <args>`                        | WP-CLI in the container, e.g. `npm run wp -- plugin list`.                    |
| `npm run composer -- <args>`                  | Composer, e.g. `npm run composer -- require wpackagist-plugin/wordpress-seo`. |
| `npm run db:export` / `db:import` / `db:pull` | Database tools, see [Database](./1.2-Database.md).                            |
| `npm run lint`                                | PHPCS, ESLint, Stylelint and Prettier.                                        |
| `npm run analyse` / `npm run rector`          | PHPStan / Rector dry run.                                                     |
| `npm run test`                                | PHPUnit, then Playwright.                                                     |
| `npm run format`                              | Format everything Prettier handles.                                           |
| `npm run i18n`                                | Generate `languages/whalepress.pot` from PHP and Twig.                        |

## Starting a new project from WhalePress

- Rename the theme directory, and update `THEME_NAME` in `.env-example`, the PSR-4 path in `composer.json`, the paths in `phpcs.xml.dist`, `phpstan.neon.dist`, `rector.php`, `lint-staged.config.js`, the root `package.json` scripts, `.gitignore`, `.dockerignore` and `THEME_DIR` in `.github/workflows/ci.yml`.
- Change the `WhalePress\` namespace, the `whalepress` text domain and hook prefixes if you want them to match the project.
- Replace `local/whalepress.png`, the theme `screenshot.png` and this documentation as you see fit.
