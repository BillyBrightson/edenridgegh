<?php
declare(strict_types=1);

/**
 * Shared bootstrap for both document roots.
 * Defines APP_ROOT, registers the autoloader, loads config and error handling.
 */

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/core/helpers.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Core\\')) {
        return;
    }
    $file = APP_ROOT . '/core/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

\Core\Config::load();



mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

$debug = \Core\Config::isDebug();
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    // Deprecations and notices are logged, never fatal — a PHP upgrade on the
    // host must not take the site down.
    if ($no & (E_DEPRECATED | E_USER_DEPRECATED | E_NOTICE | E_USER_NOTICE)) {
        \Core\Logger::write('notice', $str, ['file' => $file . ':' . $line]);
        return true;
    }
    throw new \ErrorException($str, 0, $no, $file, $line);
});

set_exception_handler(static function (\Throwable $ex): void {
    \Core\Logger::error($ex->getMessage(), [
        'type' => $ex::class,
        'file' => $ex->getFile() . ':' . $ex->getLine(),
    ]);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (\Core\Config::isDebug()) {
        echo '<pre style="padding:20px;font:13px/1.5 monospace">';
        echo e($ex::class . ': ' . $ex->getMessage()) . "\n";
        echo e($ex->getFile() . ':' . $ex->getLine()) . "\n\n";
        echo e($ex->getTraceAsString());
        echo '</pre>';
        return;
    }
    if (\Core\View::exists('pages/error')) {
        echo \Core\View::render('pages/error', ['code' => 500, 'title' => 'Something went wrong']);
    } else {
        echo 'Server error';
    }
});

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        \Core\Logger::error($err['message'], ['file' => $err['file'] . ':' . $err['line']]);
    }
});
