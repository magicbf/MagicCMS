<p align="center">
  <img src="assets/logo.svg" alt="MagicCMS" width="480">
</p>

<p align="center">
  <b>A lightweight, themeable, panel-based PHP + MySQL CMS for gaming &amp; esports teams — and anyone else.</b><br>
  Black &amp; red by default. Fast. Modular. Easy to extend (by humans <i>or</i> AI).
</p>

> **Status: v0.1 "Foundation" + the Forum are implemented and tested.** The installer, auth, roles, layout/panels, menu builder, appearance (light/dark + colour schemes),
> custom pages, shoutbox, avatars, theme engine and admin shell all work. Everything marked **(planned)** below is specified but not built yet —
> see [Implementation status](#implementation-status).

---

## Implementation status

| Area | v0.1 | Notes |
|---|---|---|
| Setup wizard, lock file, self-delete | ✅ | 4-step wizard; Superadmin created on install |
| Registration / login / logout | ✅ | open / approval / closed modes, honeypot, brute-force throttle, remember-me |
| Passwords | ✅ | Argon2id (bcrypt fallback), rehash on login |
| Roles & permissions | ✅ | Guest, Member, Moderator, Administrator, Superadmin; privilege-escalation guarded |
| Admin: dashboard, settings, users, panels, navigation, pages, appearance | ✅ | |
| Layout: **one right-hand panel column by default**; left / both / none optional (+ per-page override) | ✅ | Empty columns collapse automatically; existing sites are migrated without losing panels |
| Panels: Online Users, Shoutbox, Site Stats, Custom HTML, Navigation, Partners strip (+ optional Account panel) | ✅ | The login/register/account area is in the header; the Account panel is **off by default** – switch it on in Admin → Panels if a custom theme has no account area on top (a theme can declare `"supports": {"accountInHeader": false}` to switch it on automatically) |
| Menu builder (gradient buttons, dropdowns, page/URL/module targets) | ✅ | Emoji icons; SVG icon set planned |
| Light/dark mode + admin colour scheme | ✅ | 6 presets + custom colours, contrast warning, live preview |
| Avatars (≤ 5 MB, auto-resize, re-encode) | ✅ | 32/64/128/256 px, WebP (JPEG fallback). **Always rendered circular** (enforced in core CSS, themes cannot change it – also in the forum) |
| Replaceable site logo | ✅ | Admin → Settings → Logo: PNG/JPG/WebP/GIF (resized) or sanitized SVG; also used as favicon; one-click restore |
| Custom pages | ✅ | Sanitized HTML editor; Markdown/WYSIWYG planned |
| Theme engine (parent themes, tokens, overrides) | ✅ | Theme upload UI planned |
| Plugins / modules loader | ✅ (modules) | Core modules are loaded from the `modules` table; admin UI for enabling/installing planned |
| Plugin system (load/enable/disable from `plugins/`, own routes/views/panels/admin tab) | ✅ | Admin → Modules (superadmin) – see [§19](#19-plugin--panel-development) |
| **Matches plugin**: results & schedule, record stats, detail pages, **Recent Matches panel** (last 5/8/10, admin setting) | ✅ | `plugins/matches`, Admin → Matches |
| Two-tier header (dominant logo + sticky full-width menu), footer with logo, refined animated title bars | ✅ | |
| Hero module (slider, effects, buttons, scheduling) | ✅ | Admin → Hero – see [§12](#12-hero-module) |
| News + categories + generated covers + comments + RSS | ✅ | Admin → News – see [§13](#13-news--categories) |
| Forum (category → subcategory → topics → posts) | ✅ | Own side-panel layout (none by default; admin can enable left/right/both), BBCode, per-subcategory read/post levels, sticky/announce/lock/move/delete, reports queue, search, unread tracking, ranks – see [§15](#15-forum). Topic/subcategory subscriptions with e-mail + in-site notifications, **attachments, polls, reactions** – see below. Rich-text editor, merge/split: planned |
| Versioned migrations + auto-upgrade | ✅ | `migrations/NNNN_*.sql` (+ optional `.php` seed); new code upgrades an older database automatically |
| E-mail layer (PHP mail / SMTP / log), **e-mail verification**, **password reset** | ✅ | Admin → Settings → E-mail – see [§8](#8-users-roles--permissions) |
| **Private messages** (threads, groups, blocking, unread badge, notifications) | ✅ | see [§14](#14-built-in-panels--modules) |
| **Roster plugin** (players, squads, photos, socials, stats, panel) | ✅ | `plugins/roster` |
| **Events & tournaments plugin** (calendar, sign-ups, waitlist, .ics, brackets, panel) | ✅ | `plugins/events` |
| **Notifications centre** (bell, merged entries, read/clear) + **forum subscriptions** (follow topics/subcategories, auto-follow, signed one-click unsubscribe) | ✅ | see [§14](#14-built-in-panels--modules), [§15](#15-forum) |
| **Recruitment plugin** (application form, positions, custom questions, status tracking, notes, applicant notifications) | ✅ | `plugins/recruit` |
| **Smileys & flags**: searchable picker with 400+ emoji (smileys, gestures, hearts, gaming, animals, food, sports) and 187 country flags as self-hosted SVGs (they also work on Windows); `:shortcodes:`, `:)` text smileys and `:flag_de:` render in the forum, messages, comments, chat, pages and news; the picker is in the forum editor, chat, news comments and the visual editor | ✅ | `core/Emoji.php`, `assets/emoji.js`, `assets/flags/` |
| **Roles** (Admin → Roles): cosmetic titles such as Captain, Streamer or Founder with a custom colour, icon and style (soft, solid, outline, glow); a member can hold several; each role chooses where it is shown – forum posts, shoutbox, profile, members list, news comments; separate from permission levels | ✅ | `core/DisplayRoles.php`, `modules/admin/RolesController.php` |
| **Matches panel & Shoutbox**: the Recent Matches panel shows a season record card (win-rate ring, W/D/L bar, last-5 form, streak), a next-match countdown and result-tinted rows with opponent crests; the Shoutbox has chat bubbles, role tags, @mentions, links, emoji/flags, date dividers, live stats and a character counter | ✅ | `plugins/matches/panels/`, `panels/chat/` |
| **Visual editor** (WYSIWYG) for pages, news and HTML panels: headings, formatting, alignment, lists, links, image upload (button, drag & drop or paste), YouTube embeds, tables with row/column tools, highlight boxes, button links, HTML source view, full screen; dependency-free and CSP-safe; content is still sanitized server-side | ✅ | `assets/editor.js`, `core/Sanitizer.php` |
| **Admin console**: its own sidebar layout (grouped sections, icons, “Jump to…” finder on Ctrl/⌘+K, light + dark, mobile drawer), independent of the site theme, accent follows the colour scheme | ✅ | `assets/admin.css`, `views/admin_layout.php`, `modules/admin/views/_wrap.php` |
| **Bundled themes**: Magic Red (default), **Hazard Protocol** (terminal / hazard-yellow) **Aurora Glass** (bright frosted glass, light by default) **Mint Facet** (sharp chamfered mint on graphite) **Nebula Bloom** (ultraviolet-magenta-blue glass with thin type and round capsule title bars) **Crystallic** (faceted ice-crystal glass with cut corners, glow and drifting sparks) and **Dark gel** (ink-navy with glossy azure gel, tube-shaped carbon-weave title bars and one orange call-to-action) and **Electric Camo** (charcoal-green steel chrome, green light trails and a white paper content sheet with dark-headed panels; one look for light and dark) – each with light + dark variants and an auto-applied colour scheme | ✅ | `themes/`, see [THEMING](docs/THEMING.md) |
| **Backups & safe updates** (db / full backups, restore, automatic + cron, validated update packages, automatic rollback, CLI tools) | ✅ | see [§4.5](#45-updating--backups) |
| **Footer builder** (5 styles, own link columns, social buttons, scrolling ticker, live strip, giant wordmark, top decoration, panels in footer columns) and **logo-only header** option | ✅ | Admin → Footer, Admin → Settings |
| **Partners strip** (full-width panel above the footer, logos scroll right → left, hideable in Admin → Panels) | ✅ | Admin → Partners |

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
- **Roles & permissions**: Superadmin, Administrator, Moderator, Member, Guest — plus custom roles
- **Admin panel** for settings, users, content, layout, panels, themes, plugins
- **Theme engine** with live theme switching, **user light/dark mode toggle** and an **admin colour-scheme editor** (presets + custom colours)
- **Menu builder**: flashy gradient nav buttons, unlimited custom items, dropdowns, links to built-in modules, custom pages or external URLs
- **Hero module**: configurable banner / slider / video / animated gradient with CTA buttons
- **Plugin system** for panels, pages and widgets
- **User profiles** with avatars, signature, bio, social links, game IDs (Steam, Discord, Battle.net, …)

### Built-in modules
- 📰 **News** with custom categories (colour, icon, picture or auto-generated abstract covers), tags, comments, featured posts
- 🗨️ **Forum** (category → subcategory → topics → posts) — phpBB-style, shown inside the site with its own configurable side-panel layout
- ⚔️ **Matches** *(plugin)* — results & upcoming fixtures, opponent tag, competition, map, VOD link, match report; public page `/matches` with record (W–L–D, win rate, streak), filters and pagination; detail pages; Admin → Matches
- 👥 **Team / Roster** — players, roles, stats, social links
- 🏆 **Tournaments / Events** — brackets-lite, calendar
- 🖼️ **Gallery** — albums & screenshots
- 📥 **Downloads** — files with categories & counters
- 📨 **Private messages**
- 🧩 **Custom pages** (WYSIWYG-lite / Markdown)
- 🤝 **Sponsors & partners**
- 📝 **Join-us / recruitment form** (clan application workflow) ✅
- 🗳️ **Polls**
- 🔎 **Site search**

### Built-in panels (sidebar widgets)
`Hero` · `Login / User box` · `Navigation` · `Shoutbox / Chat` · `Recent Matches` (plugin) · `Next Match countdown` · `Online Users` · `Latest Forum Topics` · `Latest News` · `Top Posters` · `Birthdays` · `Poll` · `Server Status` (game server query) · `Discord Widget` · `Twitch/YouTube Live` · `Sponsors` · `Site Stats` · `Custom HTML`

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
| **3. Site & Superadmin** | Site name, URL, timezone, theme, plus the first account (username, email, password entered twice). **This first account becomes the Superadmin.** |
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

### 4.5 Updating & backups

**Admin → Backups & updates** (superadministrator only; permission `admin.system`).

*Backups*
- **Back up now** creates `backups/db-<time>-<label>.sql.gz` (every table of this installation, streamed, one statement per line, completeness marker). Tick *Include uploads* for one `full-….zip` with database + `uploads/`.
- **Restore** replaces the whole database with a chosen backup after you type `RESTORE`; a safety backup of the current state is made first and put back automatically if the restore fails. Full backups also merge their uploads back. Uploaded backups are verified (MagicCMS header, same table prefix, not from a newer schema, complete).
- **Automatic**: every N days (default 7, 0 = off) when a superadmin opens the admin area – the dump runs after the page was sent. Keep the newest N (default 10); safety backups from updates/restores are kept separately (newest 5). For exact schedules use cron: `php tools/backup.php [--uploads]`.
- Backups are private data (hashed passwords, messages). The `backups/` folder gets a deny-all `.htaccess`; on nginx the sample config blocks it. Put it outside the web root with `app.backup_dir` in `config/config.php` if you can.

*Updates* – never touch your settings, content, uploads or config
1. Get a release package (`MagicCMS-x.y.z.zip`, built with `php tools/build-release.php`, or use **Check for updates**, which reads the GitHub release feed over HTTPS and verifies a published `.sha256`).
2. **Review**: the package is validated *before* anything changes – `release.json`, allowed code folders only (never `config/`, `uploads/`, `cache/`, `backups/`, `setup.php`), allowed file types, no symlinks/path tricks/hidden files, per-file SHA-256, PHP syntax of every file, version not older than installed, PHP version/extensions, free disk space, writable targets. Customised bundled files are listed.
3. **Install** (type `UPDATE`): database backup → copy of every file that will be replaced (`backups/code-<ver>-<time>.zip`) → maintenance mode → atomic file replacement → the new code runs the **database migrations** on the next request → maintenance mode back to what it was. If anything fails – including a migration – code **and** database are rolled back automatically. Your own themes/plugins (anything not in the package) are never touched; put customisations in a child theme or plugin.
4. **Undo the last update** restores code and database from the pre-update backups.
CLI: `php tools/update.php package.zip`, `php tools/update.php --rollback`.

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
│   ├── magic-red/            # default theme: theme.json  tokens.css  theme.css  [templates/]
│   ├── hazard-protocol/      # terminal / hazard-yellow theme (child of magic-red)
│   ├── aurora-glass/         # bright frosted-glass theme, light by default (child of magic-red)
│   ├── mint-facet/           # sharp chamfered mint-on-graphite theme (child of magic-red)
│   ├── nebula-bloom/         # ultraviolet / magenta / blue glass, thin type, capsule title bars (child of magic-red)
│   ├── electric-camo/        # steel chrome + paper sheet + green light trails (child of magic-red)
│   ├── dark-gel/             # ink-navy + azure gel, gel-tube title bars, orange CTA (child of magic-red)
│   └── crystallic/           # ice-crystal glass, chamfered panels, prism edges, sparks (child of magic-red)
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
| `news_comments` | News comments |
| `forum_categories`, `forum_boards` (= subcategories), `forum_topics`, `forum_posts`, `forum_reads`, `forum_markread`, `forum_reports` | Forum (attachments & subscriptions tables arrive with those features) |
| `matches`, `match_maps`, `opponents`, `games`, `events` | Esports data |
| `roster_members` | Roster plugin |
| `notifications` | In-site notifications (bell) |
| `forum_topic_subs`, `forum_board_subs` | Forum subscriptions |
| `forum_attachments`, `forum_polls`, `forum_poll_options`, `forum_poll_votes`, `forum_reactions` | Forum attachments, polls and reactions |
| `recruit_positions`, `recruit_questions`, `recruit_applications`, `recruit_notes` | Recruitment plugin |
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

### Default roles & permissions

| Role | Level | What they can do |
|---|---|---|
| **Superadmin** | 100 | The account created in setup. Everything, including **Admin → Modules** (enable/disable plugins). Cannot be demoted, banned, edited or moderated by anyone else. Only the Superadmin can create Administrators. |
| **Administrator** | 80 | Everything in the admin area (settings, appearance, navigation, panels, pages, forum, news, hero, matches, users) and all moderation (forum, shoutbox, news comments) — **except anything that touches the Superadmin**: cannot ban/demote/edit that account, cannot edit, lock or delete that user's forum posts/topics, shouts, comments or news posts, cannot create or change other Administrators, cannot open Admin → Modules. |
| **Moderator** | 50 | **Moderates the forum only**: lock/sticky/move/delete topics and posts, handle reports. No admin area at all, no shoutbox or news-comment moderation, no banning. Can only moderate users at or below their own rank (not Administrators or the Superadmin). |
| **Member** | 10 | Posts in the forum and shoutbox, comments on news, reports posts, edits own profile/avatar. **Cannot change any theme, layout or site setting** — the only appearance control a member has is the **light/dark switch** (guests too). |
| **Guest** | 0 | Reads public content and the shoutbox. May also use the light/dark switch. |

**Rank rule (enforced server-side everywhere):** you may moderate content or manage accounts of users *at or below your own rank* (and your own). So Moderators cannot act on Administrators, and Administrators can never act on the Superadmin.

Default permission keys: Guest `chat.read` · Member `+ chat.post, forum.post, forum.report, forum.attach, forum.vote, forum.react, news.comment, pm.send, events.signup` · Moderator `+ forum.moderate` · Administrator `+ admin.access, admin.settings, admin.appearance, admin.users, admin.panels, admin.navigation, admin.pages, admin.forum, admin.news, admin.hero, admin.matches, admin.roster, admin.events, chat.moderate, news.moderate` · Superadmin: all (implicit; also `admin.modules`).

- Roles carry **permissions**; permissions are checked via `can('forum.moderate')`.
- Create **custom roles** (e.g. "Team Captain", "Coach") and assign any permission mix.
- Assign roles in **Admin → Users**. Every role change is written to the audit log.

### Registration & login

- Registration form: username, email, password (+ confirm), optional CAPTCHA/honeypot, ToS checkbox.
- Optional **e-mail verification** (Admin → Settings: members must click a link before they can log in) and **admin approval** modes — they can be combined.
- **Passwords** are hashed with `password_hash()` using **Argon2id** (falls back to bcrypt cost 12); automatic rehash on login when parameters change. Plaintext is never stored or logged.
- **Login** protections: per-IP + per-account rate limiting with progressive delay, generic error messages (no user enumeration), session ID regeneration on login, optional "remember me" via split-token cookies.
- **Password reset** via single-use, hashed, expiring (60 min) tokens.

---

### E-mail: verification, password reset, notifications

MagicCMS has a built-in mail layer (**Admin → Settings → E-mail**): **PHP `mail()`** (default, works on most hosts), a built-in **SMTP client** (STARTTLS, SSL or plain, AUTH LOGIN; the password is stored **encrypted** with your install secret) or a **log-only** mode (`cache/mail.log`) for development. A "send test e-mail" button checks your setup. Mails use a branded HTML template with a plain-text part.

- **E-mail verification** (switch in Admin → Settings): new members get a 24 h link; login is refused until it is clicked ("resend" is available on the login page and rate-limited). Existing members are grandfathered as verified. Changing the e-mail address in the profile sends a confirmation to the *new* address and keeps the old one until it is confirmed. Admins can mark an address verified in Admin → Users.
- **Password reset**: "Forgot your password?" on the login page. A single-use, **hashed**, 60-minute token is mailed; setting a new password signs out all remembered devices and also verifies the address. Responses are identical for known and unknown accounts (no account enumeration) and requests are rate-limited per IP.
- **Notifications**: private-message notifications (opt-out in the profile) never contain the message text — only who wrote and a link.
- Security: header-injection safe (CR/LF stripped), tokens never stored in clear, SMTP credentials encrypted at rest.

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

The CMS default is **`right`** – a single panel column on the right for a wide, modern look; the left column is optional. Examples: *both* sidebars on the home page, *none* on the forum for maximum width.

### Header & layout styles (Admin → Settings)

One site setting rearranges the logo, menu and account area – and it works with **every theme**, because the styles only move things around (structure in `assets/core.css`); colours, shapes and fonts still come from the theme's tokens.

| Style (`layout.header`) | Arrangement |
|---|---|
| `classic` (default) | Tall logo row on top, full-width sticky menu bar below |
| `inline` | **Compact**: logo · menu · account in one slim, sticky bar |
| `hanging` | **Hanging logo**: an oversized logo badge hangs off the left edge; the menu bar is offset to the right under a floating account cluster – deliberately asymmetric |
| `float` | **Floating islands**: three detached glass islands (logo, menu, account) at different heights; the bar stays sticky while scrolling |
| `topbar` | **Top bar**: a slim bar with the menu on the left and login/register on the right (max. 6 menu buttons, the rest collapse into a “More” dropdown); the logo sits below it with breathing room (left or centred). Options: sticky bar on/off, logo alignment |
| `rail` | **Side rail** (≥ 1360 px): fixed vertical menu with the logo on top and the account area at the bottom; narrower screens fall back to the classic header |

Two more switches: **Rhythm** (`layout.rhythm`: `even` / `stagger` – staggered offsets the sidebars, alternates panel insets and lifts every second stat tile, wide screens only) and **Mirror the side panels** (`layout.mirror`, swaps left ↔ right). All header styles collapse to a burger menu on phones. Themes can recommend a style with `"defaults": {"header": "hanging", "rhythm": "stagger"}` in `theme.json`.

### Footer (Admin → Footer) & logo-only header

Everything is structure + theme tokens, so it works with every theme:
- **Style**: *Columns*, *Centered*, *Minimal* (one slim line) or *Poster* (columns plus a giant outlined site name).
- **Blocks**: logo/name/short text, Explore (main menu), Community (footer menu), Latest news, up to **3 own link columns** (`Label | URL` per line), round **social buttons** (Discord, Twitch, YouTube, X, Instagram, TikTok, Steam, Kick, Facebook, Telegram, GitHub, e-mail – each lights up in its network colour), copyright line with `{year}`, back-to-top, "Powered by".
- **Vibrant extras**: a **scrolling ticker** of headlines, results/next match and upcoming events (direction and speed adjustable, pauses on hover), a **live strip** (online now · members · next match), a **giant wordmark**, and a top decoration (*animated aurora line*, *diagonal tape*, *none* or the theme's own).
- **Footer columns for panels**: in **Admin → Panels** add any panel (Custom HTML, Navigation, Latest news, Online users, Recent matches, …) to the position *Footer columns*; it is rendered as a plain column with a heading.
- **Logo-only header**: untick *Show the site name next to the logo* in Admin → Settings – header, rail, hanging badge and footer then show just the logo (the name stays as its alt text).

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
| < 1100px | Single column; side panels become **stacked panels (masonry on tablets, accordion on phones; each panel can be shown/hidden per device – desktop, tablet, phone – in Admin → Panels)** opened from header buttons (overlay, `Esc` closes) |
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
- **Button style** (Admin → Appearance → Navigation): `gradient` (default: pill group, active item glossy), `flash` (every button a gradient), `solid`, `outline`, `glass`, `pill`/`square`, plus hover effect (`glow`, `shine-sweep`, `lift`, `none`). Per-item colour override is optional.
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

- **Presets**: *Crimson* (default), *Ember* (orange→red; title bars automatically switch to dark text on bright schemes), *Royal* (blue), *Toxic* (green), *Violet*, *Mono* (greys) … each defining both dark and light palettes.
- **Custom colours** via colour pickers: primary, highlight, deep shade, accent, backgrounds, surfaces, text — with an automatic **contrast checker** (warns if text/background drops below WCAG AA 4.5:1).
- **Shape & feel**: corner radius (sharp → very round), animation level (off / subtle / full), gradient intensity, font pair.
- **Live preview** before saving; one-click **reset to theme defaults**; **export/import** a scheme as JSON.
- Stored in `settings` (`appearance.*`) and emitted as a small `<style>` block of CSS variables *after* the theme CSS, so it overrides any theme's tokens. Works with every theme that follows the token contract in [`docs/THEMING.md`](docs/THEMING.md).

---

## 12. Hero module

A configurable, animated banner (**Admin → Hero**). It is a slider of slides — or a single banner — built from your own slides and/or your featured news posts.

| Setting | Choices |
|---|---|
| **Where** | home page only (default) · all public pages (compact height) · off |
| **Content** | *my slides* · *featured news posts* · *slides, then featured news* |
| **Height / width** | compact · normal · tall · full screen  /  contained rounded card · full width (edge to edge) |
| **Transition** | fade · slide · zoom |
| **Background effect** | rising embers · aurora glow · moving grid · none |
| **Behaviour** | autoplay + seconds per slide (3–30), pause on hover/focus, arrows, dots with a progress fill, swipe on touch, ← → keys, mouse parallax on desktop |

**Per slide:** small label (eyebrow), headline, sub-headline, optional **background picture** (auto-resized to 1920 px, Ken-Burns zoom) with an adjustable **darkening overlay**, text alignment (left/centre/right), **two call-to-action buttons** (each linking to a built-in page/module, a custom page, or a URL — same targets as the menu builder), optional **custom gradient colours** (otherwise the site colour scheme), an **active** switch, **start/end dates** for scheduling (e.g. a tournament announcement), and up/down ordering. **Featured media (new):** each slide can show, beside the text, a **rounded rectangular image** or a **YouTube video** — on the **right or the left** (Admin → Hero → slide → *Featured media*). Images are cropped to 16:9 and get rounded corners, a glass frame and a gentle slide-in; videos show a poster with a play button and load **only after a click**, through `youtube-nocookie.com` (the CSP allows only that domain), so nothing is requested from YouTube until a visitor presses play. For a video you can upload your own poster (then there is no third-party request at all) or leave it empty to use YouTube's thumbnail. Paste any YouTube link form (watch, `youtu.be`, shorts, embed). The slider pauses while a video plays and stops it when you change slides. On phones the media drops below the text. *Featured news* slides can use the same layout: *Admin → Hero → Featured news pictures* = background / rounded image right / rounded image left.

A fresh install ships three gradient slides, so the home page looks good immediately.

Quality notes: effects are pure CSS (transform/opacity only); everything respects `prefers-reduced-motion` and the admin's *Animations* setting; the banner is fully keyboard- and screen-reader-accessible (carousel roles, hidden inactive slides); with no active slides the banner simply doesn't render.

---

## 13. News & categories

A blog-style news system (**Admin → News**) with custom categories, tags, comments and RSS.

**Posts:** title, slug, category, excerpt, HTML body (unsafe tags are stripped automatically), optional cover picture (cropped to 16:9), tags, status (*draft / published*), **publish date** (a future date schedules the post), **featured** flag (pinned first; can feed the hero), comments on/off, SEO title/description. Public pages add OpenGraph tags for nice link previews, a view counter, related posts and a draft preview for admins.

**Categories** — create as many as you like (*Match Reports*, *Team News*, *Patch Notes*, …):

| Field | Description |
|---|---|
| Name / slug / description | Shown on the category page |
| **Colour** | Badges, card accent line, glows and the generated cover |
| **Icon** | Any emoji |
| **Picture** (optional) | Used as the fallback cover for the category's posts |
| **Cover style** | auto · blades · orbits · mesh · waves (plus a site-wide default) |
| Sort order | Controls the filter chips |

Deleting a category keeps its posts (they become uncategorised).

**Procedural covers.** A post without a cover picture (and whose category has no picture) gets an **abstract SVG cover generated on the fly** from the post id and the category colour — layered blades, orbit rings, glowing mesh or waves. The same post always gets the same artwork, nothing is stored, there are no external services, and the response is served with immutable HTTP caching (`/cover/news/<id>.svg?v=<hash>`; the hash changes when you change the colour or style).

**Also included:** category and tag archives, category filter chips, **comments** (members; flood-limited; moderators or the author can delete), **RSS** feed (`/news/feed.xml`, optional `?category=slug`), a *Latest News* panel (optionally filtered by category), a *Latest news* grid on the home page, and a News menu entry.

**Planned:** rich-text editor, scheduled "publish" notifications, per-post gallery.

---

## 14. Built-in panels & modules

| Panel | Description |
|---|---|
| **User box** | Login form for guests; avatar, PM count, profile & logout for members. |
| **Navigation** | Menu from `menu_items`, nested, with icons. |
| **Chat / Shoutbox** | AJAX shoutbox (polling, optional SSE). Emoji, rate limit, flood protection, moderator delete, role-based posting, optional history page. |
| **Recent Matches** *(Matches plugin)* | The last **5, 8 or 10** results (admin choice in Admin → Matches) with W/L/D badge, opponent, score and age, an "Up next" card for the next fixture, and an **All matches** button to the full list at `/matches`. |
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

### Private messages

Members write each other from the profile page or **✉ Messages** in the header (with an unread badge). Conversations have a subject and up to **5 participants**, BBCode (same editor as the forum), a live unread marker, per-user delete (a thread disappears for everybody when all participants deleted it, and resurfaces when someone replies), and **blocking**. Limits: 5 s between messages, 5,000 characters, 20 new conversations per hour. Administrators and moderators **cannot read** other people's messages. Permission: `pm.send` (members and up); recipients can opt out of e-mail notifications.

### Notifications centre

The bell in the header shows unread notifications; `/notifications` lists them (opening one marks it read and redirects to a local URL), with *mark all read* and *clear read*. Read entries are purged after 30 days, any after 90. Modules add notifications with `notify_user($userId, $type, $title, '/local/url', $body, $ref)` (same `$ref` merges unread entries) or `Notifications::addForPermission('perm', …)`.

### Recruitment plugin (`plugins/recruit`)

- Public **Join us** form (`/recruit`, menu item added): position picker, name + e-mail, admin-defined **questions** (short/long text, choice list, yes/no; required or optional; reorderable). Guests may apply unless *Only registered members* is set; logged-in members are pre-filled and linked to their account.
- Spam/abuse protection: CSRF, hidden honeypot, 3 applications/hour per connection, one open application per person/e-mail, strict server-side answer validation. Applicants get an e-mail with a private **status link** (`/recruit/status/{token}`, not indexable) where they can follow the decision and **withdraw**.
- **Admin → Recruitment** (`admin.recruit`, administrators): filter by status (new → in review → interview → accepted/declined), search, read answers, internal **notes** (never shown to the applicant), decision with optional message → in-site notification + e-mail to the applicant, *Add to roster* shortcut for accepted people. New applications notify everyone with `admin.recruit` (bell) and optional extra e-mail addresses.
- **Setup**: enable/disable, intro text, positions (open/closed), questions, retention (decided applications are purged after N days, default 180; 0 = keep).

### Roster plugin (`plugins/roster`)

Public **Roster** page with player cards (4:5 rounded photo or coloured initials tile, **country flag**, role badge, social icons) grouped by **squad** with filter chips; detail page with bio, **stat tiles**, social links and a link to the member's site profile (+ "send message"); former members in a collapsible section. **Admin → Roster**: add/edit/reorder, photo upload (auto-cropped), up to 6 custom stats, 8 social links (URLs validated), link to a site account, active/former. Plus the **Our Squad** panel (off by default).

### Events & tournaments plugin (`plugins/events`)

- **Calendar** (month grid with colour-coded types), **upcoming / past lists**, detail pages with relative start time, **Add to calendar (.ics)** per event and a **subscribable feed** (`/events/feed.ics`; RFC 5545 escaping/folding, UTC times).
- **Sign-ups** for members (*going / maybe / can't make it*) with optional **capacity** and automatic **waiting list** (the oldest waiting member is promoted when a place frees up, when capacity is raised or when an admin removes someone), optional deadline, closes automatically when the event is over.
- **Tournament brackets**: single elimination for 2–32 entrants (typed in, shuffled, or taken from the "going" list), standard seeding with **byes**, scores per match, winners advance automatically, results entered later void dependent results, champion display. Rendered as a responsive bracket.
- Types: event / tournament / training / stream; drafts; **Upcoming Events** panel. Admin → Events (admins). Permission `events.signup` for members.

---

## 15. Forum

A phpBB-style forum, built as the `forum` module and shown **inside the normal site** (header, menu and footer stay) — it takes over the main content area where the home page would show the hero and news. Open it from the **Forum** menu button, the *Latest Topics* panel, or `/forum`.

**Structure:** **Category → Subcategory → Topic → Post**

```
Forum                        /forum
└─ Category                  /forum/category/3         e.g. "Community", "Esports"
   └─ Subcategory            /forum/subcategory/7      e.g. "Announcements", "Scrims"
      └─ Topic               /forum/topic/42
         └─ Posts            /forum/post/310  (jumps to the right page + anchor)
```

Categories and subcategories are created in **Admin → Forum**. Breadcrumbs show the full path on every page.

**Forum layout (side panels)** — by default forum pages use the **full width with no side panels**, so the forum feels like a page of the site. In **Admin → Forum → Forum layout** the admin can keep the site's panels visible on forum pages and choose **left only, right only, or both**. The setting (`forum.layout`) applies to every forum page and is independent from the home page's layout.

**Implemented**
- **Access per subcategory** – each subcategory has a *read level* and a *post level* (Everyone/guests, Members, Moderators, Administrators) and an optional *locked* flag. Anything a user may not read is completely hidden (index, direct URLs, search, panels).
- **Topics** – normal, **sticky** and **announcement** types; locked topics; move between subcategories; view counter; reply counter; mini page links; jump to last post / to a specific post.
- **Posting** – BBCode with a toolbar, keyboard shortcuts (Ctrl+B/I/U), **live preview**, quoting, quick-reply, edit (with "edited by" marker), flood protection (10 s), length limits.
- **BBCode** – `[b] [i] [u] [s] [quote] [quote=Name] [code] [url] [url=…] [img] (https only) [list] [list=1] [*] [spoiler]`, auto-links and emoticons. Input is HTML-escaped *first*, so no markup or script can get through.
- **Unread tracking** – unread dots on subcategories/topics, *Unread topics* page, *Mark all read*. New members only see content posted after they joined as "unread".
- **Moderation** – lock/unlock, sticky/announce/normal, move, delete topic or post; **report post → moderator queue** (`/forum/reports`) with a badge counter; all actions are written to the audit log.
- **Rules for members** – may edit their own posts (unless the topic is locked), delete their own replies (not the opening post).
- **Search** – MySQL FULLTEXT on posts + title match, filtered by what the searcher may read.
- **Author box** – circular avatar, role badge, rank (Newcomer → Legend by post count), post count, join date, signature.
- **Latest Topics panel** and a forum **menu entry** are installed automatically.
- Fully responsive: on phones the author box collapses above each post and topic rows become compact.

### Attachments, polls & reactions

- **Attachments** (permission `forum.attach`): up to 5 files per post (1–10), 5 MB each (1–5, admin setting). Images are **decoded and re-encoded** (metadata stripped, max 1920 px, 480 px thumbnail; GIFs lose animation). Documents are limited to **PDF, TXT, ZIP, 7Z** – the extension must match the detected MIME type – and can be switched off (images only). SVG/HTML/executables are always refused. Files live in `uploads/forum/` under random names with direct web access denied; they are served **only** by `/forum/attachment/{id}`, which checks the subcategory's read level, sends `nosniff`, forces download for documents (with a sandboxing CSP) and counts downloads. Authors/moderators can remove attachments when editing; deleting a post or topic deletes its files. If an upload is rejected the post is still saved and the reason is shown.
- **Polls** (create with permission `forum.post`, vote with `forum.vote`): one poll per topic, 2–10 options, single or multiple choice (with a maximum), optional end date (1–90 days), **anonymous votes** that members can change until the poll ends, and a results mode: *after voting* (default) / *always* / *only when ended*. The author or a moderator can end/reopen a poll; locked topics and ended polls refuse votes. A poll can be edited or removed by its author while it has no votes (moderators can always remove it). Topic lists show 📊/📎 markers.
- **Reactions** (permission `forum.react`): 👍 ❤️ 😂 😮 🔥 👏 – one per member per post, click again to remove, switch by clicking another; counts and names (on hover) are shown, the post author gets a (merged) notification, own posts can't be reacted to.
- Admin → Forum → **Features** switches attachments (and documents), polls and reactions on/off and sets the file limits.

---

**Planned:** rich-text editor, merge/split topics, per-user mute/warn, RSS feeds.

**Permissions:** `forum.post`, `forum.report` (Members and up), `forum.moderate` (Moderators and up), `admin.forum` (Administrators). Change them per role in the `role_permissions` table (role editor UI planned).

*Technical note:* the tables keep their original names (`forum_categories` = categories, `forum_boards` = subcategories). The old `/forum/board/ID` URL redirects to `/forum/subcategory/ID`. Earlier "sub-boards" were flattened into normal subcategories by migration `0003`.

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

**Hardening notes (security review)**
- **Sessions**: a password change or reset ends every other session and remember-me token; a fresh CSRF token is issued at login; changing the e-mail address requires the current password; current-password guessing is rate-limited.
- **Abuse limits**: registration per connection per hour (admin setting, default 5), login failures per connection (10 / 15 min) and per account (30 – high enough that a stranger cannot lock the owner out), password/e-mail change attempts; unknown accounts cost the same as real ones (no timing oracle); password-reset and verification e-mails are sent after the response.
- **E-mail**: strict address validation (no quoted local parts / SMTP-significant characters), header-injection-safe mailer, signed unsubscribe links.
- **Uploads**: images re-encoded (metadata stripped, pixel caps), SVG logos scanned, forum attachments stored outside direct web access and served by a permission-checking controller with `nosniff`, forced download and a sandboxing CSP, per-member storage quota; the nginx sample adds `nosniff` + a locked-down CSP for `/uploads/`.
- **Forum**: read levels are enforced for moderation, editing, reporting and subscribing, not only for viewing; poll votes and event sign-ups are serialised with row locks (no double votes or oversold capacity); bare URLs in posts are linked only in text nodes (never inside tags).
- **Backups/updates**: a restore only accepts the statements MagicCMS itself writes (single statements, own table prefix, no `DROP DATABASE`/`INTO OUTFILE`/chained queries); update packages are validated, checksummed and rolled back automatically on failure. Update feeds are fetched over HTTPS only. Packages are not cryptographically signed – only install packages from sources you trust.
- Cookie-less clients (bots) are not tracked in "who is online".

## 18. Theming

MagicCMS themes are **self-contained folders** that define a design via **CSS variables (design tokens)** and, if needed, **template overrides**. A theme can be a 30-line colour swap or a total redesign.

The default theme **Magic Red** (v1.1 "smoked glass") is: **frosted, tinted glass panels** (backdrop blur + saturation, fine film grain, a 1 px lit edge that is bright top-left and warm at the far corner, asymmetric corner radii, and ripples of light radiating from a different corner of each panel); **glossy, translucent title lenses** in a condensed display face (Big Shoulders Display) with a sheared right edge, brushed texture, echo arcs and a glass-bead icon, whose colour bleeds into the panel below; one orchestrated sheen sweep at page load (then on hover). The older base look: warm-black surfaces with drifting aurora glows, a masked grid and film grain; frosted-glass cards with large rounded corners; **glossy gradient title bars** with a slow shine sweep; a pill-group navigation whose active item is a glossy gradient; Unbounded display type over Inter body text (both self-hosted); live stat tiles and a gradient hero on the home page — all CSS-only (GPU-friendly transforms/opacity), respecting `prefers-reduced-motion` and the admin's animation setting ("Off" also drops the blur effects for slow devices). It includes a **light variant** (`[data-theme="light"]`) and honours the **admin colour scheme** overrides (see [§11](#11-appearance-lightdark-mode--colour-schemes)).

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

### Plugins

A **plugin** is a folder in `plugins/<key>/` that adds a feature without touching core. The bundled **Matches** plugin is the reference implementation:

```
plugins/matches/
├── plugin.json          # name, version, description, provides…
├── routes.php           # returns function (Router $r): registers routes + hooks (nav targets, admin tabs)
├── Matches.php          # namespace MagicCMS\Plugins\Matches  → autoloaded from plugins/matches/*.php
├── Controller.php  AdminController.php
├── views/               # render('matches/index') → plugins/matches/views/index.php (themes can override in templates/matches/)
└── panels/recent_matches/{panel.json, panel.php}   # auto-discovered panel
```

- **Registration:** a row in the `modules` table with `is_core = 0` (the plugin's migration inserts it). The **Superadmin** can enable/disable plugins in **Admin → Modules**; disabled plugins add no routes, panels or admin tabs.
- **Database:** add `migrations/NNNN_name.sql` (+ optional `.php` seed returning `function (Database $db): void`); they run automatically on install and upgrade.
- **Hooks used:** `nav.targets` (make the plugin selectable in the menu builder and hero buttons) and `admin.tabs` (add an Admin tab: `$tabs['matches'] = ['/admin/matches', 'Matches', 'admin.matches']`).
- **Permissions:** insert into `permissions` / `role_permissions` in the seed; guard routes with `['perm' => 'admin.matches']`.

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
| `layout.mode` | `right` | `none` / `left` / `right` / `both` – one right-hand column is the default |
| `layout.header` | `classic` | `classic` / `inline` / `hanging` / `float` / `rail` |
| `layout.rhythm` | `even` | `even` / `stagger` |
| `layout.mirror` | `false` | Swap left/right side panels |
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
**v0.2 – Community:** ✅ profiles & avatars, ✅ forum, ✅ shoutbox, ✅ online users, ✅ private messages
**v0.3 – Esports:** ✅ matches + recent-matches panel, ✅ roster, ✅ events & tournaments, ✅ recruitment form
**v0.4 – Content:** ✅ news + categories + generated covers, ✅ hero, pages, gallery, downloads, polls, search
**v0.5 – Polish:** ✅ notifications centre, audit log UI, SEO/RSS/sitemap, GDPR tools, i18n packs
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
