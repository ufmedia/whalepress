---
layout: home

hero:
  name: WhalePress
  text: Bespoke WordPress, built like software.
  tagline: Timber 2 templates, Tailwind CSS 4 and Vite, immutable Docker images, and a full quality pipeline, from local to production.
  image:
    src: /whalepress.png
    alt: WhalePress
  actions:
    - theme: brand
      text: Get started
      link: /getting-started/installation
    - theme: alt
      text: Theme guide
      link: /building/theme
    - theme: alt
      text: GitHub
      link: https://github.com/ufmedia/WhalePress

features:
  - title: Local in one command
    details: Docker Compose with MariaDB, Redis, Mailpit, Adminer and on-demand Xdebug. Database pull and import with URL rewriting, an uploads proxy, and a devcontainer.
    link: /getting-started/local-services
  - title: Timber and Twig
    details: Thin PHP controllers, Twig views and PSR-4 service classes. Composer at the project root; no theme-level dependency soup.
    link: /building/theme#php-architecture
  - title: Tokens in theme.json
    details: Utopia fluid type and space generated into theme.json and mapped to Tailwind 4, so templates and the block editor share one design system.
    link: /building/theme#styling-tailwind-css-4-and-theme-json
  - title: Content-only block editor
    details: Curated blocks, preset-only design controls, and editor styles that match the front end. No full-site editing.
    link: /building/theme#the-block-editor
  - title: Quality built in
    details: PHPCS, PHPStan level 8, Rector, PHPUnit, ESLint, Stylelint, Prettier and Playwright with axe, enforced by hooks and CI.
    link: /shipping/testing
  - title: Ship anywhere
    details: Semantic releases deployed to GHCR, AWS ECR/ECS, DigitalOcean DOCR/App Platform, or Hostinger. Every PR is tested against the production image.
    link: /shipping/deployment
---

## Principles

- **One source of truth.** Configuration comes from the environment, design tokens from `theme.json`, and dependencies from lockfiles.
- **Immutable production.** Core, plugins and theme ship with each deployment. Nothing is installed or updated from wp-admin in production.
- **Test what you ship.** CI runs the end-to-end suite against the exact image that gets deployed.
- **Docs live with the code.** This site is built from the `docs/` folder of the repository, so it changes in the same pull requests as the code it describes.

## Quick start

Requires Docker with Compose and Node 24. PHP is optional.

```sh
gh repo clone ufmedia/WhalePress my-site && cd my-site
nvm use
npm install
npm run setup   # .env, assets, containers, Composer, WordPress
npm start       # Containers + Vite dev server
```

Then read [Installation](./getting-started/installation.md) for the full command reference.
