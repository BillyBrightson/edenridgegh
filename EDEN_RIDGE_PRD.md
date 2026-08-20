# Eden Ridge — Website Rebuild + Client Dashboard
## Product Requirements Document

**Client:** Eden Ridge (Community 25, Tema, Greater Accra, Ghana)
**Prepared for:** development with Claude Code
**Version:** 1.0
**Date:** 20 August 2026

---

## 1. Summary

Rebuild the existing Eden Ridge marketing site — currently a static build at
`https://sensational-hummingbird-0d60c9.netlify.app/` — as a **PHP + SQLite application**
that is visually identical to the reference, and add a **WordPress-style admin dashboard**
at `app.edenridgegh.com` so the client can edit every piece of content, swap images and the
logo, and manage enquiries without a developer.

Two hard rules govern this build:

1. **Visual parity is non-negotiable.** The public site must render pixel-for-pixel the same
   as the reference build — same layout, same colours, same typography, same spacing, same
   scroll/hover behaviour. Nothing is redesigned in v1.
2. **Everything visible is editable.** Any text, image, number, link, phone, email or logo
   that appears on the public site must be reachable from the dashboard. The client must
   never need FTP or a developer to change a word.

---

## 2. Goals & non-goals

### Goals
- Byte-for-eye reproduction of the reference site, driven from a database instead of hardcoded markup.
- A dashboard the client can learn in ten minutes: sections as tabs, typed fields, live preview, save.
- Capture, store and route every sales enquiry; nothing lost to a mailto: link.
- Deployable onto a standard cPanel/shared-hosting package with no CLI, no daemon, no external database server.
- Fast on Ghanaian mobile networks: no framework payload, no client-side rendering, images served as WebP.

### Non-goals (v1)
- No redesign, no new sections, no new colour scheme.
- No e-commerce / online payment collection (payment structure is informational only).
- No multi-language.
- No CRM integration (leads are exportable as CSV; integrations can come later).
- No public user accounts — the only logins are staff logins.

---

## 3. Domains & environments

| Environment | Public site | Dashboard |
|---|---|---|
| Production | `edenridgegh.com` (+ `www` → apex) | `app.edenridgegh.com` |
| Staging (optional) | `staging.edenridgegh.com` | `app-staging.edenridgegh.com` |

**Deployment model — one codebase, two docroots.** A single application directory lives
outside the web root. `edenridgegh.com` docroot points at `public/`; `app.edenridgegh.com`
docroot points at `admin/public/`. Both bootstrap the same `core/` and read/write the same
SQLite file. This is a standard cPanel addon-subdomain setup.

```
/home/<cpanel-user>/edenridge/          <- app root, NOT web accessible
├── core/                               <- shared: router, DB, models, auth, helpers
├── content/                            <- default seed content (JSON)
├── storage/
│   ├── db/edenridge.sqlite             <- the database (0600, outside docroot)
│   ├── uploads/                        <- originals
│   ├── cache/                          <- rendered page cache
│   └── logs/
├── public/                             <- DOCROOT for edenridgegh.com
│   ├── index.php                       <- front controller
│   ├── assets/{css,js,fonts,img}
│   └── media/                          <- generated public image derivatives
└── admin/
    └── public/                         <- DOCROOT for app.edenridgegh.com
        ├── index.php
        └── assets/
```

If the host cannot point a subdomain at a nested folder, fall back to a symlink from
`~/public_html/app` → `admin/public`, or serve the dashboard at `edenridgegh.com/admin`
with an Apache alias. The routing layer must not care which.

---

## 4. Technical stack & constraints

| Layer | Choice | Notes |
|---|---|---|
| Language | PHP 8.2+ (8.1 minimum) | typed properties, enums, match, readonly |
| Framework | **None** — hand-rolled front controller + router | shared hosting friendly; no artisan, no queue worker |
| Database | **SQLite 3** via PDO | WAL mode ON, foreign keys ON, busy timeout 5000ms |
| Templating | Plain PHP templates with `htmlspecialchars` output helper `e()` | no Blade/Twig dependency |
| Composer | Used, but vendor committed to the repo | host may lack CLI access; `vendor/` ships with the deploy |
| Mail | PHPMailer over SMTP (host SMTP or a transactional provider) | never bare `mail()` |
| Images | Intervention Image or raw GD | WebP + responsive derivatives generated on upload |
| Front-end JS | Vanilla ES modules, no build step required | if the reference uses a bundler, ship the compiled CSS/JS as static assets |
| CSS | Compiled/copied from the reference build, tokenised into CSS custom properties | |
| Server | Apache + mod_rewrite (`.htaccess`) | provide an nginx config as a comment for portability |

**Hosting constraints to respect throughout:**
- No background workers, no cron guarantees → all work happens in-request.
- SQLite is single-writer → keep writes short, never hold a transaction across an HTTP call to an external service.
- File uploads limited by `upload_max_filesize` / `post_max_size` → surface the real limit in the UI and validate before accepting. **The 3D walkthrough video is NOT uploaded to this server** (see §10).
- `storage/` must be unreachable over HTTP. Ship a deny-all `.htaccess` in it *and* keep it outside the docroots.

---

## 5. Phase 0 — Design token extraction (do this first)

Before writing any template, capture the reference build's design system so parity is
verifiable rather than eyeballed.

