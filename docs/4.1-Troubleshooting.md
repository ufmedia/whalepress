## Troubleshooting

**`npm run setup` says Node 24 is required.**
Run `nvm use` (or install Node 24) in the project directory. Husky's hooks also load nvm, so commits work from GUI clients.

**Port already allocated.**
Another container or service is using a port. Change `WP_PORT` (and `WP_HOME` to match), `ADMINER_PORT`, `MAILPIT_PORT` or `VITE_PORT` in `.env`, then `npm run up`.

**The site has no styles.**
Either run `npm start` (dev server) or `npm run build`. If the page references `localhost:5173` while Vite isn't running, a stale `hot` file was left behind by a crash: delete `wp-content/themes/whalepress/hot`. It's only honoured in `local`/`development` environments, so it can never affect production.

**"Composer dependencies are missing".**
Run `npm run composer -- install`.

**Edits aren't showing up in the container (Docker Desktop).**
Docker Desktop's file sharing can serve a stale copy of a file that a tool replaced via rename, rather than writing in place (some editors, `sed -i`). Recreate the container with `docker compose up -d --force-recreate wordpress`. New and in-place-edited files are unaffected.

**Permission errors writing uploads (Linux).**
Apache runs as `HOST_UID:HOST_GID` from `.env`. Make sure they match `id -u` / `id -g`, then `npm run up`.

**Emails don't arrive.**
Locally, check Mailpit (http://localhost:8025). `SMTP_FROM` must be a valid address; `wordpress@localhost` is rejected.

**The object cache serves stale data after an import.**
`npm run db:import` flushes it automatically; otherwise run `npm run wp -- cache flush`.

**WordPress core is outdated locally.**
Core comes from the image. After changing `WORDPRESS_IMAGE` in the `Dockerfile`, run `docker compose build wordpress && docker compose up -d --force-recreate wordpress`.

**Playwright admin tests are skipped.**
`WP_ADMIN_USER` and `WP_ADMIN_PASSWORD` must be set in `.env` (setup does this) and must match a real administrator.

**Trivy fails CI on a base-image CVE.**
Only fixable critical vulnerabilities fail the build. Bump `WORDPRESS_IMAGE` (Dependabot opens these PRs), or, if no fix exists upstream yet, add the CVE to a `.trivyignore` file with a comment and a review date.
