## The theme

`wp-content/themes/whalepress` is a classic (non-FSE) theme: PHP controllers pass data to Twig views through [Timber 2](https://timber.github.io/docs/v2/), theme logic lives in PSR-4 classes, and Vite builds Tailwind CSS 4 and ES modules. The block editor is for **post content only**.

```
whalepress/
├── assets/
│   ├── css/          app.css (front end), editor.css (editor canvas) and partials
│   ├── icons/        SVGs for the icon() Twig function
│   └── js/           app.js entry point and modules/
├── dist/             Vite build output (gitignored)
├── languages/        Translations; `npm run i18n` writes whalepress.pot here
├── scripts/          Fluid scale generator and the Vite hot-file plugin
├── src/              PSR-4 classes: namespace WhalePress\
├── templates/        Twig views
├── *.php             Template hierarchy controllers
├── functions.php     Boots WhalePress\Theme
└── theme.json        Design tokens and editor settings
```

## PHP architecture

Composer is at the project root: Timber and the theme's `WhalePress\` namespace are autoloaded from `vendor/`, which `wp-config.php` loads. There is no `composer.json` in the theme.

`functions.php` boots `WhalePress\Theme`, which initialises Timber and registers a list of **services**. Each service implements `WhalePress\Contracts\Hookable`: its constructor only stores dependencies, and `register()` attaches hooks. Services are easy to find, test and remove.

| Service                                    | Responsibility                                                                                       |
| ------------------------------------------ | ---------------------------------------------------------------------------------------------------- |
| `Setup\ThemeSupport`                       | Theme supports, image sizes, menus, text domain. Removes block templates and core patterns.          |
| `Setup\Cleanup`                            | Removes emoji scripts, RSD and shortlinks from `wp_head`.                                            |
| `Assets\Vite`                              | Enqueues built assets from the manifest, or the dev server when it is running.                       |
| `Editor\BlockEditor`                       | Content-only editor: allowed blocks, no remote patterns, block directory, font library or Openverse. |
| `Content\PostTypes` / `Content\Taxonomies` | Register custom post types and taxonomies from a definitions array (examples in the docblocks).      |
| `Views\Context`                            | Global Twig context: `menus.primary`, `menus.footer`, `environment`.                                 |
| `Views\Twig`                               | Twig functions, e.g. `icon()`.                                                                       |

**Adding a service:** create a class in `src/` that implements `Hookable`, then add it to `Theme::services()`. Plugins can also filter the list with `whalepress/services`.

Conventions: `declare(strict_types=1)`, typed properties and returns, `array( $this, 'method' )` callbacks (so they can be unhooked), and hooks named `whalepress/<name>`. Rector and PHPStan level 8 enforce most of this.

## Templates

Each WordPress template file is a thin controller: it builds the Timber context and renders a list of Twig templates, the first that exists winning.

| Controller       | Twig candidates                                                                                              |
| ---------------- | ------------------------------------------------------------------------------------------------------------ |
| `front-page.php` | `front-page.twig`, then `page.twig` (static front page) or `home.twig` → `index.twig` (latest posts)         |
| `index.php`      | `home.twig`, `index.twig`                                                                                    |
| `page.php`       | `page-{slug}.twig`, `page.twig`                                                                              |
| `single.php`     | `single-{type}-{slug}.twig`, `single-{type}.twig`, `single.twig`; `single-password.twig` for protected posts |
| `archive.php`    | `archive-{post_type}.twig` or `archive-{taxonomy}.twig`, `archive.twig`, `index.twig`                        |
| `author.php`     | `author.twig`, `archive.twig`, `index.twig`                                                                  |
| `search.php`     | `search.twig`, `archive.twig`, `index.twig`                                                                  |
| `404.php`        | `404.twig`                                                                                                   |

So a bespoke page often needs only a Twig file, e.g. `templates/page-contact.twig`. Add a matching `page-contact.php` when it also needs extra data.

Views are organised as:

- `base.twig`: the document shell with `header`, `content` and `footer` blocks.
- `sections/`: site header and footer.
- `partials/`: reusable fragments (`tease`, `pagination`, `comments`, `page-header`).
- `components/`: macros, e.g. the recursive `menu.twig`.

`header.php`/`footer.php` are a compatibility shim for plugins that render their own pages and call `get_header()` (WooCommerce, The Events Calendar). Their output is wrapped in `page-plugin.twig`. Delete both files if you don't need them.

Global context available everywhere: Timber's defaults (`site`, `user`, `theme`, `body_class`, `request`) plus `menus` and `environment`.

### Twig helpers

```twig
{{ icon('menu', 'size-6') }} {# Decorative inline SVG from assets/icons #}
{{ icon('close', 'size-6', 'Close menu') }} {# With an accessible label #}
{{ __('Read more', 'whalepress') }} {# Translations: always pass the text domain #}
```

## Styling: Tailwind CSS 4 and theme.json

There is no Sass and no `tailwind.config.js`; Tailwind 4 is configured in CSS.

**Design tokens live in `theme.json`.** WordPress turns every preset into a custom property (`--wp--preset--color--primary`, `--wp--preset--font-size--step-2`, `--wp--preset--spacing--m`) on the front end _and_ in the editor. `assets/css/theme.css` maps those properties onto Tailwind's theme, so templates use ordinary utilities and the editor shows the same palette and sizes:

| Tokens                                                                 | Tailwind utilities                                                                      |
| ---------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Colours: `background`, `foreground`, `primary`, `secondary`, `neutral` | `bg-primary`, `text-foreground`, `border-neutral/20`                                    |
| Fluid type: `step-minus-2` … `step-6`                                  | `text-step-0`, `text-step-4` (line heights included)                                    |
| Fluid space: `3-xs` … `3-xl`, plus `s-m`, `m-l`, `l-xl`, `s-l`         | `p-s`, `gap-m`, `mt-xl`, `py-s-l`                                                       |
| Layout: content and wide sizes                                         | `max-w-content`, `max-w-wide`, and the `container-content` / `container-wide` utilities |
| Fonts: `body`, `mono`                                                  | `font-sans`, `font-mono`                                                                |

Tailwind's default palette and spacing scale remain available alongside these.

### Fluid scales (Utopia)

Type and space scales are [Utopia](https://utopia.fyi)-style `clamp()` values. To change them, edit the config at the top of `scripts/fluid-scales.js` and run:

```sh
npm --prefix wp-content/themes/whalepress run tokens
```

This rewrites the `fontSizes` and `spacingSizes` presets in `theme.json`. Add or remove matching lines in `theme.css` if you change the number of steps.

> WordPress kebab-cases preset slugs and splits digits from letters (`3xs` becomes `3-xs`), so slugs are written in that form from the start to keep every name identical.

### Stylesheets

- `app.css`: front-end entry. Tailwind with explicit `@source` paths (templates, `src/`, root PHP, JS), plus the partials below.
- `editor.css`: loaded into the editor iframe through `enqueue_block_assets`. No preflight or utilities, so it can't leak into the editor UI.
- `content.css`: typography and flow layout for block content, shared by `.entry-content` and `.editor-styles-wrapper` so the editor matches the front end. It is deliberately unlayered with zero-specificity `:where()` selectors.
- `base.css`, `layout.css`, `fonts.css`: base styles, container utilities and self-hosted fonts.

**Cascade note:** WordPress's own CSS (global styles, block library) is unlayered and therefore beats anything in Tailwind's layers. That's why `theme.json` defines presets only and no element styles. Put styling in CSS, not in `theme.json` `styles`.

Fonts are self-hosted from [Fontsource](https://fontsource.org) and bundled by Vite. Swap the package in `fonts.css` and update the `body` font family in `theme.json`.

## JavaScript

`assets/js/app.js` is loaded as an ES module (`wp_enqueue_script_module`). Put features in `assets/js/modules/` and initialise them from `app.js`. The included `navigation.js` is an accessible disclosure toggle (`aria-expanded`, Escape to close, focus return) that Tailwind styles through the `data-open:` variant.

## Vite

```sh
npm start        # From the root: containers + Vite
npm run dev      # Vite only
npm run build    # Production build to dist/
```

While the dev server runs, it writes its URL to `wp-content/themes/whalepress/hot`. `WhalePress\Assets\Vite` sees the file (in `local` and `development` environments only) and enqueues `@vite/client` and the source entries from `http://localhost:5173`, giving instant CSS updates and HMR. When the server stops, the file is removed and the site falls back to `dist/manifest.json`, whose content-hashed filenames make cache busting automatic.

- The dev server allows CORS from `WP_HOME` and any `localhost` origin. Change the port with `VITE_PORT` in `.env`.
- The manifest is written to `dist/manifest.json` rather than Vite's default `dist/.vite/`, because Docker Desktop's file sharing hides `.vite` directories from containers.

## The block editor

Content editing uses the block editor; templates and layout don't.

- `theme.json` disables custom colours, gradients, duotone, font sizes, spacing controls, borders and shadows, so editors pick from the presets only.
- `Editor\BlockEditor::ALLOWED_BLOCKS` lists the available blocks. Change it, or filter `whalepress/allowed_blocks`.
- Full-site editing, block templates, core and remote patterns, the font library, block directory and Openverse are all off.

Building custom blocks? Register them from a service (`register_block_type()` with a `block.json`), add their entry points to `vite.config.js`, and add them to the allowed list.

## Translations

All strings use the `whalepress` text domain. `npm run i18n` extracts strings from PHP and Twig (`local/i18n-twig.js` pre-processes the templates, since WP-CLI doesn't read Twig) into `languages/whalepress.pot`.