1. Pull the reference site's compiled CSS and fonts:
   - `curl -s https://sensational-hummingbird-0d60c9.netlify.app/` → read `<link rel=stylesheet>` and `<script src>` hrefs, fetch each asset.
2. Record into `public/assets/css/tokens.css` as `:root` custom properties:
   - every colour (background, surface, text primary/secondary/muted, accent, border, overlay)
   - font families + the exact Google Fonts / self-hosted font files and weights
   - type scale (clamp() values per heading level), line heights, letter spacing
   - spacing scale, container max-widths, section vertical rhythm
   - border radii, shadows, transition durations and easing curves
   - breakpoints
3. Screenshot the reference at 1920, 1440, 1024, 768, 390 px wide, full page. Store in
   `docs/reference/` as the parity baseline used in §16 acceptance testing.
4. Note every scroll-triggered animation, sticky behaviour, gallery filter interaction,
   accordion, and hover state. These must be reproduced.

**Deliverable of Phase 0:** `docs/DESIGN_TOKENS.md` + `tokens.css` + baseline screenshots.
No hardcoded hex values anywhere else in the codebase.

---

## 6. Public site — information architecture

Sections render in this exact order on a single long page, with anchor navigation.
Every section is individually toggleable (`is_published`) and re-orderable by `sort_order`
from the dashboard, but ships in this order.

| # | Section key | Anchor | Nav label |
|---|---|---|---|
| 1 | `header` | — | (nav bar) |
| 2 | `hero` | `#top` | — |
| 3 | `vision` | `#about` | About |
| 4 | `why` | `#why` | — |
| 5 | `residence` | `#homes` | Homes |
| 6 | `residence_details` | `#details` | — |
| 7 | `gallery` | `#gallery` | Gallery |
| 8 | `floorplans` | `#floor-plans` | Floor Plans |
| 9 | `amenities` | `#amenities` | Amenities |
| 10 | `location` | `#location` | Location |
| 11 | `investment` | `#investment` | — |
| 12 | `process` | `#process` | — |
| 13 | `faq` | `#faq` | — |
| 14 | `experience` | `#video` | — |
| 15 | `enquiry` | `#contact` | Contact |
| 16 | `footer` | — | — |

Additional standalone routes:

| Route | Purpose |
|---|---|
| `/` | the single-page site |
| `/thank-you` | post-enquiry confirmation (also handled inline via AJAX) |
| `/privacy` | privacy policy (editable page) |
| `/terms` | terms & disclaimer (editable page) |
| `/sitemap.xml` | generated |
| `/robots.txt` | generated |
| `/media/...` | generated image derivatives |

---

## 7. Section-by-section content specification

Content for each section is stored as a typed JSON document in `sections.content`
(see §11) and rendered by `views/sections/<key>.php`. Below, **field** = dashboard field key,
**type** = editor control, and the value shown is the seed content lifted from the reference.

Field types available to the editor: `text`, `textarea`, `richtext` (restricted: bold, italic,
link, unordered list), `number`, `image`, `link`, `toggle`, `select`, `icon`, `repeater`.

### 7.1 `header`
| Field | Type | Seed value |
|---|---|---|
| `logo_image` | image | Eden Ridge wordmark (SVG/PNG; falls back to `logo_text`) |
| `logo_text` | text | `EDEN RIDGE` |
| `nav_items` | repeater(`label` text, `anchor` text, `is_visible` toggle) | About, Homes, Floor Plans, Gallery, Location, Contact |
| `cta_label` | text | `Book a Consultation` |
| `cta_target` | link | `#contact` |
| `sticky_on_scroll` | toggle | on |

### 7.2 `hero`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | (as per reference) |
| `headline` | textarea | `Bold, contemporary residences for a new generation` |
| `body` | textarea | `A gated collection of four-bedroom homes positioned within Ghana's Eastern Corridor, designed for homeowners and investors prioritizing architecture, privacy, and long-term value.` |
| `background_image` | image | hero render |
| `stats` | repeater(`value` text, `label` text) | `4 / Bedrooms`, `Gated / Community`, `2+ / Parking Spaces`, `2025 / Collection` |
| `primary_cta_label` / `primary_cta_target` | text / link | `Register Your Interest` → `#contact` |
| `secondary_cta_label` / `secondary_cta_target` | text / link | `Explore the Residence` → `#homes` |

### 7.3 `vision`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Vision` |
| `headline` | text | `A new standard of contemporary living` |
| `body` | richtext | Modern residential living with globally-inspired yet locally-rooted design; architecture should elevate life rather than simply house it. |
| `image` | image | supporting render |

