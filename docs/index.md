# Overview

This is the developer documentation for the project: how to run it locally, how the code is organised, and how changes are tested and deployed. It is built from the repository's `docs/` folder, so update it in the same pull request as the code it describes.

## The stack

| Layer          | What's used                                                                                     | Guide                                                 |
| -------------- | ----------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| Local services | Docker Compose: WordPress (Apache, PHP 8.4), MariaDB, Redis, Mailpit, Adminer, Xdebug           | [Local services](./getting-started/local-services.md) |
| Configuration  | `wp-config.php` read from environment variables; must-use plugins for hardening and mail        | [WordPress configuration](./building/wordpress.md)    |
| Theme          | Classic theme: Twig components, PSR-4 classes, Tailwind CSS 4, daisyUI, Alpine.js and Vite      | [Theme](./building/theme.md)                          |
| Editor         | Block editor for post content only (no full-site editing), styled from `theme.json` tokens      | [Theme](./building/theme.md#the-block-editor)         |
| Dependencies   | Composer at the project root (plugins via WordPress Packagist); npm for tooling and assets      | [Plugins](./building/plugins.md)                      |
| Quality        | PHPCS, PHPStan, Rector, PHPUnit, ESLint, Stylelint, Prettier, Playwright, commit hooks          | [Testing and standards](./shipping/testing.md)        |
| Delivery       | semantic-release, then a Docker image to GHCR, AWS or DigitalOcean, or an SSH sync to Hostinger | [Deployment](./shipping/deployment.md)                |

## Ground rules

- **Configuration comes from the environment.** Nothing environment-specific is committed; `.env` locally, secrets and variables in production.
- **Production is immutable.** Core, plugins and theme ship with each release. Nothing is installed or updated from wp-admin.
- **Dependencies are declared, never committed.** Composer and npm lockfiles are the source of truth.
- **CI tests what ships.** The end-to-end suite runs against the production image before it is released.

## Where to start

- **Joining the project:** [Prerequisites](./getting-started/prerequisites.md), then [Installation](./getting-started/installation.md).
- **Creating a new project from this stack:** [Starting a new project](./getting-started/new-project.md).
- **Something not working:** [Troubleshooting](./reference/troubleshooting.md).
