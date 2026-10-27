---
order: 3
---

# Dependency updates

[Dependabot](https://docs.github.com/en/code-security/dependabot) checks for updates once a week (configured in `.github/dependabot.yml`) and opens pull requests against `main`. CI runs on them like any other pull request.

| Ecosystem      | What it updates                                                                     | Pull requests                        |
| -------------- | ----------------------------------------------------------------------------------- | ------------------------------------ |
| npm            | The root `package.json` (tooling) and the theme's `package.json` (front-end)        | One for all updates, majors included |
| Composer       | `composer.json`: plugins from WordPress Packagist, Timber and the PHP tooling       | One for all updates, majors included |
| GitHub Actions | The workflows and `.github/actions/*`                                               | One for all updates                  |
| Docker         | Base images in the `Dockerfile` (`WORDPRESS_IMAGE`, `NODE_IMAGE`, `COMPOSER_IMAGE`) | One per image                        |
| Docker Compose | Service images in `docker-compose.yml`                                              | One per image                        |

Each pull request is labelled `dependencies` plus its ecosystem (`npm`, `php`, `docker` or `github-actions`).

## Reviewing a grouped pull request

The description lists every update with its release notes. Read the major bumps first: they are the most likely to need code changes, and a plugin's major release can change behaviour that the tests don't cover.

If one update breaks the build:

- **Fix it in the pull request.** Check out the Dependabot branch and push the fix. Dependabot stops rebasing a pull request once someone else has pushed to it, so merge it promptly.
- **Or hold it back.** Comment `@dependabot ignore <dependency> major version`. Dependabot closes the pull request, and its next run opens a new one without that update. `@dependabot unignore <dependency>` lifts the hold.

Ignores made by comment are stored by Dependabot, not in the repository. For a hold that should last, add it to `dependabot.yml` so it's visible and reviewed:

```yaml
- package-ecosystem: composer
  # …
  ignore:
    - dependency-name: wpackagist-plugin/redis-cache
      update-types: ['version-update:semver-major']
```

## Releasing updates

semantic-release only cuts a release for `fix:` and `feat:` commits (see [the pipeline](./deployment.md#the-pipeline)), so the prefix on a Dependabot commit decides whether merging it deploys. `dependabot.yml` sets the prefix for each ecosystem by one rule: **an update to what runs in production releases; an update to tooling waits for the next release.**

| Updates                                            | Commit                             | Merging it                   |
| -------------------------------------------------- | ---------------------------------- | ---------------------------- |
| Composer, including at least one `require` package | `fix(deps)`                        | Releases a patch and deploys |
| Composer, `require-dev` packages only              | `chore(deps-dev)`                  | Waits for the next release   |
| Docker base images                                 | `fix(deps)`                        | Releases a patch and deploys |
| npm, GitHub Actions, Docker Compose                | `chore(deps)` or `chore(deps-dev)` | Waits for the next release   |

Why each is set the way it is:

- **Composer releases** because plugins and Timber run on the site, and plugin releases are often security fixes. On a site that gets little feature work, a `chore` update could otherwise sit unreleased for weeks. Dependabot gives a grouped pull request the production prefix if any update in it is a `require` package, so one plugin bump is enough to release the whole group. The local-only plugins (Query Monitor, User Switching, WP Crontrol) and the PHP tooling are in `require-dev`, so they don't trigger a release on their own.
- **Docker releases** because the base images carry WordPress core, PHP and the build toolchain. An image update changes what runs even without a code change, and it's the only way WordPress and PHP security patches reach the container pathways. Hostinger provides its own core and PHP, so there these releases just redeploy the same code.
- **npm waits.** The theme's built CSS and JS do ship (Alpine.js is a runtime dependency; Tailwind, daisyUI and Vite produce the assets), but the weekly group mixes them with linters and test tools, and most bumps don't change the built assets. Releasing every week would mostly redeploy identical assets.
- **GitHub Actions and Docker Compose wait** because they only affect CI and local development.

To override the prefix, change the title when squash-merging. Rename a `chore` update to `fix(deps): …` to release it now, such as an Alpine.js fix or a daisyUI change you want live. Rename a `fix` update to `chore(deps): …` to merge it without releasing, for example to batch it with other changes.

## Security updates

The groups apply to the weekly version updates only. With Dependabot security updates enabled (Settings → Code security), each vulnerable dependency gets its own pull request as soon as an alert is raised, outside the weekly schedule. Check the prefix before merging: release anything that changes what ships straight away, renaming it to `fix(deps): …` if it arrived as `chore`.

## Private Composer repositories

Dependabot can only update packages it can reach. For plugins from a [private repository](../building/plugins.md#premium-and-private-plugins), add the repository to `dependabot.yml` with credentials from Dependabot secrets (Settings → Secrets and variables → Dependabot; Actions secrets aren't available to it):

```yaml
registries:
  composer-example:
    type: composer-repository
    url: https://composer.example.com
    username: ${{secrets.COMPOSER_EXAMPLE_USER}}
    password: ${{secrets.COMPOSER_EXAMPLE_PASSWORD}}

updates:
  - package-ecosystem: composer
    registries: [composer-example]
    # …
```

## Updating by hand

```sh
npm run composer -- update                                      # Composer packages, within their constraints
npm run composer -- require wpackagist-plugin/redis-cache:^3.0  # A new major
npm update                                                      # Root tooling
npm update --prefix wp-content/themes/whalepress                # Theme
```

Then run the checks in [Testing and standards](./testing.md) before opening a pull request, and commit with the prefixes above: `fix(deps): …` for updates that should release, `chore(deps): …` for tooling.
