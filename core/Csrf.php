<?php
declare(strict_types=1);

namespace Core;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /**
     * The public home page is cached as a whole file, so its forms cannot
     * carry a baked-in token — every visitor would receive the token of
     * whoever warmed the cache. Public templates emit this placeholder and
     * hydrate() swaps in the real per-session token on the way out.
     */
    public const PLACEHOLDER = '{{__csrf_token__}}';

    public static function deferredField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::PLACEHOLDER . '">';
    }

    public static function hydrate(string $html): string
    {
        if (!str_contains($html, self::PLACEHOLDER)) {
            return $html;
        }
        return str_replace(self::PLACEHOLDER, e(self::token()), $html);
    }

    public static function check(?string $token = null): bool
    {
        $token ??= (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $known = $_SESSION['_csrf'] ?? '';
        return $known !== '' && $token !== '' && hash_equals((string)$known, $token);
    }

    /** Abort the request unless the CSRF token is valid. */
    public static function verify(): void
    {
        if (self::check()) {
            return;
        }
        Logger::error('CSRF check failed', ['path' => Router::currentPath()]);
        if (wants_json()) {
            json_response(['ok' => false, 'error' => 'Your session expired. Please refresh and try again.'], 419);
        }
        http_response_code(419);
        echo 'Your session expired. Please go back, refresh the page and try again.';
        exit;
    }
}
