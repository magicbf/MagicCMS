# MagicCMS Theming Guide

> Audience: human developers **and AI models** asked to create a MagicCMS theme.
> If you are an AI: read §1–§7, follow the checklist in §9, and output files exactly as specified.

---

## 1. Concept

A theme = a folder in `themes/<slug>/`. MagicCMS loads:

1. `theme.json` — manifest
2. `tokens.css` — **design tokens** (CSS custom properties). Everything visual should derive from these.
3. `theme.css` — component styling using the tokens
4. `theme.js` *(optional)* — small progressive enhancements
5. `templates/*.php` *(optional)* — override core HTML structure

Minimal viable theme: **`theme.json` + `tokens.css`**. All missing files fall back to the default theme (`magic-red`) / core templates, so a theme can never "break" the site by omission.

```
themes/my-theme/
├── theme.json
├── tokens.css
├── theme.css
├── theme.js              (optional)
├── preview.png           (optional, 800×500, shown in Admin → Themes)
└── templates/            (optional overrides)
    ├── layout.php
    ├── header.php
    ├── footer.php
    ├── panel.php
    └── ...
```

## 2. Manifest — `theme.json`

```json
{
  "name": "Blue Ice",
  "slug": "blue-ice",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "Cold blue esports look.",
  "requires": "1.0",
  "parent": "magic-red",
  "stylesheets": ["tokens.css", "theme.css"],
  "scripts": ["theme.js"],
  "supports": { "panels": ["left", "right"], "darkLight": false }
}
```

| Field | Required | Notes |
|---|---|---|
| `name`, `slug`, `version` | ✅ | `slug` = folder name, `[a-z0-9-]+` |
| `parent` | ❌ | Inherit everything from another theme; only override what you need (**recommended for AI-made themes**) |
| `stylesheets` | ❌ | Loaded in order. Defaults to `["tokens.css","theme.css"]` |
| `scripts` | ❌ | Loaded with `defer` |
| `requires` | ❌ | Minimum MagicCMS version |

## 3. Design tokens — `tokens.css`

All tokens are defined on `:root`. **Never hard-code colours in `theme.css`; use `var(--token)`** (derive translucent variants with `color-mix(in srgb, var(--c-primary) 40%, transparent)`). The default theme ("Ember" look) defines:

```css
:root {
  /* Brand — the signature gradient runs  --c-primary-hi → --c-primary.  Admin colour schemes override these. */
  --c-primary:#e5091f;  --c-primary-hi:#ff5238;  --c-primary-lo:#8c0012;  --c-accent:#ff9a3d;
  --c-text-on-primary:#ffffff;
  --c-on-bar:#ffffff;            /* text on gradient title bars & hero. Set by core: dark on bright schemes, white on deep ones */

  /* Surfaces */
  --c-bg:#0a0706; --c-bg-2:#120c0a; --c-surface:#18110e; --c-surface-2:#221814; --c-border:#2d211b;
  --glass:linear-gradient(180deg,rgba(34,24,20,.72),rgba(18,13,11,.72));   /* card/panel background */
  --glass-border:rgba(255,255,255,.07);  --glass-hl:rgba(255,200,160,.07); /* hairline + inset top highlight */
  --field-bg:…; --field-border:…;  --pill-bg:…;                            /* inputs; nav group, tabs, chips */
  --btn-bg:#0e0907; --btn-fg:#fff4ec; --btn-border:rgba(255,255,255,.10);  /* default (dark) button */

  /* Text */
  --c-text:#fff4ec; --c-text-dim:#b5a196; --c-text-faint:#8f7d73;

  /* Status */
  --c-success:#3ddc84; --c-warning:#f5a623; --c-danger:#ff5a5a; --c-info:#7cc4ff;

  /* Gradients */
  --g-primary:linear-gradient(135deg,var(--c-primary-hi),var(--c-primary));
  --g-gloss:linear-gradient(180deg,rgba(255,255,255,.26),rgba(255,255,255,0) 55%);  /* layered on top of --g-primary for the glossy button look */
  --g-titlebar:/* glossy highlight + warm radial + horizontal gradient */;

  /* Shape — the admin "corner radius" slider sets --radius; -sm / -lg follow (½× and 1.5×) */
  --radius-sm:8px; --radius:16px; --radius-lg:24px;

  --shadow:0 24px 48px rgba(0,0,0,.35);  --glow:…;

  /* Type — Unbounded (display) + Inter (body), self-hosted in themes/magic-red/fonts (SIL OFL) */
  --font-body:"Inter",system-ui,sans-serif;  --font-display:"Unbounded","Arial Black",sans-serif;
  --fs-base:15px; --lh:1.6;

  /* Layout & motion */
  --container-w:1360px; --sidebar-w:300px; --gap:22px; --header-h:68px;
  --ease:cubic-bezier(.2,.8,.2,1); --t-fast:.15s; --t:.3s;
}
```

