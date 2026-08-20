# Eden Ridge

The Eden Ridge marketing site rebuilt as a **PHP + SQLite** application, plus a
WordPress-style dashboard at `app.edenridgegh.com` where the client edits every
word, number, image and link on the public site.

- Public site: `edenridgegh.com` — one long page, sixteen sections, all driven from the database.
- Dashboard: `app.edenridgegh.com` — content, media, gallery, enquiries, SEO, users, backups.
- Reference build reproduced: `https://sensational-hummingbird-0d60c9.netlify.app/`

Built against `EDEN_RIDGE_PRD.md`. Parity evidence and the five deliberate
deviations are in [`docs/DESIGN_TOKENS.md`](docs/DESIGN_TOKENS.md). The client's
handover guide is [`docs/CLIENT_GUIDE.md`](docs/CLIENT_GUIDE.md).

---

## Requirements

| | |
|---|---|
| PHP | 8.1 minimum, 8.2+ recommended |
| Extensions | `pdo_sqlite`, `gd`, `fileinfo`, `mbstring`; `zip` for backups |
| Server | Apache with `mod_rewrite` (nginx equivalents are in the `.htaccess` comments) |
| Composer | **not needed** — there are no third-party dependencies at all |

There is no framework, no build step, no queue worker, no cron requirement and
no external database server. SMTP is spoken directly (`core/Mailer.php`) rather
than through a library, so nothing has to be installed on the host.

---

## Layout

```
edenridge/                     <- app root, NOT web accessible
├── core/                      <- router, DB, models, auth, mailer, helpers
│   ├── migrations/            <- schema, applied in order by Migrator
│   └── install_wizard.php     <- first-boot setup, self-locking
├── content/                   <- seed content as JSON (home, gallery, settings, pages)
├── views/                     <- plain PHP templates
│   ├── sections/              <- one file per public section
│   ├── admin/                 <- dashboard screens
│   └── emails/                <- notification and auto-reply templates
├── storage/                   <- database, uploads, cache, logs, backups (deny-all)
├── public/                    <- DOCROOT for edenridgegh.com
│   ├── index.php              <- public front controller
│   ├── assets/{css,js}
│   └── media/                 <- generated image derivatives
└── admin/
    └── public/                <- DOCROOT for app.edenridgegh.com
        ├── index.php
        └── assets/
```

---

## Deploying to cPanel

1. Upload the whole repository to `/home/<user>/edenridge` — **above** `public_html`.
2. Point the `edenridgegh.com` document root at `/home/<user>/edenridge/public`.
3. Create `app.edenridgegh.com` as a subdomain with its document root at
   `/home/<user>/edenridge/admin/public`.
   If the host will not allow a nested document root, symlink
   `~/public_html/app` → `admin/public`, or serve the dashboard from
   `edenridgegh.com/admin` with an Apache alias — the router does not care which.
4. Make `storage/` writable (`0755`) and confirm `public/media/` is writable.
5. Visit `https://app.edenridgegh.com/install` and complete the one-page wizard:
   environment check → URLs → your admin account → optional SMTP.
   It creates the database, runs the migrations, seeds all the reference content
   and images, then locks itself.
6. Sign in, open **Site settings**, and send a test email.

`config.php` is written by the installer at the app root with mode `0600` and is
never served. Both document roots force HTTPS and send their security headers
from `.htaccess` and PHP.

### Local development

```sh
php -S 127.0.0.1:8000 -t public public/router.php        # public site
php -S 127.0.0.1:8001 -t admin/public admin/public/router.php   # dashboard
```

The two `router.php` files exist only for PHP's built-in server; Apache uses
`.htaccess`.

---

## How the content model works

`core/Schema.php` is the single source of truth. Each of the sixteen sections
declares its fields once — type, label, hint, character limits, repeaters and
their sub-fields:

