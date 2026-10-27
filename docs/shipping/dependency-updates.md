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

Dependabot's commits are `chore(deps)` or `chore(deps-dev)`, which semantic-release doesn't treat as releasable (see [the pipeline](./deployment.md#the-pipeline)). Merging one deploys nothing; the updates go out with the next `fix:` or `feat:` release. To ship an update straight away, such as a plugin's security fix, squash-merge it with the title changed to `fix(deps): …`.

## Security updates

The groups apply to the weekly version updates only. With Dependabot security updates enabled (Settings → Code security), each vulnerable dependency gets its own pull request as soon as an alert is raised, outside the weekly schedule. Release these straight away, as above.

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

Then run the checks in [Testing and standards](./testing.md) before opening a pull request.
