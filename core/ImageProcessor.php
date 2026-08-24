<?php
declare(strict_types=1);

namespace Core;

/**
 * Derivative generation with plain GD — no Composer dependency, because the
 * target host may have no CLI at all.
 */
final class ImageProcessor
{
    /** Derivative widths, largest first. Never upscales. */
    public const WIDTHS = [
        'xs' => 320,
        'sm' => 640,
        'md' => 960,
        'lg' => 1440,
        'xl' => 1920,
        'xxl' => 2560,
    ];

    public const FALLBACK_WIDTH = 400;
    public const LQIP_WIDTH     = 24;

    public static function supportsWebp(): bool
    {
        return function_exists('imagewebp');
    }

    /**
     * GD decodes an image to width x height x 4 bytes, and resizing holds the
     * source and destination at once. Shared hosts commonly cap PHP at 128 MB,
     * which a photo straight off a modern phone will blow through — so raise
     * the ceiling where the host allows it, and refuse politely where it does
     * not, rather than dying mid-request with a blank page.
     */
    private const BYTES_PER_PIXEL = 4;
    private const OVERHEAD_FACTOR = 2.2;
    private const WANTED_MEMORY   = 512 * 1024 * 1024;

    public static function raiseMemoryLimit(): void
    {
        if (self::memoryLimitBytes() < self::WANTED_MEMORY) {
            @ini_set('memory_limit', '512M');
        }
    }

    public static function memoryLimitBytes(): int
    {
        $raw = trim((string)ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') {
            return PHP_INT_MAX;
        }
        $unit = strtolower(substr($raw, -1));
        $n    = (int)$raw;
        return match ($unit) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }

    /** Largest image, in pixels, this process can safely decode and resize. */
    public static function maxPixels(): int
    {
        $available = self::memoryLimitBytes() - memory_get_usage(true);
        return (int)max(1_000_000, $available / (self::BYTES_PER_PIXEL * self::OVERHEAD_FACTOR));
    }

    public static function canProcess(?int $width, ?int $height): bool
    {
        if (!$width || !$height) {
            return true; // Unknown dimensions (SVG); nothing to decode.
        }
        return $width * $height <= self::maxPixels();
    }

    public static function mediaDir(): string
    {
        $dir = PUBLIC_PATH . '/media';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /** Load an image resource from a file, respecting EXIF orientation. */
    private static function load(string $path, string $mime): ?\GdImage
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif'  => @imagecreatefromgif($path),
            default      => false,
        };
        if (!$img instanceof \GdImage) {
            return null;
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = (int)($exif['Orientation'] ?? 1);
            $rotated = match ($orientation) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => null,
            };
            if ($rotated instanceof \GdImage) {
                $img = $rotated;
            }
        }
        return $img;
    }

    private static function resize(\GdImage $src, int $targetWidth): array
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $w  = min($targetWidth, $sw);
        $h  = (int)max(1, round($sh * ($w / $sw)));
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
        return [$dst, $w, $h];
    }

    /**
     * Generate every derivative for a media record and store the rows.
     * Returns the number of files written.
     */
    public static function generate(array $media): int
    {
        $original = APP_ROOT . '/storage/uploads/' . $media['filename'];
        if (!is_file($original)) {
            return 0;
        }
        if ($media['mime'] === 'image/svg+xml') {
            // Vector: publish the file itself, no raster derivatives.
            $target = self::mediaDir() . '/' . $media['filename'];
            if (!is_file($target)) {
                copy($original, $target);
            }
            return 1;
        }

        self::raiseMemoryLimit();
        if (!self::canProcess($media['width'] ? (int)$media['width'] : null, $media['height'] ? (int)$media['height'] : null)) {
            Logger::error('Image too large to resize on this server', [
                'media_id'   => $media['id'],
                'dimensions' => $media['width'] . 'x' . $media['height'],
                'max_pixels' => self::maxPixels(),
            ]);
            return 0;
        }

        $src = self::load($original, (string)$media['mime']);
        if ($src === null) {
            Logger::error('Could not decode image for derivatives', ['media_id' => $media['id']]);
            return 0;
        }
        $srcWidth = imagesx($src);
        $base     = pathinfo((string)$media['filename'], PATHINFO_FILENAME);
        $written  = 0;

        DB::delete('media_variants', 'media_id = ?', [$media['id']]);

        // Ascending widths; min() caps at the original so nothing is upscaled,
        // and the loop stops once a derivative reaches the original's width.
        foreach (self::WIDTHS as $label => $width) {
            [$img, $w, $h] = self::resize($src, $width);
            $name = sprintf('%s-%d.%s', $base, $w, self::supportsWebp() ? 'webp' : 'jpg');
            $path = self::mediaDir() . '/' . $name;
            if (self::supportsWebp()) {
                imagewebp($img, $path, 82);
            } else {
                self::flatten($img);
                imagejpeg($img, $path, 84);
            }
            $written++;
            DB::insert('media_variants', [
                'media_id' => $media['id'],
                'label'    => $label,
                'format'   => self::supportsWebp() ? 'webp' : 'jpg',
                'width'    => $w,
                'height'   => $h,
                'path'     => '/media/' . $name,
                'bytes'    => is_file($path) ? (int)filesize($path) : 0,
            ]);
            if ($w >= $srcWidth) {
                break; // Reached the original's width; larger labels would upscale.
            }
        }

        // JPEG fallback for browsers without WebP.
        [$img, $w, $h] = self::resize($src, self::FALLBACK_WIDTH);
        self::flatten($img);
        $name = sprintf('%s-%d.jpg', $base, $w);
        $path = self::mediaDir() . '/' . $name;
        imagejpeg($img, $path, 82);
        $written++;
        DB::insert('media_variants', [
            'media_id' => $media['id'],
            'label'    => 'fallback',
            'format'   => 'jpg',
            'width'    => $w,
            'height'   => $h,
            'path'     => '/media/' . $name,
            'bytes'    => is_file($path) ? (int)filesize($path) : 0,
        ]);

        // Tiny blurred placeholder, stored inline on the media row.
        [$img, , ] = self::resize($src, self::LQIP_WIDTH);
        self::flatten($img);
        ob_start();
        imagejpeg($img, null, 40);
        $lqip = (string)ob_get_clean();
        DB::update('media', [
            'lqip'   => 'data:image/jpeg;base64,' . base64_encode($lqip),
            'width'  => $srcWidth,
            'height' => imagesy($src),
        ], 'id = :id', ['id' => $media['id']]);
        return $written;
    }

    /** Composite onto white so JPEG output does not go black behind alpha. */
    private static function flatten(\GdImage $img): void
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $bg = imagecreatetruecolor($w, $h);
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, true);
        imagecopy($bg, $img, 0, 0, 0, 0, $w, $h);
        imagecopy($img, $bg, 0, 0, 0, 0, $w, $h);
    }

    /** Remove every generated file for a media record. */
    public static function purge(array $media): void
    {
        foreach (DB::all('SELECT path FROM media_variants WHERE media_id = ?', [$media['id']]) as $variant) {
            $file = PUBLIC_PATH . $variant['path'];
            if (is_file($file)) {
                @unlink($file);
            }
        }
        DB::delete('media_variants', 'media_id = ?', [$media['id']]);
        $svg = self::mediaDir() . '/' . $media['filename'];
        if ($media['mime'] === 'image/svg+xml' && is_file($svg)) {
            @unlink($svg);
        }
    }
}
