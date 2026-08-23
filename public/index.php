<?php
declare(strict_types=1);

/**
 * Front controller for edenridgegh.com.
 * Document root: this directory. Everything else lives above it.
 */

// Tell the app where this document root lives. On shared hosting the two
// docroots are often siblings of the app root rather than folders inside it.
define('PUBLIC_ROOT', __DIR__);

require dirname(__DIR__) . '/core/bootstrap.php';

use Core\{Cache, Config, Content, Csrf, Enquiry, Logger, Mailer, Router, Schema, Seo, Session, Settings, Video, View};

if (!Config::installed()) {
    // The dashboard lives on a subdomain, and a subdomain has to be created in
    // the hosting panel — which can lag behind the main site going up. So the
    // installer answers on either host while the app is unconfigured, and
    // disappears from both the moment config.php exists.
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    Session::start('eden_public');

    $setup = new Router();
    $setup->any('/install', static fn() => require APP_ROOT . '/core/install_wizard.php');
    $setup->fallback(static function (): void {
        http_response_code(503);
        header('Retry-After: 3600');
        echo View::render('pages/error', [
            'code'    => 503,
            'title'   => 'Not installed yet',
            'message' => 'Open /install to finish setting up this site.',
        ]);
    });
    $setup->dispatch();
    exit;
}

