---
order: 4
---

# Local services

| Service   | URL                                       | Purpose                                                  |
| --------- | ----------------------------------------- | -------------------------------------------------------- |
| WordPress | `WP_HOME` (default http://localhost:8088) | The site, from the `dev` image target.                   |
| Adminer   | http://localhost:8080                     | Database browser. Server `db`, credentials from `.env`.  |
| Mailpit   | http://localhost:8025                     | Catches every email WordPress sends.                     |
| Redis     | internal                                  | Persistent object cache, as in production.               |
| Vite      | http://localhost:5173                     | Asset dev server with HMR (`npm start` / `npm run dev`). |

## What's mounted

Only `wp-content/`, `wp-config.php` and `vendor/` are mounted from the repository. WordPress core lives inside the container and is refreshed from the image whenever the container is recreated. Apache runs as your host user (`HOST_UID`/`HOST_GID` in `.env`), so files WordPress writes, such as uploads, stay editable on Linux.

`vendor/` is mounted beside `wp-content` at `/var/www/html/vendor`, mirroring the repository so Composer's autoloader paths resolve. Apache denies all HTTP access to it (`build/apache.conf`).

## Adminer

Log in with system **MySQL / MariaDB**, server **db**, and the `WORDPRESS_DB_USER` / `WORDPRESS_DB_PASSWORD` from `.env` (or `root` / `DB_ROOT_PASSWORD`).

## Mailpit

`SMTP_HOST=mailpit` routes all mail through the `whalepress-mail` must-use plugin to Mailpit. Nothing leaves your machine. The same plugin sends real mail in production when `SMTP_*` variables are set; see [WordPress configuration](../building/wordpress.md#mail).

## Redis

The [Redis Object Cache](https://wordpress.org/plugins/redis-cache/) plugin is installed by Composer and enabled by setup. `npm run wp -- redis status` shows its state. Leave `REDIS_HOST` empty to switch persistent caching off.

## Xdebug

Xdebug 3 is installed in the `dev` image and **off by default** (zero overhead).

1. Set `XDEBUG_MODE=debug` in `.env` (or `debug,develop`, `profile`, `coverage`).
2. `npm run up` to recreate the container.
3. Copy `local/.vscode-example/launch.json` to `.vscode/launch.json` and start **Listen for Xdebug**.

Path mappings cover `wp-content`, `vendor` and `wp-config.php`. Xdebug connects to `host.docker.internal:9003`, which Compose maps to your host on Linux too.

## Development plugins

`require-dev` in `composer.json` installs plugins only for local development; they are never in the production image:

- [Query Monitor](https://wordpress.org/plugins/query-monitor/)
- [User Switching](https://wordpress.org/plugins/user-switching/)
- [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)

## Devcontainer

`.devcontainer/` attaches VS Code (or any devcontainer client) to the `wordpress` service with Node 24 added, the whole repository at `/workspace`, and the stack's other services running. On first start it runs Composer and npm installs, builds assets and installs WordPress.

- Stop the regular stack first (`npm stop`) so the devcontainer starts a fresh `wordpress` container with the workspace mounted.
- Inside the container, run `wp …`, `composer …` and `npm run dev` directly. The `npm run wp` / `npm run composer` wrappers call Docker, which isn't available inside.
