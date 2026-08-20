<?php
/**
 * Development-only front controller for PHP's built-in server:
 *
 *   php -S 127.0.0.1:8000 -t public public/router.php
 *
 * Apache/nginx use .htaccess (or the nginx block in it) in production; this
 * file simply lets the built-in server serve real files itself and hand
 * everything else to index.php.
 */
$path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false;
}
require __DIR__ . '/index.php';
