<?php
declare(strict_types=1);

namespace Core;

final class Logger
{
    private const MAX_BYTES = 2097152; // 2 MB before rotation

    public static function write(string $level, string $message, array $context = []): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/app.log';
        if (is_file($file) && filesize($file) > self::MAX_BYTES) {
            @rename($file, $dir . '/app-' . gmdate('Ymd-His') . '.log');
            self::prune($dir);
        }
        $line = sprintf(
            "[%s] %s: %s%s\n",
            now(),
            strtoupper($level),
            $message,
            $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : ''
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /** Keep the five most recent rotated files. */
    private static function prune(string $dir): void
    {
        $files = glob($dir . '/app-*.log') ?: [];
        if (count($files) <= 5) {
            return;
        }
        sort($files);
        foreach (array_slice($files, 0, count($files) - 5) as $old) {
            @unlink($old);
        }
    }
}
