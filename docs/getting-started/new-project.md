---
order: 5
---

# Starting a new project

Every project built on this stack starts as a copy of the [starter repository](https://github.com/ufmedia/whalepress). These steps turn a copy into a project with its own name and repository. Do them once, when the project is created; developers joining later only need [Installation](./installation.md).

## 1. Copy and rename

```sh
git clone https://github.com/ufmedia/whalepress.git my-project && cd my-project
nvm use
npm install
npm run init
```

`npm run init` asks for a **project name** in PascalCase (e.g. `Acme`) and a **slug** (e.g. `acme`), then renames everything that carries the current name:

| What                                                  | Example after renaming                                     |
| ----------------------------------------------------- | ---------------------------------------------------------- |
| Theme directory and `THEME_NAME` defaults             | `wp-content/themes/acme`                                   |
| PHP namespace                                         | `Acme\Theme`, `Acme\Tests\TestCase`                        |
| Text domain, hook names, function and option prefixes | `__( '…', 'acme' )`, `acme/security_headers`, `acme_env()` |
| Must-use plugins, translation template, docs logo     | `mu-plugins/acme-mail.php`, `languages/acme.pot`           |
| Package names, Compose project, image names, titles   | `acme/acme` in `composer.json`, `acme:ci`                  |

It needs a clean working tree, so the whole rename is one reviewable diff (`git diff`), undone with `git reset --hard`. It then offers to remove the `origin` remote. It can be re-run later to rename the project again.

## 2. Make it yours

- Replace the logo (`docs/public/<slug>.png`) and the theme's `screenshot.png`.
- Rewrite `README.md` for the project, and set `description` in `composer.json` and both `package.json` files.
- Set the default site title (`SITE_TITLE` in `local/bin/install.sh`).
- Remove the example feature flag in `switcheroo.json`, and any deployment workflow you won't use (`.github/workflows/deploy-*.yml`, plus its job in `ci.yml`).

## 3. Build and check

```sh
npm run setup
npm run lint && npm run analyse && npm run test
```

## 4. Push to the new repository

Create an empty repository (no README or licence), then:

```sh
git add -A
git commit -m "chore: initialise project"
git remote add origin git@github.com:<owner>/<repository>.git
git push -u origin main
```

Only `main` is pushed, not the starter's tags, so semantic-release publishes the project's first release as **v1.0.0**.

::: tip Fresh history
To start without the starter's commit history, run `git checkout --orphan fresh && git commit -m "chore: initialise project" && git branch -M fresh main` before pushing. The trade-off: [pulling starter updates](#pulling-in-starter-updates) then needs `--allow-unrelated-histories` and conflicts more.
:::

## 5. Repository settings

Out of the box, the first push runs CI, creates release v1.0.0 and pushes the image to GHCR, with no secrets needed. Configure the rest in the repository settings:

| Setting                                   | Where                                      | Why                                                                                                                   |
| ----------------------------------------- | ------------------------------------------ | --------------------------------------------------------------------------------------------------------------------- |
| Pages source: **GitHub Actions**          | Settings → Pages                           | Publishes these docs. Until then the Docs workflow builds them and skips publishing.                                  |
| Branch protection for `main`              | Settings → Rules                           | Require the CI checks (PHP quality, assets, image and end-to-end) and pull requests.                                  |
| `production` and `hostinger` environments | Settings → Environments                    | Add required reviewers to gate deployments; environment-level secrets keep credentials scoped.                        |
| Deployment variables and secrets          | Settings → Secrets and variables → Actions | Enable each pathway you use; see [Deployment](../shipping/deployment.md#repository-settings).                         |
| Dependabot alerts and security updates    | Settings → Code security                   | Version updates are already configured in `.github/dependabot.yml`.                                                   |
| Package visibility                        | The GHCR package's settings                | Images are private by default when the repository is private. Servers pulling them need a token with `read:packages`. |

## Pulling in starter updates

Improvements to the starter can be brought into a project with Git:

```sh
git remote add starter https://github.com/ufmedia/whalepress.git
git fetch starter
git cherry-pick <commit>     # Specific fixes
git merge starter/main       # Or everything (expect conflicts where files were renamed)
```

Conflicts arise where the project renamed or changed the same lines. Treat a merge like any other change: a branch, a pull request and green CI.
