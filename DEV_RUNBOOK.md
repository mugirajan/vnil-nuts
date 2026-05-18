# Vnil Nuts — Developer Runbook

**Date:** 2026-05-18
**Audience:** The developer who has to do the work in [GAP_ANALYSIS.md](./GAP_ANALYSIS.md) and [DESIGN_ANALYSIS.md](./DESIGN_ANALYSIS.md).
**Scope:** Step-by-step recipes for the design-polish and imagery line items. Content items are covered separately in [CONTENT_BRIEF.md](./CONTENT_BRIEF.md).

> Conventions used in this doc:
> - `path/to/file.ext:NN` = file at line NN.
> - Code blocks are copy-pasteable from a PowerShell prompt on Windows unless noted.
> - "Verify" steps tell you how to prove you didn't break anything.

---

## Table of contents

1. [Rename `sec_ptb_100` utility classes (and fix the padding)](#1-rename-sec_ptb_100-utility-classes-and-fix-the-padding)
2. [Replace Font Awesome with Phosphor / Lucide / Tabler](#2-replace-font-awesome-with-phosphor--lucide--tabler)
3. [Replace Slick carousel with CSS-snap or Embla](#3-replace-slick-carousel-with-css-snap-or-embla)
4. [Image folder cull (destructive)](#4-image-folder-cull-destructive)
5. [WebP / AVIF format pick + PNG conversion](#5-webp--avif-format-pick--png-conversion)
6. [`srcset` for hero images (needs image variants)](#6-srcset-for-hero-images-needs-image-variants)

---

## 1. Rename `sec_ptb_100` utility classes (and fix the padding)

**Why this exists.** Today the class names lie. `assets/css/style.css:151-169` shows:

```css
.sec_ptb_100, .sec_space_small, .sec_space_xxs_50 {
  padding-top: 2em !important;
  padding-bottom: 2em !important;
}
.sec_top_space_50 { padding-top: 4em !important; }
.sec_bottom_space_70 { padding-bottom: 4em !important; }
```

The name `sec_ptb_100` reads "100px padding". Actual value is 2em (~32px). The original definition in `assets/scss/elements/_space.scss:152` is `padding: 100px 0;` — but it's overridden later. Per [DESIGN_ANALYSIS.md §4](./DESIGN_ANALYSIS.md#4-layout-spacing--rhythm) we also want to **bump padding** from 2em → 5em desktop, 3em → 4em mobile.

### 1a. Decide the new naming scheme

Use a numeric spacing scale tied to actual values (CSS custom properties), not arbitrary suffixes:

| Token | Value desktop | Value mobile | Replaces |
|---|---|---|---|
| `--space-section-sm` | 3em | 2em | (new) |
| `--space-section-md` | 5em | 4em | `sec_ptb_100`, `sec_space_small`, `sec_space_xxs_50`, `cta_wrap` |
| `--space-section-lg` | 8em | 5em | `sec_space_xs_70`, `sec_space_mid_small` |
| `--space-section-xl` | 12em | 6em | `sec_space_xlarge`, `sec_space_large` |

New utility class names:

| Old class | New class |
|---|---|
| `sec_ptb_100` | `section-pad-md` |
| `sec_space_small` | `section-pad-md` |
| `sec_space_xxs_50` | `section-pad-sm` |
| `sec_top_space_50` | `section-pad-top-md` |
| `sec_bottom_space_70` | `section-pad-bottom-md` |
| `cta_wrap` | keep name (semantic), but use new tokens internally |

> Pick whatever naming you prefer — what matters is that *the name matches the value*. The mapping above is a recommendation; if the team prefers `py-section-md` (Tailwind-style) or `section--lg` (BEM), use that instead. **Be consistent across CSS + HTML.**

### 1b. Update the CSS source

Edit `assets/css/style.css:151-169` and replace the block with token-driven utilities:

```css
:root {
  --space-section-sm: 3em;
  --space-section-md: 5em;
  --space-section-lg: 8em;
}
@media (max-width: 767px) {
  :root {
    --space-section-sm: 2em;
    --space-section-md: 4em;
    --space-section-lg: 5em;
  }
}

.section-pad-sm { padding-top: var(--space-section-sm) !important; padding-bottom: var(--space-section-sm) !important; }
.section-pad-md { padding-top: var(--space-section-md) !important; padding-bottom: var(--space-section-md) !important; }
.section-pad-lg { padding-top: var(--space-section-lg) !important; padding-bottom: var(--space-section-lg) !important; }
.section-pad-top-md    { padding-top: var(--space-section-md) !important; }
.section-pad-bottom-md { padding-bottom: var(--space-section-md) !important; }
.cta_wrap { padding-top: var(--space-section-md) !important; padding-bottom: var(--space-section-md) !important; }
```

> **Drop the `!important`** if nothing else needs to override it. Right now `!important` is being used to win against the SCSS source — fix the SCSS instead (step 1d) and the `!important` becomes unnecessary.

### 1c. Update the SCSS source (the real source of truth)

`assets/scss/elements/_space.scss:149-156` defines the originals. Either:

- **Option A (preferred):** delete the old `sec_ptb_*` rules from `_space.scss` and add the new ones using the same tokens. Recompile (`prepros.config` is in the repo — Prepros watches and rebuilds `style-dist.css`).
- **Option B (lazy):** leave SCSS alone, only edit `style.css` and `style-dist.css`. Will drift the next time someone touches SCSS. **Don't do this** unless the SCSS pipeline is dead.

Check `prepros.config` to confirm which CSS file the HTML actually loads:

```powershell
Select-String -Path "*.html" -Pattern "style\.css|style-dist\.css" | Select-Object -First 5
```

### 1d. Search/replace across HTML

Files touched: `index.html`, `about-us.html`, `products.html`, `contact-us.html`, `blog-list.html`, `blog-grid.html`, `blog-details.html`, `privacy-policy.html`, `terms-and-conditions.html`.

PowerShell one-liner (run from repo root). Test on a single file first:

```powershell
$replacements = @{
  'sec_ptb_100'        = 'section-pad-md'
  'sec_space_small'    = 'section-pad-md'
  'sec_space_xxs_50'   = 'section-pad-sm'
  'sec_top_space_50'   = 'section-pad-top-md'
  'sec_bottom_space_70'= 'section-pad-bottom-md'
}
Get-ChildItem -Path . -Filter *.html | ForEach-Object {
  $content = Get-Content $_.FullName -Raw
  foreach ($k in $replacements.Keys) { $content = $content -replace [regex]::Escape($k), $replacements[$k] }
  Set-Content -Path $_.FullName -Value $content -Encoding utf8
}
```

Bash version (if dev prefers WSL/Git Bash):

```bash
for f in *.html; do
  sed -i \
    -e 's/sec_ptb_100/section-pad-md/g' \
    -e 's/sec_space_small/section-pad-md/g' \
    -e 's/sec_space_xxs_50/section-pad-sm/g' \
    -e 's/sec_top_space_50/section-pad-top-md/g' \
    -e 's/sec_bottom_space_70/section-pad-bottom-md/g' \
    "$f"
done
```

### 1e. Verify

```powershell
# Should return 0 matches if the rename is complete:
Select-String -Path "*.html","assets/css/*.css","assets/scss/**/*.scss" -Pattern "sec_ptb_100|sec_space_small|sec_space_xxs_50|sec_top_space_50|sec_bottom_space_70"
```

Then open `index.html`, `about-us.html`, `products.html` in a browser and visually check that sections still have breathing room (they should now have *more* — that's intentional).

**Gotcha:** The SCSS file has many other `sec_*` classes (`sec_space_xlarge`, `sec_inner_top_80`, etc.) that you may also want to rename for consistency. Out of scope for this ticket unless you want to do the whole sweep — flag for a follow-up.

---

## 2. Replace Font Awesome with Phosphor / Lucide / Tabler

**Why this exists.** Font Awesome is fine functionally but reads "Bootstrap template" — see [DESIGN_ANALYSIS.md §6](./DESIGN_ANALYSIS.md#6-components-cards-buttons-icons). Currently loaded from CDN in every HTML page (e.g. `index.html:11-12`), used **788 times** across 9 files.

### 2a. Pick the library

| Lib | Format | Bundle size | Why pick it | Why not |
|---|---|---|---|---|
| **Phosphor** | Web font + SVG + React/Vue | ~80 KB woff2 (subset) | Has 6 weights (thin/light/regular/bold/fill/duotone); covers ~all icons we use; CDN-able like FA | Webfont still means render-block |
| **Lucide** | SVG (inline or `<img>`) | Per-icon (~1 KB each) | Cleanest aesthetic; modern; tree-shakable | Means inlining SVG or per-icon `<img>` — bigger HTML changes |
| **Tabler** | SVG + webfont | ~120 KB webfont | Largest set (4500+ icons), webfont mode = drop-in | Visually less distinct than Phosphor |

**Recommendation: Phosphor webfont.** It's the closest drop-in replacement for the Font Awesome workflow you already have. You change the CDN link, run a class-name find/replace, done.

If the team wants the cleanest result, use **Lucide as inline SVG** — but plan for 2–3× more work (you replace `<i class="...">` tags with `<svg>` snippets).

### 2b. Swap the CDN tag

Files that currently load FA (line numbers approximate):

| File | Line | Current |
|---|---|---|
| `index.html` | 12 | `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css` |
| `about-us.html` | 12 | same |
| `contact-us.html` | 13 | same |
| `products.html` | 12 | same |
| `privacy-policy.html` | 12 | same |
| `terms-and-conditions.html` | 12 | same |
| `blog-grid.html` | 13 | **6.0.0-beta3** ← inconsistent version |
| `blog-list.html` | 13 | **6.0.0-beta3** ← inconsistent version |
| `blog-details.html` | 12 | check |

Replace each with Phosphor webfont:

```html
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" />
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css" />
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css" />
```

(You don't need all three weights — start with regular + fill for filled/outlined parity with FA's `fas`/`far` distinction. Drop the others to save bytes.)

### 2c. Map the icon classes

The actual icons used (compiled from a full repo scan):

| Font Awesome | Phosphor regular | Phosphor fill (if used filled) |
|---|---|---|
| `fas fa-arrow-up` | `ph ph-arrow-up` | — |
| `fas fa-arrow-left` | `ph ph-arrow-left` | — |
| `fas fa-arrow-right` | `ph ph-arrow-right` | — |
| `fas fa-long-arrow-alt-right` | `ph ph-arrow-right` | — |
| `fas fa-chevron-right` | `ph ph-caret-right` | — |
| `fas fa-home` | `ph ph-house` | — |
| `fas fa-bars` | `ph ph-list` | — |
| `fas fa-times` | `ph ph-x` | — |
| `fas fa-search` | `ph ph-magnifying-glass` | — |
| `fas fa-user` | `ph ph-user` | `ph-fill ph-user` |
| `fas fa-user-circle` | `ph ph-user-circle` | — |
| `fas fa-user-cog` | `ph ph-user-gear` | — |
| `fas fa-user-check` | `ph ph-user-check` | — |
| `fas fa-user-shield` | `ph ph-shield-check` (approx) | — |
| `fas fa-sign-out-alt` | `ph ph-sign-out` | — |
| `fas fa-heart` | `ph ph-heart` | `ph-fill ph-heart` |
| `fas fa-shopping-bag` | `ph ph-shopping-bag` | — |
| `fas fa-shopping-cart` | `ph ph-shopping-cart` | — |
| `fas fa-star` | `ph-fill ph-star` | `ph-fill ph-star` |
| `fas fa-circle` (decorative dot) | `ph-fill ph-circle` | `ph-fill ph-circle` |
| `fas fa-clock` | `ph ph-clock` | — |
| `fas fa-calendar-alt` | `ph ph-calendar` | — |
| `fas fa-envelope` | `ph ph-envelope` | — |
| `fas fa-envelope-open-text` | `ph ph-envelope-open` | — |
| `fas fa-phone` | `ph ph-phone` | — |
| `fas fa-phone-volume` | `ph ph-phone-call` | — |
| `fas fa-map-marker-alt` | `ph ph-map-pin` | — |
| `fas fa-globe` | `ph ph-globe` | — |
| `fas fa-globe-asia` | `ph ph-globe-hemisphere-east` | — |
| `fas fa-link` | `ph ph-link` | — |
| `fas fa-external-link-alt` | `ph ph-arrow-square-out` | — |
| `fas fa-info-circle` | `ph ph-info` | — |
| `fas fa-exclamation-triangle` | `ph ph-warning` | — |
| `fas fa-check` | `ph ph-check` | — |
| `fas fa-shield-alt` | `ph ph-shield` | — |
| `fas fa-cogs` | `ph ph-gear-six` | — |
| `fas fa-sync-alt` | `ph ph-arrows-clockwise` | — |
| `fas fa-undo-alt` | `ph ph-arrow-counter-clockwise` | — |
| `fas fa-handshake` | `ph ph-handshake` | — |
| `fas fa-hand-holding-heart` | `ph ph-hand-heart` | — |
| `fas fa-leaf` | `ph ph-leaf` | — |
| `fas fa-seedling` | `ph ph-plant` | — |
| `fas fa-flask` | `ph ph-flask` | — |
| `fas fa-award` | `ph ph-medal` | — |
| `fas fa-gift` | `ph ph-gift` | — |
| `fas fa-truck` | `ph ph-truck` | — |
| `fas fa-credit-card` | `ph ph-credit-card` | — |
| `fas fa-box-open` | `ph ph-package` | — |
| `fas fa-cookie-bite` | `ph ph-cookie` | — |
| `fas fa-database` | `ph ph-database` | — |
| `fas fa-child` | `ph ph-baby` | — |
| `fas fa-quote-left` | `ph-fill ph-quotes` | — |
| `fab fa-facebook-f` | `ph ph-facebook-logo` | — |
| `fab fa-facebook-square` | `ph-fill ph-facebook-logo` | — |
| `fab fa-instagram` | `ph ph-instagram-logo` | — |
| `fab fa-x-twitter` | `ph ph-x-logo` | — |
| `fab fa-twitter` | `ph ph-x-logo` | — |
| `fab fa-linkedin-in` | `ph ph-linkedin-logo` | — |
| `fab fa-linkedin` | `ph-fill ph-linkedin-logo` | — |
| `fab fa-youtube` | `ph ph-youtube-logo` | — |
| `fab fa-pinterest-p` | `ph ph-pinterest-logo` | — |
| `fab fa-google-plus-g` | **remove** (Google+ is dead) | — |

> Verify every Phosphor name at https://phosphoricons.com before shipping. A few above are best-guess substitutions for FA icons that don't have a 1:1 match.

### 2d. Run the find/replace

You **cannot do a naive global sed** because FA classes use multi-word names (`fa-x-twitter` overlaps with `fa-twitter` — order matters; longer-first). Use a Node or PowerShell script that applies replacements longest-first.

`scripts/swap-icons.ps1`:

```powershell
$map = [ordered]@{
  # Multi-word classes FIRST (longest match wins)
  'fab fa-google-plus-g'        = ''  # delete (Google+ is dead)
  'fab fa-facebook-square'      = 'ph-fill ph-facebook-logo'
  'fab fa-linkedin-in'          = 'ph ph-linkedin-logo'
  'fas fa-long-arrow-alt-right' = 'ph ph-arrow-right'
  'fas fa-map-marker-alt'       = 'ph ph-map-pin'
  'fas fa-envelope-open-text'   = 'ph ph-envelope-open'
  'fas fa-external-link-alt'    = 'ph ph-arrow-square-out'
  'fas fa-exclamation-triangle' = 'ph ph-warning'
  'fas fa-sign-out-alt'         = 'ph ph-sign-out'
  'fas fa-hand-holding-heart'   = 'ph ph-hand-heart'
  'fas fa-phone-volume'         = 'ph ph-phone-call'
  'fas fa-globe-asia'           = 'ph ph-globe-hemisphere-east'
  'fas fa-shield-alt'           = 'ph ph-shield'
  'fas fa-info-circle'          = 'ph ph-info'
  'fas fa-undo-alt'             = 'ph ph-arrow-counter-clockwise'
  'fas fa-sync-alt'             = 'ph ph-arrows-clockwise'
  'fas fa-calendar-alt'         = 'ph ph-calendar'
  'fas fa-user-circle'          = 'ph ph-user-circle'
  'fas fa-user-shield'          = 'ph ph-shield-check'
  'fas fa-user-check'           = 'ph ph-user-check'
  'fas fa-user-cog'             = 'ph ph-user-gear'
  'fas fa-shopping-bag'         = 'ph ph-shopping-bag'
  'fas fa-shopping-cart'        = 'ph ph-shopping-cart'
  'fas fa-credit-card'          = 'ph ph-credit-card'
  'fas fa-box-open'             = 'ph ph-package'
  'fas fa-cookie-bite'          = 'ph ph-cookie'
  'fas fa-chevron-right'        = 'ph ph-caret-right'
  'fas fa-arrow-right'          = 'ph ph-arrow-right'
  'fas fa-arrow-left'           = 'ph ph-arrow-left'
  'fas fa-arrow-up'             = 'ph ph-arrow-up'
  'fas fa-quote-left'           = 'ph-fill ph-quotes'
  'fas fa-gear-six'             = 'ph ph-gear-six'
  'fas fa-cogs'                 = 'ph ph-gear-six'
  'fab fa-facebook-f'           = 'ph ph-facebook-logo'
  'fab fa-instagram'            = 'ph ph-instagram-logo'
  'fab fa-x-twitter'            = 'ph ph-x-logo'
  'fab fa-twitter'              = 'ph ph-x-logo'
  'fab fa-linkedin'             = 'ph-fill ph-linkedin-logo'
  'fab fa-youtube'              = 'ph ph-youtube-logo'
  'fab fa-pinterest-p'          = 'ph ph-pinterest-logo'
  # Single-word classes
  'fas fa-bars'                 = 'ph ph-list'
  'fas fa-times'                = 'ph ph-x'
  'fas fa-search'               = 'ph ph-magnifying-glass'
  'fas fa-home'                 = 'ph ph-house'
  'fas fa-heart'                = 'ph ph-heart'
  'fas fa-user'                 = 'ph ph-user'
  'fas fa-star'                 = 'ph-fill ph-star'
  'fas fa-circle'               = 'ph-fill ph-circle'
  'fas fa-check'                = 'ph ph-check'
  'fas fa-clock'                = 'ph ph-clock'
  'fas fa-envelope'             = 'ph ph-envelope'
  'fas fa-phone'                = 'ph ph-phone'
  'fas fa-globe'                = 'ph ph-globe'
  'fas fa-link'                 = 'ph ph-link'
  'fas fa-handshake'            = 'ph ph-handshake'
  'fas fa-leaf'                 = 'ph ph-leaf'
  'fas fa-seedling'             = 'ph ph-plant'
  'fas fa-flask'                = 'ph ph-flask'
  'fas fa-award'                = 'ph ph-medal'
  'fas fa-gift'                 = 'ph ph-gift'
  'fas fa-truck'                = 'ph ph-truck'
  'fas fa-database'             = 'ph ph-database'
  'fas fa-child'                = 'ph ph-baby'
}

Get-ChildItem -Path . -Filter *.html | ForEach-Object {
  $c = Get-Content $_.FullName -Raw
  foreach ($k in $map.Keys) { $c = $c.Replace($k, $map[$k]) }
  # Clean up double spaces / stray empty class chunks from the Google+ removal
  $c = $c -replace '\s+"', '"' -replace '"\s+', '"'
  $c = $c -replace 'class=""', ''
  Set-Content -Path $_.FullName -Value $c -Encoding utf8
}
```

### 2e. Verify

```powershell
# All FA classes should be gone:
Select-String -Path "*.html" -Pattern "\bfa[srlbd]?\s+fa-"
# All Phosphor classes should be present:
Select-String -Path "*.html" -Pattern "\bph(-fill)?\s+ph-" | Measure-Object | Select-Object Count
```

Then open every page in a browser. Phosphor renders icons that don't exist as a small box — those need a manual fix.

### 2f. Aria-label sweep (bonus, since you're touching every icon)

[DESIGN_ANALYSIS.md §8](./DESIGN_ANALYSIS.md#8-accessibility--mobile-concerns) flags missing `aria-label` on Font Awesome icons. While you're in the file, add labels to social and action icons:

```html
<!-- before -->
<a href="..."><i class="ph ph-instagram-logo"></i></a>
<!-- after -->
<a href="..." aria-label="Instagram"><i class="ph ph-instagram-logo" aria-hidden="true"></i></a>
```

---

## 3. Replace Slick carousel with CSS-snap or Embla

**Why this exists.** Slick brings 150+ KB (jQuery + slick.js + slick.css + slick-theme.css) for what is mostly auto-rotating testimonials and a hero. [DESIGN_ANALYSIS.md §7](./DESIGN_ANALYSIS.md#7-microinteractions--motion). There are **24+ `.slick({...})` invocations** in `assets/js/custom.js` (lines 210, 297, 356, 391, 437, 471, 507, 559, 609, 659, 695, 722, 729, 768, 776, 810, 818, 849, 857).

> **Half of those slick instances are probably dead.** This is a template. Many `slideshow1`…`slideshow9` selectors don't appear in *any* HTML. Verify before you bother re-implementing them.

### 3a. Audit which slick instances are actually used

Run this and triage:

```powershell
$selectors = @(
  'main_slider','slideshow1_slider','slideshow2_slider','slideshow3_slider',
  'slideshow4_slider','slideshow5_slider','slideshow6_slider','slideshow7_slider',
  'slideshow8_slider','slideshow9_slider','center','slider-for','slider-nav',
  'product7_slide_items','product7_slide_thumb','testimonial3_slider_items',
  'testimonial3_slider_thmb','product11_slide_item','product11_slide_thumb',
  'slideshow3_slider'
)
foreach ($s in $selectors) {
  $count = (Select-String -Path "*.html" -Pattern $s -AllMatches | Measure-Object).Count
  "{0,-30} {1}" -f $s, $count
}
```

Anything with `0` count → delete that block from `custom.js`. No replacement needed.

### 3b. Pick a replacement strategy per slider

Per surviving instance, pick one:

| Use case | Use this | Why |
|---|---|---|
| Hero/banner with autoplay + arrows + dots | **Embla** | Lightweight (4 KB gzip), modern, no jQuery |
| Testimonial fader (1 slide visible, fades, auto) | **CSS animation** | No JS needed for a 3-quote rotator |
| Horizontal product strip ("Featured", category rail) | **CSS scroll-snap** | Native, GPU-smooth, free; nav arrows are optional sugar |
| Product image gallery (main + thumbnails) | **Embla** with two instances synced | The one place a real library is justified |

### 3c. Recipe — CSS scroll-snap for product strips

Replaces simple horizontal-scroll Slick instances (`.center`, `slideshow1_slider` for category, etc.).

**HTML** (was `<div class="slick-track">…</div>`):

```html
<ul class="snap-rail">
  <li class="snap-item">…card…</li>
  <li class="snap-item">…card…</li>
  …
</ul>
```

**CSS:**

```css
.snap-rail {
  display: flex;
  gap: 1.5rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-padding-inline: 1rem;
  scrollbar-width: none;            /* Firefox */
  -webkit-overflow-scrolling: touch;
  padding-inline: 1rem;
  list-style: none;
}
.snap-rail::-webkit-scrollbar { display: none; }
.snap-item {
  flex: 0 0 calc(25% - 1.5rem);     /* 4 visible on desktop */
  scroll-snap-align: start;
}
@media (max-width: 1024px) { .snap-item { flex-basis: calc(33% - 1.5rem); } }
@media (max-width: 768px)  { .snap-item { flex-basis: calc(50% - 1.5rem); } }
@media (max-width: 480px)  { .snap-item { flex-basis: calc(85% - 1.5rem); } }
```

That's it. No JS. Swipe works natively on touch devices.

### 3d. Recipe — Embla for the hero (`.main_slider`)

Install via CDN (no build step needed since the site is plain HTML):

```html
<script src="https://unpkg.com/embla-carousel/embla-carousel.umd.js" defer></script>
<script src="https://unpkg.com/embla-carousel-autoplay/embla-carousel-autoplay.umd.js" defer></script>
```

**HTML:**

```html
<section class="embla">
  <div class="embla__viewport">
    <div class="embla__container">
      <div class="embla__slide"><!-- slide 1 markup --></div>
      <div class="embla__slide"><!-- slide 2 markup --></div>
      <div class="embla__slide"><!-- slide 3 markup --></div>
    </div>
  </div>
  <button class="embla__prev" aria-label="Previous slide"><i class="ph ph-arrow-left"></i></button>
  <button class="embla__next" aria-label="Next slide"><i class="ph ph-arrow-right"></i></button>
  <ul class="embla__dots" aria-label="Slide indicators"></ul>
</section>
```

**CSS:**

```css
.embla { position: relative; }
.embla__viewport { overflow: hidden; }
.embla__container { display: flex; }
.embla__slide { flex: 0 0 100%; min-width: 0; }
.embla__prev, .embla__next {
  position: absolute; top: 50%; transform: translateY(-50%);
  background: var(--vnil-deep-black); color: var(--vnil-white);
  border: 0; width: 44px; height: 44px; border-radius: 50%;
  display: grid; place-items: center; cursor: pointer;
}
.embla__prev { left: 1rem; } .embla__next { right: 1rem; }
.embla__dots { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem; }
.embla__dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(0,0,0,0.2); border: 0; padding: 0; cursor: pointer; }
.embla__dot--selected { background: var(--vnil-rose-gold); }
```

**JS** (replaces the slick block in `custom.js:210-294`):

```js
document.addEventListener('DOMContentLoaded', () => {
  const node = document.querySelector('.embla');
  if (!node) return;
  const viewport = node.querySelector('.embla__viewport');
  const autoplay = EmblaCarouselAutoplay({ delay: 5000, stopOnInteraction: false });
  const embla = EmblaCarousel(viewport, { loop: true, duration: 30 }, [autoplay]);

  node.querySelector('.embla__prev').addEventListener('click', () => embla.scrollPrev());
  node.querySelector('.embla__next').addEventListener('click', () => embla.scrollNext());

  const dotsNode = node.querySelector('.embla__dots');
  const buildDots = () => {
    dotsNode.innerHTML = '';
    embla.scrollSnapList().forEach((_, i) => {
      const li = document.createElement('li');
      const btn = document.createElement('button');
      btn.className = 'embla__dot';
      btn.setAttribute('aria-label', `Slide ${i+1}`);
      btn.addEventListener('click', () => embla.scrollTo(i));
      li.appendChild(btn); dotsNode.appendChild(li);
    });
  };
  const syncDots = () => {
    const sel = embla.selectedScrollSnap();
    dotsNode.querySelectorAll('.embla__dot').forEach((d, i) =>
      d.classList.toggle('embla__dot--selected', i === sel));
  };
  embla.on('init', () => { buildDots(); syncDots(); });
  embla.on('select', syncDots);
  embla.on('reInit', () => { buildDots(); syncDots(); });
});
```

### 3e. Recipe — CSS-only testimonial fader

Replaces the testimonial Slick (`.slideshow3_slider`) which only shows 1 quote and fades. Per [DESIGN_ANALYSIS.md §4](./DESIGN_ANALYSIS.md#4-layout-spacing--rhythm), the homepage has **three duplicate testimonial blocks** (`index.html:2035, 2056, 2077`) — kill two of them while you're at it.

```html
<div class="testimonial-fader">
  <blockquote>“Quote 1” — Customer 1</blockquote>
  <blockquote>“Quote 2” — Customer 2</blockquote>
  <blockquote>“Quote 3” — Customer 3</blockquote>
</div>
```

```css
.testimonial-fader { position: relative; min-height: 200px; }
.testimonial-fader blockquote {
  position: absolute; inset: 0;
  opacity: 0;
  animation: testimonial-rotate 15s infinite;
}
.testimonial-fader blockquote:nth-child(1) { animation-delay: 0s; }
.testimonial-fader blockquote:nth-child(2) { animation-delay: 5s; }
.testimonial-fader blockquote:nth-child(3) { animation-delay: 10s; }
@keyframes testimonial-rotate {
  0%,30%  { opacity: 1; }
  33%,100%{ opacity: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .testimonial-fader blockquote { animation: none; opacity: 1; position: static; }
}
```

Total cost: 0 KB JS.

### 3f. Cleanup

Once every slick caller is migrated:

1. Delete the `<link>` to `slick.css` and `slick-theme.css` from all HTML.
2. Delete the `<script>` to `slick.min.js`.
3. Delete the slick blocks from `custom.js`.
4. Delete `assets/css/slick.css`, `assets/css/slick-theme.css`, `assets/js/slick.min.js`.
5. (Optional) drop jQuery if nothing else needs it. Check: `Select-String -Path "*.html","assets/js/*.js" -Pattern "jquery|\$\("`

---

## 4. Image folder cull (destructive)

**Why this exists.** `assets/images/` has 36 subfolders, most likely template residue. [DESIGN_ANALYSIS.md §5](./DESIGN_ANALYSIS.md#5-imagery--photography) lists the cleanup target: 6 themed folders (`hero`, `product`, `lifestyle`, `process`, `team`, `og`).

> ⚠️ **Destructive.** Commit clean, branch off, and let the user review the deletion PR.

### 4a. Find which folders are actually referenced

PowerShell:

```powershell
$folders = Get-ChildItem -Path "assets/images" -Directory | Select-Object -ExpandProperty Name
foreach ($f in $folders) {
  $hits = (Select-String -Path "*.html","assets/css/*.css","assets/scss/**/*.scss","assets/js/*.js" -Pattern "images/$f/" -AllMatches | Measure-Object).Count
  "{0,5}  {1}" -f $hits, $f
}
```

Anything with `0` is a deletion candidate. Expected suspects from a template-cleanup standpoint: `cart`, `compare`, `deals`, `funfact`, `gallery`, `grid`, `mega`, `offer`, `sales`, `services`, `store`, `trends`, `vendor` — but **verify** before deleting.

### 4b. Find unreferenced individual files inside referenced folders

The `product/` folder has 8 AVIFs (real product packets) + 52 template PNGs (`product1.png`…`product56.png`). Likely most are unused.

```powershell
$files = Get-ChildItem -Path "assets/images" -Recurse -File
foreach ($f in $files) {
  $rel = $f.FullName.Substring((Get-Location).Path.Length + 1).Replace('\','/')
  $name = $f.Name
  # Search by basename to catch path-relative refs too
  $hits = (Select-String -Path "*.html","assets/css/*.css","assets/scss/**/*.scss","assets/js/*.js" -Pattern ([regex]::Escape($name)) -AllMatches | Measure-Object).Count
  if ($hits -eq 0) { "DEAD  $rel" }
}
```

Pipe the output to a file, eyeball it, then delete in a separate commit so it's easy to revert:

```powershell
# After review:
Get-Content dead-images.txt | ForEach-Object { Remove-Item $_.Replace('DEAD  ','') }
```

### 4c. Specific known bugs

- `assets/images/hero/apple.png` and `mango.png` — **wrong for a nuts brand**, template residue. Delete after the new hero shots arrive (see [CONTENT_BRIEF.md](./CONTENT_BRIEF.md) §photography).
- `assets/images/banner/banner1.png`…`banner9.png` — generic template banners. Audit, rename the ones you keep to descriptive names (`banner-hero-cashew-flat.jpg` etc.), delete the rest.
- `assets/images/product/product1.png`…`product56.png` — likely all unused now that branded AVIFs exist. Confirm with the script above, then delete.

### 4d. Target folder structure after cull

```
assets/images/
  hero/         # 3-5 banner-quality shots, branded
  product/      # one AVIF + WebP fallback per SKU + lifestyle context
  lifestyle/    # in-context usage (gift box, breakfast, snack)
  process/      # facility, packing, sourcing
  team/         # founder + team headshots
  og/           # social share images (1200×630)
  brand/        # logo variants (svg + png), favicon set
```

Delete everything else after verifying it's unreferenced.

---

## 5. WebP / AVIF format pick + PNG conversion

**Why this exists.** Product packets are already AVIF (good). Banners, hero, blog, team are still PNG. Page weight is unnecessarily high.

### 5a. Pick one format

**Recommendation: AVIF as primary, WebP as fallback, JPG as last-resort.**

- AVIF: smallest files (~50% of WebP), supported in all modern browsers since 2023.
- WebP: ~3% of users on iOS 13 or older still need this — provide as fallback.
- PNG: keep only for assets that need transparency *and* sharp lines (logos, icons). Most photos don't.

### 5b. One-time conversion script (Node + sharp)

`scripts/convert-images.js`:

```js
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const ROOT = path.resolve(__dirname, '../assets/images');
const exts = ['.png', '.jpg', '.jpeg'];

function* walk(dir) {
  for (const f of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, f.name);
    if (f.isDirectory()) yield* walk(p);
    else if (exts.includes(path.extname(f.name).toLowerCase())) yield p;
  }
}

(async () => {
  for (const src of walk(ROOT)) {
    const base = src.replace(/\.(png|jpe?g)$/i, '');
    try {
      await sharp(src).avif({ quality: 60, effort: 6 }).toFile(base + '.avif');
      await sharp(src).webp({ quality: 78 }).toFile(base + '.webp');
      console.log('OK', src);
    } catch (e) {
      console.error('FAIL', src, e.message);
    }
  }
})();
```

Run:

```powershell
cd E:\BITS\github\vnil-nuts
npm init -y
npm install sharp --save-dev
node scripts/convert-images.js
```

Time budget: ~1 minute for the whole image folder on a modern laptop.

**Don't delete the source PNG yet** — wait until the HTML is updated (next step), then delete in a separate commit.

### 5c. Update HTML to use `<picture>`

Replace `<img>` with `<picture>` everywhere a converted image is used. Pattern:

```html
<!-- before -->
<img src="assets/images/hero/cashew-hero.png" alt="Cashew" loading="lazy">

<!-- after -->
<picture>
  <source srcset="assets/images/hero/cashew-hero.avif" type="image/avif">
  <source srcset="assets/images/hero/cashew-hero.webp" type="image/webp">
  <img src="assets/images/hero/cashew-hero.png" alt="Cashew" loading="lazy" decoding="async">
</picture>
```

PowerShell script that does this naively (only converts `<img src="…png">` with no other attributes; **hand-check the diff before committing**):

```powershell
$pattern = '<img\s+src="(assets/images/[^"]+)\.(png|jpe?g)"([^>]*)>'
Get-ChildItem -Path . -Filter *.html | ForEach-Object {
  $c = Get-Content $_.FullName -Raw
  $c = [regex]::Replace($c, $pattern, {
    param($m)
    $base = $m.Groups[1].Value
    $ext  = $m.Groups[2].Value
    $rest = $m.Groups[3].Value
    "<picture><source srcset=`"$base.avif`" type=`"image/avif`"><source srcset=`"$base.webp`" type=`"image/webp`"><img src=`"$base.$ext`"$rest></picture>"
  })
  Set-Content -Path $_.FullName -Value $c -Encoding utf8
}
```

> The regex is intentionally simple. It misses `<img>` tags split across multiple lines or with `src` not as the first attribute. Run, then `git diff` and fix outliers by hand.

### 5d. Verify and delete sources

After visual check passes in all 9 pages, delete the PNG/JPG originals where AVIF+WebP versions exist:

```powershell
Get-ChildItem -Path assets/images -Recurse -Include *.png,*.jpg,*.jpeg | Where-Object {
  Test-Path ($_.FullName -replace '\.(png|jpe?g)$','.avif')
} | Remove-Item -Confirm
```

(`-Confirm` prompts per file. Drop it once you're sure.)

**Keep as PNG/SVG (don't convert):** logos, icons, favicons, anything where transparency or pixel sharpness matters.

---

## 6. `srcset` for hero images (needs image variants)

**Why this exists.** Hero is the largest single asset on the homepage. Mobile users on 3G should not download 1920×1080. Per [DESIGN_ANALYSIS.md §5](./DESIGN_ANALYSIS.md#5-imagery--photography) and [GAP_ANALYSIS.md §6](./GAP_ANALYSIS.md#6-seo-performance--technical-hygiene-high-priority).

### 6a. Generate variants

Extend the conversion script in §5b to emit multiple widths for hero images only:

```js
// scripts/convert-hero.js
const sharp = require('sharp');
const fs = require('fs'), path = require('path');

const SIZES = [480, 768, 1024, 1440, 1920];
const HERO_DIR = path.resolve(__dirname, '../assets/images/hero');

(async () => {
  for (const f of fs.readdirSync(HERO_DIR)) {
    if (!/\.(png|jpe?g|webp|avif)$/i.test(f)) continue;
    const src = path.join(HERO_DIR, f);
    const base = path.join(HERO_DIR, f.replace(/\.[^.]+$/, ''));
    for (const w of SIZES) {
      await sharp(src).resize({ width: w }).avif({ quality: 55 }).toFile(`${base}-${w}.avif`);
      await sharp(src).resize({ width: w }).webp({ quality: 75 }).toFile(`${base}-${w}.webp`);
    }
    console.log('Variants generated for', f);
  }
})();
```

Output: `cashew-hero-480.avif`, `cashew-hero-768.avif`, …, plus WebP equivalents.

### 6b. Update hero markup

```html
<picture>
  <source
    type="image/avif"
    srcset="
      assets/images/hero/cashew-hero-480.avif   480w,
      assets/images/hero/cashew-hero-768.avif   768w,
      assets/images/hero/cashew-hero-1024.avif 1024w,
      assets/images/hero/cashew-hero-1440.avif 1440w,
      assets/images/hero/cashew-hero-1920.avif 1920w"
    sizes="100vw">
  <source
    type="image/webp"
    srcset="
      assets/images/hero/cashew-hero-480.webp   480w,
      assets/images/hero/cashew-hero-768.webp   768w,
      assets/images/hero/cashew-hero-1024.webp 1024w,
      assets/images/hero/cashew-hero-1440.webp 1440w,
      assets/images/hero/cashew-hero-1920.webp 1920w"
    sizes="100vw">
  <img
    src="assets/images/hero/cashew-hero-1024.webp"
    alt="Hand-sorted whole cashews on cream linen"
    width="1920" height="1080"
    fetchpriority="high"
    decoding="async">
</picture>
```

Key attributes:

- `sizes="100vw"` — hero is full-width.
- `fetchpriority="high"` — tells browser to load this before below-the-fold content.
- `width`/`height` — prevents Cumulative Layout Shift (CLS); use the natural dimensions of the *largest* variant.
- **No `loading="lazy"` on hero** — it's above the fold. Lazy-loading kills the LCP score.

For below-the-fold heroes (banner page, breadcrumb, etc.), use `loading="lazy" fetchpriority="low"` instead.

### 6c. Verify

Open Chrome DevTools → Network → throttle to "Slow 4G" → reload `index.html`. The hero image request should be for the variant matching the viewport width. LCP score should be < 2.5s.

---

## Order of operations

If you do these in this order, each step's verification is easy:

1. **§1 first** — rename utility classes. Pure search/replace, lowest risk, biggest visual payoff (more breathing room).
2. **§4 second** — image folder cull. Fewer assets = faster everything else.
3. **§5 third** — format conversion. Affects every image.
4. **§6 fourth** — hero `srcset`. Builds on §5's variants.
5. **§2 fifth** — icon swap. Long but mechanical; verify in browser.
6. **§3 last** — carousel swap. Hardest because it touches HTML + CSS + JS. Do it on a feature branch and merge only when every page works.

Make a separate commit per step. If something breaks, `git revert <hash>` cleanly undoes it.

---

## When in doubt

- All the SCSS source files live in `assets/scss/`. If a CSS change you make in `style.css` keeps getting overwritten, the SCSS source has a conflicting rule. Edit the source.
- The site is plain static HTML — no build step required for HTML/CSS changes (you do need Node only for the image conversion + Embla install in §3 and §5).
- `prepros.config` exists at the repo root → if Prepros is open, it'll recompile SCSS automatically on save. If not, run `npx sass assets/scss/style.scss assets/css/style-dist.css` once.
- Test on real mobile (or Chrome DevTools device emulation set to "Slow 4G + Mid-tier mobile") before declaring a step done. Premium feel breaks fastest on bad connections.
