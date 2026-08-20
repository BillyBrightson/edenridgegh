<?php
declare(strict_types=1);

/**
 * Front controller for app.edenridgegh.com (the dashboard).
 * Document root: this directory.
 */

require dirname(__DIR__, 2) . '/core/bootstrap.php';

use Core\{Activity, Auth, Backup, Cache, Config, Content, Csrf, DB, Enquiry, Gallery, Icons,
    ImageProcessor, Logger, Mailer, Media, Migrator, Router, Schema, Seeder, Seo, Session,
    Settings, Video, View};

header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
if (Session::isHttps()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

Session::start('eden_admin');
$router = new Router();

// ======================================================================
// Installer — available only until the app is configured.
// ======================================================================
$installed = Config::installed() && DB_ready();

function DB_ready(): bool
{
    try {
        return DB::tableExists('users') && (int)DB::value('SELECT COUNT(*) FROM users') > 0;
    } catch (\Throwable) {
        return false;
    }
}

if (!$installed) {
    $router->any('/install', static fn() => require APP_ROOT . '/core/install_wizard.php');
    $router->fallback(static fn() => redirect('/install'));
    $router->dispatch();
    exit;
}

$router->get('/install', static function (): void {
    http_response_code(410);
    render('admin/installed', ['title' => 'Already installed']);
});

// ======================================================================
// Helpers
// ======================================================================

function render(string $template, array $data = []): void
{
    echo View::page('admin/layout', $template, $data);
}

function renderAuth(string $template, array $data = []): void
{
    echo View::page('admin/auth_layout', $template, $data);
}

function back(string $fallback = '/'): never
{
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $host    = parse_url($referer, PHP_URL_HOST);
    $self    = $_SERVER['HTTP_HOST'] ?? '';
    redirect($host !== null && $host === $self ? $referer : $fallback);
}

function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function query(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}

// ======================================================================
// Authentication
// ======================================================================

$router->get('/login', static function (): void {
    if (Auth::check()) {
        redirect('/');
    }
    renderAuth('admin/login', ['title' => 'Sign in']);
});

$router->post('/login', static function (): void {
    Csrf::verify();
    $email    = (string)post('email');
    $password = (string)post('password');

    if (Auth::throttled($email)) {
        Session::flash('error', 'Too many failed attempts. Please wait fifteen minutes and try again.');
        redirect('/login');
    }
    $user = Auth::attempt($email, $password, post('remember') === '1');
    if (!$user) {
        Session::flash('error', 'That email and password combination was not recognised.');
        redirect('/login');
    }
    if ((int)$user['must_reset'] === 1) {
        redirect('/account/password');
    }
    $intended = (string)Session::pull('intended', '/');
    redirect($intended !== '/login' ? $intended : '/');
});

$router->post('/logout', static function (): void {
    Csrf::verify();
    Auth::logout();
    Session::flash('info', 'You have been signed out.');
    redirect('/login');
});

$router->get('/forgot', static fn() => renderAuth('admin/forgot', ['title' => 'Reset password']));

$router->post('/forgot', static function (): void {
    Csrf::verify();
    $email = strtolower(trim((string)post('email')));
    $user  = DB::first('SELECT * FROM users WHERE lower(email) = ? AND is_active = 1', [$email]);
    if ($user) {
        $token = Auth::createResetToken((int)$user['id']);
        $link  = rtrim((string)Config::get('admin_url', ''), '/') . '/reset?token=' . urlencode($token);
        Mailer::queue(
            (string)$user['email'],
            'Reset your Eden Ridge dashboard password',
            Mailer::render('password_reset', ['user' => $user, 'link' => $link])
        );
        Activity::log((int)$user['id'], 'auth.reset_requested', 'user', (int)$user['id']);
    }
    // Always the same answer, so the form cannot be used to discover accounts.
    Session::flash('info', 'If that address belongs to an account, a reset link is on its way.');
    redirect('/login');
});

$router->get('/reset', static function (): void {
    $user = Auth::consumeResetToken(query('token'));
    if (!$user) {
        Session::flash('error', 'That reset link has expired. Please request a new one.');
        redirect('/forgot');
    }
    renderAuth('admin/reset', ['title' => 'Choose a password', 'token' => query('token')]);
});

$router->post('/reset', static function (): void {
    Csrf::verify();
    $user = Auth::consumeResetToken((string)post('token'));
    if (!$user) {
        Session::flash('error', 'That reset link has expired. Please request a new one.');
        redirect('/forgot');
    }
    $problem = Auth::passwordProblem((string)post('password'), (string)post('password_confirm'));
    if ($problem) {
        Session::flash('error', $problem);
        back('/reset?token=' . urlencode((string)post('token')));
    }
    Auth::setPassword((int)$user['id'], (string)post('password'));
    Auth::clearResetTokens((int)$user['id']);
    Auth::login($user);
    Session::flash('success', 'Your password has been updated.');
    redirect('/');
});

// Everything below requires a session. The sign-in screens above are the
// only routes that may be reached anonymously.
$publicRoutes = ['/login', '/logout', '/forgot', '/reset'];
if (!in_array(Router::currentPath(), $publicRoutes, true)) {
    Auth::requireLogin();
}

$currentUser = Auth::user();
if ($currentUser && (int)$currentUser['must_reset'] === 1
    && !in_array(Router::currentPath(), array_merge($publicRoutes, ['/account/password']), true)) {
    redirect('/account/password');
}

// ======================================================================
// Dashboard
// ======================================================================

$router->get('/', static function (): void {
    $today = gmdate('Y-m-d') . ' 00:00:00';
    $week  = gmdate('Y-m-d H:i:s', time() - 7 * 86400);
    render('admin/dashboard', [
        'title'   => 'Dashboard',
        'stats'   => [
            'new'      => Enquiry::unreadCount(),
            'today'    => Enquiry::countSince($today),
            'week'     => Enquiry::countSince($week),
            'video'    => Video::requestCount(),
        ],
        'recent'  => Enquiry::search([], 5)['rows'],
        'edits'   => Activity::recent(6),
        'drafts'  => array_filter(Content::sections('home', false), fn($s) => Content::hasDraft($s)),
    ]);
});

// ======================================================================
// Pages — the home section editor
// ======================================================================

$router->get('/pages/home', static function (): void {
    Auth::requireAbility('edit_content');
    $sections = Content::sections('home', false);
    $key      = query('section') ?: (string)($sections[0]['key'] ?? 'hero');
    $active   = null;
    foreach ($sections as $section) {
        if ($section['key'] === $key) {
            $active = $section;
        }
    }
    if (!$active) {
        redirect('/pages/home');
    }
    render('admin/section_editor', [
        'title'     => 'Home page',
        'sections'  => $sections,
        'section'   => $active,
        'schema'    => Schema::section((string)$active['key']),
        'content'   => Content::draft($active),
        'hasDraft'  => Content::hasDraft($active),
        'revisions' => Content::revisions((int)$active['id'], 10),
    ]);
});

$router->post('/pages/home/save', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $id      = (int)post('section_id');
    $section = DB::first('SELECT * FROM sections WHERE id = ?', [$id]);
    if (!$section) {
        Session::flash('error', 'That section no longer exists.');
        redirect('/pages/home');
    }
    $fields  = Schema::fields((string)$section['key']);
    $content = Schema::coerce($fields, (array)post('content', []));
    $publish = post('action') === 'publish';

    if ($publish) {
        Content::publish($id, $content, Auth::id());
        Session::flash('success', Schema::title((string)$section['key']) . ' is live on the site.');
    } else {
        Content::saveDraft($id, $content, Auth::id());
        Session::flash('info', 'Draft saved. It is not on the public site until you publish.');
    }
    redirect('/pages/home?section=' . urlencode((string)$section['key']));
});

