<p align="center">
  <img src="assets/logo.svg" alt="MagicCMS" width="480">
</p>

<p align="center">
  <b>A lightweight, themeable, panel-based PHP + MySQL CMS for gaming &amp; esports teams — and anyone else.</b><br>
  Black &amp; red by default. Fast. Modular. Easy to extend (by humans <i>or</i> AI).
</p>

> **Status: v0.1 "Foundation" is implemented and tested.** The installer, auth, roles, layout/panels, menu builder, appearance (light/dark + colour schemes),
> custom pages, shoutbox, avatars, theme engine and admin shell all work. Everything marked **(planned)** below is specified but not built yet —
> see [Implementation status](#implementation-status).

---

## Implementation status

| Area | v0.1 | Notes |
|---|---|---|
| Setup wizard, lock file, self-delete | ✅ | 4-step wizard; Superadmin created on install |
| Registration / login / logout | ✅ | open / approval / closed modes, honeypot, brute-force throttle, remember-me |
| Passwords | ✅ | Argon2id (bcrypt fallback), rehash on login |
| Roles & permissions | ✅ | Guest, Member, Moderator, Administrator, Superadministrator; privilege-escalation guarded |
| Admin: dashboard, settings, users, panels, navigation, pages, appearance | ✅ | |
| Layout: none / left / right / both (+ per-page override) | ✅ | Empty columns collapse automatically |
| Panels: Account, Online Users, Shoutbox, Site Stats, Custom HTML, Navigation | ✅ | Multiple instances of Custom HTML / Navigation |
| Menu builder (gradient buttons, dropdowns, page/URL/module targets) | ✅ | Emoji icons; SVG icon set planned |
| Light/dark mode + admin colour scheme | ✅ | 6 presets + custom colours, contrast warning, live preview |
| Avatars (≤ 5 MB, auto-resize, re-encode) | ✅ | 32/64/128/256 px, WebP (JPEG fallback). **Always rendered circular** (enforced in core CSS, themes cannot change it – also in the forum) |
| Replaceable site logo | ✅ | Admin → Settings → Logo: PNG/JPG/WebP/GIF (resized) or sanitized SVG; also used as favicon; one-click restore |
| Custom pages | ✅ | Sanitized HTML editor; Markdown/WYSIWYG planned |
| Theme engine (parent themes, tokens, overrides) | ✅ | Theme upload UI planned |
| Plugins / modules loader | ✅ (modules) | Core modules are loaded from the `modules` table; admin UI for enabling/installing planned |
| Hero module | ⏳ | v0.1 ships a static "hero-lite" intro on the home page; full module planned |
| News + categories + generated covers | ⏳ | planned (v0.4) |
| Forum | ⏳ | planned (v0.2) |
| Matches / roster / events panels | ⏳ | planned (v0.3) |
| Private messages, email verification, password reset | ⏳ | need a mail layer – planned |
| Update/migration runner UI | ⏳ | planned |

---

## Table of contents

0. [Implementation status](#implementation-status)
1. [What is MagicCMS?](#1-what-is-magiccms)
2. [Feature overview](#2-feature-overview)
3. [Requirements](#3-requirements)
4. [Installation & one-time setup](#4-installation--one-time-setup)
5. [Project structure](#5-project-structure)
6. [Architecture](#6-architecture)
7. [Database schema](#7-database-schema)
8. [Users, roles & permissions](#8-users-roles--permissions)
9. [Layout system & panels](#9-layout-system--panels)
10. [Navigation & menu builder](#10-navigation--menu-builder)
11. [Appearance: light/dark mode & colour schemes](#11-appearance-lightdark-mode--colour-schemes)
12. [Hero module](#12-hero-module)
13. [News & categories](#13-news--categories)
14. [Built-in panels & modules](#14-built-in-panels--modules)
15. [Forum](#15-forum)
16. [Avatars & uploads](#16-avatars--uploads)
17. [Security](#17-security)
18. [Theming](#18-theming)
19. [Plugin / panel development](#19-plugin--panel-development)
20. [Configuration reference](#20-configuration-reference)
21. [Roadmap](#21-roadmap)
22. [Brand & logo](#22-brand--logo)
23. [License](#23-license)

---

## 1. What is MagicCMS?

MagicCMS is a small, dependency-free content management system in the spirit of **WebSPELL** and **PHP-Fusion**, built for **gaming clans and esports teams** but general enough for communities, guilds, clubs, or small organisations.

**Design goals**

| Goal | What it means |
|---|---|
| **Lightweight & snappy** | No framework, no Composer required at runtime, no jQuery. Vanilla PHP 8 + a few KB of vanilla JS and CSS. |
| **Everything in MySQL** | Content, users, settings, layout, panels, theme choice — all stored in the database (uploads on disk, referenced from DB). |
| **Modular** | Every feature is a *module* or a *panel*. Disable or remove what you don't need. |
| **Customisable layout** | Left panels, right panels, both, or neither — per site, per page, configured from the admin UI. |
| **Themeable** | Themes are plain folders (CSS tokens + PHP templates). Documented so well that an AI model can generate a complete theme from a prompt. |
| **Secure by default** | Argon2id/bcrypt hashing, CSRF tokens, prepared statements everywhere, output escaping, hardened uploads. |
| **Responsive** | Mobile-first layout; side panels collapse into a slide-in drawer on small screens. |

---

## 2. Feature overview

### Core
- Responsive, mobile-first layout with **configurable side panels** (none / left / right / both)
- **Registration & login** with email verification (optional), password reset, "remember me"
- **Roles & permissions**: Superadministrator, Administrator, Moderator, Member, Guest — plus custom roles
- **Admin panel** for settings, users, content, layout, panels, themes, plugins
- **Theme engine** with live theme switching, **user light/dark mode toggle** and an **admin colour-scheme editor** (presets + custom colours)
- **Menu builder**: flashy gradient nav buttons, unlimited custom items, dropdowns, links to built-in modules, custom pages or external URLs
- **Hero module**: configurable banner / slider / video / animated gradient with CTA buttons
- **Plugin system** for panels, pages and widgets
- **User profiles** with avatars, signature, bio, social links, game IDs (Steam, Discord, Battle.net, …)

### Built-in modules
- 📰 **News** with custom categories (colour, icon, picture or auto-generated abstract covers), tags, comments, featured posts
- 🗨️ **Forum** (categories → boards → topics → posts) — phpBB-style
- ⚔️ **Matches** — upcoming & recent results, opponents, maps, scores, VOD links
- 👥 **Team / Roster** — players, roles, stats, social links
- 🏆 **Tournaments / Events** — brackets-lite, calendar
- 🖼️ **Gallery** — albums & screenshots
- 📥 **Downloads** — files with categories & counters
- 📨 **Private messages**
- 🧩 **Custom pages** (WYSIWYG-lite / Markdown)
- 🤝 **Sponsors & partners**
- 📝 **Join-us / recruitment form** (clan application workflow)
- 🗳️ **Polls**
- 🔎 **Site search**

### Built-in panels (sidebar widgets)
`Hero` · `Login / User box` · `Navigation` · `Shoutbox / Chat` · `Recent Matches` · `Next Match countdown` · `Online Users` · `Latest Forum Topics` · `Latest News` · `Top Posters` · `Birthdays` · `Poll` · `Server Status` (game server query) · `Discord Widget` · `Twitch/YouTube Live` · `Sponsors` · `Site Stats` · `Custom HTML`

---

## 3. Requirements

| Component | Minimum | Recommended |
|---|---|---|
| PHP | 8.1 | 8.3+ |
| PHP extensions | `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `gd` *(or `imagick`)* | + `intl`, `sodium` |
| Database | MySQL 5.7 / MariaDB 10.4 (`utf8mb4`, InnoDB) | MySQL 8 / MariaDB 10.6+ |
| Web server | Apache 2.4 (`mod_rewrite`) or Nginx | with HTTPS |
| Disk | ~1 MB core + uploads | |
| php.ini | `upload_max_filesize` and `post_max_size` ≥ 5M for 5 MB avatars | |

Runs on ordinary shared hosting. No shell access needed.

---

## 4. Installation & one-time setup

### 4.1 Quick start

1. Upload the MagicCMS files to your web root (or a sub-folder).
2. Create an empty MySQL database and a user with full rights on it.
3. Make these directories writable by PHP: `config/`, `uploads/`, `cache/`.
4. Open `https://your-site.tld/setup.php` in your browser. (Local try-out: `php -S 127.0.0.1:8080 index.php`, then visit `http://127.0.0.1:8080/setup.php`.)
5. Follow the wizard (below). When finished, MagicCMS **disables the installer automatically**.

### 4.2 The setup wizard (`setup.php`)

| Step | What happens |
|---|---|
| **1. Requirements check** | PHP version, extensions (PDO MySQL, mbstring, fileinfo, dom, GD/WebP, Argon2id), writable folders, `upload_max_filesize`. Blocks on hard failures, warns on optional ones. |
| **2. Database** | Host, port, DB name, user, password, table prefix (default `mcms_`). The connection is tested live; an already-populated prefix is refused. |
| **3. Site & Superadministrator** | Site name, URL, timezone, theme, plus the first account (username, email, password entered twice). **This first account becomes the Superadministrator.** |
| **4. Install & self-lock** | Creates all tables, seeds roles/permissions/panels/menus/settings and a demo page (in one transaction), writes `config/config.php`, then locks and removes the installer (see below). |

### 4.3 How the installer removes itself

Both mechanisms are applied, in this order, so the installer can never be re-run by an attacker:

1. **Lock file** — `config/installed.lock` is written (contains install timestamp + version). `setup.php` and `/install/*` refuse to run if it exists, returning `404`.
2. **Self-deletion** — `setup.php` attempts `unlink(__FILE__)` and removes the `/install/` directory.
3. **Fallback** — if the host forbids deletion (permissions), the wizard shows a clear red warning: *"Delete setup.php manually."* The lock file still blocks it, and the admin dashboard shows a persistent warning until the file is gone.

Re-installing intentionally requires deleting `config/installed.lock` **and** re-uploading `setup.php`.

### 4.4 After setup

- Log in with the Superadmin account → **Admin → Dashboard**.
- Review **Admin → Settings → Layout** to choose side panels.
- Assign Administrator/Moderator roles under **Admin → Users**.

### 4.5 Updating *(planned)*

Upload new files over old ones, open `/admin/update`. Versioned SQL migrations in `migrations/` run in order; the applied version is tracked in `mcms_meta`.

---

## 5. Project structure

```
MagicCMS/
├── index.php                 # Front controller (all requests)
├── setup.php                 # One-time installer (locks + deletes itself)
├── .htaccess / nginx.conf.sample
├── config/                   # config.php + installed.lock are generated by setup (never web-accessible)
├── core/                     # Framework-free core
│   ├── Bootstrap.php         # autoload, config, session, security headers, module loading, dispatch
│   ├── Database.php          # PDO wrapper: prepared statements only, {table} prefix placeholders
│   ├── Settings.php          # DB-backed settings
│   ├── Auth.php              # login, sessions, remember-me, brute-force limit, roles & permissions
│   ├── Csrf.php  Hooks.php  Router.php
│   ├── View.php              # template + theme-chain resolver, asset tags
│   ├── Layout.php  Panels.php  Nav.php  Appearance.php
│   ├── Sanitizer.php         # allow-list HTML sanitizer
│   ├── Uploads.php           # hardened avatar upload + resize
│   ├── Installer.php         # logic used by setup.php
│   └── helpers.php           # e(), url(), t(), setting(), can(), render(), csrf_field(), …
├── modules/                  # home, auth, profile, members, pages, chat, admin
│   └── <key>/ module.json  routes.php  Controller.php  views/
├── panels/                   # user_box, online_users, chat, site_stats, custom_html, navigation
│   └── <key>/ panel.json  panel.php
├── plugins/                  # third-party modules & panels (plugins/<name>/panels/<key>/…)
├── themes/
│   └── magic-red/            # theme.json  tokens.css  theme.css  [templates/]
├── views/                    # core templates: layout, header, nav, footer, panel, pagination, 404 …
├── assets/                   # core.css (structure), core.js, admin.js, logo.svg, logo-mark.svg
├── lang/                     # en.php (source language), add de.php, fr.php …
├── migrations/               # 0001_init.sql (schema, {table} placeholders)
├── uploads/                  # avatars/ … (PHP execution disabled)
├── cache/
└── docs/THEMING.md
```

---

## 6. Architecture

- **Front controller** — every request hits `index.php`; the `Router` maps `/forum/topic/12` → module controller.
- **Modules** register routes, permissions, menu entries, admin pages and panels through `module.json` + hooks.
- **MVC-lite** — controllers prepare data, **views** are plain PHP templates (`<?= e($x) ?>`), models use PDO prepared statements.
- **Hooks** — modules/plugins subscribe to events (`user.registered`, `forum.post.created`, `layout.render.head`, …) without editing core.
- **Settings** are stored in `mcms_settings` (key/value, JSON) and cached per request (optional file cache).
- **Theme resolution** — for any template, MagicCMS looks in `themes/<active>/templates/…` first, then falls back to the module's default view. Themes can therefore restyle *or* fully restructure output.

### Request lifecycle

```
index.php → Bootstrap → Session/Auth → Router → Controller
          → View::render(template, data) → Layout::compose(slots + panels) → Theme → HTML
```

---

## 7. Database schema

All tables use the configurable prefix (default `mcms_`), InnoDB, `utf8mb4_unicode_ci`.

| Table | Purpose |
|---|---|
| `users` | id, username, email, password_hash, role_id, avatar, signature, bio, timezone, lang, theme, theme_mode (`dark/light/auto`), status (`active/pending/banned`), created_at, last_login, last_seen |
| `roles` | id, name, slug, level, is_system |
| `permissions` / `role_permissions` | Granular permission keys (e.g. `forum.moderate`, `admin.users`) |
| `sessions` | Optional DB sessions: id, user_id, ip, user_agent, last_activity |
| `remember_tokens` | selector + hashed validator (split-token "remember me") |
| `password_resets` / `email_verifications` | Hashed, expiring tokens |
| `settings` | `key`, `value` (JSON), `autoload` |
| `modules` / `plugins` | Installed + enabled state, version |
| `panels` | id, `panel_key`, title, position (`left/right/top/bottom`), sort_order, enabled, visibility rules (JSON), settings (JSON) |
| `menus` | id, `key` (main/footer/custom), title, style options (JSON) |
| `menu_items` | id, menu_id, parent_id, label, icon, target_type (`module/page/category/url/action`), target_ref, new_tab, visibility (JSON), badge, style (JSON), sort_order |
| `hero_slides` | id, headline, subline, media, cta (JSON), overlay, placement (JSON), starts_at, ends_at, sort_order, enabled |
| `pages` | Custom pages |
| `news` | id, title, slug, category_id, cover, excerpt, body, status, featured, publish_at, author_id, views, seo (JSON) |
| `news_categories` | id, parent_id, name, slug, color, icon, image, cover_style, sort_order, visibility |
| `news_tags` / `news_tag_map` | Tags |
| `comments` | Comments (news and other content) |
| `forum_categories`, `forum_boards`, `forum_topics`, `forum_posts`, `forum_attachments`, `forum_reads`, `forum_subscriptions`, `forum_reports` | Forum |
| `matches`, `match_maps`, `opponents`, `games`, `events` | Esports data |
| `team_members` | Roster |
| `chat_messages` | Shoutbox |
| `private_messages` | PMs |
| `polls`, `poll_options`, `poll_votes` | Polls |
| `gallery_albums`, `gallery_items`, `downloads`, `download_categories` | Media |
| `online_sessions` | Who is online (user/guest, last_seen, location) |
| `audit_log` | Admin actions (who/what/when/IP) |
| `meta` | Schema version, install info |

Migrations live in `migrations/NNNN_description.sql` and are idempotent per version.

---

## 8. Users, roles & permissions

### Default roles

| Role | Level | Description |
|---|---|---|
| **Superadministrator** | 100 | The account created in setup. Full, unrestricted access. Cannot be demoted or deleted by anyone else. Only a Superadmin can create other Administrators. |
| **Administrator** | 80 | Manages site content, users, panels, themes. No access to DB/config-level settings or Superadmin management. |
| **Moderator** | 50 | Moderates forum, comments, chat; can ban/warn. |
| **Member** | 10 | Standard registered user. |
| **Guest** | 0 | Not logged in. |

- Roles carry **permissions**; permissions are checked via `can('forum.moderate')`.
- Create **custom roles** (e.g. "Team Captain", "Coach") and assign any permission mix.
- Assign roles in **Admin → Users**. Every role change is written to the audit log.

### Registration & login

- Registration form: username, email, password (+ confirm), optional CAPTCHA/honeypot, ToS checkbox.
- Optional **email verification** and **admin approval** modes.
- **Passwords** are hashed with `password_hash()` using **Argon2id** (falls back to bcrypt cost 12); automatic rehash on login when parameters change. Plaintext is never stored or logged.
- **Login** protections: per-IP + per-account rate limiting with progressive delay, generic error messages (no user enumeration), session ID regeneration on login, optional "remember me" via split-token cookies.
- **Password reset** via single-use, hashed, expiring (60 min) tokens.

---

## 9. Layout system & panels

The page is composed from **slots**:

```
┌───────────────────────────────────────────┐
│                 header / nav              │
├───────────┬───────────────────┬───────────┤
│   LEFT    │                   │   RIGHT   │
│  panels   │   main content    │  panels   │
│           │                   │           │
├───────────┴───────────────────┴───────────┤
│                  footer                   │
└───────────────────────────────────────────┘
```

### Layout modes

Configured in **Admin → Settings → Layout** and overridable **per page/module**:

| Mode | Result |
|---|---|
| `none` | Content only, full width |
| `left` | Left panels + content |
| `right` | Content + right panels |
| `both` | Left + content + right |

Examples: show both sidebars on the home page, but use *none* on the forum for maximum width, and *right* on news articles.

### Panel management (Admin → Panels)

- **Enable/disable/remove** any panel with one click.
- **Order** with a number field and move panels between left/right (drag & drop planned).
- **Visibility rules** per panel: by role, by logged-in/guest, by module/URL pattern, by device (hide on mobile).
- **Per-panel settings** (e.g. "number of items", "refresh interval").
- Panels can be **collapsible**; each user's collapsed state is remembered (cookie/DB).
- If a slot ends up empty, the layout automatically collapses that column.

### Responsive behaviour

| Viewport | Behaviour |
|---|---|
| ≥ 1100px | Grid with the enabled columns (left / content / right) |
| < 1100px | Single column; side panels become **slide-in drawers** opened from header buttons (overlay, `Esc` closes) |
| < 900px | The main menu collapses into an animated hamburger; dropdowns open on tap |
| < 768px | Panels/menu items flagged "hide on mobile" disappear |

Layout uses CSS Grid + custom properties (`--sidebar-w`, `--gap`), no JS required for the base layout.

---

## 10. Navigation & menu builder

The main menu is **fully data-driven** (`menus` + `menu_items` tables) and edited in **Admin → Navigation** — no code needed.

- **Add / edit / delete / reorder** buttons by drag & drop; unlimited items, with nested **dropdowns** (2 levels).
- Each item has: label, optional icon, **target**, open in same/new tab, visibility, and optional style override.
- **Target types**
  | Type | Example |
  |---|---|
  | Built-in module | Forum, News, Matches, Team, Gallery |
  | **Custom page** | pick any page from **Admin → Pages** (stored by page ID, so renaming a slug never breaks the link) |
  | News category | "Tournament Reports" |
  | External URL | `https://twitch.tv/yourteam` |
  | Anchor / action | `#contact`, "Login/Register" smart button |
- **Visibility** per item: everyone / guests only / members only / by role / hide on mobile.
- **Multiple menus**: `main` (header), `footer`, plus custom menus that can be shown in a *Navigation* panel.
- **Active-state** highlighting and optional **badges** (e.g. unread PMs, "NEW", live-stream dot).
- **Button style** (Admin → Appearance → Navigation): `gradient` (default), `solid`, `outline`, `glass`, `pill`/`square`, plus hover effect (`glow`, `shine-sweep`, `lift`, `none`). Per-item colour override is optional.
- **Responsive**: on small screens the menu collapses into an animated hamburger drawer; dropdowns become accordions; touch targets ≥ 44px.

Default look: bold, flashy **red-gradient buttons** with a light "shine sweep" on hover and a glowing underline for the active item (GPU-friendly, honours `prefers-reduced-motion`).

---

## 11. Appearance: light/dark mode & colour schemes

### Light / dark switch
- The default theme ships with **both a dark (black & red) and a light variant**; the dark one is the default.
- **Users** toggle with a header button (sun/moon). The choice is saved in their profile (`users.theme_mode`) — and in a cookie/`localStorage` for guests — and applied *before first paint* to avoid flashing.
- Modes: `dark`, `light`, `auto` (follows the OS via `prefers-color-scheme`).
- Admin controls: default mode, and whether users may switch at all (Admin → Appearance).

### Colour scheme (admin)
In **Admin → Appearance → Colour scheme** the site admin can pick the look without touching code:

- **Presets**: *Crimson* (default), *Ember* (orange), *Royal* (blue), *Toxic* (green), *Violet*, *Mono* (greys) … each defining both dark and light palettes.
- **Custom colours** via colour pickers: primary, highlight, deep shade, accent, backgrounds, surfaces, text — with an automatic **contrast checker** (warns if text/background drops below WCAG AA 4.5:1).
- **Shape & feel**: corner radius (sharp → very round), animation level (off / subtle / full), gradient intensity, font pair.
- **Live preview** before saving; one-click **reset to theme defaults**; **export/import** a scheme as JSON.
- Stored in `settings` (`appearance.*`) and emitted as a small `<style>` block of CSS variables *after* the theme CSS, so it overrides any theme's tokens. Works with every theme that follows the token contract in [`docs/THEMING.md`](docs/THEMING.md).

---

## 12. Hero module

A configurable banner at the top of the home page (or any page / module), managed in **Admin → Hero**.

| Option | Choices |
|---|---|
| **Placement** | home only / selected pages / all pages / off |
| **Type** | Single banner · **Slider** (multiple slides, auto-play, arrows/dots) · Video background · Animated abstract gradient (default, no image needed) |
| **Content per slide** | headline, sub-headline, up to 2 **call-to-action buttons** (label + target, same target types as the menu), image or video, overlay opacity, text alignment |
| **Dynamic content** | optional *Next match countdown*, *Latest news* (auto-fills from featured posts), *Live stream* embed, *Join us* CTA |
| **Layout** | height (compact / normal / full-screen), full-width or contained, content position |
| **Behaviour** | transition (fade/slide), interval, pause on hover, hide on mobile or use simplified version |
| **Scheduling** | start/end dates per slide (e.g. tournament announcement) |

Images are auto-resized into responsive `srcset` variants; the hero never blocks first paint (lazy-loaded video, CSS-only default background).

---

## 13. News & categories

Full blog-style news system with **custom categories**.

**Posts**: title, slug, cover image, excerpt, body (Markdown/WYSIWYG-lite), category, tags, author, status (`draft` / `scheduled` / `published`), **featured/sticky** flag, comment toggle, SEO title/description, view counter.

**Categories** (Admin → News → Categories) — create as many as you like (e.g. *Match Reports*, *Team News*, *Patch Notes*, *Interviews*):

| Field | Description |
|---|---|
| Name / slug / description | Shown on category pages |
| **Colour** | Used for category badges, title-bar accents and generated covers |
| **Icon** | Built-in icon set |
| **Custom picture** | Optional uploaded image (auto-resized) used as category banner & fallback cover |
| **Default generated picture** | If no picture is uploaded, MagicCMS **procedurally generates an abstract cover** |
| Parent category | Optional nesting |
| Visibility / sort order | Per role, drag & drop order |

**Procedural covers.** When a post has no cover image and its category has no picture, an abstract SVG is generated *deterministically* from a seed (post/category ID + category colour): layered gradients, blades, orbit rings, grids or waves in the category's colour on a dark/light base. The same post always gets the same artwork; no external services; output is a few KB, cached in `cache/covers/` and fully theme-aware. Admins can pick the generator style (*blades*, *orbits*, *mesh*, *waves*) per site or per category.

Other: category & tag archives, RSS per category, related posts, "featured" slider (can feed the Hero), and a *Latest News* panel with optional category filter.

---

## 14. Built-in panels & modules

| Panel | Description |
|---|---|
| **User box** | Login form for guests; avatar, PM count, profile & logout for members. |
| **Navigation** | Menu from `menu_items`, nested, with icons. |
| **Chat / Shoutbox** | AJAX shoutbox (polling, optional SSE). Emoji, rate limit, flood protection, moderator delete, role-based posting, optional history page. |
| **Recent Matches** | Last N results with W/L badge, opponent, score, game icon. |
| **Next Match** | Countdown to the next fixture. |
| **Online Users** | Members + guest count currently active (default window: 5 min), with role colours. |
| **Latest Forum Topics** | Newest/active threads (respects forum permissions). |
| **Latest News** | Headline list with thumbnails. |
| **Top Posters / New Members** | Community highlights. |
| **Birthdays** | Today's/upcoming member birthdays. |
| **Poll** | Active poll with live results. |
| **Server Status** | Query game servers (Source/Minecraft/etc.) — players, map, ping. |
| **Discord Widget** | Online count & invite button. |
| **Live Streams** | Twitch/YouTube live indicator for roster members. |
| **Sponsors** | Logo carousel. |
| **Site Stats** | Members, posts, topics, visitors. |
| **Custom HTML** | Admin-defined sanitized HTML/Markdown block. |

**Extra "essential" ideas included in the plan:** audit log, notifications centre, cookie/GDPR consent banner & data export/delete, RSS feeds, sitemap.xml, SEO meta/OpenGraph per page, maintenance mode, spam honeypot/Akismet-style hook, backup/export button, multilingual UI, dark/light accent toggle.

---

## 15. Forum

A full phpBB-style forum.

**Structure:** Categories → Boards (with sub-boards) → Topics → Posts

**Features**
- Topics: sticky, announcement, locked, moved, merged/split (moderators)
- Posting: BBCode **or** Markdown (admin choice) with live preview, quoting, edit history, emoji
- Attachments (images/files) with size & type limits
- Per-board **permissions** per role (view / read / post / reply / edit own / delete own / moderate)
- Private/hidden boards (e.g. *Team-only*, *Admin lounge*)
- Unread tracking, "new posts since last visit", mark-all-read
- Topic subscriptions & email notifications
- Polls in topics
- Reputation / "thanks" / reactions *(planned)*
- Report post → moderation queue
- Moderator tools: warn, mute, ban, move, lock, prune, IP view
- User post counts, ranks (by post count or custom), signatures
- Search (MySQL FULLTEXT) with filters
- Pagination, breadcrumbs, jump-to-last-post, RSS per board

---

## 16. Avatars & uploads

- Members upload avatars on **Profile → Avatar** — JPG, PNG, GIF, WebP, **max 5 MB**.
- Pipeline (server-side, `gd`/`imagick`):
  1. Validate size, real MIME type (`finfo`), and that the file decodes as an image.
  2. **Re-encode** the image (strips EXIF/metadata and any embedded payloads).
  3. Auto-crop to square and generate sizes: **256, 128, 64, 32 px** (WebP/JPEG).
  4. Save as random filename (`uploads/avatars/<hash>_<size>.webp`) — original name is discarded.
  5. Animated GIFs: optional (admin toggle); first-frame fallback otherwise.
- Avatars are **always displayed as circles** everywhere (profiles, chat, member lists, forum posts). This is enforced in `assets/core.css` with `!important`, so themes cannot square them.
- Default avatar = generated initial on a gradient in the current colour scheme.
- `uploads/` ships with an `.htaccess` / nginx rule that **disables PHP execution** and forces safe content types.

---

## 17. Security

| Area | Measure |
|---|---|
| **SQL injection** | PDO prepared statements only; no string-built queries. |
| **XSS** | Context-aware escaping via `e()`; user HTML filtered through an allow-list sanitizer; CSP header with nonces. |
| **CSRF** | Per-session tokens on every state-changing form/AJAX call; `SameSite=Lax` cookies. |
| **Passwords** | Argon2id/bcrypt, per-hash salt, rehash on login, strength meter, breach-list check *(optional)*. |
| **Sessions** | `HttpOnly`, `Secure`, `SameSite`, ID regeneration, idle & absolute timeouts. |
| **Brute force** | Rate limiting + lockout windows, login attempt log. |
| **Uploads** | MIME sniffing, re-encoding, random names, no-exec directory, size caps. |
| **Config** | Secrets in `config/config.php` (denied via server rules; PHP file returning an array). |
| **Headers** | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, optional HSTS. |
| **Auditing** | Admin actions and role changes recorded in `audit_log`. |
| **Installer** | Lock file + self-delete + dashboard warning. |

Found a vulnerability? Please report it privately to the maintainers rather than opening a public issue.

---

## 18. Theming

MagicCMS themes are **self-contained folders** that define a design via **CSS variables (design tokens)** and, if needed, **template overrides**. A theme can be a 30-line colour swap or a total redesign.

The default theme **Magic Red** is: black surfaces, crimson accents, bold gradient title-bars, large rounded corners (16–24px), subtle animated gradients, glow hovers, and abstract geometric backgrounds — all CSS-only (GPU-friendly transforms/opacity), respecting `prefers-reduced-motion`. It includes a **light variant** (`[data-theme="light"]`) and honours the **admin colour scheme** overrides (see [§11](#11-appearance-lightdark-mode--colour-schemes)).

👉 **Full guide, including an AI prompt template: [`docs/THEMING.md`](docs/THEMING.md)**

Quick taste of a theme manifest:

```json
{
  "name": "Magic Red",
  "slug": "magic-red",
  "version": "1.0.0",
  "author": "MagicCMS",
  "description": "Elegant black & red default theme.",
  "requires": "1.0",
  "stylesheets": ["tokens.css", "theme.css"],
  "scripts": ["theme.js"],
  "supports": { "darkLight": true, "panels": ["left", "right"] }
}
```

---

## 19. Plugin / panel development

### Creating a panel

`panels/hello/panel.json`
```json
{
  "key": "hello",
  "title": "Hello Panel",
  "version": "1.0.0",
  "positions": ["left", "right"],
  "multiple": false,
  "settings": {
    "greeting": { "type": "text", "default": "Hello", "label": "Greeting" }
  }
}
```

`panels/hello/panel.php`
```php
<?php
/** Variables available: $settings (array), $user (array|null), $panel (DB row). Use db() for queries. */
?>
<p><?= e($settings['greeting']) ?>, <?= e($user['username'] ?? t('guest')) ?>!</p>
```

Drop the folder in `panels/` (or `plugins/<name>/panels/`), then add it under **Admin → Panels** (settings from `panel.json` get an auto-generated form). The wrapper chrome (title bar, collapse button, rounded card) is provided by the theme — your panel only outputs its inner content.

### Creating a module

A module may add routes, DB tables (via `migrations/`), permissions, admin pages, menu items and panels:

```json
{
  "key": "tournaments",
  "name": "Tournaments",
  "version": "1.0.0",
  "routes": "routes.php",
  "permissions": ["tournaments.view", "tournaments.manage"],
  "menu": [{ "title": "Tournaments", "url": "/tournaments", "icon": "trophy" }],
  "admin": [{ "title": "Tournaments", "url": "/admin/tournaments", "permission": "tournaments.manage" }],
  "panels": ["next_tournament"]
}
```

### Hooks (excerpt)

| Hook | Fired when |
|---|---|
| `user.registered`, `user.login`, `user.logout` | Auth events |
| `forum.post.created`, `forum.topic.created` | Forum activity |
| `layout.render.head`, `layout.render.footer` | Inject tags/scripts |
| `panel.render.before`, `panel.render.after` | Wrap panel output |
| `admin.menu.build` | Add admin menu entries |

```php
Hooks::on('user.registered', function (array $user) {
    // e.g. post to Discord webhook
});
```

### Template helpers

`e($s)` escape · `url($path)` · `asset($path)` · `t($text)` translate · `can($perm)` permission check · `user()` · `db()` · `csrf_field()` · `setting($key, $default)` · `render($tpl, $vars)` · `fmt_date($utc)` · `time_ago($utc)` · `avatar_url($user, $px)` · `audit($action, $detail)`

---

## 20. Configuration reference

`config/config.php` (generated):

```php
<?php
return [
    'db' => [
        'host' => 'localhost', 'port' => 3306,
        'name' => 'magiccms', 'user' => '…', 'pass' => '…',
        'prefix' => 'mcms_', 'charset' => 'utf8mb4',
    ],
    'app' => [
        'url'      => 'https://example.com',
        'timezone' => 'Europe/Berlin',
        'debug'    => false,
        'secret'   => '<64 hex chars generated at install>',
    ],
];
```

Everything else (site name, layout mode, theme, registration mode, upload limits, …) lives in the **`settings` table** and is edited in **Admin → Settings**.

Key settings:

| Key | Default | Description |
|---|---|---|
| `site.name` | `MagicCMS` | Site title |
| `layout.mode` | `both` | `none` / `left` / `right` / `both` |
| `theme.active` | `magic-red` | Active theme slug |
| `appearance.mode_default` | `dark` | `dark` / `light` / `auto` |
| `appearance.allow_user_mode` | `1` | Let users switch light/dark |
| `appearance.scheme` | `crimson` | Preset key or `custom` |
| `appearance.colors` | `{}` | JSON of custom token overrides |
| `appearance.radius` / `appearance.motion` | `16` / `full` | Corner radius px; `off/subtle/full` |
| `nav.button_style` / `nav.hover_fx` | `gradient` / `shine` | Menu look |
| `hero.enabled` / `hero.placement` | `1` / `home` | Hero module |
| `news.cover_style` | `blades` | Generated cover style (`blades/orbits/mesh/waves`) |
| `auth.registration` | `open` | `open` / `approval` / `closed` |
| `auth.email_verify` | `0` | Require email verification |
| `upload.avatar_max_mb` | `5` | Avatar size cap |
| `online.window_min` | `5` | "Online" window in minutes |
| `forum.markup` | `bbcode` | `bbcode` / `markdown` |
| `site.maintenance` | `0` | Maintenance mode |

---

## 21. Roadmap

**v0.1 – Foundation:** setup wizard, DB layer, auth, roles, settings, router, theme engine, layout/panels, admin shell
**v0.1 also:** menu builder, appearance (light/dark + colour schemes), hero module
**v0.2 – Community:** profiles & avatars, forum, private messages, shoutbox, online users
**v0.3 – Esports:** matches, roster, events, recent-matches & next-match panels, recruitment form
**v0.4 – Content:** news + categories + generated covers, pages, gallery, downloads, polls, search
**v0.5 – Polish:** notifications, audit log UI, SEO/RSS/sitemap, GDPR tools, i18n packs
**v1.0 – Stable:** security review, docs, theme & plugin marketplace-ready manifests

---

## 22. Brand & logo

The MagicCMS mark is an abstract **"M"** formed from two crimson blades meeting at a point, crowned by a bright diamond "spark" — set on a black rounded tile with faint orbital rings. Wordmark: **Magic** in off-white, **CMS** in red gradient.

- Logo file: [`assets/logo.svg`](assets/logo.svg) (scalable). The header emblem can be replaced by admins in **Admin → Settings → Logo** (stored in `uploads/branding/`).
- Palette: `#E10613` red · `#FF4D4D` highlight · `#7A0008` deep red · `#0B0B0D` black · `#F4F4F6` off-white

---

## 23. License

To be decided by the project owner (suggested: MIT or GPL-3.0, as is common among PHP CMSs).

---

<p align="center"><sub>MagicCMS — build something bold.</sub></p>