```php
['key' => 'headline', 'type' => 'textarea', 'label' => 'Headline',
 'hint' => Schema::ITALIC_HINT, 'rows' => 3, 'max' => 90],
```

From that one declaration:

- the public template reads the value,
- the dashboard renders the right control (including nested, draggable repeaters),
- the save path coerces and sanitises the input and drops anything not declared.

Adding a field to a section means adding one line here and one line in the
template. Nothing else needs to change.

Content is stored as JSON in `sections.content`, with unpublished work in
`sections.draft_content` and the last thirty published versions in
`section_revisions`.

Field types: `text`, `textarea`, `richtext`, `number`, `image`, `link`, `toggle`,
`select`, `icon`, `repeater`.

---

## Images

Uploads are validated by real MIME type (never by extension), SVGs are stripped
of scripts, event handlers and external references, and originals are stored
outside the document root. On upload, GD generates WebP derivatives at 320, 640,
960, 1440, 1920 and 2560 px (never upscaling), a 400 px JPEG fallback and a 24 px
inline blur placeholder. Every `<img>` ships `srcset`, `sizes`, intrinsic
`width`/`height` and `alt`; the hero is preloaded and marked
`fetchpriority="high"`.

Derivative filenames are content-hashed and served with a one-year immutable
cache header.

---

## Leads

`POST /enquiry` works with JavaScript disabled and is enhanced to AJAX with
inline validation. Layered spam controls: CSRF token, honeypot, minimum
time-on-form, per-IP hourly rate limit, and optional Cloudflare Turnstile.

Every submission is stored with its source page, referrer, UTM parameters, a
hashed IP (never the raw address) and user agent, then triggers a sales
notification (Reply-To set to the enquirer) and a branded auto-reply.

Mail failures never break a submission: messages queue in `mail_outbox` and are
retried on subsequent requests, with a warning banner in the dashboard until
they clear.

---

## Security

Prepared statements throughout; output escaped through `e()`; richtext passed
through an allow-list sanitiser on both save and render; CSRF on every
state-changing request; `password_hash()` with `PASSWORD_DEFAULT`; login
throttling at five failures per email or IP per fifteen minutes; rotating
selector/verifier remember-me tokens; single-use password resets.

`Content-Security-Policy` is generated from what the site actually loads — it
widens only when analytics, Turnstile or a video provider is configured. The
dashboard sends `X-Robots-Tag: noindex, nofollow` and `X-Frame-Options: DENY` on
every response.

---

## Operations

- **Cache** — the rendered home page is cached to `storage/cache`, invalidated on
  every publish, and served with `ETag` and `Last-Modified` (verified returning 304).
- **Backups** — `VACUUM INTO` plus the uploads, zipped, from Tools. A
  database-only backup runs automatically on the first request after midnight
  UTC, seven days retained. Restore is admin-only, takes a typed confirmation,
  and snapshots the current state first.
- **Logs** — `storage/logs/app.log`, rotated at 2 MB, five files kept.
- **Maintenance mode** — a branded holding page for visitors; signed-in admins pass through.

Housekeeping that would normally need cron (mail retries, trash pruning, the
nightly backup) runs in a shutdown handler on public requests, so nothing
depends on the host offering a scheduler.

---

## Measured against the PRD's quality gates

| Gate | Result |
|---|---|
| Visual parity | 17–18 of 19 page blocks match to the pixel at each of the five widths; all differences documented |
| Copy | every visible string matches the reference verbatim |
| Initial HTML | 66 KB (13 KB gzipped) — budget 100 KB |
| JavaScript | 17.5 KB raw, **4.4 KB gzipped** — budget 60 KB |
| CSS | 35 KB raw, 8.5 KB gzipped, no web fonts |
| Layout shift | every image carries intrinsic dimensions |
| Structured data | `RealEstateAgent`, `Residence`, `FAQPage`, `BreadcrumbList` |
| Contrast | AA everywhere except the brand accent on paper (documented, with a one-line fix available) |
