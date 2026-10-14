# Writing documentation

This documentation is a wiki kept in the repository: every Markdown file in `docs/` is published, and the sidebar is generated from the files. Developers and agents add to it in the same pull request as the code it describes.

## Adding a page

1. Create a Markdown file in the folder for its section, e.g. `docs/building/search.md`.
2. Start it with a `# Heading`. That becomes the page's sidebar title.
3. Open a pull request. CI builds the docs and fails on broken links; merging to `main` publishes them.

No configuration changes are needed. A new folder becomes a new sidebar section, named after the folder (`docs/integrations/` → "Integrations") or, if the folder has one, its `index.md` heading.

## Ordering and visibility

Pages are sorted alphabetically by title unless they set an `order` in front matter:

```md
---
order: 2
---

# Theme
```

| Front matter     | Effect                                                                         |
| ---------------- | ------------------------------------------------------------------------------ |
| `order: <n>`     | Position within its section (lower first). Pages without one follow, by title. |
| `title: <text>`  | Sidebar title, if it should differ from the heading.                           |
| `sidebar: false` | Leaves the page out of the sidebar. It is still published and searchable.      |

Sections are ordered by their `index.md`'s `order`, then by the `sections` list in `docs/.vitepress/config.mjs`, then by name.

## Conventions

- **Write instructions, not marketing.** Describe how things work and what to do.
- **Keep it project-agnostic.** Refer to "the theme" and "the project"; use the real directory and namespace names in paths and code (`npm run init` keeps them correct when the project is renamed). Don't hard-code repository or site URLs.
- **Link relatively, with the extension:** `[Deployment](../shipping/deployment.md#hostinger)`. Dead links fail the build.
- **Images and downloads** go in `docs/public/` and are referenced from the root: `![Diagram](/diagram.png)`.
- Markdown extensions such as `::: tip` callouts, code groups and tables are available; see the [VitePress Markdown guide](https://vitepress.dev/guide/markdown).

## Previewing

```sh
npm run docs:dev      # http://localhost:5174, reloads as you edit
npm run docs:build    # What CI runs, including the dead-link check
```

Publishing is covered in [Deployment](../shipping/deployment.md#documentation-site).