### 7.4 `why` — Why Eden Ridge
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Why Eden Ridge` |
| `headline` | text | `Considered from every angle` |
| `items` | repeater(`icon` icon, `title` text, `body` textarea) | 8 items: Contemporary Architecture; Scandinavian-Influenced Interiors; Secure Gated Community; Investment Potential; Family-Friendly Living; Connected Location; Quality Construction; Thoughtful Design (copy per reference) |

### 7.5 `residence` — The Residence
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `The Residence` |
| `headline` | text | `4-Bedroom Contemporary Townhouse` |
| `price_note` | text | `Reservation from $5,000 — contact for full pricing` |
| `specs` | repeater(`label` text, `value` text) | Bedrooms `4`; Bathrooms `En-suite primary + additional`; Ground Floor Area `46.68 m²`; Parking `2+ spaces`; Access `Gated, controlled access community` |
| `features` | repeater(`text` text) | Open-plan living/kitchen/dining; Indoor garden beneath staircase; Glass balustrade floating staircase; Green marble kitchen island; Walk-in wardrobe with LED lighting; Private terrace off master suite; Smart-home & fibre internet ready; Backup power & water storage |
| `image` | image | primary residence render |
| `cta_1` / `cta_2` | link | `View Floor Plans` → `#floor-plans`; `Enquire About This Home` → `#contact` |

### 7.6 `residence_details` — numbered detail blocks
Alternating image/text blocks, numbered `01`–`07`.

`blocks` = repeater(`number` text, `label` text, `headline` text, `body` textarea, `image` image, `image_side` select[left|right])

Seed (numbering per reference):
- **02 — Dining Room** — "Refined Dining: An atmosphere of effortless occasion" — soaring ceilings, sculptural chandelier, full-height glazing.
- **03 — Designer Kitchen** — "Space made for gathering" — green marble island countertops, full-height timber cabinetry, integrated appliances open to living and dining zones.
- **04 — Primary Bathroom** — "Sanctuary of Stone & Light" — frameless shower glass, black matte fittings, travertine-look tiles.
- **05 — Master Bedroom** — "Your private retreat" — generous proportions, curated seating lounge, seamless walk-in wardrobe connection.
- **06 — Private Terrace** — "Outdoor Living Elevated" — private extension of the master suite, unobstructed views, black-framed glazing.
- **07 — Walk-In Wardrobe** — "Organised elegance" — warm timber, LED-lit rails, smoked glass drawers, display shelving.

> Preserve the reference's exact block order and numbering. If the reference shows an `01` block (Living Room), include it.

### 7.7 `gallery`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Gallery` |
| `headline` | text | `Every space, in detail` |
| `disclaimer` | text | `Renders are indicative of finished residences only.` |
| `categories` | repeater(`slug` text, `label` text, `sort_order` number) | All; Exterior; Living Room; Kitchen & Dining; Bed & Bath; Wardrobe & Terrace |
| `items` | media picker (multi) with per-item `category`, `alt`, `caption`, `sort_order` | the supplied render set |

Behaviour: filter chips filter client-side with a fade transition; clicking an image opens a
lightbox with keyboard (←/→/Esc) and swipe support; images lazy-load below the fold.

### 7.8 `floorplans`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Floor Plans` |
| `headline` | text | `Considered room by room` |
| `note` | text | `Downloadable plan packs available upon request.` |
| `plans` | repeater(`title` text, `image` image, `rows` sub-repeater(`label` text, `value` text), `total_label` text, `total_value` text) | see below |

1. **Ground Floor Living Area** — Living / Kitchen / Dining `43.92 m²`; WC `2.76 m²`; Entrance & Lobby `Included`; Indoor Garden Zone `Included`; **Total `46.68 m²`**
2. **Kitchen Plan** — Designer Kitchen `Open plan`; Green Marble Island `Included`; Breakfast Bar Seating `2 seats`; Utility & Storage `Included`
3. **Upper Floor Suite & Bath** — Master Suite; Primary Bathroom `En-suite`; Walk-In Wardrobe `Included`; Private Terrace `Included`; Bedrooms `4 total`

Plan images are the top-down renders supplied by the client.

### 7.9 `amenities`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Community Amenities` |
| `headline` | text | `Elevated Community: Beyond your front door` |
| `items` | repeater(`number` text, `icon` icon, `title` text, `body` textarea) | 01 Swimming Pool; 02 Outdoor Lounge Areas; 03 Modern Fitness Centre; 04 Secure Gated Access; 05 Private Clubhouse; 06 Children's Recreation; 07 Landscaped Gardens; 08 Utility Infrastructure (copy per reference) |

### 7.10 `location`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Location & Connectivity` |
| `headline` | text | `Connected to everything that matters` |
| `address_line` | text | `Community 25, Tema, Ghana's Eastern Corridor` |
| `distances` | repeater(`label` text, `value` text) | Accra CBD `Approx. 50 miles`; Kotoka Airport `~60 minutes`; Eastern Corridor Hwy `Minutes away` |
| `nearby` | repeater(`title` text, `body` textarea) | Healthcare; Education & Retail; Hospitality & Dining; Lifestyle |
| `map_embed_type` | select[image \| iframe \| none] | |
| `map_image` / `map_embed_url` | image / text | static map or Google Maps embed |
| `map_link` | link | "Open in Google Maps" |

### 7.11 `investment`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Investment Case` |
| `headline` | text | `Why invest in Eden Ridge` |
| `items` | repeater(`number` text, `title` text, `body` textarea) | Strategic Location; Contemporary Architecture; Secure Gated Living; Long-Term Investment Potential; Modern Lifestyle Experience; Future Growth Market |
| `summary` | text | `A development designed for both lifestyle and long-term value.` |

