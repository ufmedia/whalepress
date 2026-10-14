---
order: 1
---

# WordPress configuration

`wp-config.php` is committed and reads everything from the environment, so the same file serves local development (`.env` through Compose) and every production pathway. Any variable can also be read from a file by setting `NAME_FILE=/path`, which is how Docker and Swarm secrets are consumed.

Empty values count as unset.

## Environment variables

### Core

| Variable                                              | Default                              | Notes                                                                                                                                              |
| ----------------------------------------------------- | ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `WP_ENVIRONMENT_TYPE`                                 | `production`                         | `local`, `development`, `staging` or `production`. Drives several defaults below.                                                                  |
| `WP_HOME`                                             | —                                    | Site URL; also used for `WP_SITEURL`. Unset to use the database values.                                                                            |
| `WORDPRESS_DB_HOST` / `_NAME` / `_USER` / `_PASSWORD` | `db` / `wordpress` / `wordpress` / — |                                                                                                                                                    |
| `WORDPRESS_DB_CHARSET` / `_COLLATE`                   | `utf8mb4` / —                        |                                                                                                                                                    |
| `WORDPRESS_TABLE_PREFIX`                              | `wp_`                                |                                                                                                                                                    |
| `WORDPRESS_AUTH_KEY` … `WORDPRESS_NONCE_SALT`         | —                                    | Eight keys and salts. If unset, WordPress generates random values and stores them in the database, so there is never a shared, committed fallback. |

### Debugging

| Variable                                       | Default |
| ---------------------------------------------- | ------- |
| `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY` | `false` |
| `WP_DISABLE_FATAL_ERROR_HANDLER`               | `false` |
| `SCRIPT_DEBUG`                                 | `false` |

Booleans accept `true`/`false`, `1`/`0`, `yes`/`no`, `on`/`off`. The log is `wp-content/debug.log`.

### Behaviour

| Variable                                  | Default                          | Notes                                                                                            |
| ----------------------------------------- | -------------------------------- | ------------------------------------------------------------------------------------------------ |
| `DISALLOW_FILE_MODS`                      | `true` outside local/development | Plugins, themes and core can't be installed or updated from wp-admin. Use Composer.              |
| `DISABLE_WP_CRON`                         | `false`                          | Set `true` where a system cron runs `wp cron event run --due-now` (all production templates do). |
| `WP_CACHE`                                | `false`                          | Only for page-cache plugins that provide `advanced-cache.php`.                                   |
| `WP_MEMORY_LIMIT` / `WP_MAX_MEMORY_LIMIT` | `256M` / `512M`                  | PHP's own `memory_limit` is 512M (`build/php.ini`).                                              |
| `WP_POST_REVISIONS`                       | `10`                             |                                                                                                  |
| `WP_ALLOW_MULTISITE`                      | `false`                          | See [Multisite](#multisite).                                                                     |

Always on: `DISALLOW_FILE_EDIT`, `AUTOMATIC_UPDATER_DISABLED` (updates ship as new images) and `FS_METHOD=direct`. HTTPS is detected behind proxies through `X-Forwarded-Proto`.

### Services

| Variable                                                                                             | Notes                                                                                                     |
| ---------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_PREFIX`                                         | Object cache. Empty `REDIS_HOST` disables it. The prefix defaults to the database name plus table prefix. |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASSWORD`, `SMTP_FROM`, `SMTP_FROM_NAME` | Outgoing mail; see [Mail](#mail).                                                                         |
| `UPLOADS_PROXY_URL`                                                                                  | See [Uploads proxy](#uploads-proxy).                                                                      |
| `APP_VERSION`                                                                                        | Set by the image build to the release version.                                                            |

Adding your own setting? Define it in `wp-config.php` with `whalepress_env( 'NAME', 'default' )` or `whalepress_env_bool()`, add it to `.env-example`, and document it here.

## Must-use plugins

`wp-content/mu-plugins/` holds behaviour that should apply regardless of theme.

### Autoload

`whalepress-autoload.php` loads Composer's `vendor/autoload.php` from beside `wp-content`. The project's `wp-config.php` already does this; the plugin covers hosts that use their own `wp-config.php`, such as [Hostinger](../shipping/deployment.md#hostinger). It is a no-op when the autoloader is already loaded.

### Hardening

`whalepress-hardening.php`:

- Removes the generator tag and disables XML-RPC (remove those lines if you need Jetpack or the mobile apps).
- Hides `/wp/v2/users` from anonymous REST requests and 404s `?author=N` enumeration.
- Generic login error messages.
- Sends `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` and `Permissions-Policy`. Add a CSP, or change any header, with the `whalepress/security_headers` filter. HSTS belongs at your proxy.

### Mail

`whalepress-mail.php`: when `SMTP_HOST` is set, PHPMailer is configured for SMTP: Mailpit locally, your provider in production (e.g. SES, Postmark or SendGrid SMTP credentials). `SMTP_FROM` sets the sender; WordPress's default `wordpress@localhost` fails validation.

### Environment

`whalepress-environment.php`: outside `production`, the site always reports "discourage search engines", whatever the database says, so staging can't be indexed by accident.

### Uploads proxy

Also in `whalepress-environment.php`: with `UPLOADS_PROXY_URL=https://www.example.com` in a local or development environment, requests for missing files under `wp-content/uploads` redirect to the same path on that site. Work against a production database without downloading the media library.

## WP-CLI

```sh
npm run wp -- plugin list
npm run wp -- user create editor editor@example.com --role=editor
npm run wp -- shell
```

Commands run in the `wordpress` container as the same user as Apache. In production images WP-CLI is at `/usr/local/bin/wp`; run it as `www-data` (e.g. `docker exec -u www-data <container> wp …`).

## Multisite

Set `WP_ALLOW_MULTISITE=true`, then visit **Tools → Network Setup**. Add the constants WordPress generates (`MULTISITE`, `SUBDOMAIN_INSTALL`, `DOMAIN_CURRENT_SITE`, etc.) to `wp-config.php`, ideally reading them from the environment like the rest of the file. The `.htaccess` rules go in `build/` and need copying in the Dockerfile. Subdirectory networks are simplest locally.
