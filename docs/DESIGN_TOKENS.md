# Design tokens — Eden Ridge

Phase 0 output: the design system extracted from the reference build at
`https://sensational-hummingbird-0d60c9.netlify.app/`, so parity is verifiable
rather than eyeballed.

The reference is a single HTML file with its CSS inlined in one `<style>` block
and 28 images embedded as base64 data URIs. Every value below was read out of
that stylesheet, not guessed.

All tokens live in **`public/assets/css/tokens.css`**. No other stylesheet in
the project contains a raw colour, and there are no hardcoded hex values in any
PHP template — the build is checked with:

```sh
grep -nE '#[0-9a-fA-F]{3,6}\b|rgba?\(' public/assets/css/site.css   # must return nothing
```

---

## Colour

| Token | Value | Where it is used |
|---|---|---|
| `--pine` | `#131f19` | dark section backgrounds, nav overlay, mobile menu |
| `--pine-deep` | `#0d1712` | stat strip, contact section, footer, plan panels |
| `--pine-light` | `#1c2a22` | map panel gradient |
| `--paper` | `#f6f3ec` | page background, cards on dark |
| `--paper-warm` | `#efe9dc` | floor plan and FAQ section backgrounds |
| `--ink` | `#1b1c19` | body text on light |
| `--ink-soft` | `#4a4a45` | secondary text on light |
| `--ink-inverse` | `#161b13` | text on the brass button |
| `--stone` | `#8d8b82` | form note |
| `--paper-bright` | `#f8f6ef` | hero headline |
| `--paper-soft` | `#f2eee2` | mobile menu links, filter chips |
| `--paper-tint` | `#f1ede0` | spread captions, spec values, map label |
| `--paper-dim` | `#e9e5d8` | nav links, lightbox caption |
| `--paper-muted` | `#e4e0d2` | hero sub-copy, scroll cue |
| `--sand` | `#cfc9b8` | body text on dark |
| `--sand-dim` | `#a9a493` | labels and notes on dark |
| `--sand-faint` | `#818073` | footer disclaimer (see deviation 3) |
| `--brass` | `#b6904f` | accent: eyebrows, rules, primary button, icons |
| `--brass-soft` | `#cdae74` | accent on dark: stat numbers, plan values |

Rules, scrims and overlays (`--line-on-dark`, `--line-on-light`, `--hero-scrim`,
`--band-scrim`, `--video-scrim`, `--media-scrim`, `--tag-scrim`, `--lightbox-bg`,
`--map-bg`, `--shadow-float`, …) are declared in the same file with the exact
gradients and alpha values from the reference.

## Typography

Both families are declared with `@font-face { src: local(...) }` and a fallback
stack — exactly as the reference does. **No web fonts are downloaded**, which is
deliberate: it is the fastest possible option on Ghanaian mobile networks, and it
reproduces the reference's rendering on every platform.

| Token | Value |
|---|---|
| `--serif` | `'EdenSerif', 'Iowan Old Style', 'Palatino Linotype', Georgia, serif` |
| `--sans` | `'EdenSans', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif` |

Type scale (all `clamp()` values copied from the reference):

| Token | Value | Element |
|---|---|---|
| `--fs-h1` | `clamp(44px, 7vw, 92px)` | hero headline |
| `--fs-h2` | `clamp(30px, 3.6vw, 46px)` | section headings |
| `--fs-h2-spread` | `clamp(30px, 3.4vw, 44px)` | spread headings |
| `--fs-h2-band` | `clamp(28px, 4vw, 42px)` | full-bleed banner headings |
| `--fs-h3` | `19px` | card titles |
| `--fs-base` / `--lh-base` | `16px` / `1.6` | body |
| `--fs-eyebrow` / `--ls-eyebrow` | `12px` / `.22em` | uppercase eyebrows |

## Spacing & layout

| Token | Value |
|---|---|
| `--maxw` | `1320px` |
| `--gutter` / `--gutter-mobile` | `40px` / `22px` |
| `--section-pad` / `--section-pad-mobile` | `120px` / `76px` |

## Shape & motion

