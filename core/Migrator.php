<?php
declare(strict_types=1);

namespace Core;

final class Migrator
{
    public static function migrationsDir(): string
    {
        return APP_ROOT . '/core/migrations';
    }

    private static function ensureTable(): void
    {
        DB::conn()->exec('CREATE TABLE IF NOT EXISTS migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL
        )');
    }

    public static function applied(): array
    {
        self::ensureTable();
        return array_column(DB::all('SELECT version FROM migrations ORDER BY version'), 'version');
    }

    public static function pending(): array
    {
        $applied = self::applied();
        $files   = glob(self::migrationsDir() . '/*.php') ?: [];
        sort($files);
        return array_values(array_filter($files, fn($f) => !in_array(basename($f, '.php'), $applied, true)));
    }

    public static function currentVersion(): string
    {
        $applied = self::applied();
        return $applied ? (string)end($applied) : 'none';
    }

    /** @return string[] the versions applied */
    public static function migrate(): array
    {
        self::ensureTable();
        $done = [];
        foreach (self::pending() as $file) {
            $version = basename($file, '.php');
            $migration = require $file;
            if (!is_callable($migration)) {
                throw new \RuntimeException('Migration ' . $version . ' must return a callable.');
            }
            $migration(DB::conn());
            DB::insert('migrations', ['version' => $version, 'applied_at' => now()]);
            $done[] = $version;
        }
        return $done;
    }
}
