<?php
declare(strict_types=1);

namespace Core;

/**
 * Zip backups of the database and uploads, plus restore.
 * VACUUM INTO gives a consistent snapshot even with WAL enabled.
 */
final class Backup
{
    public static function dir(): string
    {
        $dir = APP_ROOT . '/storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function available(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    /** Build a zip and return its path. */
    public static function create(bool $includeUploads = true): string
    {
        if (!self::available()) {
            throw new \RuntimeException('The ZipArchive extension is not available on this server.');
        }
        $stamp   = gmdate('Ymd-His');
        $zipPath = self::dir() . '/edenridge-' . $stamp . '.zip';
        $dbCopy  = self::dir() . '/db-' . $stamp . '.sqlite';

        DB::conn()->exec("VACUUM INTO '" . str_replace("'", "''", $dbCopy) . "'");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($dbCopy);
            throw new \RuntimeException('Could not create the backup archive.');
        }
        $zip->addFile($dbCopy, 'db/edenridge.sqlite');
        $zip->addFromString('manifest.json', (string)json_encode([
            'created_at' => now(),
            'site_url'   => Config::get('site_url'),
            'version'    => Migrator::currentVersion(),
            'uploads'    => $includeUploads,
        ], JSON_PRETTY_PRINT));

        if ($includeUploads) {
            foreach (glob(APP_ROOT . '/storage/uploads/*') ?: [] as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, 'uploads/' . basename($file));
                }
            }
        }
        $zip->close();
        @unlink($dbCopy);
        self::rotate();
        return $zipPath;
    }

    /** Keep seven days of automatic backups. */
    public static function rotate(int $keepDays = 7): void
    {
        $cutoff = time() - $keepDays * 86400;
        foreach (glob(self::dir() . '/edenridge-*.zip') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    public static function list(): array
    {
        $files = glob(self::dir() . '/edenridge-*.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return array_map(fn($f) => [
            'name'  => basename($f),
            'bytes' => (int)filesize($f),
            'mtime' => (int)filemtime($f),
        ], $files);
    }

    /**
     * Nightly backup without a guaranteed cron: the first request after
     * midnight UTC triggers one.
     */
    public static function maybeNightly(): void
    {
        if (!self::available() || !Settings::bool('auto_backup_enabled', true)) {
            return;
        }
        $last = (string)Settings::get('last_auto_backup', '');
        if ($last !== '' && substr($last, 0, 10) === gmdate('Y-m-d')) {
            return;
        }
        try {
            self::create(false); // Database only — uploads rarely change and cost bandwidth.
            Settings::set('last_auto_backup', now());
        } catch (\Throwable $ex) {
            Logger::error('Automatic backup failed', ['error' => $ex->getMessage()]);
        }
    }

    /** Restore from an uploaded zip. Destructive: the caller must confirm. */
    public static function restore(string $zipPath, int $userId): void
    {
        if (!self::available()) {
            throw new \RuntimeException('The ZipArchive extension is not available on this server.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('That file is not a readable zip archive.');
        }
        if ($zip->locateName('db/edenridge.sqlite') === false) {
            $zip->close();
            throw new \RuntimeException('That archive does not contain an Eden Ridge database.');
        }

        // Snapshot the current state first, so a bad restore is recoverable.
        self::create(false);

        $tmp = sys_get_temp_dir() . '/eden-restore-' . bin2hex(random_bytes(6));
        mkdir($tmp, 0700, true);
        $zip->extractTo($tmp);
        $zip->close();

        $dbPath = (string)Config::get('db_path');
        DB::conn()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        copy($tmp . '/db/edenridge.sqlite', $dbPath);
        @unlink($dbPath . '-wal');
        @unlink($dbPath . '-shm');

        if (is_dir($tmp . '/uploads')) {
            foreach (glob($tmp . '/uploads/*') ?: [] as $file) {
                copy($file, APP_ROOT . '/storage/uploads/' . basename($file));
            }
        }
        self::rmdir($tmp);
        Cache::flush();
        Activity::log($userId, 'tools.restore', null, null, ['file' => basename($zipPath)]);
    }

    private static function rmdir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? self::rmdir($file) : @unlink($file);
        }
        @rmdir($dir);
    }

    /** Regenerate every image derivative — used after a restore. */
    public static function rebuildDerivatives(): int
    {
        $count = 0;
        foreach (DB::all('SELECT * FROM media') as $media) {
            $count += ImageProcessor::generate($media) > 0 ? 1 : 0;
        }
        Media::flushCache();
        Cache::flush();
        return $count;
    }
}