$router->post('/pages/home/discard', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $id      = (int)post('section_id');
    $section = DB::first('SELECT * FROM sections WHERE id = ?', [$id]);
    Content::discardDraft($id);
    Session::flash('info', 'Draft discarded — the published version is unchanged.');
    redirect('/pages/home?section=' . urlencode((string)($section['key'] ?? '')));
});

$router->post('/pages/home/toggle', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $id      = (int)post('section_id');
    $section = DB::first('SELECT * FROM sections WHERE id = ?', [$id]);
    if ($section) {
        Content::setPublished($id, (int)$section['is_published'] !== 1, Auth::id());
        Session::flash('success', (int)$section['is_published'] === 1 ? 'Section hidden.' : 'Section shown.');
    }
    back('/pages/home');
});

$router->post('/pages/home/reorder', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $ids = array_filter(array_map('intval', explode(',', (string)post('order'))));
    Content::reorder($ids, Auth::id());
    json_response(['ok' => true]);
});

$router->post('/pages/home/revert', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $id      = (int)post('section_id');
    $section = DB::first('SELECT * FROM sections WHERE id = ?', [$id]);
    $ok      = Content::revert($id, (int)post('revision_id'), Auth::id());
    Session::flash($ok ? 'success' : 'error', $ok ? 'Reverted and published.' : 'That revision could not be found.');
    redirect('/pages/home?section=' . urlencode((string)($section['key'] ?? '')));
});

