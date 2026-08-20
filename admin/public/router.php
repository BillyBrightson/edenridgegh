<?php
/**
 * Development-only front controller for PHP's built-in server:
 *
 *   php -S 127.0.0.1:8001 -t admin/public admin/public/router.php
 */
$path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false;
}
require __DIR__ . '/index.php';
