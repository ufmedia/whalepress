---
order: 2
---

# The theme

`wp-content/themes/whalepress` is a classic (non-FSE) theme: PHP controllers pass data to Twig views through [Timber 2](https://timber.github.io/docs/v2/), theme logic lives in PSR-4 classes, and Vite builds the front end: [Tailwind CSS 4](https://tailwindcss.com) with [daisyUI 5](https://daisyui.com) components, and [Alpine.js](https://alpinejs.dev) for interactivity. The UI is a library of Twig components, each called with parameters. The block editor is for **post content only**.

```
whalepress/
├── assets/
│   ├── css/          app.css (front end), editor.css (editor canvas) and partials
│   ├── icons/        SVGs for the icon() Twig function
│   └── js/           app.js entry point and Alpine components/
├── dist/             Vite build output (gitignored)
├── languages/        Translations; `npm run i18n` writes whalepress.pot here
├── patterns/         Block patterns for editors
├── scripts/          Fluid scale generator and the Vite hot-file plugin
├── src/              PSR-4 classes: namespace WhalePress\
├── templates/
│   ├── layouts/      The document shell
│   ├── components/   Every reusable piece of UI
│   └── *.twig        Views, chosen by the template hierarchy
├── *.php             Template hierarchy controllers
├── functions.php     Boots WhalePress\Theme
└── theme.json        Design tokens and editor settings
```

## PHP architecture

Composer is at the project root: Timber and the theme's `WhalePress\` namespace are autoloaded from `vendor/`, which `wp-config.php` loads. There is no `composer.json` in the theme.

`functions.php` boots `WhalePress\Theme`, which initialises Timber and registers a list of **services**. Each service implements `WhalePress\Contracts\Hookable`: its constructor only stores dependencies, and `register()` attaches hooks. Services are easy to find, test and remove.

| Service                                    | Responsibility                                                                                                                     |
| ------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------- |
| `Setup\ThemeSupport`                       | Theme supports, image sizes, menus, the Footer widget area, page excerpts, text domain. Removes block templates and core patterns. |
| `Setup\Cleanup`                            | Removes emoji scripts, RSD and shortlinks from `wp_head`.                                                                          |
| `Assets\Vite`                              | Enqueues built assets from the manifest, or the dev server when it is running.                                                     |
| `Assets\ColorScheme`                       | Restores the visitor's light/dark choice before first paint (see [Light and dark](#light-and-dark)).                               |
| `Editor\BlockEditor`                       | Content-only editor: allowed blocks, no remote patterns, block directory, font library or Openverse.                               |
| `Editor\BlockStyles`                       | Extra block styles (Card, Lead, Eyebrow, Plain), styled in `content.css`.                                                          |
| `Content\PostTypes` / `Content\Taxonomies` | Register custom post types and taxonomies from a definitions array (examples in the docblocks).                                    |
| `Views\Context`                            | Global Twig context: `menus`, rendered `widgets`, `environment`.                                                                   |
| `Views\Twig`                               | Twig functions: `component()` and `icon()`.                                                                                        |
| `Views\Forms`                              | Brings WordPress's own forms into the UI: `get_search_form()` renders the search-form component; comment form button classes.      |

**Adding a service:** create a class in `src/` that implements `Hookable`, then add it to `Theme::services()`. Plugins can also filter the list with `whalepress/services`.

Conventions: `declare(strict_types=1)`, typed properties and returns, `array( $this, 'method' )` callbacks (so they can be unhooked), and hooks named `whalepress/<name>`. Rector and PHPStan level 8 enforce most of this.

## Templates

Each WordPress template file is a thin controller: it builds the Timber context and renders a list of Twig views, the first that exists winning.

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

`templates/` has three kinds of file:

- **The layout**, `layouts/base.twig`: the document shell. It renders the `site-header` and `site-footer` components around a `content` block. The body is a flex column so the footer sits at the bottom of short pages. `<main>` has `py-xl` padding; a view that opens with a full-bleed section sets `{% set main_class = '' %}` outside its blocks to remove it.
- **Views** (`*.twig` at the top level): one per template-hierarchy entry. A view extends the layout and composes components with the data its controller provides; it holds little markup of its own.
  - `front-page.twig`: a hero, the page's content, the latest posts and, when the home page is the blog, a search prompt. On a static front page the hero comes from the page itself (title, excerpt and featured image), and its button links to the posts page, labelled with that page's title.
  - `index.twig`: post lists (the blog, archives, search) as a grid of cards. The blog and category archives also get category links from `index.php` and `archive.php`; remove those lines to drop them.
  - `single.twig`: a post with its categories, featured image, tags, previous/next links and comments.
- **Components** (`components/*.twig`): every reusable piece of UI, below.

`header.php`/`footer.php` are a compatibility shim for plugins that render their own pages and call `get_header()` (WooCommerce, The Events Calendar). Their output is wrapped in `page-plugin.twig`. Delete both files if you don't need them.

Global context available to views: Timber's defaults (`site`, `user`, `theme`, `body_class`, `request`) plus `menus` (keyed by location), `widgets` (rendered widget areas, keyed by ID) and `environment`. Components don't see it; views pass what each needs.

### Components

A component is a Twig file in `templates/components/`, rendered with the `component()` function and a hash of parameters:

```twig
{{
  component(
    'button',
    {
      label: __('Get in touch', 'whalepress'),
      url: '/contact/',
      icon: 'arrow-right'
    }
  )
}}
```

- **Only its parameters.** A component sees the props it is given and nothing else from the calling template, so it behaves the same wherever it is used. Pass everything it needs.
- **Documented in place.** Each file starts with a comment listing its parameters and an example. That comment is the reference; the table below is a map.
- **Slots are parameters.** Components that wrap content (`section`, `cta`) take it as a `content` prop. Capture markup with `{% set %}`, or pass another component:

  ```twig
  {% set cards %}
    <div class="auto-grid">
      {% for item in posts %}
        {{
          component(
            'post-card',
            {
              post: item
            }
          )
        }}
      {% endfor %}
    </div>
  {% endset %}

  {{
    component(
      'section',
      {
        title: __('Latest posts', 'whalepress'),
        content: cards
      }
    )
  }}
  {{
    component(
      'cta',
      {
        title: __('Looking for something?', 'whalepress'),
        content: component('search-form')
      }
    )
  }}
  ```

- **Fallbacks.** Pass a list and the first component that exists is used, e.g. `component(['post-card-' ~ post.post_type, 'post-card'], { post: post })`, so adding `post-card-project.twig` changes how projects are listed.
- **Styling.** Use [daisyUI](https://daisyui.com/components/) classes where a component fits (`btn`, `card`, `menu`, `navbar`, `join`, `input`, `badge`, `swap`, `avatar`, `link`) and Tailwind utilities for layout and spacing. Accept a `class` prop for extra classes from the caller.
- **Behaviour.** Interactive components use an Alpine component from `assets/js/components` (see [JavaScript](#javascript)).

| Component                               | Purpose                                                                                                        |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `button`, `buttons`                     | A link or `<button>` (daisyUI `btn`) with variants, sizes and an icon; a row of them from a list.              |
| `heading`                               | A heading at a variable level, so components keep a correct outline wherever they are placed.                  |
| `card`, `post-card`                     | A card with image, meta, title and text, clickable as a whole when it has a URL; a post as a card.             |
| `hero`                                  | Lead section: title, optional eyebrow, text, buttons and image.                                                |
| `section`                               | Vertical rhythm, container, an optional `h2` that labels the section as a region, and buttons beside it.       |
| `cta`                                   | A contained call to action with buttons, or any markup (e.g. a form).                                          |
| `page-header`                           | The `h1` with optional eyebrow, meta and description.                                                          |
| `site-header`, `site-footer`            | The header (site name, primary menu, mobile menu toggle, theme toggle) and footer (tagline, widgets, menu, ©). |
| `menu`, `menu-item`                     | A WordPress menu, recursive. Items with children get a submenu disclosure.                                     |
| `theme-toggle`                          | The light/dark switch.                                                                                         |
| `search-form`                           | Search input and button (daisyUI `join`). Also replaces core's `get_search_form()`.                            |
| `post-terms`, `tag-list`, `term-filter` | Categories as an eyebrow; tags as badges; category links to filter a post list.                                |
| `pagination`, `post-navigation`         | Numbered pages (daisyUI `join`); previous/next post links.                                                     |
| `comments`, `comment`                   | The comment thread and core's comment form.                                                                    |

**Adding a component:** create `templates/components/{name}.twig` with a header comment, then call `component('{name}', { … })`. Names are lowercase letters, digits and hyphens.

### Twig helpers

```twig
{{
  component(
    'card',
    {
      title: post.title,
      url: post.link
    }
  )
}}
{# A component with its props #}
{{ icon('menu', 'size-6') }} {# Decorative inline SVG from assets/icons #}
{{ icon('close', 'size-6', 'Close menu') }} {# With an accessible label #}
{{ __('Read more', 'whalepress') }} {# Translations: always pass the text domain #}
```

## Styling: Tailwind CSS 4, daisyUI and theme.json

There is no Sass and no `tailwind.config.js`; Tailwind 4 and daisyUI are configured in CSS (`assets/css/theme.css`).

**Design tokens live in `theme.json`.** WordPress turns every preset into a custom property (`--wp--preset--color--primary`, `--wp--preset--font-size--step-2`, `--wp--preset--spacing--m`) on the front end _and_ in the editor. `theme.css` maps those properties onto daisyUI's theme and Tailwind's, so components, utilities and the editor's palette all agree:

| Tokens                                                         | Utilities                                                                               |
| -------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Colours, as daisyUI theme colours (below)                      | `bg-base-200`, `text-base-content/70`, `border-base-300`, `bg-primary`, `btn-primary`   |
| Fluid type: `step-minus-2` … `step-6`                          | `text-step-0`, `text-step-4` (line heights included)                                    |
| Fluid space: `3-xs` … `3-xl`, plus `s-m`, `m-l`, `l-xl`, `s-l` | `p-s`, `gap-m`, `mt-xl`, `py-s-l`                                                       |
| Layout: content and wide sizes                                 | `max-w-content`, `max-w-wide`, and the `container-content` / `container-wide` utilities |
| Fonts: `body`, `mono`                                          | `font-sans`, `font-mono`                                                                |

Tailwind's default palette and spacing scale remain available alongside these. `layout.css` also adds `auto-grid`, a responsive grid with no breakpoints (as many columns as fit at `--auto-grid-min`, default `18rem`).

### daisyUI theme

daisyUI's built-in themes are switched off. One theme, `whalepress`, takes its colours from the `theme.json` palette:

| theme.json (editor palette)   | daisyUI                      |
| ----------------------------- | ---------------------------- |
| `background`                  | `base-100`                   |
| `surface`                     | `base-200`                   |
| `border`                      | `base-300`                   |
| `foreground`                  | `base-content`, `neutral`    |
| `primary`, `primary-contrast` | `primary`, `primary-content` |

Colours editors don't need (`secondary`, `accent`, `info`, `success`, `warning`, `error`) are defined in the theme block in `theme.css`, as are daisyUI's radius, size, border and effect variables (`--depth` and `--noise` are off for a flat look). For muted text use `text-base-content/70`. Change a colour in `theme.json` and the editor, block content and every component follow; add a theme colour editors should pick by adding it to both.

### Light and dark

Every colour is a [`light-dark()`](https://developer.mozilla.org/docs/Web/CSS/color_value/light-dark) pair, and `base.css` sets `color-scheme: light dark`, so the single daisyUI theme follows the visitor's system preference with no `dark:` classes and no second theme. Use the theme colours rather than Tailwind's palette and dark mode comes for free.

- **Toggle.** The `theme-toggle` component (Alpine `colorScheme`, `assets/js/components/color-scheme.js`) lets visitors override the system setting. Their choice is stored in `localStorage` and applied as `data-theme="light|dark"` on `<html>`; `Assets\ColorScheme` prints a tiny inline script in `<head>` that restores it before first paint. The button is hidden (`x-cloak`) until Alpine starts, so it never appears without JavaScript. `data-theme` here only sets `color-scheme`; it doesn't select a daisyUI theme.
- **One-off overrides.** The `dark:` variant follows the same rules (system preference unless `data-theme` says otherwise), e.g. `dark:hidden` on an image only suited to light backgrounds.
- **Changing colours.** Edit both values of a pair in `theme.json` (or `theme.css` for status colours), keeping text at 4.5:1 contrast against `background` and `surface` in both schemes. The e2e suite runs axe against the home page in each scheme.
- **Editor.** The editor canvas declares no colour scheme, so it shows the light values.
- **Content Security Policy.** The inline script is printed with `wp_print_inline_script_tag()`, so add a nonce through the `wp_inline_script_attributes` filter. See also Alpine's [CSP build](#javascript).

### Fluid scales (Utopia)

Type and space scales are [Utopia](https://utopia.fyi)-style `clamp()` values. To change them, edit the config at the top of `scripts/fluid-scales.js` and run:

```sh
npm --prefix wp-content/themes/whalepress run tokens
```

This rewrites the `fontSizes` and `spacingSizes` presets in `theme.json`. Add or remove matching lines in `theme.css` if you change the number of steps.

> WordPress kebab-cases preset slugs and splits digits from letters (`3xs` becomes `3-xs`), so slugs are written in that form from the start to keep every name identical.

### Stylesheets

- `app.css`: front-end entry. Tailwind with explicit `@source` paths (templates, `src/`, root PHP, JS), plus the partials below. Tailwind only generates the classes, daisyUI's included, that it finds in those files, so write class names in full (`btn-primary`, not `'btn-' ~ variant`).
- `editor.css`: loaded into the editor iframe through `enqueue_block_assets`. No preflight, utilities or daisyUI, so nothing leaks into the editor UI.
- `theme.css`: the daisyUI plugin and theme, and the Tailwind theme, mapped from `theme.json`.
- `content.css`: typography and flow layout for block content, shared by `.entry-content` and `.editor-styles-wrapper` so the editor matches the front end. It uses only `--wp--preset--*` properties, which exist in both. It is deliberately unlayered with zero-specificity `:where()` selectors. Where a core block's own single-class styles would win (buttons, tables, pullquotes, details, separators), a second section scopes rules to the wrapper to outrank them. It also holds the block style variations.
- `base.css`: colour scheme, focus styles, `[x-cloak]`, `.screen-reader-text` and the skip link.
- `components.css`: daisyUI styles for markup WordPress generates, applied with `@apply`: the comment and password forms, forms that plugins put in post content, and the Footer widget area. Everything else uses classes in `templates/components`.
- `layout.css`, `fonts.css`: container and grid utilities, and self-hosted fonts.

**Cascade note:** WordPress's own CSS (global styles, block library) is unlayered and therefore beats anything in Tailwind's layers. That's why `theme.json` defines presets only and no element styles. Put styling in CSS, not in `theme.json` `styles`.

One case catches templates too: the block library sets `:where(figure) { margin: 0 0 1em }`, which beats any Tailwind margin utility on a `<figure>` (`mx-auto`, `container-wide`, `mb-l`). Put margins on a wrapping `<div>` instead.

Fonts are self-hosted from [Fontsource](https://fontsource.org) and bundled by Vite. Swap the package in `fonts.css` and update the `body` font family in `theme.json`.

## JavaScript

All interactivity is [Alpine.js](https://alpinejs.dev). `assets/js/app.js` (loaded as an ES module with `wp_enqueue_script_module`) registers each behaviour as an Alpine component and starts Alpine:

| Alpine component | File                         | Used by                                                                                                                                           |
| ---------------- | ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `disclosure`     | `components/disclosure.js`   | The mobile menu and submenus: a toggle that shows a region, closed by Escape (focus returns to the toggle) or a click outside (`menu-item.twig`). |
| `colorScheme`    | `components/color-scheme.js` | The light/dark toggle.                                                                                                                            |

Conventions:

- **Logic in components, names in markup.** Write behaviour in an `Alpine.data()` component and reference only its properties and methods in Twig: `x-data="disclosure"`, `x-on:click="toggle"`, `x-bind:aria-expanded="open"`. Templates stay readable, and switching to Alpine's [CSP build](https://alpinejs.dev/advanced/csp) (`@alpinejs/csp`, which can't evaluate inline expressions) is a one-line import change if the site adds a strict Content Security Policy.
- **State in ARIA, styles in CSS.** Bind state to the attribute that describes it (`aria-expanded`, `aria-pressed`, or `data-open` when there is none) and style it with Tailwind variants: `aria-expanded:swap-active`, `max-lg:data-open:block`. This keeps markup free of class expressions, and screen readers hear the same state that sighted users see.
- **Long attribute names.** The components use `x-on:` and `x-bind:` rather than the `@` and `:` shorthands, so Alpine attributes are easy to spot and search for.
- **Without JavaScript.** Controls that do nothing without Alpine are hidden with `x-cloak` until it starts.

To add a behaviour, create `assets/js/components/{name}.js` exporting a function that returns the component, register it in `app.js` with `Alpine.data('{name}', …)`, and use it from a Twig component. `window.Alpine` is set, so plugins that use Alpine share this instance.

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

- `theme.json` disables custom colours, gradients, duotone, font sizes, margins, borders and shadows, so editors pick from the presets only. Padding is on, limited to the spacing scale.
- `Editor\BlockEditor::ALLOWED_BLOCKS` lists the available blocks. Change it, or filter `whalepress/allowed_blocks`. The widget editors also get the blocks they need to manage widget areas.
- Full-site editing, block templates, core and remote patterns, the font library, block directory and Openverse are all off.
- Pages support excerpts, which the front page uses as the hero's lead text.

Content defaults, all in `content.css`: groups with a background colour get padding and rounded corners; full-width groups keep their content off the screen edge; tables, pullquotes, details (an accordion), separators and quotes match the palette in both colour schemes.

### Block styles

`Editor\BlockStyles` registers extra options in each block's Styles panel. Change the list there or filter `whalepress/block_styles`; each style's CSS is an `.is-style-{name}` rule in `content.css` (a unit test checks every style has one).

| Block         | Style   | Use                                                                           |
| ------------- | ------- | ----------------------------------------------------------------------------- |
| Group, Column | Card    | Bordered, padded panel. On columns, a row of cards stretches to equal height. |
| Paragraph     | Lead    | Larger, muted introduction.                                                   |
| Paragraph     | Eyebrow | Small uppercase label above a heading.                                        |
| List          | Plain   | No bullets or indent.                                                         |

### Patterns

Starting-point sections live in `patterns/`, one PHP file each, and appear in the inserter's Patterns tab: feature columns, a stats band, text beside an image, a testimonial, FAQs and a call to action. WordPress registers every file in the folder from its header comment (`Title`, `Slug`, `Categories`). Strings are wrapped in `esc_html_e()` so they can be translated.

To add one, build the section in the editor, copy it with **Copy all blocks** (or from the code editor), and save it as `patterns/{name}.php` with a header. Use only allowed blocks and theme presets; an e2e test fails if any pattern contains invalid or disallowed blocks.

WordPress caches the list of pattern files until the theme's version changes. Locally, `WP_DEVELOPMENT_MODE` is `theme` (see [WordPress](./wordpress.md)), which turns the cache off; elsewhere, bump the version in `style.css` or flush the object cache after adding a pattern.

### Widget area

The Footer widget area (Appearance > Widgets) takes the same blocks as post content: an address, opening hours, a short list of links. Register more in `Setup\ThemeSupport::register_widget_areas()` and add them to `widgets` in `Views\Context`, which renders each with `Timber::get_widgets('id')`. That returns the markup; calling `dynamic_sidebar()` from Twig would also print its return value.

### Forms

The theme ships no form builder: pick a plugin per project, add it with Composer (see [Plugins](./plugins.md)) and place its form with the Shortcode block or the plugin's own block (add that to the allowed list). Inputs and submit buttons inside `.entry-content` already pick up the theme's form styles.

Building custom blocks? Register them from a service (`register_block_type()` with a `block.json`), add their entry points to `vite.config.js`, and add them to the allowed list.

## Translations

All strings use the `whalepress` text domain. `npm run i18n` extracts strings from PHP and Twig (`local/i18n-twig.js` pre-processes the templates, since WP-CLI doesn't read Twig) into `languages/whalepress.pot`.