// ---- simple pages (privacy, terms) ----
$router->get('/pages/{slug}', static function (array $args): void {
    Auth::requireAbility('edit_content');
    $slug = (string)$args['slug'];
    if ($slug === 'home') {
        redirect('/pages/home');
    }
    $page = Content::page($slug);
    if (!$page) {
        http_response_code(404);
        render('admin/404', ['title' => 'Not found']);
        return;
    }
    render('admin/simple_page', [
        'title'   => (string)$page['title'],
        'page'    => $page,
        'section' => Content::find('body', $slug),
        'slug'    => $slug,
    ]);
});

$router->post('/pages/{slug}/save', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $slug    = (string)$args['slug'];
    $section = Content::find('body', $slug);
    if (!$section) {
        Session::flash('error', 'That page could not be found.');
        redirect('/');
    }
    $title = \Core\Sanitizer::text((string)post('title'));
    DB::update('pages', ['title' => $title, 'is_published' => post('is_published') === '1' ? 1 : 0, 'updated_at' => now()], 'slug = :slug', ['slug' => $slug]);
    Content::publish((int)$section['id'], ['body' => \Core\Sanitizer::richtext((string)post('body'))], Auth::id());
    Activity::log(Auth::id(), 'page.save', 'page', (int)$section['page_id'], ['slug' => $slug]);
    Session::flash('success', 'Page updated.');
    redirect('/pages/' . $slug);
});

// ======================================================================
// Enquiries
// ======================================================================

$router->get('/enquiries', static function (): void {
    Auth::requireAbility('view_enquiries');
    $filters = [
        'status'   => query('status'),
        'interest' => query('interest'),
        'q'        => query('q'),
        'from'     => query('from'),
        'to'       => query('to'),
        'video'    => query('video'),
        'trash'    => query('trash'),
        'sort'     => query('sort'),
    ];
    $page   = max(1, (int)query('page', '1'));
    $limit  = 25;
    $result = Enquiry::search($filters, $limit, ($page - 1) * $limit);
    render('admin/enquiries', [
        'title'    => 'Enquiries',
        'wide'     => true,
        'rows'     => $result['rows'],
        'total'    => $result['total'],
        'page'     => $page,
        'limit'    => $limit,
        'filters'  => $filters,
        'interests' => array_column(DB::all("SELECT DISTINCT interest FROM enquiries WHERE interest IS NOT NULL AND interest <> '' ORDER BY interest"), 'interest'),
    ]);
});

