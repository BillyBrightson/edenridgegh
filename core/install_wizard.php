<?php
declare(strict_types=1);

/**
 * First-boot installer. Reachable at /install on the dashboard host only
 * while the app is unconfigured; afterwards the route returns 410 Gone.
 */

use Core\{Config, Csrf, DB, Migrator, Seeder, Session, Settings, View};

$checks = [
    'PHP 8.1 or newer'        => [PHP_VERSION_ID >= 80100, PHP_VERSION],
    'PDO SQLite driver'       => [extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'available' : 'missing'],
    'GD image library'        => [extension_loaded('gd'), extension_loaded('gd') ? 'available' : 'missing'],
    'WebP support'            => [function_exists('imagewebp'), function_exists('imagewebp') ? 'available' : 'JPEG fallback only'],
    'fileinfo extension'      => [extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'available' : 'missing'],
    'mbstring extension'      => [extension_loaded('mbstring'), extension_loaded('mbstring') ? 'available' : 'missing'],
    'ZipArchive (backups)'    => [class_exists(ZipArchive::class), class_exists(ZipArchive::class) ? 'available' : 'backups disabled'],
    'storage/ writable'       => [is_writable(APP_ROOT . '/storage'), APP_ROOT . '/storage'],
    'media folder writable'   => [is_writable(PUBLIC_PATH . '/media') || @mkdir(PUBLIC_PATH . '/media', 0755, true), PUBLIC_PATH . '/media'],
    'app root writable'       => [is_writable(APP_ROOT), 'needed once, to write config.php'],
];
$required = ['PHP 8.1 or newer', 'PDO SQLite driver', 'GD image library', 'fileinfo extension', 'mbstring extension', 'storage/ writable', 'media folder writable', 'app root writable'];
$blocked  = false;
foreach ($required as $key) {
    if (!$checks[$key][0]) {
        $blocked = true;
    }
}

$errors = [];
$done   = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$blocked) {
    Csrf::verify();
    $siteUrl  = rtrim(trim((string)($_POST['site_url'] ?? '')), '/');
    $adminUrl = rtrim(trim((string)($_POST['admin_url'] ?? '')), '/');
    $name     = trim((string)($_POST['admin_name'] ?? ''));
    $email    = strtolower(trim((string)($_POST['admin_email'] ?? '')));
    $password = (string)($_POST['admin_password'] ?? '');
    $confirm  = (string)($_POST['admin_password_confirm'] ?? '');
    // Where the public site's document root lives on disk. Auto-detected when
    // the installer is opened on the public host; typed in otherwise, because
    // image derivatives are written there and the path is not guessable.
    $publicPath = rtrim(trim((string)($_POST['public_path'] ?? '')), '/') ?: PUBLIC_PATH;

    if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter the full public site URL, including https://';
    }
    if (!filter_var($adminUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter the full dashboard URL, including https://';
    }
    if ($name === '') {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!is_dir($publicPath)) {
        $errors[] = 'The public site directory does not exist: ' . $publicPath;
    }
    if (strlen($password) < 10) {
        $errors[] = 'Choose a password of at least 10 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        try {
            Config::write([
                'site_url'  => $siteUrl,
                'admin_url' => $adminUrl,
                'env'       => 'production',
                'debug'     => false,
                'app_key'   => bin2hex(random_bytes(32)),
                'db_path'   => APP_ROOT . '/storage/db/edenridge.sqlite',
                'public_path' => $publicPath,
                'admin_public_path' => defined('ADMIN_PUBLIC_ROOT') ? ADMIN_PUBLIC_ROOT : APP_ROOT . '/admin/public',
                'mail'      => [
                    'transport'  => 'smtp',
                    'host'       => trim((string)($_POST['smtp_host'] ?? '')),
                    'port'       => (int)($_POST['smtp_port'] ?? 587),
                    'encryption' => (string)($_POST['smtp_encryption'] ?? 'tls'),
                    'username'   => trim((string)($_POST['smtp_username'] ?? '')),
                    'password'   => (string)($_POST['smtp_password'] ?? ''),
                    'from_email' => trim((string)($_POST['smtp_from_email'] ?? '')) ?: 'no-reply@' . (parse_url($siteUrl, PHP_URL_HOST) ?: 'edenridgegh.com'),
                    'from_name'  => 'Eden Ridge',
                ],
            ]);

            Migrator::migrate();
            $counts = Seeder::run(true);
            Seeder::createAdmin($name, $email, $password);
            Settings::set('notification_emails', $email);
            Settings::set('smtp_host', trim((string)($_POST['smtp_host'] ?? '')));
            Settings::set('smtp_port', (int)($_POST['smtp_port'] ?? 587), 'int');
            Settings::set('smtp_encryption', (string)($_POST['smtp_encryption'] ?? 'tls'));
            Settings::set('smtp_username', trim((string)($_POST['smtp_username'] ?? '')));
            if (($_POST['smtp_password'] ?? '') !== '') {
                Settings::set('smtp_password', (string)$_POST['smtp_password']);
            }
            $done = true;
        } catch (\Throwable $ex) {
            \Core\Logger::error('Install failed', ['error' => $ex->getMessage()]);
            $errors[] = 'Installation failed: ' . $ex->getMessage();
        }
    }
}

echo View::page('admin/auth_layout', 'admin/install', [
    'title'   => 'Install Eden Ridge',
    'checks'  => $checks,
    'blocked' => $blocked,
    'errors'  => $errors,
    'done'    => $done,
    'counts'  => $counts ?? [],
]);
