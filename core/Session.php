<?php
declare(strict_types=1);

namespace Core;

final class Session
{
    private const IDLE_TIMEOUT     = 7200;  // 2 hours
    private const ABSOLUTE_TIMEOUT = 43200; // 12 hours

    public static function start(string $name = 'eden_admin'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = self::isHttps();
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $now = time();
        if (isset($_SESSION['_started_at'])) {
            $idleDead = ($now - (int)($_SESSION['_last_seen'] ?? $now)) > self::IDLE_TIMEOUT;
            $absDead  = ($now - (int)$_SESSION['_started_at']) > self::ABSOLUTE_TIMEOUT;
            if ($idleDead || $absDead) {
                self::destroy();
                session_start();
                $_SESSION['_started_at'] = $now;
                $_SESSION['expired']     = true;
            }
        } else {
            $_SESSION['_started_at'] = $now;
        }
        $_SESSION['_last_seen'] = $now;
    }

    public static function isHttps(): bool
    {
        return (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $v;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_started_at'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Queue a one-shot flash message. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function flashes(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }
}