$router->get('/enquiries/export', static function (): void {
    Auth::requireAbility('view_enquiries');
    $filters = [
        'status' => query('status'), 'interest' => query('interest'), 'q' => query('q'),
        'from' => query('from'), 'to' => query('to'), 'video' => query('video'), 'trash' => query('trash'),
    ];
    $rows = Enquiry::exportRows($filters);
    Activity::log(Auth::id(), 'enquiry.export', 'enquiry', null, ['count' => count($rows)]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="eden-ridge-enquiries-' . gmdate('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF" . Enquiry::toCsv($rows);
});

$router->get('/enquiries/{id}', static function (array $args): void {
    Auth::requireAbility('view_enquiries');
    $enquiry = Enquiry::find((int)$args['id']);
    if (!$enquiry) {
        http_response_code(404);
        render('admin/404', ['title' => 'Not found']);
        return;
    }
    render('admin/enquiry_detail', [
        'title'   => 'Enquiry from ' . $enquiry['name'],
        'enquiry' => $enquiry,
        'notes'   => Enquiry::notes((int)$enquiry['id']),
    ]);
});

$router->post('/enquiries/{id}/status', static function (array $args): void {
    Auth::requireAbility('view_enquiries');
    Csrf::verify();
    Enquiry::setStatus((int)$args['id'], (string)post('status'), Auth::id());
    Session::flash('success', 'Status updated.');
    back('/enquiries');
});

$router->post('/enquiries/{id}/note', static function (array $args): void {
    Auth::requireAbility('view_enquiries');
    Csrf::verify();
    Enquiry::addNote((int)$args['id'], (string)post('body'), Auth::id());
    back('/enquiries/' . (int)$args['id']);
});

$router->post('/enquiries/{id}/trash', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Enquiry::trash((int)$args['id'], Auth::id());
    Session::flash('info', 'Moved to the trash. It will be deleted permanently after 30 days.');
    redirect('/enquiries');
});

$router->post('/enquiries/{id}/restore', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Enquiry::restore((int)$args['id'], Auth::id());
    Session::flash('success', 'Enquiry restored.');
    back('/enquiries');
});

// ======================================================================
// Media
// ======================================================================

$router->get('/media', static function (): void {
    Auth::requireAbility('edit_content');
    $search = query('q');
    $page   = max(1, (int)query('page', '1'));
    $limit  = 48;
    render('admin/media', [
        'title'  => 'Media library',
        'wide'   => true,
        'items'  => Media::all($search, $limit, ($page - 1) * $limit),
        'total'  => Media::count($search),
        'page'   => $page,
        'limit'  => $limit,
        'search' => $search,
    ]);
});

$router->get('/media/picker', static function (): void {
    Auth::requireAbility('edit_content');
    echo View::render('admin/partials/media_grid', [
        'items'      => Media::all(query('q'), 60),
        'selectable' => true,
    ]);
});

$router->post('/media/upload', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $file = $_FILES['file'] ?? null;
    if (!$file) {
        json_response(['ok' => false, 'error' => 'No file was received.'], 400);
    }
    [$media, $error] = Media::store($file, Auth::id());
    if ($error !== null) {
        json_response(['ok' => false, 'error' => $error], 422);
    }
    json_response(['ok' => true, 'media' => [
        'id'    => (int)$media['id'],
        'thumb' => Media::url((int)$media['id'], 640),
        'name'  => (string)$media['original_name'],
        'alt'   => (string)$media['alt'],
    ]]);
});