Changing only these values re-skins the entire CMS.

**Fonts:** ship font files inside the theme (`themes/<slug>/fonts/*.woff2`) and declare them with `@font-face` using a *relative* `url(fonts/…)`. External font CDNs are blocked by the site's Content-Security-Policy on purpose (privacy, speed).

**Decor layer:** the core layout outputs `<div class="bg-fx" aria-hidden="true">` with six empty spans (`.fx-a .fx-b .fx-c .fx-grid .fx-grain .fx-vig`). Core only positions them; a theme paints them (the default theme draws aurora glows, a masked grid, film grain and a vignette) or sets `.bg-fx{display:none}`.

### 3.1 Light & dark mode

Core sets `data-theme="dark"` or `data-theme="light"` on `<html>` (resolved from the user's choice, the admin default, or `prefers-color-scheme` for `auto`) **before first paint**. Dark tokens live on `:root`; a theme that supports both **must** also define the light overrides:

```css
:root[data-theme="light"] {
  --c-bg:#f5f5f7; --c-bg-2:#ffffff; --c-surface:#ffffff; --c-surface-2:#f0f0f3;
  --c-border:#dcdce2; --c-text:#17171c; --c-text-dim:#5a5a66;
  --shadow:0 6px 18px rgba(20,20,30,.12);
  --glow:0 0 0 1px var(--c-primary), 0 0 18px rgba(225,6,19,.25);
  color-scheme: light;
}
```

Rules: never hard-code colours (use tokens) so both modes work; brand colours may stay the same; set `"darkLight": true` in `theme.json`. Style mode-specific details with `[data-theme="light"] .selector`. The toggle button is `.mode-toggle` (core JS flips the attribute and saves the preference).

### 3.2 Admin colour-scheme overrides

Admins can choose a preset or custom colours in **Admin → Appearance**. Core stores them and emits, *after* all theme stylesheets:

```html
<style id="mcms-scheme">
:root{--c-primary:#2f6bff;--c-primary-hi:#6e9bff;--c-primary-lo:#0a2a7a;--radius:12px}
:root[data-theme="light"]{--c-primary:#1f56e0}
</style>
```

Overridable tokens (emitted by core): `--c-primary`, `--c-primary-hi`, `--c-primary-lo`, `--c-accent`, `--c-text-on-primary`, **`--c-on-bar`** (computed from the gradient's brightness), `--radius`, `--radius-sm`, `--radius-lg`, and the motion level (`<html data-motion="off|subtle|full">`).

**Consequence for theme authors:** derive gradients, glows and shadows from tokens (`color-mix(in srgb, var(--c-primary) 45%, transparent)` for translucent variants) instead of hard-coded RGB, so scheme changes recolour everything. Honour `[data-motion="off"]` by disabling animations.

## 4. Required CSS class contract

MagicCMS core markup uses stable, BEM-ish class names. A theme **should style at least these** (anything unstyled falls back to the parent/default theme):

### Layout
| Selector | Element |
|---|---|
| `body.mcms` | `<body>`; also has `layout-none\|left\|right\|both` and `page-<module>` classes |
| `.site-header` (tall brand row), `.site-navbar` (full-width sticky menu bar; gets `.is-stuck` once the header has scrolled away), `.site-nav`, `.site-footer` | Chrome. Header brand: `.brand` containing `img.brand-mark` (bundled emblem, 40×40) **or** `img.brand-logo` (admin-uploaded logo, any aspect ratio, `height:40px;width:auto`) plus optional `.brand-text` |
| `.site-grid` | CSS grid wrapper: `[left] [main] [right]` |
| `.col-left`, `.col-main`, `.col-right` | Columns (absent when the layout mode hides them) |
| `.drawer-toggle`, `.drawer` | Mobile sidebar controls |

### Header & footer
Header (`views/header.php`): `header.site-header#top > .container.header-top` with `.brand.brand-xl` (the logo is the dominant element: 64 px) and `.header-actions`; then `.nav-sentinel` (an invisible marker watched by core.js) and `div.site-navbar > .container.navbar-inner` containing `.nav-mini-brand` (shown only while `.is-stuck`) and the `nav.site-nav`. The menu list stretches over the full width and its items share the space equally.
Footer (`views/footer.php`): `.site-footer > .footer-glow`, `.footer-grid` with `.fcol` columns (`.fbrand`, Explore, Community, Latest news; `h4` headings), and `.footer-bottom` (`.powered`, `.to-top`).

### Panels
```html
<section class="panel" data-panel="online_users">
  <header class="panel-title"><h3>Online Users</h3><button class="panel-toggle"></button></header>
  <div class="panel-body"> …panel output… </div>
</section>
```
Selectors: `.panel`, `.panel-title` (optionally containing `.panel-icon` – an emoji chip from the panel's `icon` in `panel.json`), `.panel-body`, `.panel-toggle`, `.panel.is-collapsed`. Title bars are decorated with `::before` (hatching) and `::after` (travelling glint); the default theme also animates a flowing gradient (`tb-flow`). The text colour/shadow come from `--c-on-bar` / `--bar-text-shadow`, which core computes from the colour scheme.

### Content blocks
`.card`, `.card-title` (bold title-bar), `.btn`, `.btn-primary`, `.btn-ghost`, `.btn-danger`, `.badge`, `.alert.alert-{success|warning|danger|info}`, `.table`, `.pagination`, `.breadcrumb`, `.tabs`, `.avatar` (+ `.avatar-xs|sm|md|lg|xl`; **always circular – core.css forces `border-radius:50%`, do not fight it; style only border/shadow**), `.role-{superadmin|admin|mod|member}`

### Forms
`.form-row`, `.input`, `.select`, `.textarea`, `.checkbox`, `.label`, `.help`, `.error`

### Forum
Structure (grid/responsive) is in core.css; a theme paints it. Forum pages are rendered with the layout mode chosen in Admin → Forum (default `layout-none`), so a theme must look right both with and without side columns. Classes: `.breadcrumb` (+`.sep`), `.cat-link`, `.forum-head`, `.forum-bar`, `.forum-cat`, `.forum-table`, `.forum-row` with `.board-row` / `.topic-row` and state `.is-unread`, cells `.fr-icon` `.fr-main` `.fr-stat` `.fr-last`, `.topic-title`, `.mini-pages`, `.subboards`,
posts: `.post` (grid: `.post-author` + `.post-main`), `.author-name`, `.post-meta`, `.post-body`, `.post-edited`, `.post-signature`, `.post-actions`; BBCode output: `.bb-quote` (+`cite`), `.bb-code`, `.bb-spoiler`, `.bb-img`;
editor: `.editor`, `.editor-toolbar`, `.editor-preview`; moderation: `.mod-tools`, `.report`, `.report-item`, `.search-hit`; panel: `.latest-list`. Avatars inside posts use the normal `.avatar` classes and are always circular.

### Matches / esports
`.match-row`, `.match-win`, `.match-loss`, `.match-draw`, `.team-card`, `.countdown`

### Chat
`.chat`, `.chat-log`, `.chat-msg`, `.chat-input`

### Navigation (menu builder output)
```html
<nav class="site-nav" aria-label="Main">
  <button class="nav-burger" aria-expanded="false"></button>
  <ul class="nav-list">
    <li class="nav-item is-active"><a class="nav-btn" href="/news"><i class="icon"></i><span>News</span><b class="nav-badge">3</b></a></li>
    <li class="nav-item has-sub"><a class="nav-btn" href="#">Team</a>
      <ul class="nav-sub"><li><a class="nav-btn nav-btn-sub" href="/team">Roster</a></li></ul></li>
  </ul>
</nav>
```
Selectors: `.nav-list`, `.nav-item`, `.nav-btn`, `.nav-btn-sub`, `.nav-sub`, `.nav-badge`, `.nav-burger`, `.is-active`, `.is-open`, `.nav-vertical` (sidebar variant), `.hide-mobile`. The burger/dropdown *behaviour* is in core.js + core.css; the theme only paints it. The `<html>` carries `data-nav-style="gradient|flash|solid|outline|glass"` (gradient = pill group with a gradient active item; flash = every button gradient) and `data-nav-fx="shine|glow|lift|none"` (admin choice) — style each combination. Items may carry an inline `--btn-color` override: derive the button from it, e.g. `--btn: var(--btn-color, var(--c-primary))` (see Magic Red).

### Hero
Markup (from `core/Hero.php`; the theme only paints it):
```html
<div class="hero-wrap [hero-wrap-full]"><div class="container">
<section class="hero hero-h-{compact|normal|tall|full} hero-tr-{fade|slide|zoom} hero-fx-{embers|aurora|grid|none}" data-hero …>
  <div class="hero-fx"> <i style="--x;--s;--d;--dl;--dx"> … | .fxa-1 .fxa-2 | .fxg </div>
  <div class="hero-slides">
    <article class="hero-slide align-{left|center|right} [has-image] [is-active|is-leaving]" style="--hf;--hs;--ov">
      <div class="hero-bg"></div>  <!-- or: .hero-media (background-image) + .hero-overlay -->
      <div class="hero-content"><span class="eyebrow"> <h2 class="hero-title"> <p class="lead"> <div class="hero-cta"><a class="btn btn-dark"> <a class="btn btn-glass"></div></div>
      <!-- optional featured media; the slide then also has .has-media .media-left|.media-right -->
      <div class="hero-mediabox is-image|is-video"> <img class="hero-pic">  |  <div class="hero-video" data-yt="ID"><button class="hero-videobtn"><img class="hero-pic"><span class="hero-play"></span></button> (+ <iframe class="hero-iframe"> after click)</div> </div>
    </article> …
  </div>
  <button class="hero-arrow prev|next"> <div class="hero-dots"><button class="hero-dot is-active"> …
</section></div></div>
```
Custom properties set by core: `--hh` (height, on `.hero`), `--hf`/`--hs` (optional slide gradient colours), `--ov` (overlay 0–.9), `--iv` (autoplay interval, drives the dot progress fill), `--px`/`--py` (parallax offsets from `hero.js`). Slides are stacked in one grid cell and switched with `.is-active` / `.is-leaving`, so a theme animates them with plain CSS transitions. Use `var(--c-on-bar)` for text on the gradient and white on picture slides. `.hero-mediabox` must keep rounded corners (`border-radius: var(--radius-lg)`, `overflow:hidden`, 16:9) in every theme; below 900 px it should stack under the text.

### News & categories
`.news-grid`, `.news-card` (+`.news-featured`), `.news-cover`, `.news-flag`, `.news-body`, `.news-meta`, `.news-title`, `.news-excerpt`, `.news-foot`, `.cat-badge`, `.chip-row` / `.chip`, `.tag`; article: `.article`, `.article-cover`, `.article-head`, `.article-title`, `.article-lead`, `.article-body`; comments: `.comment`; panel: `.news-mini`.
Each category-coloured element gets `style="--cat-color:#hex"` — use `var(--cat-color, var(--c-primary))` for badges, borders and title-bar accents. Covers are `<img>` inside `.news-cover` pointing to an uploaded image **or** a generated SVG (`/cover/news/<id>.svg`); style both identically (`object-fit:cover; aspect-ratio:16/9`).

### Home & stats (default theme)
`.page-intro`, `.page-title`, `.eyebrow`, `.live-dot`, `.hero-lite` (`.hero-mark`, `.hero-title`, `.lead`, `.hero-cta` with `.btn-dark` / `.btn-glass`), `.stat-grid` / `.stat-tile` (`.is-hot` highlights one tile), `.grad-text`.

### Mode toggle
`.mode-toggle` (button), with `[data-theme]` on `<html>`.

## 5. Template overrides (optional)

Place a file with the same name in `themes/<slug>/templates/` to replace the core one. Templates are plain PHP. **Always escape output with `e()`**.

| Template | Purpose | Variables |
|---|---|---|
| `layout.php` | Whole page shell | `$title`, `$content`, `$layoutMode`, `$panels` (`['left'=>[…],'right'=>[…]]`), `$user`, `$meta` |
| `header.php` / `footer.php` | Chrome | `$user`, `$menu`, `$siteName` |
| `nav.php` | Menu markup | `$items` (tree: `label,url,icon,badge,active,children,color,new_tab,hideMobile`), `$class` |
| `news/_card.php` | News teaser card | `$p` (post row with `cat_name,cat_slug,cat_color,cat_icon,author`), `$big` |
| `panel.php` | Panel wrapper | `$panel` (`id,key,title,hideMobile`), `$html` |
| `pagination.php`, `breadcrumb.php`, `alert.php` | Components | component-specific |

Skeleton of `layout.php` (this mirrors the core `views/layout.php`; copy it and edit):

```php
<?php /** @var string $title,$content,$bodyClass,$description,$mode,$modeSetting  @var array $layout */ ?>
<!doctype html>
<html lang="<?= e(setting('site.lang', 'en')) ?>" data-theme="<?= e($mode) ?>" data-theme-setting="<?= e($modeSetting) ?>"
      data-motion="<?= e(MagicCMS\Core\Appearance::motion()) ?>" data-nav-style="<?= e(setting('nav.button_style', 'gradient')) ?>" data-nav-fx="<?= e(setting('nav.hover_fx', 'shine')) ?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · <?= e(setting('site.name')) ?></title>
  <!-- keep: resolves "auto" light/dark before first paint (CSP nonce required) -->
  <script nonce="<?= e(MagicCMS\Core\Bootstrap::$nonce) ?>">(function(){var d=document.documentElement;if(d.dataset.themeSetting==='auto'){d.dataset.theme=matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'}})();</script>
  <?= theme_assets('head') ?>          <!-- core.css + theme stylesheets + admin colour-scheme <style> -->
  <?= hook('layout.render.head') ?>
</head>
<body class="mcms layout-<?= e($layout['mode']) ?> <?= e($bodyClass) ?>" data-csrf="<?= e(MagicCMS\Core\Csrf::token()) ?>" data-base="<?= e(url('')) ?>">
  <?= render('header', ['layout' => $layout]) ?>
  <div class="container">
    <?php foreach (flashes() as [$type, $msg]): ?><div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div><?php endforeach ?>
    <div class="site-grid">
      <?php if ($layout['left']): ?><aside class="col-left" id="drawer-left"><?= implode("\n", $layout['left']) ?></aside><?php endif ?>
      <main class="col-main" id="main"><?= $content ?></main>
      <?php if ($layout['right']): ?><aside class="col-right" id="drawer-right"><?= implode("\n", $layout['right']) ?></aside><?php endif ?>
    </div>
  </div>
  <div class="drawer-overlay" data-drawer-close></div>
  <?= render('footer') ?>
  <?= hook('layout.render.footer') ?>
  <?= theme_assets('foot') ?>          <!-- core.js (+ theme scripts): drawers, mode toggle, chat … -->
</body>
</html>
```

`$layout` = `['mode' => 'none|left|right|both', 'left' => [rendered panel HTML…], 'right' => [...]]` — the mode is already the *effective* one (a column with no visible panels is dropped).

> **Structure vs. look:** `assets/core.css` (always loaded first) provides only structure — grid, drawers, nav mechanics, utilities. Colours, gradients, shadows, typography and animation belong to the theme. A theme without a `parent` must therefore style everything in §4; a theme with `"parent": "magic-red"` inherits it all.

Rules for template overrides: keep the class contract (§4), keep `<?= $content ?>`, never echo unescaped user data, don't remove `hook()` calls or `csrf_field()`.

## 6. Responsive requirements

Every theme **must** remain usable at 360px width.

- Use the `--sidebar-w` and `--gap` tokens in the grid.
- Provided breakpoints (use consistently): `1200px`, `768px`, `480px`.
- Below `768px`, `.col-left` / `.col-right` become drawers (core JS toggles `.is-open`); style `.drawer.is-open`.
- No fixed pixel widths on content; tables wrap in `.table-wrap { overflow-x:auto }`.
- Touch targets ≥ 40px.

## 7. Performance & accessibility rules

- Animate only `transform` and `opacity` (plus `background-position` for gradient shifts). No layout-thrashing animations.
- Wrap decorative animation in `@media (prefers-reduced-motion: no-preference)`.
- Total theme CSS ideally < 40 KB; images as SVG/WebP; no external font/CDN requests unless the admin opts in.
- Contrast ratio ≥ 4.5:1 for body text (check `--c-text` on `--c-surface`).
- Visible `:focus-visible` outlines (use `--glow`).
- Don't remove semantic elements from templates.

## 8. Example — "Magic Red" signature effects

```css
/* Bold gradient title-bar */
.panel-title, .card-title {
  background: var(--g-titlebar);
  background-size: 200% 100%;
  color: var(--c-text-on-primary);
  font-family: var(--font-display);
  text-transform: uppercase;
  letter-spacing: .06em;
  border-radius: var(--radius) var(--radius) 0 0;
  padding: .7rem 1rem;
}
@media (prefers-reduced-motion: no-preference) {
  .panel-title, .card-title { animation: shine 8s linear infinite; }
  @keyframes shine { to { background-position: 200% 0; } }
}

/* Cards with glow hover */
.panel, .card {
  background: var(--g-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  transition: transform var(--t) var(--ease), box-shadow var(--t) var(--ease);
}
.panel:hover, .card:hover { transform: translateY(-2px); box-shadow: var(--shadow), var(--glow); }

/* Abstract background */
body.mcms {
  background:
    radial-gradient(900px 500px at 85% -10%, rgba(225,6,19,.18), transparent 60%),
    radial-gradient(700px 400px at -10% 30%, rgba(122,0,8,.25), transparent 60%),
    var(--c-bg);
  color: var(--c-text);
  font: var(--fs-base)/var(--lh) var(--font-body);
}
```

## 9. Checklist for creating a theme (human or AI)

1. Create `themes/<slug>/`.
2. Write `theme.json` (set `"parent": "magic-red"` unless building from scratch).
3. Write `tokens.css` overriding tokens from §3 (colours first, then radius/fonts) — **dark and `[data-theme="light"]` variants** (§3.1).
4. Optionally add `theme.css` for extra effects using only `var(--…)`.
5. Style the class contract (§4) if not inheriting.
6. Verify: dark + light mode, an admin-changed primary colour (§3.2), nav dropdowns, hero, news cards with and without covers; 360px, 768px, 1280px; layouts `none/left/right/both`; forum page; a form; a table.
7. Verify contrast and reduced-motion (§7).
8. Activate in **Admin → Themes** (or set `theme.active`).

## 10. AI prompt template

Copy, fill the `<…>` parts, and give to any AI model together with this file:

```
You are creating a theme for MagicCMS. Read docs/THEMING.md fully.
Create a theme named "<NAME>" with slug "<slug>".
Mood/style: <e.g. cold cyberpunk, neon blue/purple on near-black, sharp 6px corners>.
Use "parent": "magic-red" and override tokens (§3) first; add theme.css only for
extra effects. Provide dark AND light tokens (§3.1), derive all colours from tokens so admin colour schemes work (§3.2), keep the class contract (§4) incl. navigation, hero and news, be responsive (§6), obey §7.
Output exactly these files, each in its own fenced code block labelled with its path:
themes/<slug>/theme.json
themes/<slug>/tokens.css
themes/<slug>/theme.css
Do not modify any file outside themes/<slug>/. Do not use external CDNs.
```

## 11. Packaging & sharing

Zip the `themes/<slug>/` folder (the folder itself at the zip root). Admin → Themes → *Upload theme* validates the manifest, rejects `.php` files outside `templates/`, and installs it. Only Superadministrators can upload themes that contain PHP templates.

## 12. Troubleshooting

| Symptom | Fix |
|---|---|
| Theme not listed | `theme.json` invalid JSON or `slug` ≠ folder name |
| Styles not updating | Bump `version` in `theme.json` (used for cache-busting) or clear `cache/` |
| Sidebar missing | Layout mode hides it (Admin → Settings → Layout) or the slot has no enabled panels |
| Unstyled widget | Add missing selectors from §4 or inherit via `parent` |