// ---------------------------------------------------------------- security
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');
if (Session::isHttps()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header('Content-Security-Policy: ' . csp());

/** Content Security Policy, widened only for what the site actually loads. */
function csp(): string
{
    $script = ["'self'", "'unsafe-inline'"];
    $frame  = ["'self'"];
    $img    = ["'self'", 'data:'];
    $connect = ["'self'"];

    if (Settings::get('ga4_id', '') !== '') {
        $script[] = 'https://www.googletagmanager.com';
        $connect[] = 'https://www.google-analytics.com';
        $img[] = 'https://www.google-analytics.com';
    }
    if (Settings::get('meta_pixel_id', '') !== '') {
        $script[] = 'https://connect.facebook.net';
        $img[] = 'https://www.facebook.com';
        $connect[] = 'https://www.facebook.com';
    }
    if (Settings::get('turnstile_site_key', '') !== '') {
        $script[] = 'https://challenges.cloudflare.com';
        $frame[] = 'https://challenges.cloudflare.com';
    }
    $provider = (string)Settings::get('video_provider', '');
    $frame = array_merge($frame, match ($provider) {
        'youtube' => ['https://www.youtube-nocookie.com', 'https://www.youtube.com'],
        'vimeo'   => ['https://player.vimeo.com'],
        'bunny'   => ['https://iframe.mediadelivery.net'],
        default   => [],
    });
    if (Content::get('location')['map_type'] === 'iframe') {
        $frame[] = 'https://www.google.com';
    }
    $img[] = 'https://*.ytimg.com';

    return implode('; ', [
        "default-src 'self'",
        'script-src ' . implode(' ', array_unique($script)),
        "style-src 'self' 'unsafe-inline'",
        'img-src ' . implode(' ', array_unique($img)),
        "font-src 'self'",
        'connect-src ' . implode(' ', array_unique($connect)),
        'frame-src ' . implode(' ', array_unique($frame)),
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ]);
}

// ------------------------------------------------------- maintenance mode
Session::start('eden_public');
$path = Router::currentPath();
if (Settings::bool('maintenance_mode', false) && !str_starts_with($path, '/media')) {
    http_response_code(503);
    header('Retry-After: 3600');
    echo View::render('pages/maintenance');
    exit;
}

// Housekeeping that would otherwise need a cron: retry queued mail,
// prune the enquiry trash and take the nightly backup.
register_shutdown_function(static function (): void {
    try {
        Mailer::drain(2);
        if (random_int(1, 50) === 1) {
            Enquiry::purgeOldTrash();
            \Core\Backup::maybeNightly();
        }
    } catch (\Throwable $ex) {
        Logger::error('Background housekeeping failed', ['error' => $ex->getMessage()]);
    }
});

$router = new Router();

// ------------------------------------------------------------------ home
$router->get('/', static function (): void {
    $cacheKey = 'home';
    $html = Cache::get($cacheKey);
    if ($html === null) {
        $sections = Content::sections('home');
        $html = View::page('public', 'pages/home', [
            'sections' => $sections,
            'slug'     => 'home',
            'path'     => '/',
            'jsonld'   => Seo::jsonLd(),
        ]);
        Cache::put($cacheKey, $html);
    }
    if (Cache::conditional($html)) {
        return;
    }
    echo Csrf::hydrate($html);
});

// --------------------------------------------------- privacy / terms pages
$router->get('/privacy', static fn() => renderSimplePage('privacy'));
$router->get('/terms', static fn() => renderSimplePage('terms'));

function renderSimplePage(string $slug): void
{
    $page = Content::simplePage($slug);
    if (!$page || (int)$page['is_published'] !== 1) {
        notFound();
        return;
    }
    $html = View::page('public', 'pages/simple', [
        'page'   => $page,
        'slug'   => $slug,
        'path'   => '/' . $slug,
        'header' => renderSection('header'),
        'footer' => renderSection('footer'),
        'jsonld' => Seo::breadcrumb((string)$page['title'], '/' . $slug),
    ]);
    if (Cache::conditional($html)) {
        return;
    }
    echo Csrf::hydrate($html);
}

function renderSection(string $key): string
{
    $section = Content::find($key);
    if (!$section || (int)$section['is_published'] !== 1) {
        return '';
    }
    return View::render('sections/' . $key, [
        'c'       => Content::get($key),
        'section' => $section,
    ]);
}

// ------------------------------------------------------------- thank you
$router->get('/thank-you', static function (): void {
    $message = (string)(Session::pull('enquiry_message')
        ?: Content::get('enquiry')['success_message']
        ?: 'Your enquiry has been received.');
    echo View::page('public', 'pages/thankyou', [
        'slug'    => 'home',
        'path'    => '/thank-you',
        'message' => $message,
        'header'  => renderSection('header'),
        'footer'  => renderSection('footer'),
    ]);
});

// --------------------------------------------------------- enquiry intake
$router->post('/enquiry', static function (): void {
    Csrf::verify();
    $content = Content::get('enquiry');
    $options = array_values(array_filter(array_map(
        static fn(array $row) => (string)($row['label'] ?? ''),
        rows($content, 'interest_options')
    )));

    $spam = Enquiry::spamProblem($_POST);
    if ($spam !== null) {
        respond(['ok' => false, 'error' => $spam], 422, $spam);
        return;
    }

    [$data, $errors] = Enquiry::validate($_POST, $options);
    if ($errors) {
        respond(['ok' => false, 'errors' => $errors], 422, reset($errors));
        return;
    }

    $videoRequest = ($_POST['video_request'] ?? '') === '1';
    $data['source_page'] = (string)($_POST['source_page'] ?? '/');
    $id = Enquiry::create($data, $videoRequest);
    Enquiry::notify($id);
    if ($videoRequest) {
        Video::recordRequest($id);
    }

    $message = (string)($content['success_message'] ?: 'Thank you — your enquiry has been received.');
    respond([
        'ok'        => true,
        'message'   => $message,
        'interest'  => $data['interest'],
        'video_url' => $videoRequest ? Video::embedUrl() : '',
    ], 200, $message);
});

/** Answer with JSON for the enhanced form, or a redirect for a plain POST. */
function respond(array $payload, int $status, string $flashMessage): void
{
    if (wants_json()) {
        json_response($payload, $status);
    }
    Session::set('enquiry_message', $flashMessage);
    if (!empty($payload['ok'])) {
        redirect('/thank-you');
    }
    Session::flash('error', $flashMessage);
    redirect('/#contact');
}

// -------------------------------------------------------- generated files
$router->get('/sitemap.xml', static function (): void {
    header('Content-Type: application/xml; charset=utf-8');
    echo Seo::sitemap();
});

$router->get('/robots.txt', static function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo Seo::robots();
});

$router->get('/health', static function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ok\n";
});

$router->fallback(static fn() => notFound());

function notFound(): void
{
    http_response_code(404);
    echo View::render('pages/error', ['code' => 404, 'title' => 'Page not found']);
}

$router->dispatch();
