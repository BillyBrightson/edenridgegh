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

    public static function mediaDir(): string
    {
        $dir = APP_ROOT . '/public/media';
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
            $file = APP_ROOT . '/public' . $variant['path'];
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
