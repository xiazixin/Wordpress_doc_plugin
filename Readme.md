# Docs Layout

**Docs Layout** is a small WordPress plugin that turns any page into documentation with a three-column layout:

- **Left sidebar** — docs navigation managed from the **Docs Nav** admin screen (Projects → Groups → Docs) with drag-and-drop ordering, or an automatic fallback to the page hierarchy.
- **Middle** — the page content.
- **Right sidebar** — an "On this page" table of contents built from the page's `H2`/`H3` headings, with scrollspy highlighting.

There is no build step and no external dependency: the admin editor uses jQuery UI Sortable, which ships with WordPress.

## Features

- **Page template** — "Docs Layout (left nav + TOC)" appears in the editor's template list and is applied per page.
- **Managed navigation tree** — build Projects → Groups → Docs from the **Docs Nav** admin screen; add, remove, and reorder rows by dragging their handle.
- **Automatic fallback** — pages using the template with no managed navigation configured fall back to the page hierarchy.
- **On-page table of contents** — generated from `H2` (sections) and `H3` (sub-sections) headings; missing anchor ids are created on the fly and the section in view stays highlighted while scrolling.
- **Resilient rendering** — nav entries pointing at deleted or unpublished pages are skipped, and groups/projects left empty after that are pruned automatically. Titles and links are resolved live, so page renames and permalink changes are picked up without re-saving the navigation.
- **Sticky, responsive layout** — sidebars stick below the admin bar when logged in and stack into a single column on screens 1100px and narrower.
- **Scoped assets** — the front-end CSS/JS load only on pages that use the template.

## Requirements

- WordPress 5.9+ recommended — the template renders the theme's header and footer through `block_template_part()` when available.
- No PHP extensions, libraries, or build tools are required. jQuery UI Sortable (bundled with WordPress) powers the admin drag-and-drop.

## Installation

1. Zip the plugin folder (the folder containing this `Readme.md`).
2. In the WordPress dashboard, go to **Plugins → Add New Plugin → Upload Plugin**.
3. Click **Choose File**, select the zipped folder, and click **Install Now**.
4. Click **Activate Plugin**.

## Usage

### 1. Apply the template

1. Create or edit the page that should use the docs layout.
2. In the editor sidebar, set **Template** to **Docs Layout (left nav + TOC)** (in the classic editor: **Page Attributes → Template**).
3. Save the page and view it on the front end.

Assign the template to every page that should use the layout, including all child and sibling doc pages you want navigable.

### 2. Build the left navigation (optional)

1. In wp-admin, open **Docs Nav** (document icon, below Comments).
2. Click **+ Add project**, give it a name, then **+ Add group** inside it, then **+ Add documentation** and pick a published page from the dropdown.
3. Drag rows by the handle to reorder projects, groups, and docs.
4. Click **Save navigation**.

Behavior notes:

- Projects/groups without a title and docs without a selected page are dropped on save.
- When the current page belongs to the tree, the sidebar shows only its project; on any other docs-layout page the whole tree is shown.
- Only published pages are selectable, and docs whose target page is currently unpublished are skipped on the front end without losing their slot in the tree.

### 3. Fallback navigation

When no managed navigation is configured (or none of its entries resolve), the left sidebar uses the page hierarchy instead:

- For a page with a parent, it lists the parent (the "hub") plus all of the hub's published child pages — the current page included.
- For a top-level page, it lists the page's own published child pages.

Child pages are ordered by `menu_order`.

### 4. The right-hand table of contents

The TOC is built in the browser from the headings inside the page content:

- `H2` headings become top-level entries; `H3` headings nest under the preceding `H2`.
- Headings without an `id` get one generated automatically (duplicates are suffixed).
- The entry for the section currently in view is highlighted while scrolling (scrollspy).
- A page with no `H2`/`H3` headings shows "No sections on this page yet."

## Plugin structure

| File | Purpose |
| --- | --- |
| `docs-layout.php` | Plugin bootstrap: registers the template, serves it via `template_include`, enqueues front-end assets, loads the admin screen. |
| `templates/docs-layout.php` | Front-end template: left sidebar (managed nav or fallback), content, right TOC container. |
| `includes/nav-tree.php` | Storage, sanitization, and per-page resolution of the managed navigation tree. |
| `admin/docs-nav-admin.php` | "Docs Nav" admin screen: menu, nonce-protected save handler, row renderers. |
| `admin/docs-nav-admin.js` | Add/remove rows and drag-to-reorder via jQuery UI Sortable. |
| `admin/docs-nav-admin.css` | Admin screen styling. |
| `assets/docs-layout.js` | Builds the TOC from the content headings and runs the scrollspy. |
| `assets/docs-layout.css` | Three-column layout, sticky sidebars, responsive stacking. |

## Data and customization

- The tree is stored in the `docs_layout_nav_tree` option: an array of projects (`id`, `title`, `groups[]`) → groups (`id`, `title`, `docs[]`) → docs (`id`, `page_id`).
- Saving requires the `manage_options` capability and a valid nonce; text is sanitized with `sanitize_text_field()`, ids with `sanitize_key()`, and page ids with `absint()`.
- Relevant functions: `docs_layout_get_nav_tree()`, `docs_layout_sanitize_nav_tree()`, and `docs_layout_get_nav_for_page( $page_id )`.
- The layout picks up the theme's preset colors (`--wp--preset--color--accent`, `--contrast`, `--contrast-2/3/4`) with built-in fallbacks, so it inherits the site palette. Override in a child theme or via Additional CSS if needed.
- When a user is logged in, the sticky sidebars and anchor targets are offset to clear the 32px admin bar.

## Troubleshooting

- **The sidebar shows the page hierarchy instead of my tree** — check that the current page uses the Docs Layout template; the fallback is only used when the managed tree is empty or none of its entries resolve to published pages.
- **A page is missing from the sidebar** — make sure it is published and that a doc row in **Docs Nav** references it.
- **The TOC is empty** — the page has no `H2`/`H3` headings; section headings must use the Heading block (or `<h2>`/`<h3>` markup) at those levels.

## Uninstall

The plugin ships no uninstall routine, so deactivating or deleting it leaves the `docs_layout_nav_tree` option in the database. Remove the option for a clean uninstall, for example:

```
wp option delete docs_layout_nav_tree
```
