<?php
declare(strict_types=1);

namespace Core;

/**
 * Rendered-page cache. Files land in storage/cache and are wiped whenever
 * anything is published, so the client never sees a stale page.
 */
final class Cache
{
    private static function dir(): string
    {
        $dir = APP_ROOT . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function enabled(): bool
    {
        if (Config::isDebug()) {
            return false;
        }
        return Settings::bool('cache_enabled', true) && !Settings::bool('maintenance_mode', false);
    }

    private static function file(string $key): string
    {
        return self::dir() . '/page-' . hash('sha256', $key) . '.html';
    }

    public static function get(string $key): ?string
    {
        if (!self::enabled()) {
            return null;
        }
        $file = self::file($key);
        if (!is_file($file)) {
            return null;
        }
        $html = (string)file_get_contents($file);
        return $html !== '' ? $html : null;
    }

    public static function put(string $key, string $html): void
    {
        if (!self::enabled()) {
            return;
        }
        @file_put_contents(self::file($key), $html, LOCK_EX);
    }

    public static function flush(): void
    {
        foreach (glob(self::dir() . '/page-*.html') ?: [] as $file) {
            @unlink($file);
        }
        Content::flushCache();
        Settings::flush();
    }

    /**
     * Emit ETag / Last-Modified and short-circuit on a conditional request.
     * Returns true when a 304 was sent and the caller should stop.
     */
    public static function conditional(string $html): bool
    {
        $etag = '"' . substr(hash('sha256', $html), 0, 32) . '"';
        $last = gmdate('D, d M Y H:i:s', self::lastPublishTime()) . ' GMT';
        header('ETag: ' . $etag);
        header('Last-Modified: ' . $last);
        header('Cache-Control: public, max-age=0, must-revalidate');

        $ifNone = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        $ifMod  = trim((string)($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? ''));
        if (($ifNone !== '' && $ifNone === $etag) || ($ifMod !== '' && $ifMod === $last)) {
            http_response_code(304);
            return true;
        }
        return false;
    }

    public static function lastPublishTime(): int
    {
        $updated = (string)(DB::value('SELECT MAX(updated_at) FROM sections') ?? now());
        return strtotime($updated . ' UTC') ?: time();
    }

    public static function size(): int
    {
        $bytes = 0;
        foreach (glob(self::dir() . '/*') ?: [] as $file) {
            $bytes += is_file($file) ? (int)filesize($file) : 0;
        }
        return $bytes;
    }
}
