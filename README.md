![CI/CD](https://github.com/ufmedia/WhalePress/actions/workflows/ci.yml/badge.svg)
![GitHub contributors](https://img.shields.io/github/contributors-anon/ufmedia/WhalePress)
![GitHub last commit](https://img.shields.io/github/last-commit/ufmedia/WhalePress)

<img src="local/whalepress.png" align="center" height="332" width="400">

# WhalePress

A WordPress starter for developers who build bespoke sites in code: **Timber 2** templates, **Tailwind CSS 4 + Vite** assets with design tokens in `theme.json`, the block editor for content only, and immutable **Docker** images from local to production.

- **Local:** Docker Compose with MariaDB, Redis, Mailpit, Adminer and on-demand Xdebug; one-command setup; HMR; database pull and import with URL rewriting; an uploads proxy; a devcontainer.
- **Theme:** PSR-4 service classes, Twig views, Utopia fluid type and space generated into `theme.json` and mapped to Tailwind, and a content-only block editor whose styles match the front end.
- **Quality:** PHPCS (WPCS), PHPStan level 8, Rector, PHPUnit + Brain Monkey, ESLint, Stylelint, Prettier (Twig included), Playwright with axe, and Husky + lint-staged + commitlint.
- **Delivery:** every PR is tested against the production image; merges to `main` are released by semantic-release and deployed to **GHCR**, **AWS** (ECR/ECS) and/or **DigitalOcean** (DOCR/App Platform).

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

Start at [docs/Home.md](docs/Home.md).

|                                                                                                                                                                         |                 |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------- |
| [Prerequisites](docs/1.0-Prerequisites.md) · [Installation](docs/1.1-Installation.md) · [Database](docs/1.2-Database.md) · [Local services](docs/1.3-Local-Services.md) | Getting started |
| [WordPress configuration](docs/2.0-WordPress.md) · [Theme](docs/2.1-Theme.md) · [Plugins](docs/2.2-Plugins.md)                                                          | Building        |
| [Testing and standards](docs/3.0-Testing-and-Standards.md) · [Deployment](docs/3.1-Deployment.md)                                                                       | Shipping        |
| [Further reading](docs/4.0-Further-Reading.md) · [Troubleshooting](docs/4.1-Troubleshooting.md)                                                                         | Reference       |

## License

GPL-2.0-or-later.
