<img src="https://github.com/ufmedia/WhalePress/blob/main/local/whalepress.png" align="center" height="332" width="400">

## WhalePress

WhalePress is a WordPress starter for teams who build bespoke sites in code. It pairs a containerised local environment with an opinionated, modern theme stack, and ships immutable Docker images to whichever host you deploy to.

**The stack**

- **Docker Compose** locally: WordPress, MariaDB, Redis, Mailpit and Adminer, with Xdebug on demand.
- **Timber 2 / Twig** for templates, with PSR-4 PHP classes for theme logic.
- **Tailwind CSS 4 + Vite** for assets, with hot module replacement and design tokens defined once in `theme.json`.
- **Block editor for content only**: no full-site editing; layout belongs to Twig.
- **Quality tooling**: PHPCS (WordPress standards), PHPStan, Rector, PHPUnit, ESLint, Stylelint, Prettier and Playwright with axe accessibility checks.
- **CI/CD**: every pull request runs the full suite against the production image; merges to `main` are released with semantic-release and deployed to GHCR, AWS (ECR/ECS) or DigitalOcean (DOCR/App Platform).

**Principles**

- _One source of truth._ Configuration comes from the environment; design tokens come from `theme.json`; dependencies come from lockfiles.
- _Immutable production._ Core, plugins and theme are baked into the image. Nothing is installed or updated from wp-admin in production.
- _Same image everywhere._ CI tests the exact image that gets deployed.

## Contents

1. Getting started
   - [Prerequisites](./1.0-Prerequisites.md)
   - [Installation](./1.1-Installation.md)
   - [Database](./1.2-Database.md)
   - [Local services](./1.3-Local-Services.md)
2. Building
   - [WordPress configuration](./2.0-WordPress.md)
   - [Theme](./2.1-Theme.md)
   - [Plugins](./2.2-Plugins.md)
3. Shipping
   - [Testing and standards](./3.0-Testing-and-Standards.md)
   - [Deployment](./3.1-Deployment.md)
4. Reference
   - [Further reading](./4.0-Further-Reading.md)
   - [Troubleshooting](./4.1-Troubleshooting.md)

## Something missing?

Open a [discussion](https://github.com/ufmedia/WhalePress/discussions) or an issue.