$router->post('/media/upload-many', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $uploaded = 0;
    $errors   = [];
    $files    = $_FILES['files'] ?? null;
    if ($files && is_array($files['name'])) {
        foreach (array_keys($files['name']) as $i) {
            [$media, $error] = Media::store([
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ], Auth::id());
            $media ? $uploaded++ : $errors[] = $error;
        }
    }
    Session::flash($uploaded ? 'success' : 'error', $uploaded
        ? $uploaded . ' file' . ($uploaded === 1 ? '' : 's') . ' uploaded.'
        : ($errors[0] ?? 'Nothing was uploaded.'));
    foreach (array_slice(array_unique(array_filter($errors)), 0, 3) as $error) {
        Session::flash('warn', $error);
    }
    redirect('/media');
});

$router->post('/media/{id}/update', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Media::update((int)$args['id'], [
        'alt'     => \Core\Sanitizer::text((string)post('alt')),
        'caption' => \Core\Sanitizer::text((string)post('caption')),
        'focal_x' => max(0, min(1, (float)post('focal_x', 0.5))),
        'focal_y' => max(0, min(1, (float)post('focal_y', 0.5))),
    ], Auth::id());
    Session::flash('success', 'Image details saved.');
    back('/media');
});

$router->post('/media/{id}/delete', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Media::delete((int)$args['id'], Auth::id());
    Session::flash('success', 'Image deleted and removed from every section that used it.');
    redirect('/media');
});

// ======================================================================
// Gallery
// ======================================================================

$router->get('/gallery', static function (): void {
    Auth::requireAbility('edit_content');
    render('admin/gallery', [
        'title'      => 'Gallery',
        'wide'       => true,
        'categories' => Gallery::categories(),
        'items'      => Gallery::items(false),
    ]);
});

$router->post('/gallery/save', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    foreach ((array)post('items', []) as $id => $data) {
        Gallery::saveItem((int)$id, (array)$data);
    }
    foreach ((array)post('categories', []) as $id => $data) {
        $label = \Core\Sanitizer::text((string)($data['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        Gallery::saveCategory((int)$id, $label, (int)($data['sort_order'] ?? 0));
    }
    $newCategory = \Core\Sanitizer::text((string)post('new_category'));
    if ($newCategory !== '') {
        Gallery::saveCategory(null, $newCategory, 99);
    }
    Cache::flush();
    Activity::log(Auth::id(), 'gallery.save', 'gallery');
    Session::flash('success', 'Gallery updated.');
    redirect('/gallery');
});

$router->post('/gallery/add', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $added = 0;
    foreach ((array)post('media_ids', []) as $mediaId) {
        if ((int)$mediaId > 0) {
            Gallery::addItem((int)$mediaId, null, '');
            $added++;
        }
    }
    Cache::flush();
    Session::flash($added ? 'success' : 'error', $added ? $added . ' image(s) added to the gallery.' : 'Choose at least one image.');
    redirect('/gallery');
});

$router->post('/gallery/{id}/delete', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Gallery::deleteItem((int)$args['id']);
    Cache::flush();
    Session::flash('success', 'Removed from the gallery. The image is still in the media library.');
    redirect('/gallery');
});

$router->post('/gallery/category/{id}/delete', static function (array $args): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Gallery::deleteCategory((int)$args['id']);
    Cache::flush();
    Session::flash('success', 'Filter removed.');
    redirect('/gallery');
});

// ======================================================================
// Video tour
// ======================================================================

$router->get('/video', static function (): void {
    Auth::requireAbility('edit_content');
    render('admin/video', [
        'title'    => 'Video tour',
        'requests' => Video::requests(50),
        'count'    => Video::requestCount(),
    ]);
});

$router->post('/video', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Settings::setMany([
        'video_provider'        => (string)post('video_provider'),
        'video_url'             => \Core\Sanitizer::text((string)post('video_url')),
        'video_email_body'      => (string)post('video_email_body'),
        'video_poster_media_id' => (string)post('video_poster_media_id'),
    ]);
    Settings::set('video_gate_enabled', post('video_gate_enabled') === '1', 'bool');
    Cache::flush();
    Activity::log(Auth::id(), 'settings.save', null, null, ['group' => 'video']);
    Session::flash('success', 'Video settings saved.');
    redirect('/video');
});