### 7.12 `process` — How to purchase
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Purchase Process` |
| `headline` | text | `How to Purchase: Payment structure` |
| `stages` | repeater(`stage_number` text, `title` text, `amount` text, `body` textarea) | 01 Reservation `$5,000` refundable deposit securing preferred unit; 02 Contract `30%` upon signing sale and purchase agreement; 03 Construction `30%` staged instalments aligned with verified construction milestones; 04 Completion `40%` final balance upon completion and handover |
| `disclaimer` | textarea | `Prices and payment terms subject to change. Contact us for current pricing and availability. Flexible arrangements available for qualified buyers.` |

### 7.13 `faq`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Frequently Asked Questions` |
| `headline` | text | `Common questions` |
| `items` | repeater(`question` text, `answer` richtext) | the 6 Q&As from the reference, verbatim |

Behaviour: accordion, one open at a time, first item open by default, `<details>`-equivalent
semantics with proper ARIA. Emits `FAQPage` JSON-LD (§14).

### 7.14 `experience` — video
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Experience Eden Ridge` |
| `headline` | text | `See it in motion` |
| `body` | textarea | `Request our curated video walkthrough of every space — living, dining, kitchen, bathroom, and beyond.` |
| `poster_image` | image | still frame from the walkthrough |
| `cta_label` | text | `Request the Video Tour` |
| `delivery_mode` | select[gated_request \| inline_embed] | `gated_request` |
| `video_provider` | select[youtube \| vimeo \| bunny \| direct_url] | |
| `video_url` | text | external URL — see §10 |

### 7.15 `enquiry`
| Field | Type | Seed value |
|---|---|---|
| `eyebrow` | text | `Make an Enquiry` |
| `headline` | text | `Register your interest` |
| `body` | textarea | `To arrange a private viewing, request a detailed information pack, or discuss flexible payment options, contact our sales team directly. We welcome enquiries from homebuyers, investors, and diaspora buyers globally.` |
| `contact_items` | repeater(`type` select[phone\|whatsapp\|email\|address], `label` text, `value` text) | Phone `+1 (404) 931-7171`; WhatsApp `+1 (404) 931-7171`; Email `edbenson11@outlook.com`; Location `Community 25, Tema, Greater Accra, Ghana` |
| `interest_options` | repeater(`value` text, `label` text) | Purchasing a home; Investment opportunity; Requesting the video tour; General information pack |
| `submit_label` | text | `Send Enquiry` |
| `response_note` | text | `We typically respond within one business day.` |
| `success_message` | textarea | shown after submit |

> **Note for the client:** the seed phone number is a US number and the email is a personal
> Outlook address. The dashboard makes both editable; flag to the client that a
> `sales@edenridgegh.com` address and a Ghanaian/WhatsApp Business number would look stronger.

### 7.16 `footer`
| Field | Type | Seed value |
|---|---|---|
| `logo_text` | text | `EDEN RIDGE` |
| `blurb` | textarea | short positioning line |
| `link_groups` | repeater(`heading` text, `links` sub-repeater(`label`, `target`)) | **Explore**: About, Homes, Floor Plans, Gallery · **Development**: Location, Amenities, Contact |
| `contact_email` / `contact_phone` | text | `edbenson11@outlook.com` / `+1 (404) 931-7171` |
| `social_links` | repeater(`platform` select, `handle` text, `url` text) | Instagram `@edenridgegh`, Facebook `@edenridgegh`, X `@edenridgegh`, TikTok `@edenridgegh`, LinkedIn `@edenridgegh`, YouTube `@edenridgegh` — hidden individually via `is_visible` |
| `copyright` | text | `© 2025 Eden Ridge. All rights reserved. Community 25 · Tema · Greater Accra · Ghana` (year auto-updates) |
| `disclaimer` | textarea | full disclaimer per reference, incl. `Renders by Blueprint 3D Studios.` |
| `legal_links` | repeater | Privacy, Terms |

---

## 8. Admin dashboard (`app.edenridgegh.com`)

### 8.1 Look & feel
Clean, light, WordPress-familiar: fixed left sidebar, top bar with site name + "View site" +
account menu, content area with a sticky save bar. Built with the same font family as the
public site for continuity, but its own neutral admin palette. Fully responsive — the client
should be able to fix a typo from his phone.

### 8.2 Navigation

```
Dashboard            overview: recent enquiries, quick stats, "view site", last edited
Pages
  └ Home             the section tabs (§7)
  └ Privacy
  └ Terms
