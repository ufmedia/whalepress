# Plugins

Plugins are managed with Composer, never through wp-admin (which is read-only in production; see `DISALLOW_FILE_MODS`).

## From wordpress.org

[WordPress Packagist](https://wpackagist.org) mirrors the plugin directory:

```sh
npm run composer -- require wpackagist-plugin/wordpress-seo
npm run wp -- plugin activate wordpress-seo
```

Composer installs plugins into `wp-content/plugins/` (gitignored), and the production image installs the same locked versions. Activation state lives in the database, so activate in each environment, or with WP-CLI as part of a deployment.

Use `require-dev` for plugins that should only exist locally, like the bundled Query Monitor, User Switching and WP Crontrol.

Must-use plugins from Composer (`type: wordpress-muplugin`) install into `wp-content/mu-plugins/<name>/`. Note that WordPress only auto-loads PHP files at the top level of `mu-plugins/`, so add a small loader file for them.

## Premium and private plugins

For plugins from a private Composer repository (Satis, Private Packagist, or a vendor's own, such as Gravity Forms or Delicious Brains), add the repository to `composer.json` and supply credentials through `COMPOSER_AUTH`:

```jsonc
// composer.json
"repositories": [
  { "type": "composer", "url": "https://composer.example.com" }
]
```

- **Locally:** `auth.json` in the project root (gitignored), or `npm run composer -- config http-basic.composer.example.com <user> <pass>`.
- **Docker builds:** credentials are passed as a BuildKit secret, so they never appear in an image layer:
  ```sh
  docker build --secret id=composer_auth,src=auth.json .
  ```
- **CI:** add a `COMPOSER_AUTH` repository secret containing the JSON, e.g. `{"http-basic":{"composer.example.com":{"username":"…","password":"…"}}}`. Every deploy workflow passes it to the build.

## Custom plugins

Plugins you write live in `wp-content/plugins/<name>/` and are committed. Two ignore files allow-list what is tracked and what goes into the image, so add an exception to both:

```gitignore
# .gitignore
!/wp-content/plugins/my-plugin/
```

```gitignore
# .dockerignore
!wp-content/plugins/my-plugin/
```

Site-wide behaviour that must not be deactivated belongs in `wp-content/mu-plugins/` instead (see the bundled [must-use plugins](./wordpress.md#must-use-plugins)). Those are tracked and copied into the image automatically.

## Bundled plugins

| Plugin                                     | Environment | Why                                                                   |
| ------------------------------------------ | ----------- | --------------------------------------------------------------------- |
| Redis Object Cache                         | All         | Persistent object cache; the drop-in is baked into production images. |
| Switcheroo                                 | All         | Feature flags (configured in `switcheroo.json`).                      |
| Query Monitor, User Switching, WP Crontrol | Local only  | Debugging.                                                            |
