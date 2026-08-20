<?php
declare(strict_types=1);

namespace Core;

final class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $out = [];
        foreach (DB::all('SELECT key, value, type FROM settings') as $row) {
            $out[$row['key']] = self::cast($row['value'], $row['type']);
        }
        return self::$cache = $out;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::all()[$key] ?? null;
        if ($v === null || $v === '') {
            return $default;
        }
        return (bool)$v;
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $stored = match ($type) {
            'json' => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'bool' => $value ? '1' : '0',
            'int'  => (string)(int)$value,
            default => (string)$value,
        };
        DB::run(
            'INSERT INTO settings (key, value, type, updated_at) VALUES (:k, :v, :t, :u)
             ON CONFLICT(key) DO UPDATE SET value = :v, type = :t, updated_at = :u',
            ['k' => $key, 'v' => $stored, 't' => $type, 'u' => now()]
        );
        self::$cache = null;
    }

    /** @param array<string, mixed> $pairs */
    public static function setMany(array $pairs, array $types = []): void
    {
        foreach ($pairs as $key => $value) {
            self::set($key, $value, $types[$key] ?? 'string');
        }
    }

    public static function typeOf(string $key): string
    {
        return (string)(DB::value('SELECT type FROM settings WHERE key = ?', [$key]) ?? 'string');
    }

    private static function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'json' => json_decode($value, true) ?? [],
            'bool' => $value === '1' || $value === 'true',
            'int'  => (int)$value,
            default => $value,
        };
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
