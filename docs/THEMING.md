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

All tokens are defined on `:root`. **Never hard-code colours in `theme.css`; use `var(--token)`.** Complete list:

```css
:root {
  /* Brand */
  --c-primary:        #e10613;   /* main accent */
  --c-primary-hi:     #ff4d4d;   /* highlight / hover */
  --c-primary-lo:     #7a0008;   /* deep shade */
  --c-accent:         #ff7a59;   /* secondary accent */

  /* Surfaces (dark → light layering) */
  --c-bg:             #0b0b0d;   /* page background */
  --c-bg-2:           #121216;   /* header/footer */
  --c-surface:        #17171c;   /* cards, panels */
  --c-surface-2:      #1f1f26;   /* inputs, hover rows */
  --c-border:         #2a2a33;

  /* Text */
  --c-text:           #f4f4f6;
  --c-text-dim:       #a0a0aa;
  --c-text-on-primary:#ffffff;

  /* Status */
  --c-success:        #2ecc71;
  --c-warning:        #f5a623;
  --c-danger:         #ff3b3b;
  --c-info:           #3aa0ff;

  /* Gradients */
  --g-primary:        linear-gradient(135deg, var(--c-primary-hi), var(--c-primary) 55%, var(--c-primary-lo));
  --g-titlebar:       linear-gradient(90deg, var(--c-primary-lo), var(--c-primary) 60%, var(--c-primary-hi));
  --g-surface:        linear-gradient(180deg, var(--c-surface), var(--c-bg-2));

  /* Shape */
  --radius-sm:        8px;
  --radius:           16px;
  --radius-lg:        24px;

  /* Elevation & glow */
  --shadow:           0 8px 24px rgba(0,0,0,.45);
  --glow:             0 0 0 1px var(--c-primary), 0 0 24px rgba(225,6,19,.45);

  /* Type */
  --font-body:        system-ui, "Segoe UI", Inter, Roboto, sans-serif;
  --font-display:     "Segoe UI Black", "Arial Black", Impact, system-ui, sans-serif;
  --fs-base:          16px;
  --fs-title:         1.15rem;
  --lh:               1.55;

  /* Layout */
  --container-w:      1280px;
  --sidebar-w:        280px;
  --gap:              20px;
  --header-h:         64px;

  /* Motion */
  --ease:             cubic-bezier(.2,.8,.2,1);
  --t-fast:           .15s;
  --t:                .3s;
}
```

Changing only these values re-skins the entire CMS.

## 4. Required CSS class contract

MagicCMS core markup uses stable, BEM-ish class names. A theme **should style at least these** (anything unstyled falls back to the parent/default theme):

### Layout
| Selector | Element |
|---|---|
| `body.mcms` | `<body>`; also has `layout-none\|left\|right\|both` and `page-<module>` classes |
| `.site-header`, `.site-nav`, `.site-footer` | Chrome |
| `.site-grid` | CSS grid wrapper: `[left] [main] [right]` |
| `.col-left`, `.col-main`, `.col-right` | Columns (absent when the layout mode hides them) |
| `.drawer-toggle`, `.drawer` | Mobile sidebar controls |

### Panels
```html
<section class="panel" data-panel="online_users">
  <header class="panel-title"><h3>Online Users</h3><button class="panel-toggle"></button></header>
  <div class="panel-body"> …panel output… </div>
</section>
```
Selectors: `.panel`, `.panel-title`, `.panel-body`, `.panel-toggle`, `.panel.is-collapsed`

### Content blocks
`.card`, `.card-title` (bold title-bar), `.btn`, `.btn-primary`, `.btn-ghost`, `.btn-danger`, `.badge`, `.alert.alert-{success|warning|danger|info}`, `.table`, `.pagination`, `.breadcrumb`, `.tabs`, `.avatar` (+ `.avatar-sm|md|lg`), `.role-{superadmin|admin|mod|member}`

### Forms
`.form-row`, `.input`, `.select`, `.textarea`, `.checkbox`, `.label`, `.help`, `.error`

### Forum
`.forum-category`, `.forum-board`, `.forum-topic-row`, `.post`, `.post-author`, `.post-body`, `.post-meta`, `.post-signature`, `.topic-sticky`, `.topic-locked`, `.unread`

### Matches / esports
`.match-row`, `.match-win`, `.match-loss`, `.match-draw`, `.team-card`, `.countdown`

### Chat
`.chat`, `.chat-log`, `.chat-msg`, `.chat-input`

## 5. Template overrides (optional)

Place a file with the same name in `themes/<slug>/templates/` to replace the core one. Templates are plain PHP. **Always escape output with `e()`**.

| Template | Purpose | Variables |
|---|---|---|
| `layout.php` | Whole page shell | `$title`, `$content`, `$layoutMode`, `$panels` (`['left'=>[…],'right'=>[…]]`), `$user`, `$meta` |
| `header.php` / `footer.php` | Chrome | `$user`, `$menu`, `$siteName` |
| `panel.php` | Panel wrapper | `$panel` (`key,title,collapsed`), `$html` |
| `pagination.php`, `breadcrumb.php`, `alert.php` | Components | component-specific |

Skeleton of `layout.php`:

```php
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · <?= e(setting('site.name')) ?></title>
  <?= theme_assets('head') ?>        <!-- outputs <link>s from theme.json -->
  <?= hook('layout.render.head') ?>
</head>
<body class="mcms layout-<?= e($layoutMode) ?> page-<?= e($page) ?>">
  <?= render('header') ?>
  <div class="site-grid">
    <?php if (!empty($panels['left'])):  ?><aside class="col-left"><?= render_panels($panels['left']) ?></aside><?php endif ?>
    <main class="col-main"><?= $content ?></main>
    <?php if (!empty($panels['right'])): ?><aside class="col-right"><?= render_panels($panels['right']) ?></aside><?php endif ?>
  </div>
  <?= render('footer') ?>
  <?= theme_assets('foot') ?>
</body>
</html>
```

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
3. Write `tokens.css` overriding tokens from §3 (colours first, then radius/fonts).
4. Optionally add `theme.css` for extra effects using only `var(--…)`.
5. Style the class contract (§4) if not inheriting.
6. Verify: 360px, 768px, 1280px; layouts `none/left/right/both`; forum page; a form; a table.
7. Verify contrast and reduced-motion (§7).
8. Activate in **Admin → Themes** (or set `theme.active`).

## 10. AI prompt template

Copy, fill the `<…>` parts, and give to any AI model together with this file:

```
You are creating a theme for MagicCMS. Read docs/THEMING.md fully.
Create a theme named "<NAME>" with slug "<slug>".
Mood/style: <e.g. cold cyberpunk, neon blue/purple on near-black, sharp 6px corners>.
Use "parent": "magic-red" and override tokens (§3) first; add theme.css only for
extra effects. Keep the class contract (§4), be responsive (§6), obey §7.
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
