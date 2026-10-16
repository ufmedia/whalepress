[![CI/CD](../../actions/workflows/ci.yml/badge.svg)](../../actions/workflows/ci.yml)
[![Docs](../../actions/workflows/docs.yml/badge.svg)](../../actions/workflows/docs.yml)

# WhalePress

WordPress with a Timber 2 (Twig) theme, Tailwind CSS 4 with daisyUI, Alpine.js and Vite, the block editor for content only, and Docker images from local to production.

## Quick start

Requires Docker with Compose and Node 24. PHP is optional.

```sh
nvm use
npm install
npm run setup   # .env, assets, containers, Composer, WordPress
npm start       # Containers + Vite dev server
```

Creating a new project from this one? See [Starting a new project](docs/getting-started/new-project.md).

## Documentation

The developer documentation is a wiki in [`docs/`](docs/index.md): add a Markdown file and it appears in the sidebar. It is published to GitHub Pages by the Docs workflow (the URL is under **Settings → Pages**). Preview it locally with `npm run docs:dev`.

- **Getting started:** [Prerequisites](docs/getting-started/prerequisites.md) · [Installation](docs/getting-started/installation.md) · [Database](docs/getting-started/database.md) · [Local services](docs/getting-started/local-services.md) · [Starting a new project](docs/getting-started/new-project.md)
- **Building:** [WordPress configuration](docs/building/wordpress.md) · [Theme](docs/building/theme.md) · [Plugins](docs/building/plugins.md)
- **Shipping:** [Testing and standards](docs/shipping/testing.md) · [Deployment](docs/shipping/deployment.md)
- **Reference:** [Further reading](docs/reference/further-reading.md) · [Troubleshooting](docs/reference/troubleshooting.md) · [Writing documentation](docs/reference/writing-docs.md)

## Licence

GPL-2.0-or-later.
