# Deployment

WhalePress has four deployment pathways. Three ship **immutable Docker images** (GHCR, AWS, DigitalOcean); the fourth syncs code to **Hostinger** managed hosting over SSH, for sites (such as demos) that don't need a container platform.

For the container pathways: The `production` target in the `Dockerfile` bakes in WordPress core, the built theme, Composer's `vendor/` and plugins, the must-use plugins and `wp-config.php`. Configuration comes from environment variables and secrets at runtime (see [WordPress configuration](../building/wordpress.md)), so one image runs in every environment.

The only state a container needs is the **database** and **uploads**: point it at a managed database and give `wp-content/uploads` a persistent volume (or offload media to object storage).

## The pipeline

```
pull request ──► PHP quality ─┐
                Assets       ├─► (all must pass)
                Image + E2E ─┘
push to main ──► same checks ──► semantic-release ──► deploy-ghcr       (on by default)
                                  (tag + GitHub       deploy-ecr        (DEPLOY_ECR=true)
                                   release)            deploy-docr       (DEPLOY_DOCR=true)
                                                       deploy-hostinger  (DEPLOY_HOSTINGER=true)
```

- [semantic-release](https://semantic-release.gitbook.io) reads the Conventional Commits since the last tag. If any are releasable, it tags `vX.Y.Z` and publishes GitHub release notes. If not, nothing is deployed.
- Each enabled container deploy workflow checks out the tag, builds the image with `APP_VERSION=X.Y.Z` (stamped into the theme's `style.css`), pushes `X.Y.Z`, `X.Y` and `latest` tags with SBOM and provenance attestations, then triggers the target platform.
- Every deploy workflow can also be run manually (**Actions → Deploy (…) → Run workflow**) with any existing version, which is handy for rollbacks.
- Container deploy jobs use a GitHub environment named `production`, and Hostinger uses `hostinger`. Add required reviewers there to gate releases.
- All third-party actions are pinned to commit SHAs; Dependabot keeps them current.

## Repository settings

| Setting            | Where    | Purpose                                                                                                                  |
| ------------------ | -------- | ------------------------------------------------------------------------------------------------------------------------ |
| `DEPLOY_GHCR`      | Variable | Set to `false` to stop pushing to GHCR.                                                                                  |
| `DEPLOY_ECR`       | Variable | `true` enables the AWS pathway.                                                                                          |
| `DEPLOY_DOCR`      | Variable | `true` enables the DigitalOcean pathway.                                                                                 |
| `DEPLOY_HOSTINGER` | Variable | `true` enables the Hostinger pathway.                                                                                    |
| `COMPOSER_AUTH`    | Secret   | Optional: credentials for private Composer repositories ([details](../building/plugins.md#premium-and-private-plugins)). |

## GHCR (GitHub Container Registry)

Needs no setup: the workflow authenticates with `GITHUB_TOKEN` and pushes to `ghcr.io/<owner>/<repo>`.

Optional: set a `GHCR_DEPLOY_WEBHOOK_URL` secret, and the workflow POSTs to it after pushing. Use this for a Portainer stack/service webhook, Coolify, Dokku, or a small endpoint on your server that runs `docker service update --image …`.

**Running it:** `infra/swarm/stack.yml` is a Docker Swarm stack for a single server or cluster behind Traefik. It has zero-downtime `start-first` updates with automatic rollback, an uploads volume, Redis, Docker secrets for credentials, and an [Ofelia](https://github.com/mcuadros/ofelia) job for WP-Cron.

```sh
printf '%s' 'db-password' | docker secret create db_password -
# …one secret per key in the file
docker stack deploy -c infra/swarm/stack.yml --with-registry-auth mysite
```

If the package is private, log in on the server with a token that has `read:packages`.

## AWS (ECR, optionally ECS)

| Setting                                      | Where     | Notes                                                                                        |
| -------------------------------------------- | --------- | -------------------------------------------------------------------------------------------- |
| `DEPLOY_ECR`                                 | Variable  | `true`                                                                                       |
| `AWS_REGION`                                 | Variable  | e.g. `eu-west-2`                                                                             |
| `AWS_ROLE_ARN`                               | Variable  | Recommended: an IAM role trusted for GitHub OIDC with ECR push (and ECS update) permissions. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | Secrets   | Only if you are not using OIDC.                                                              |
| `ECR_REPOSITORY`                             | Variable  | Defaults to the GitHub repository name. The ECR repository must exist.                       |
| `ECS_CLUSTER`, `ECS_SERVICE`                 | Variables | Optional: forces a new deployment of the service and waits for it to stabilise.              |

`infra/aws/ecs-task-definition.json` is a Fargate task definition using the `:latest` tag (so `--force-new-deployment` picks up each release), with Secrets Manager for credentials, EFS for uploads and CloudWatch logs. Pair it with RDS (MySQL/MariaDB) and, optionally, ElastiCache for `REDIS_HOST`. Run WP-Cron as an EventBridge scheduled task using the same task definition with the command `wp cron event run --due-now`.

**OIDC role trust policy** (replace the placeholders):

```json
{
  "Effect": "Allow",
  "Principal": {
    "Federated": "arn:aws:iam::<account>:oidc-provider/token.actions.githubusercontent.com"
  },
  "Action": "sts:AssumeRoleWithWebIdentity",
  "Condition": {
    "StringEquals": { "token.actions.githubusercontent.com:aud": "sts.amazonaws.com" },
    "StringLike": {
      "token.actions.githubusercontent.com:sub": "repo:<owner>/<repo>:environment:production"
    }
  }
}
```

## DigitalOcean (DOCR, optionally App Platform)

| Setting                     | Where    | Notes                                                      |
| --------------------------- | -------- | ---------------------------------------------------------- |
| `DEPLOY_DOCR`               | Variable | `true`                                                     |
| `DIGITALOCEAN_ACCESS_TOKEN` | Secret   | API token with registry (and apps) write access.           |
| `DOCR_REGISTRY`             | Variable | Your registry's name (`registry.digitalocean.com/<this>`). |
| `DOCR_REPOSITORY`           | Variable | Defaults to the GitHub repository name.                    |
| `DO_APP_ID`                 | Variable | Optional: deploy this App Platform app after pushing.      |

`infra/digitalocean/app.yaml` is an App Platform spec: the web service on `:latest`, a managed MySQL database, encrypted secrets and a scheduled job for WP-Cron. Create the app once with `doctl apps create --spec infra/digitalocean/app.yaml`, then set `DO_APP_ID`. App Platform has no persistent disk, so offload uploads to Spaces before launch. A Droplet running the Swarm stack is the alternative.

## Hostinger

`deploy-hostinger.yml` deploys to an existing WordPress install on Hostinger (shared, cloud or VPS with hPanel) over SSH. Hostinger owns WordPress core, `wp-config.php` and the database; the workflow syncs only what this project owns:

| Source                                  | Destination (`public_html/…`)   | Behaviour                                                |
| --------------------------------------- | ------------------------------- | -------------------------------------------------------- |
| `vendor/` (`composer install --no-dev`) | `vendor/`                       | Mirrored. An `.htaccess` denies web access.              |
| Built theme, minus sources              | `wp-content/themes/whalepress/` | Mirrored.                                                |
| Each Composer plugin                    | `wp-content/plugins/<name>/`    | Each mirrored individually; other plugins are untouched. |
| `wp-content/mu-plugins/*.php`           | `wp-content/mu-plugins/`        | Copied; Hostinger's own must-use plugins are untouched.  |

It then activates the theme and Composer plugins, flushes rewrites and the object cache, and purges LiteSpeed Cache if it is active. Uploads, other plugins and drop-ins on the server are never deleted. Like the other pathways it runs after a release, or manually for any version.

**One-off setup**

1. In hPanel, create the site with WordPress and set **PHP 8.3 or later** (Advanced → PHP Configuration).
2. Enable SSH access (Advanced → SSH Access) and add a deploy key's public half.
3. Add these to the repository (or the `hostinger` environment):

| Setting            | Where    | Value                                                                                                                                                                                                                                         |
| ------------------ | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `HOSTINGER`        | Secret   | JSON: `{"host": "…", "user": "u123456789", "port": "65002", "ssh_key": "-----BEGIN OPENSSH PRIVATE KEY-----\n…", "known_hosts": "…"}`. Get `known_hosts` from `ssh-keyscan -p 65002 <host>`; without it the host key is trusted on first use. |
| `HOSTINGER_DOMAIN` | Variable | The site's domain, e.g. `demo.example.com`. The web root is `/home/<user>/domains/<domain>/public_html`.                                                                                                                                      |
| `HOSTINGER_PATH`   | Variable | Optional: overrides the web root.                                                                                                                                                                                                             |
| `DEPLOY_HOSTINGER` | Variable | `true` to deploy after each release. Leave unset to deploy only manually.                                                                                                                                                                     |

4. Edit the site's `wp-config.php` on the server (File Manager or SSH) and add the settings this project would otherwise take from the environment:

```php
define( 'WP_ENVIRONMENT_TYPE', 'staging' ); // Not 'production': keeps a demo out of search engines.
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );        // Plugins and theme come from deployments.
```

`wp-config.php` can't load Composer's autoloader on Hostinger, so the [`whalepress-autoload`](../building/wordpress.md#autoload) must-use plugin does it instead. Settings read with `whalepress_env()` (Redis, `SMTP_*`, the uploads proxy) are inactive there unless you define them yourself; Hostinger's own SMTP and LiteSpeed plugins are the usual alternatives.

## Documentation site

These docs are built with [VitePress](https://vitepress.dev) and published to GitHub Pages by `.github/workflows/docs.yml` whenever `docs/` changes on `main`. They stay in the repository so code and documentation change together; CI builds them on every pull request, failing on dead links.

- **Preview locally:** `npm run docs:dev` (http://localhost:5174).
- **First-time setup:** in the repository settings, set **Pages → Build and deployment → Source** to **GitHub Actions**.
- **Navigation:** the sidebar is defined in `docs/.vitepress/config.mjs`. Add new pages there.
- **Base path:** the site is served from `/<repository>/`. For a custom domain, set the `DOCS_BASE` repository variable to `/` and add the domain in the Pages settings.

## Production checklist

- Unique keys and salts set as secrets (or deliberately left to WordPress's database fallback).
- `WP_HOME` set to the public URL; TLS terminated at the proxy or load balancer, which forwards `X-Forwarded-Proto`.
- `DISABLE_WP_CRON=true`, with a scheduler running `wp cron event run --due-now` every few minutes.
- Persistent uploads (volume, EFS, or object storage offload).
- `SMTP_*` configured for a transactional mail provider.
- `REDIS_HOST` pointing at Redis or Valkey.
- HSTS (and, if you can, a Content-Security-Policy) at the edge.
- Run the **OWASP ZAP baseline** and **E2E (deployed site)** workflows against staging before the first launch.

## Database changes between environments

Deployments carry code, not content. Move content with `npm run db:pull` (production → local; see [Database](../getting-started/database.md)), and make schema or option changes in code: from a service on `init`/an upgrade routine, or as WP-CLI commands run after deployment.