Enquiries            leads inbox
Media Library        uploads
Gallery              curate + categorise the render set
Video Tour           walkthrough settings + gated-request log
Appearance           logo, favicon, brand colours, fonts
Site Settings        contact details, social handles, analytics
SEO                  per-page meta, OG images, sitemap, robots
Users                staff accounts (admin only)
Activity Log         who changed what, when (admin only)
Tools                backup / export / restore, cache clear
```

### 8.3 Section editing UX
- **Home** opens a vertical tab list of the 16 sections in page order.
- Each section shows: a **Published** toggle, a **drag handle** for order, its typed fields,
  and a **Preview** button that opens the public page anchored to that section.
- Repeaters: add / duplicate / delete / drag-reorder rows; collapsed rows show the row title.
- Image fields open the Media Library modal (choose existing or upload) and show alt-text +
  focal-point controls.
- **Draft vs published:** every save writes a new row to `section_revisions`. A "Publish"
  action promotes the draft to live. "Revert" restores any prior revision. Keep the last 30
  revisions per section.
- **Unsaved-changes guard** on navigation away.
- Character-count hints on fields where the design breaks past a length (headline, eyebrow,
  stat labels) — soft warning, not a hard block.

### 8.4 Roles

| Role | Can |
|---|---|
| `admin` | everything, incl. users, tools, activity log |
| `editor` | all content, media, gallery, enquiries; not users/tools |
| `viewer` | read-only + enquiries inbox (for a sales agent) |

Seed a single `admin` account at install; force a password change on first login.

### 8.5 Auth & session security
- Email + password, `password_hash()` with `PASSWORD_DEFAULT`.
- Session cookie: `HttpOnly`, `Secure`, `SameSite=Lax`, regenerate ID on login, idle timeout 2h, absolute 12h.
- Rate limit: 5 failed attempts per email or IP per 15 minutes → 15-minute lockout, logged.
- "Remember me" via a rotating selector/verifier token stored hashed (30 days), revocable.
- Password reset by emailed single-use token, 60-minute expiry.
- Optional TOTP 2FA for `admin` (v1.1 — design the schema for it now, ship the toggle disabled).
- All admin routes behind the auth middleware; CSRF token on every state-changing request.

---

## 9. Leads / enquiry flow

### 9.1 Submission
1. Public form posts to `POST /enquiry` (progressive enhancement: works without JS, enhanced to AJAX with inline validation).
2. Server-side validation: name (2–100), email (RFC + DNS MX check, soft-fail), phone (optional, libphonenumber-lite or permissive regex), interest (must match a configured option), message (≤ 2000).
3. **Spam controls, layered:** CSRF token + honeypot field + minimum time-on-form (3s) + per-IP rate limit (5/hour) + optional Cloudflare Turnstile (site key configurable in Settings, off by default).
4. Persist to `enquiries` with `source_page`, `utm_*`, `referrer`, `ip_hash` (hashed, not raw), `user_agent`, `created_at`.
5. Respond: inline success message + `success_message` copy; non-JS falls back to `/thank-you`.

### 9.2 Notifications
- **To sales:** immediate email to every address in `settings.notification_emails` (comma-separated), with all fields and a one-click deep link into the dashboard record. Reply-To set to the enquirer.
- **To enquirer:** branded auto-reply using an editable template (Settings → Email Templates), with merge tags `{{name}}`, `{{interest}}`, `{{phone}}`, `{{email}}`.
- **WhatsApp:** v1 ships a **click-to-chat** action in the inbox (`https://wa.me/<number>?text=<prefilled>`) so the agent can reply in one tap, plus an optional outbound webhook URL in Settings for a future WhatsApp Business API/Twilio hookup. No API key required in v1.
- Mail failures must never break the submission — queue to `mail_outbox` and retry on the next request (poor-man's queue), and always surface a failure banner in the dashboard.

### 9.3 Inbox
- Table: date, name, interest, phone, email, status, source. Sortable, searchable, filterable by status/interest/date range.
- Statuses: `new`, `contacted`, `qualified`, `won`, `lost`, `spam`. Colour-coded.
- Detail drawer: full message, internal notes (append-only, timestamped, attributed), status change, mail-to / call / WhatsApp actions.
- **CSV export** of the current filtered view.
- Unread badge count in the sidebar; dashboard widget for the last 5.
- Soft delete with a 30-day trash.

---

## 10. Video walkthrough

The 3D render walkthrough is too large to host on the shared package (upload limits + bandwidth).

- **Storage:** the file is hosted externally — YouTube (unlisted), Vimeo, or Bunny Stream. The dashboard stores the provider + URL/ID, not the file.
- **Gated request (default):** clicking *Request the Video Tour* opens a short form (name, email, phone optional). On submit the lead is stored in `enquiries` with `interest = video_tour` and `video_request = 1`, then the video is revealed inline (facade → iframe) *and* the link is emailed. This turns the walkthrough into a lead magnet, which is the point of it.
- **Inline embed mode:** an alternative setting that just shows the player, no gate.
- **Privacy-light embed:** render a poster-image facade; only load the third-party iframe on click (protects LCP and avoids third-party cookies on load).
- The dashboard's *Video Tour* screen shows the request log and conversion count.

---

## 11. Data model (SQLite)

```sql
PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

-- ---------- content ----------
CREATE TABLE pages (
  id            INTEGER PRIMARY KEY,
  slug          TEXT NOT NULL UNIQUE,          -- home, privacy, terms
  title         TEXT NOT NULL,
  is_published  INTEGER NOT NULL DEFAULT 1,
  created_at    TEXT NOT NULL,
  updated_at    TEXT NOT NULL
);

CREATE TABLE sections (
  id            INTEGER PRIMARY KEY,
  page_id       INTEGER NOT NULL REFERENCES pages(id) ON DELETE CASCADE,
  key           TEXT NOT NULL,                 -- hero, vision, why, ...
  type          TEXT NOT NULL,                 -- template identifier
  title         TEXT NOT NULL,                 -- admin-facing label
  content       TEXT NOT NULL DEFAULT '{}',    -- JSON, published
  draft_content TEXT,                          -- JSON, unpublished edits
  sort_order    INTEGER NOT NULL DEFAULT 0,
  is_published  INTEGER NOT NULL DEFAULT 1,
  updated_by    INTEGER REFERENCES users(id),
  updated_at    TEXT NOT NULL,
  UNIQUE(page_id, key)
);

CREATE TABLE section_revisions (
  id          INTEGER PRIMARY KEY,
  section_id  INTEGER NOT NULL REFERENCES sections(id) ON DELETE CASCADE,
  content     TEXT NOT NULL,
  user_id     INTEGER REFERENCES users(id),
  note        TEXT,
  created_at  TEXT NOT NULL
);
CREATE INDEX idx_revisions_section ON section_revisions(section_id, created_at DESC);

-- ---------- media ----------
CREATE TABLE media (
  id            INTEGER PRIMARY KEY,
  filename      TEXT NOT NULL,                 -- stored name
  original_name TEXT NOT NULL,
  mime          TEXT NOT NULL,
  ext           TEXT NOT NULL,
  bytes         INTEGER NOT NULL,
  width         INTEGER,
  height        INTEGER,
  alt           TEXT DEFAULT '',
  caption       TEXT DEFAULT '',
  focal_x       REAL DEFAULT 0.5,
  focal_y       REAL DEFAULT 0.5,
  hash          TEXT,                          -- sha1, dedupe
  uploaded_by   INTEGER REFERENCES users(id),
  created_at    TEXT NOT NULL
);
CREATE INDEX idx_media_created ON media(created_at DESC);

CREATE TABLE media_variants (
  id        INTEGER PRIMARY KEY,
  media_id  INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
  label     TEXT NOT NULL,                     -- thumb|sm|md|lg|xl
  format    TEXT NOT NULL,                     -- webp|jpg
  width     INTEGER NOT NULL,
  height    INTEGER NOT NULL,
  path      TEXT NOT NULL,
  bytes     INTEGER NOT NULL
);

-- ---------- gallery ----------
CREATE TABLE gallery_categories (
  id         INTEGER PRIMARY KEY,
  slug       TEXT NOT NULL UNIQUE,
  label      TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE gallery_items (
  id          INTEGER PRIMARY KEY,
  media_id    INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
  category_id INTEGER REFERENCES gallery_categories(id) ON DELETE SET NULL,
  title       TEXT DEFAULT '',
  caption     TEXT DEFAULT '',
  sort_order  INTEGER NOT NULL DEFAULT 0,
  is_published INTEGER NOT NULL DEFAULT 1
);

-- ---------- leads ----------
CREATE TABLE enquiries (
  id             INTEGER PRIMARY KEY,
  name           TEXT NOT NULL,
  email          TEXT NOT NULL,
  phone          TEXT,
  interest       TEXT,
  message        TEXT,
  status         TEXT NOT NULL DEFAULT 'new',
  video_request  INTEGER NOT NULL DEFAULT 0,
  source_page    TEXT,
  referrer       TEXT,
  utm_source     TEXT, utm_medium TEXT, utm_campaign TEXT,
  ip_hash        TEXT,
  user_agent     TEXT,
  is_spam        INTEGER NOT NULL DEFAULT 0,
  deleted_at     TEXT,
  created_at     TEXT NOT NULL
);
CREATE INDEX idx_enquiries_created ON enquiries(created_at DESC);
CREATE INDEX idx_enquiries_status  ON enquiries(status);

CREATE TABLE enquiry_notes (
  id           INTEGER PRIMARY KEY,
  enquiry_id   INTEGER NOT NULL REFERENCES enquiries(id) ON DELETE CASCADE,
  user_id      INTEGER REFERENCES users(id),
  body         TEXT NOT NULL,
  created_at   TEXT NOT NULL
);

CREATE TABLE mail_outbox (
  id          INTEGER PRIMARY KEY,
  to_email    TEXT NOT NULL,
  subject     TEXT NOT NULL,
  body_html   TEXT NOT NULL,
  reply_to    TEXT,
  attempts    INTEGER NOT NULL DEFAULT 0,
  last_error  TEXT,
  sent_at     TEXT,
  created_at  TEXT NOT NULL
);

-- ---------- users & ops ----------
CREATE TABLE users (
  id             INTEGER PRIMARY KEY,
  name           TEXT NOT NULL,
  email          TEXT NOT NULL UNIQUE,
  password_hash  TEXT NOT NULL,
  role           TEXT NOT NULL DEFAULT 'editor',
  must_reset     INTEGER NOT NULL DEFAULT 0,
  totp_secret    TEXT,
  last_login_at  TEXT,
  is_active      INTEGER NOT NULL DEFAULT 1,
  created_at     TEXT NOT NULL
);

CREATE TABLE auth_tokens (
  id          INTEGER PRIMARY KEY,
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  selector    TEXT NOT NULL UNIQUE,
  verifier_hash TEXT NOT NULL,
  purpose     TEXT NOT NULL,                   -- remember|reset
  expires_at  TEXT NOT NULL,
  created_at  TEXT NOT NULL
);

CREATE TABLE login_attempts (
  id          INTEGER PRIMARY KEY,
  identifier  TEXT NOT NULL,                   -- email or ip hash
  succeeded   INTEGER NOT NULL,
  created_at  TEXT NOT NULL
);

CREATE TABLE settings (
  key        TEXT PRIMARY KEY,
  value      TEXT NOT NULL,
  type       TEXT NOT NULL DEFAULT 'string',   -- string|json|bool|int
  updated_at TEXT NOT NULL
);

CREATE TABLE activity_log (
  id          INTEGER PRIMARY KEY,
  user_id     INTEGER REFERENCES users(id),
  action      TEXT NOT NULL,                   -- section.publish, media.delete, ...
  entity      TEXT,
  entity_id   INTEGER,
  meta        TEXT,
  ip_hash     TEXT,
  created_at  TEXT NOT NULL
);
```

**Settings keys (seed):** `site_name`, `tagline`, `logo_media_id`, `favicon_media_id`,
`contact_phone`, `contact_whatsapp`, `contact_email`, `contact_address`,
`social_instagram|facebook|x|tiktok|linkedin|youtube` (all `@edenridgegh`),
`notification_emails`, `smtp_*`, `ga4_id`, `meta_pixel_id`, `turnstile_site_key`,
`turnstile_secret`, `maintenance_mode`, `cache_enabled`, `brand_colors` (json),
`whatsapp_webhook_url`, `video_provider`, `video_url`, `video_gate_enabled`.

Migrations live in `core/migrations/NNN_description.php` and run through a one-command
runner (also exposed at `app.edenridgegh.com/install` on first boot, then locked).

---

## 12. Media pipeline

- Accept: JPEG, PNG, WebP, SVG (SVG sanitised — strip `<script>`, `on*`, `xlink:href` to non-fragment), max 12 MB.
- Validate by real MIME (finfo), not extension. Store outside docroot; serve derivatives from `public/media/`.
- On upload generate WebP derivatives at widths **320, 640, 960, 1440, 1920, 2560** (never upscale) plus a 400px JPEG fallback and a 24px LQIP blur placeholder.
- Filenames: `<sha1-prefix>-<slug>-<width>.webp`, immutable, `Cache-Control: public, max-age=31536000, immutable`.
- Every `<img>` renders with `srcset`, `sizes`, explicit `width`/`height`, `loading="lazy"` (except the hero, which is `fetchpriority="high"` and preloaded), and `alt` from the media record.
- Library UI: grid, search by name/alt, filter by type, bulk delete with usage check ("used in Hero, Gallery — delete anyway?").
- Deleting media removes originals + all variants and nulls references safely.

---

## 13. Front-end requirements

- **Semantic HTML5**, one `<h1>` (hero), logical heading order, landmarks, skip-to-content link.
- **Accessibility target: WCAG 2.1 AA.** Visible focus rings, 4.5:1 text contrast, accordion/lightbox/nav keyboard operable, `prefers-reduced-motion` disables scroll animations and parallax.
- **Responsive** at 390 / 768 / 1024 / 1440 / 1920. Mobile nav = full-screen overlay with focus trap.
- **No layout shift:** all media has intrinsic dimensions; fonts use `font-display: swap` with a metric-matched fallback.
- **Progressive enhancement:** the page is readable and the form submits with JS disabled.
- **Performance budget:** LCP < 2.5s on 4G, CLS < 0.1, INP < 200ms, total JS < 60 KB gzipped, initial HTML < 100 KB. Lighthouse mobile ≥ 90 across Performance / Accessibility / Best Practices / SEO.
- **Caching:** rendered home page cached to `storage/cache` as static HTML, invalidated on any publish. Serve `ETag` + `Last-Modified`.
- Gzip/Brotli via `.htaccess`, long-cache static assets, versioned asset URLs (`?v=<filemtime>`).

---

## 14. SEO & analytics

- Editable per page: title, meta description, canonical, OG title/description/image, Twitter card, `noindex` toggle.
- Defaults: title `Eden Ridge — Bold Contemporary Residences | Community 25, Tema`.
- JSON-LD: `Organization`, `LocalBusiness`/`RealEstateAgent` (address, geo, phone, sameAs → the `@edenridgegh` socials), `Product`/`Residence` for the townhouse, `FAQPage` from the FAQ section, `BreadcrumbList` on sub-pages.
- Auto-generated `sitemap.xml` and `robots.txt` (robots editable; blocks `app.` subdomain entirely).
- GA4 and Meta Pixel IDs configurable, loaded only when set, and gated behind a cookie-consent banner if `cookie_consent_enabled`.
- Track as events: `enquiry_submit`, `video_request`, `whatsapp_click`, `phone_click`, `floorplan_view`, `gallery_open`.
- `app.edenridgegh.com` sends `X-Robots-Tag: noindex, nofollow` on every response.

---

## 15. Security & operations

- Prepared statements everywhere; no string-concatenated SQL.
- Escape all output through `e()`; richtext sanitised server-side through an allow-list (HTMLPurifier or equivalent) on **save and** on render.
- CSRF tokens on all POST/PUT/DELETE, per-session, rotated on login.
- Security headers via `.htaccess` + PHP: `Content-Security-Policy` (script-src self + configured analytics + video host), `X-Frame-Options: SAMEORIGIN` (public) / `DENY` (admin), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Strict-Transport-Security`, `Permissions-Policy`.
- Force HTTPS on both hosts.
- `storage/` and `core/` deny-all; `.sqlite` never served; `config.php` outside docroot with 0600.
- **Backups:** Tools → "Download backup" produces a zip of the SQLite file (via `VACUUM INTO`, safe under WAL) plus `storage/uploads`. Nightly automatic backup to `storage/backups` (7-day rotation) triggered by a cPanel cron if available, else on first request after midnight. Restore from an uploaded zip, admin-only, with a confirmation step.
- Error handling: display errors off in production, all errors logged to `storage/logs/app.log` with rotation; friendly 404/500 pages that match the site design.
- Maintenance mode toggle serving a branded holding page while letting logged-in admins through.

---

## 16. Acceptance criteria

**Visual parity**
1. Side-by-side screenshots of the new site vs the Phase 0 baselines at 1920/1440/1024/768/390 show no perceptible difference in layout, colour, type or spacing.
2. Every animation, hover state, sticky behaviour, gallery filter, lightbox and accordion in the reference is reproduced.
3. All copy matches the reference verbatim unless the client has since edited it.

**Editability**
4. Every string, number, image and link on the public site can be changed from the dashboard, and the change is visible on the public site within one page refresh.
5. Logo and favicon can be replaced from Appearance without touching code.
6. Repeater items (nav, stats, features, gallery, amenities, FAQ, stages, socials) can be added, removed and reordered.
7. Sections can be hidden and reordered.
8. A bad edit can be reverted from revision history.

**Leads**
9. A form submission is stored, appears in the inbox within seconds, triggers a sales notification email and an auto-reply, and survives an SMTP outage via the outbox retry.
10. Spam controls block a bot submitting instantly with a filled honeypot.
11. CSV export returns the filtered set.

**Quality gates**
12. Lighthouse mobile ≥ 90 in all four categories on the home page.
13. Zero critical/serious axe-core violations.
14. Valid HTML (W3C) and valid JSON-LD (Rich Results Test).
15. Runs on PHP 8.2 shared hosting with no CLI dependency at runtime.

---

## 17. Build phases

| Phase | Scope | Output |
|---|---|---|
| **0. Extraction** | pull reference assets, tokenise design system, baseline screenshots | `tokens.css`, `DESIGN_TOKENS.md`, `docs/reference/` |
| **1. Foundation** | app skeleton, router, PDO/SQLite layer, migrations, config, `.htaccess`, error handling | bootable app on both docroots |
| **2. Public site** | all 16 sections as templates fed from seeded JSON, responsive + interactions | pixel-parity static-content site |
| **3. Admin core** | auth, layout, section editor, repeaters, revisions, publish flow | client can edit all copy |
| **4. Media & gallery** | uploads, derivatives, library UI, gallery curation, logo/favicon | client can swap every image |
| **5. Leads** | form pipeline, spam controls, mailer + outbox, inbox, notes, CSV, WhatsApp actions | working sales funnel |
| **6. Video, SEO, settings** | gated video flow, meta editor, JSON-LD, sitemap, analytics, socials | launch-ready |
| **7. Hardening & launch** | caching, headers, backups, Lighthouse/axe passes, seed real content, client training doc | production deploy |

**Estimated effort:** Phases 0–2 are the bulk of the visual work; 3–5 the bulk of the app
work. Sequence them strictly — do not start the admin until the public templates are reading
from `sections.content`, or the field schema will be guessed twice.

---

## 18. Deliverables

1. Full source repository with `vendor/` committed and a `README.md` deploy guide.
2. `install.php` first-boot wizard: environment check, DB creation, migrations, seed content, admin account, then self-lock.
3. Seeded database containing all reference content and the supplied render set.
4. `.htaccess` files for both docroots (+ nginx equivalents in comments).
5. **Client handover guide** (`docs/CLIENT_GUIDE.md`) with screenshots: how to log in, edit a section, swap an image, read enquiries, take a backup.
6. Backup/restore procedure and credentials handover document.

---

## 19. Assumptions & open items

**Assumptions**
- Hosting is cPanel-style Apache with PHP 8.2, mod_rewrite, SQLite PDO, GD, and either host SMTP or an allowed outbound SMTP port.
- `app.edenridgegh.com` can be created as an addon subdomain with a custom document root.
- The client supplies the final render set at full resolution, plus a vector logo.
- The walkthrough video will be hosted externally (YouTube/Vimeo/Bunny) — a URL is provided at build time.

**Open items to confirm with the client**
1. **Logo** — is there a vector wordmark, or is the text lockup on the reference the logo?
2. **Contact details** — keep the US number `+1 (404) 931-7171` and `edbenson11@outlook.com`, or switch to a Ghana number and a `@edenridgegh.com` address before launch?
3. **Social accounts** — are all six `@edenridgegh` handles live, or should only the active ones show?
4. **Video host** — YouTube unlisted (free, easy) vs Vimeo/Bunny (no branding, no suggested videos)?
5. **SMTP** — use the hosting mailbox, or a transactional provider (Brevo/Postmark/SES) for deliverability on notification emails?
6. **Google Maps** — embed a live map, or a static styled map image that matches the site's palette?
7. **Legal pages** — does the client have privacy policy and terms copy, or should we draft placeholders?
8. **Staff logins** — how many people need dashboard access, and at what roles?

---

*Reference build: https://sensational-hummingbird-0d60c9.netlify.app/ — all copy, structure and
visual treatment in this document derive from it. Renders by Blueprint 3D Studios.*
