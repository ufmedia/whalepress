![CI/CD](https://github.com/ufmedia/whalepress/actions/workflows/ci.yml/badge.svg)
![Docs](https://github.com/ufmedia/whalepress/actions/workflows/docs.yml/badge.svg)
![GitHub contributors](https://img.shields.io/github/contributors-anon/ufmedia/WhalePress)
![GitHub last commit](https://img.shields.io/github/last-commit/ufmedia/WhalePress)

<img src="docs/public/whalepress.png" align="center" height="332" width="400">

# WhalePress

A WordPress starter for developers who build bespoke sites in code: **Timber 2** templates, **Tailwind CSS 4 + Vite** assets with design tokens in `theme.json`, the block editor for content only, and immutable **Docker** images from local to production.

- **Local:** Docker Compose with MariaDB, Redis, Mailpit, Adminer and on-demand Xdebug; one-command setup; HMR; database pull and import with URL rewriting; an uploads proxy; a devcontainer.
- **Theme:** PSR-4 service classes, Twig views, Utopia fluid type and space generated into `theme.json` and mapped to Tailwind, and a content-only block editor whose styles match the front end.
- **Quality:** PHPCS (WPCS), PHPStan level 8, Rector, PHPUnit + Brain Monkey, ESLint, Stylelint, Prettier (Twig included), Playwright with axe, and Husky + lint-staged + commitlint.
- **Delivery:** every PR is tested against the production image; merges to `main` are released by semantic-release and deployed to **GHCR**, **AWS** (ECR/ECS), **DigitalOcean** (DOCR/App Platform) and/or **Hostinger**.

## Quick start

Requires Docker with Compose and Node 24. PHP is optional.

```sh
gh repo clone ufmedia/WhalePress my-site && cd my-site
nvm use
npm install
npm run setup   # .env, assets, containers, Composer, WordPress
npm start       # Containers + Vite dev server
```

## Documentation

**[ufmedia.github.io/whalepress](https://ufmedia.github.io/whalepress/)**, built from [`docs/`](docs/index.md) with VitePress. Preview locally with `npm run docs:dev`.

- **Getting started:** [Prerequisites](docs/getting-started/prerequisites.md) · [Installation](docs/getting-started/installation.md) · [Database](docs/getting-started/database.md) · [Local services](docs/getting-started/local-services.md)
- **Building:** [WordPress configuration](docs/building/wordpress.md) · [Theme](docs/building/theme.md) · [Plugins](docs/building/plugins.md)
- **Shipping:** [Testing and standards](docs/shipping/testing.md) · [Deployment](docs/shipping/deployment.md)
- **Reference:** [Further reading](docs/reference/further-reading.md) · [Troubleshooting](docs/reference/troubleshooting.md)

## License

GPL-2.0-or-later.