// ======================================================================
// Appearance / Settings / SEO
// ======================================================================

$router->get('/appearance', static fn() => appearanceScreen());

function appearanceScreen(): void
{
    Auth::requireAbility('edit_content');
    render('admin/appearance', ['title' => 'Appearance']);
}

$router->post('/appearance', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    Settings::setMany([
        'logo_media_id'    => (string)post('logo_media_id'),
        'favicon_media_id' => (string)post('favicon_media_id'),
        'site_name'        => \Core\Sanitizer::text((string)post('site_name')),
        'tagline'          => \Core\Sanitizer::text((string)post('tagline')),
    ]);
    // The header logo lives in the section document so it can be previewed there too.
    $header = Content::find('header');
    if ($header) {
        $content = Content::decode((string)$header['content']);
        $content['logo_image'] = post('logo_media_id') !== '' ? (int)post('logo_media_id') : null;
        Content::publish((int)$header['id'], $content, Auth::id(), 'Logo changed from Appearance');
    }
    Cache::flush();
    Activity::log(Auth::id(), 'settings.save', null, null, ['group' => 'appearance']);
    Session::flash('success', 'Appearance updated.');
    redirect('/appearance');
});

$router->get('/settings', static function (): void {
    Auth::requireAbility('edit_content');
    render('admin/settings', ['title' => 'Site settings']);
});

$router->post('/settings', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $strings = [
        'contact_phone', 'contact_whatsapp', 'contact_email', 'contact_address',
        'notification_emails', 'autoreply_subject', 'autoreply_body',
        'smtp_transport', 'smtp_host', 'smtp_encryption', 'smtp_username',
        'smtp_from_email', 'smtp_from_name', 'ga4_id', 'meta_pixel_id',
        'cookie_consent_text', 'turnstile_site_key', 'turnstile_secret',
        'maintenance_message', 'whatsapp_webhook_url', 'robots_txt',
    ];
    foreach ($strings as $key) {
        Settings::set($key, (string)post($key));
    }
    foreach (array_keys(Icons::socialOptions()) as $platform) {
        Settings::set('social_' . $platform . '_handle', \Core\Sanitizer::text((string)post('social_' . $platform . '_handle')));
        Settings::set('social_' . $platform . '_url', \Core\Schema::safeLink((string)post('social_' . $platform . '_url')));
    }
    Settings::set('smtp_port', (int)post('smtp_port', 587), 'int');
    if ((string)post('smtp_password') !== '') {
        Settings::set('smtp_password', (string)post('smtp_password'));
    }
    foreach (['autoreply_enabled', 'cookie_consent_enabled', 'maintenance_mode', 'cache_enabled', 'auto_backup_enabled'] as $flag) {
        Settings::set($flag, post($flag) === '1', 'bool');
    }
    Cache::flush();
    Activity::log(Auth::id(), 'settings.save', null, null, ['group' => 'site']);
    Session::flash('success', 'Settings saved.');
    redirect('/settings');
});

$router->post('/settings/test-email', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $to = (string)post('test_email');
    try {
        Mailer::send($to, 'Eden Ridge test email', '<p>This is a test from the Eden Ridge dashboard. If you can read it, SMTP is configured correctly.</p>');
        Session::flash('success', 'Test email sent to ' . $to . '.');
    } catch (\Throwable $ex) {
        Session::flash('error', 'Test email failed: ' . $ex->getMessage());
    }
    redirect('/settings');
});

$router->get('/seo', static function (): void {
    Auth::requireAbility('edit_content');
    $slug = query('page') ?: 'home';
    render('admin/seo', [
        'title' => 'SEO',
        'slug'  => $slug,
        'meta'  => Seo::meta($slug),
    ]);
});

