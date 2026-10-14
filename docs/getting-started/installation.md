---
order: 2
---

# Installation

Creating the project for the first time? Follow [Starting a new project](./new-project.md) first.

```sh
git clone <repository-url> my-project
cd my-project
nvm use
npm install
npm run setup
```

`npm run setup` is interactive and safe to re-run. It:

1. Checks for Node 24 and Docker Compose.
2. Creates `.env` from `.env-example`, asking for the Compose project name, port and URL, and generating salts and an admin password.
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
| `npm run db:export` / `db:import` / `db:pull` | Database tools, see [Database](./database.md).                                |
| `npm run lint`                                | PHPCS, ESLint, Stylelint and Prettier.                                        |
| `npm run analyse` / `npm run rector`          | PHPStan / Rector dry run.                                                     |
| `npm run test`                                | PHPUnit, then Playwright.                                                     |
| `npm run format`                              | Format everything Prettier handles.                                           |
| `npm run i18n`                                | Generate `languages/whalepress.pot` from PHP and Twig.                        |
| `npm run docs:dev` / `docs:build`             | Preview / build this documentation site (VitePress).                          |
| `npm run init`                                | Rename the project ([Starting a new project](./new-project.md)).              |