| Token | Value |
|---|---|
| `--radius` | `2px` |
| `--ease` | `cubic-bezier(.2,.7,.2,1)` |
| `--dur-fast` / `--dur` / `--dur-slow` / `--dur-reveal` | `.25s` / `.3s` / `.6s` / `.9s` |

## Breakpoints

`560px` (card grids collapse) · `700px` (amenities) · `780px` (gutters, masonry,
section padding) · `860px` (model card, plan panels) · `900px` (spreads, contact)
· `960px` (burger menu replaces the inline nav).

---

## Interactions reproduced

Every behaviour in the reference is present in `public/assets/js/site.js`:

- nav background/padding change past 40px of scroll
- full-screen mobile menu (now with a focus trap and Escape to close)
- `IntersectionObserver` reveal at a 0.12 threshold, disabled under `prefers-reduced-motion`
- gallery filter chips (with the fade the PRD asks for; the resting state is identical)
- lightbox with previous/next, Escape, arrow keys — plus swipe and focus restore
- floor plan tabs (now with `tablist`/`tab`/`tabpanel` roles and arrow-key navigation)
- FAQ accordion, one open at a time, `max-height` transition
- animated scroll cue, hover underline on nav links, image zoom on gallery hover
- floating WhatsApp button

---

## Parity baselines

`docs/reference/reference/` and `docs/reference/rebuild/` hold full-page captures
of both builds at 1920, 1440, 1024, 768 and 390 px wide (JPEG q82, captured
through the Chrome DevTools Protocol with each page scrolled end-to-end first so
every lazy image and reveal has settled).

The authoritative check is a block-by-block layout measurement of both pages at
each width. Result:

| Width | Blocks matching to the pixel |
|---|---|
| 1920 | 18 / 19 |
| 1440 | 18 / 19 |
| 1024 | 18 / 19 |
| 768 | 17 / 19 |
| 390 | 17 / 19 |

Rendered copy was also diffed string by string: **every visible string matches the
reference verbatim**. The only additions are a visually hidden skip link, three
`sr-only` table captions, the honeypot label, and the copyright year, which now
updates itself.

---

## Deliberate deviations

Five, all of them deliberate, all of them because reproducing the reference
exactly would have broken something the PRD also requires.

**1. Four gallery images are broken in the reference.**
Four of the twelve base64 blobs in the reference gallery fail to decode in the
browser (`naturalWidth === 0`), so those tiles collapse to zero height and the
grid renders eight images instead of twelve. The originals extract fine, so the
rebuild serves all twelve. This is the only reason the gallery section is taller
than the reference at every width.

**2. The investment section does not collapse on mobile in the reference.**
Its grid is set with an inline `style="grid-template-columns:1fr 1.1fr"`, and an
inline style beats the `@media (max-width:900px)` rule that is supposed to stack
it. Below 900px the reference squeezes two columns into ~180px each. The rebuild
moves that declaration into a `.invest-grid` class so the existing media query
works, and the section stacks properly on phones.

**3. Two contrast fixes.**
The lede paragraphs in the Location and Payment sections are `--ink-soft` on
`--pine` in the reference — 1.9:1, effectively invisible. Both sections now carry
the `.on-dark` class, which activates the reference's *own* rule
(`.on-dark .section-head p.lede { color: var(--sand) }`) and lifts them to
10.3:1. The footer disclaimer moved from `#7d7b70` to `#818073` (4.30:1 → 4.59:1),
an imperceptible nudge that clears AA.

**4. `--brass` on `--paper` still fails AA, and is kept anyway.**
The brand accent is 5.7:1 on the pine surfaces but only 2.7:1 on paper, where it
colours the small uppercase eyebrow labels. Changing it would alter the brand
colour on every section, so parity wins. `tokens.css` carries the nearest
accessible alternative (`#87652c`, 4.8:1) in a comment — a one-line change if the
client ever prefers AA over an exact colour match.

**5. Heading levels.**
The reference uses `<h4>` for amenity, investment and location list headings.
Those are now `<h3>`, which renders identically (both match the same
`h1,h2,h3,h4` base rule and the class sets the size explicitly) and stops the
heading order skipping a level. The footer's `<h5>` column headings are left
alone: `h5` is outside that base rule and carries a UA top margin, so promoting
it would shift the footer columns.

Everything else — every colour, every measurement, every string — matches.
