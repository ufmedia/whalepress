# Database

Local development uses **MariaDB 11.8** (LTS) in the `db` service, with data in the `<project>_db_data` volume. WordPress connects with the credentials in `.env`.

Browse it with [Adminer](./local-services.md#adminer), or from the command line:

```sh
npm run wp -- db cli                      # Interactive MariaDB shell
npm run wp -- db query "SELECT ID, post_title FROM wp_posts LIMIT 5"
```

## Export and import

```sh
npm run db:export                         # local/var/db/<project>-<timestamp>.sql.gz
npm run db:export -- path/to/file.sql.gz

npm run db:import -- dump.sql.gz          # Replaces the local database
npm run db:import -- dump.sql.gz --from=https://www.example.com
```

`--from` runs `wp search-replace` from that URL to your `WP_HOME` (all tables with the site's prefix, serialised data included, GUIDs untouched), then flushes the object cache and rewrite rules. Exports and imports run the MariaDB client inside the `db` container, so nothing is needed on your machine. `local/var/` is gitignored.

## Pulling from another environment

`npm run db:pull` imports whatever SQL a shell command writes to stdout, then rewrites URLs. Set both variables in `.env`:

```sh
DB_PULL_FROM_URL=https://www.example.com

# A traditional server with WP-CLI
DB_PULL_COMMAND=ssh deploy@example.com "wp db export - --path=/var/www/html"

# Docker Swarm (GHCR pathway)
DB_PULL_COMMAND=ssh deploy@swarm-manager 'docker exec $(docker ps -qf name=mysite_wordpress) wp db export - --allow-root'

# Managed MySQL (AWS RDS, DigitalOcean) reachable from your machine or a bastion
DB_PULL_COMMAND=mysqldump --single-transaction -h db.example.com -u reader -p'…' wordpress
```

There is intentionally no `db:push`: overwriting a production database should be a deliberate, manual operation.

Pair a pulled database with the [uploads proxy](../building/wordpress.md#uploads-proxy) so media loads without copying the uploads folder.

## Seeding a fresh environment

`.sql` or `.sql.gz` files in `local/initial-db/` are imported automatically the **first time** the database volume is created. To start again from those files:

```sh
docker compose down -v   # Deletes the database volume
npm run up
```

Run `npm run db:import -- <file> --from=<url>` instead if the dump's URLs need rewriting.

## Upgrading from the MySQL-based setup

Earlier versions of WhalePress used MySQL 9 in a `<project>_mysql_data` volume. MariaDB uses a new volume, so the old one is left untouched. To bring data across, export it with the old stack (`docker compose exec db mysqldump …`) before upgrading, then `npm run db:import`.