$router->post('/seo', static function (): void {
    Auth::requireAbility('edit_content');
    Csrf::verify();
    $slug = (string)post('page_slug', 'home');
    Seo::save($slug, $_POST, Auth::id());
    Session::flash('success', 'SEO settings saved.');
    redirect('/seo?page=' . urlencode($slug));
});

// ======================================================================
// Users & account
// ======================================================================

$router->get('/users', static function (): void {
    Auth::requireAbility('manage_users');
    render('admin/users', [
        'title' => 'Users',
        'users' => DB::all('SELECT * FROM users ORDER BY created_at ASC'),
    ]);
});

$router->post('/users', static function (): void {
    Auth::requireAbility('manage_users');
    Csrf::verify();
    $email = strtolower(trim((string)post('email')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Session::flash('error', 'Enter a valid email address.');
        redirect('/users');
    }
    if (DB::first('SELECT id FROM users WHERE lower(email) = ?', [$email])) {
        Session::flash('error', 'A user with that email already exists.');
        redirect('/users');
    }
    $password = bin2hex(random_bytes(8));
    $id = DB::insert('users', [
        'name'          => \Core\Sanitizer::text((string)post('name')),
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role'          => in_array((string)post('role'), ['admin', 'editor', 'viewer'], true) ? (string)post('role') : 'editor',
        'must_reset'    => 1,
        'is_active'     => 1,
        'created_at'    => now(),
    ]);
    $token = Auth::createResetToken($id);
    $link  = rtrim((string)Config::get('admin_url', ''), '/') . '/reset?token=' . urlencode($token);
    Mailer::queue($email, 'Your Eden Ridge dashboard account', Mailer::render('password_reset', [
        'user' => ['name' => post('name')],
        'link' => $link,
    ]));
    Activity::log(Auth::id(), 'user.create', 'user', $id, ['email' => $email]);
    Session::flash('success', 'User created. They have been emailed a link to set their password.');
    Session::flash('info', 'Fallback password if the email does not arrive: ' . $password);
    redirect('/users');
});

$router->post('/users/{id}/update', static function (array $args): void {
    Auth::requireAbility('manage_users');
    Csrf::verify();
    $id = (int)$args['id'];
    $role = in_array((string)post('role'), ['admin', 'editor', 'viewer'], true) ? (string)post('role') : 'editor';
    $active = post('is_active') === '1' ? 1 : 0;
    if ($id === Auth::id() && ($role !== 'admin' || $active === 0)) {
        Session::flash('error', 'You cannot remove your own admin access.');
        redirect('/users');
    }
    DB::update('users', [
        'name'      => \Core\Sanitizer::text((string)post('name')),
        'role'      => $role,
        'is_active' => $active,
    ], 'id = :id', ['id' => $id]);
    Activity::log(Auth::id(), 'user.update', 'user', $id);
    Session::flash('success', 'User updated.');
    redirect('/users');
});

$router->post('/users/{id}/delete', static function (array $args): void {
    Auth::requireAbility('manage_users');
    Csrf::verify();
    $id = (int)$args['id'];
    if ($id === Auth::id()) {
        Session::flash('error', 'You cannot delete your own account.');
        redirect('/users');
    }
    if ((int)DB::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1") <= 1
        && (string)DB::value('SELECT role FROM users WHERE id = ?', [$id]) === 'admin') {
        Session::flash('error', 'There must always be at least one active administrator.');
        redirect('/users');
    }
    DB::delete('users', 'id = ?', [$id]);
    Activity::log(Auth::id(), 'user.delete', 'user', $id);
    Session::flash('success', 'User deleted.');
    redirect('/users');
});

$router->get('/account', static fn() => render('admin/account', ['title' => 'Your account']));

$router->post('/account', static function (): void {
    Csrf::verify();
    DB::update('users', ['name' => \Core\Sanitizer::text((string)post('name'))], 'id = :id', ['id' => Auth::id()]);
    Session::flash('success', 'Your details were updated.');
    redirect('/account');
});

$router->get('/account/password', static fn() => render('admin/password', [
    'title' => 'Change password',
    'forced' => (int)(Auth::user()['must_reset'] ?? 0) === 1,
]));

$router->post('/account/password', static function (): void {
    Csrf::verify();
    $user = Auth::user();
    $forced = (int)($user['must_reset'] ?? 0) === 1;
    if (!$forced && !password_verify((string)post('current_password'), (string)$user['password_hash'])) {
        Session::flash('error', 'Your current password was not correct.');
        redirect('/account/password');
    }
    $problem = Auth::passwordProblem((string)post('password'), (string)post('password_confirm'));
    if ($problem) {
        Session::flash('error', $problem);
        redirect('/account/password');
    }
    Auth::setPassword(Auth::id(), (string)post('password'));
    Session::flash('success', 'Password updated.');
    redirect('/');
});

// ======================================================================
// Activity & tools
// ======================================================================

$router->get('/activity', static function (): void {
    Auth::requireAbility('view_activity');
    $page = max(1, (int)query('page', '1'));
    render('admin/activity', [
        'title'   => 'Activity log',
        'wide'    => true,
        'entries' => Activity::recent(50, ($page - 1) * 50),
        'total'   => Activity::count(),
        'page'    => $page,
        'limit'   => 50,
    ]);
});

$router->get('/tools', static function (): void {
    Auth::requireAbility('manage_tools');
    render('admin/tools', [
        'title'    => 'Tools',
        'backups'  => Backup::list(),
        'cacheSize' => Cache::size(),
        'version'  => Migrator::currentVersion(),
        'mailFailed' => Mailer::failed(10),
    ]);
});

$router->post('/tools/backup', static function (): void {
    Auth::requireAbility('manage_tools');
    Csrf::verify();
    try {
        $path = Backup::create(post('include_uploads') === '1');
        Activity::log(Auth::id(), 'tools.backup', null, null, ['file' => basename($path)]);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    } catch (\Throwable $ex) {
        Session::flash('error', 'Backup failed: ' . $ex->getMessage());
        redirect('/tools');
    }
});

$router->post('/tools/restore', static function (): void {
    Auth::requireAbility('manage_tools');
    Csrf::verify();
    if (post('confirm') !== 'RESTORE') {
        Session::flash('error', 'Type RESTORE to confirm — this replaces all current content.');
        redirect('/tools');
    }
    $file = $_FILES['archive'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        Session::flash('error', Media::uploadErrorMessage((int)($file['error'] ?? 4)));
        redirect('/tools');
    }
    try {
        Backup::restore((string)$file['tmp_name'], Auth::id());
        Session::flash('success', 'Backup restored. Rebuilding image derivatives…');
        Backup::rebuildDerivatives();
        redirect('/tools');
    } catch (\Throwable $ex) {
        Session::flash('error', 'Restore failed: ' . $ex->getMessage());
        redirect('/tools');
    }
});

$router->post('/tools/cache', static function (): void {
    Auth::requireAbility('manage_tools');
    Csrf::verify();
    Cache::flush();
    Activity::log(Auth::id(), 'tools.cache_clear');
    Session::flash('success', 'Cache cleared — the public site will rebuild on the next visit.');
    redirect('/tools');
});

$router->post('/tools/derivatives', static function (): void {
    Auth::requireAbility('manage_tools');
    Csrf::verify();
    $count = Backup::rebuildDerivatives();
    Session::flash('success', 'Regenerated derivatives for ' . $count . ' image(s).');
    redirect('/tools');
});

$router->post('/tools/mail-retry', static function (): void {
    Auth::requireAbility('manage_tools');
    Csrf::verify();
    Mailer::drain(20);
    Session::flash('info', 'Retried the queued messages.');
    redirect('/tools');
});

$router->fallback(static function (): void {
    http_response_code(404);
    render('admin/404', ['title' => 'Not found']);
});

$router->dispatch();
