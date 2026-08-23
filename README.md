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
5. Visit `https://app.edenridgegh.com/install` — or `https://edenridgegh.com/install`, since the
   installer answers on either host until it is done — and complete the one-page wizard:
   environment check → URLs → your admin account → optional SMTP.
   It creates the database, runs the migrations, seeds all the reference content
   and images, then locks itself.
6. Sign in, open **Site settings**, and send a test email.

`config.php` is written by the installer at the app root with mode `0600` and is
never served. Both document roots force HTTPS and send their security headers
from `.htaccess` and PHP.

### Hosts that serve each domain from its own folder (DreamHost and similar)

Some hosts give every domain a directory in the account home — `~/example.com/`
— and serve that directory. The home directory itself is not a document root,
which makes this the *best* layout for this app: the application root sits
beside the sites rather than inside one.

```
/home/<user>/
├── edenridge/              <- app root; no domain points here
│   ├── core/  views/  content/
│   └── storage/            <- database, uploads, cache, logs
├── edenridgegh.com/        <- contents of public/
└── app.edenridgegh.com/    <- contents of admin/public/
```

Both front controllers then need the same one-line edit:

```php
require dirname(__DIR__) . '/edenridge/core/bootstrap.php';
```

Delete the two `router.php` files (built-in server only) and open
`/install` on either host. The installer records the document root paths in
`config.php`, so the dashboard knows where to write image derivatives even
though it runs on a different hostname.

`deploy/app-root.htaccess.txt` is **not** needed here — the app root is already
outside every document root.

### Deploying with FTP only

FTP is enough to move the files, but it cannot create a subdomain or set a
document root — those are DNS and vhost operations. What you need from the
control panel (or a one-line request to the host) is exactly one thing:
**`app.edenridgegh.com` must exist and resolve to a folder you can reach over
FTP.** On cPanel that folder defaults to `public_html/app`. Everything else can
be done with an FTP client.

Upload into this shape:

```
public_html/                 <- edenridgegh.com
├── (contents of public/)
├── app/                     <- app.edenridgegh.com
│   └── (contents of admin/public/)
└── _eden/                   <- app root: NOT a document root
    ├── core/  views/  content/
    ├── storage/             <- chmod 755, plus storage/uploads
    └── .htaccess            <- deploy/app-root.htaccess.txt, renamed
```

Then edit two lines so the front controllers can find the relocated app root:

| File | Change |
|---|---|
| `public_html/index.php` | `require __DIR__ . '/_eden/core/bootstrap.php';` |
| `public_html/app/index.php` | `require dirname(__DIR__) . '/_eden/core/bootstrap.php';` |

Delete both `router.php` files (they are only for PHP's built-in server), then
visit `https://app.edenridgegh.com/install`.

Because the app root sits inside `public_html` here, its own deny-all
`.htaccess` is the only thing keeping `config.php` — which holds the app key and
the SMTP password — from being served if PHP ever stops executing. Renaming
`deploy/app-root.htaccess.txt` into place is not optional in this layout.
Verified against Apache 2.4: with the file present every path under `_eden/`
returns 403 while both sites serve normally.

**Do not** put that file in the app root of the standard two-docroot layout.
There the app root is above both document roots, where Apache applies
`AllowOverride None`, and any directive in an `.htaccess` at that position makes
Apache return 500 for the whole site.

**Without a subdomain at all**, the dashboard would have to run from a
subdirectory such as `edenridgegh.com/admin`. That does not work today: roughly
forty internal links, form actions and `fetch()` calls are root-relative, so
they would resolve above the subdirectory. Supporting it needs a base-path
change to the router and the templates.

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
- **Maintenance mode** — a branded holding page for visitors, with a secret
  preview link (Site settings) that lets reviewers see the real site. The
  dashboard is on a different hostname, so its session cookie cannot reach the
  public site; a token in the URL is what actually works.

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
