<?php
declare(strict_types=1);

namespace Core;

final class Media
{
    public const MAX_BYTES = 12582912; // 12 MB
    public const ALLOWED   = [
        'image/jpeg'    => 'jpg',
        'image/png'     => 'png',
        'image/webp'    => 'webp',
        'image/svg+xml' => 'svg',
    ];

    public static function uploadDir(): string
    {
        $dir = APP_ROOT . '/storage/uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /** The real server upload ceiling, so the UI can state it honestly. */
    public static function serverLimit(): int
    {
        $toBytes = static function (string $v): int {
            $v = trim($v);
            if ($v === '') {
                return 0;
            }
            $unit = strtolower(substr($v, -1));
            $n = (int)$v;
            return match ($unit) {
                'g' => $n * 1073741824,
                'm' => $n * 1048576,
                'k' => $n * 1024,
                default => $n,
            };
        };
        $limits = array_filter([
            $toBytes((string)ini_get('upload_max_filesize')),
            $toBytes((string)ini_get('post_max_size')),
            self::MAX_BYTES,
        ]);
        return $limits ? (int)min($limits) : self::MAX_BYTES;
    }

    /** @var array<int, array|null> */
    private static array $rowCache = [];
    /** @var array<int, array> */
    private static array $variantCache = [];

    public static function find(?int $id): ?array
    {
        if (!$id) {
            return null;
        }
        if (array_key_exists($id, self::$rowCache)) {
            return self::$rowCache[$id];
        }
        return self::$rowCache[$id] = DB::first('SELECT * FROM media WHERE id = ?', [$id]);
    }

    public static function flushCache(): void
    {
        self::$rowCache = [];
        self::$variantCache = [];
    }

    public static function all(string $search = '', int $limit = 60, int $offset = 0): array
    {
        if ($search !== '') {
            $like = '%' . $search . '%';
            return DB::all(
                'SELECT * FROM media WHERE original_name LIKE ? OR alt LIKE ? OR caption LIKE ?
                 ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?',
                [$like, $like, $like, $limit, $offset]
            );
        }
        return DB::all('SELECT * FROM media ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?', [$limit, $offset]);
    }

    public static function count(string $search = ''): int
    {
        if ($search !== '') {
            $like = '%' . $search . '%';
            return (int)DB::value(
                'SELECT COUNT(*) FROM media WHERE original_name LIKE ? OR alt LIKE ? OR caption LIKE ?',
                [$like, $like, $like]
            );
        }
        return (int)DB::value('SELECT COUNT(*) FROM media');
    }

    /**
     * Store an uploaded file. Returns [mediaRow, null] or [null, errorMessage].
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    public static function store(array $file, int $userId, string $alt = ''): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, self::uploadErrorMessage((int)($file['error'] ?? 4))];
        }
        if ((int)$file['size'] > self::MAX_BYTES) {
            return [null, 'That file is ' . human_bytes((int)$file['size']) . '. The limit is ' . human_bytes(self::MAX_BYTES) . '.'];
        }
        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp) && !is_file($tmp)) {
            return [null, 'The upload did not arrive intact. Please try again.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string)$finfo->file($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            return [null, 'Unsupported file type (' . e($mime) . '). Upload a JPEG, PNG, WebP or SVG.'];
        }
        $ext = self::ALLOWED[$mime];

        if ($mime === 'image/svg+xml') {
            $clean = Sanitizer::svg((string)file_get_contents($tmp));
            file_put_contents($tmp, $clean);
        }

        $hash = (string)hash_file('sha1', $tmp);
        $existing = DB::first('SELECT * FROM media WHERE hash = ?', [$hash]);
        if ($existing) {
            return [$existing, null];
        }

        $slug     = str_slug(pathinfo((string)$file['name'], PATHINFO_FILENAME));
        $filename = substr($hash, 0, 8) . '-' . substr($slug, 0, 60) . '.' . $ext;
        $target   = self::uploadDir() . '/' . $filename;
        $moved    = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $target) : rename($tmp, $target);
        if (!$moved) {
            return [null, 'The file could not be saved. Check the storage folder permissions.'];
        }
        @chmod($target, 0644);

        $width = $height = null;
        if ($mime !== 'image/svg+xml') {
            $size = @getimagesize($target);
            if ($size) {
                [$width, $height] = $size;
            }
            // File size says little about how much memory decoding will take:
            // a 3 MB JPEG can be 48 megapixels. Check before we commit.
            ImageProcessor::raiseMemoryLimit();
            if (!ImageProcessor::canProcess($width, $height)) {
                @unlink($target);
                $megapixels = round(((int)$width * (int)$height) / 1_000_000, 1);
                $allowed    = round(ImageProcessor::maxPixels() / 1_000_000, 1);
                return [null, sprintf(
                    'That image is %d × %d pixels (%s megapixels), and this server can only resize images up to about %s megapixels. Please scale it down — around 2500 pixels on the longest side is plenty for the website — and upload it again.',
                    (int)$width,
                    (int)$height,
                    $megapixels,
                    $allowed
                )];
            }
        }

        $id = DB::insert('media', [
            'filename'      => $filename,
            'original_name' => mb_substr((string)$file['name'], 0, 180),
            'mime'          => $mime,
            'ext'           => $ext,
            'bytes'         => (int)filesize($target),
            'width'         => $width,
            'height'        => $height,
            'alt'           => $alt !== '' ? $alt : self::altFromName((string)$file['name']),
            'caption'       => '',
            'lqip'          => null,
            'focal_x'       => 0.5,
            'focal_y'       => 0.5,
            'hash'          => $hash,
            'uploaded_by'   => $userId ?: null,
            'created_at'    => now(),
        ]);

        self::flushCache();
        $row = self::find($id);
        if ($row) {
            ImageProcessor::generate($row);
            self::flushCache();
            $row = self::find($id);
        }
        Activity::log($userId, 'media.upload', 'media', $id, ['name' => $file['name']]);
        return [$row, null];
    }

    private static function altFromName(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/^[0-9a-f]{6,}-/', '', $base) ?? $base;
        return ucfirst(trim(str_replace(['-', '_'], ' ', $base)));
    }

    public static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is larger than this server accepts (' . human_bytes(self::serverLimit()) . ').',
            UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE   => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not write the file. Contact your host.',
            UPLOAD_ERR_EXTENSION => 'A server extension blocked the upload.',
            default              => 'The upload failed.',
        };
    }

    public static function update(int $id, array $data, int $userId): void
    {
        $allowed = array_intersect_key($data, array_flip(['alt', 'caption', 'focal_x', 'focal_y']));
        if (!$allowed) {
            return;
        }
        DB::update('media', $allowed, 'id = :id', ['id' => $id]);
        self::flushCache();
        Cache::flush();
        Activity::log($userId, 'media.update', 'media', $id);
    }

    public static function delete(int $id, int $userId): void
    {
        $media = self::find($id);
        if (!$media) {
            return;
        }
        ImageProcessor::purge($media);
        $original = self::uploadDir() . '/' . $media['filename'];
        if (is_file($original)) {
            @unlink($original);
        }
        DB::delete('media', 'id = ?', [$id]);
        self::flushCache();
        self::detachEverywhere($id);
        Cache::flush();
        Activity::log($userId, 'media.delete', 'media', $id, ['name' => $media['original_name']]);
    }

    /** Null out references to a deleted image across all section documents. */
    private static function detachEverywhere(int $id): void
    {
        foreach (DB::all('SELECT id, key, content, draft_content FROM sections') as $section) {
            foreach (['content', 'draft_content'] as $col) {
                $json = (string)($section[$col] ?? '');
                if ($json === '' || !str_contains($json, (string)$id)) {
                    continue;
                }
                $doc = Content::decode($json);
                $changed = false;
                self::walkDetach($doc, $id, $changed);
                if ($changed) {
                    DB::update('sections', [$col => Content::encode($doc)], 'id = :id', ['id' => $section['id']]);
                }
            }
        }
        foreach (['logo_media_id', 'favicon_media_id', 'og_image_media_id', 'video_poster_media_id'] as $key) {
            if ((int)Settings::get($key, 0) === $id) {
                Settings::set($key, '');
            }
        }
    }

    private static function walkDetach(array &$node, int $id, bool &$changed): void
    {
        foreach ($node as $key => &$value) {
            if (is_array($value)) {
                self::walkDetach($value, $id, $changed);
            } elseif (is_int($value) && $value === $id) {
                $value = null;
                $changed = true;
            }
        }
    }

    /** Which sections reference this image — shown before deleting. */
    public static function usage(int $id): array
    {
        $used = [];
        foreach (DB::all('SELECT s.key, s.content FROM sections s') as $section) {
            $doc = Content::decode((string)$section['content']);
            if (self::documentUses($doc, $id)) {
                $used[] = Schema::title((string)$section['key']);
            }
        }
        if ((int)DB::value('SELECT COUNT(*) FROM gallery_items WHERE media_id = ?', [$id]) > 0) {
            $used[] = 'Gallery';
        }
        foreach (['logo_media_id' => 'Logo', 'favicon_media_id' => 'Favicon', 'og_image_media_id' => 'Social share image'] as $key => $label) {
            if ((int)Settings::get($key, 0) === $id) {
                $used[] = $label;
            }
        }
        return array_values(array_unique($used));
    }

    private static function documentUses(array $node, int $id): bool
    {
        foreach ($node as $value) {
            if (is_array($value)) {
                if (self::documentUses($value, $id)) {
                    return true;
                }
            } elseif (is_int($value) && $value === $id) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------------
    // Rendering
    // ------------------------------------------------------------------

    /**
     * Image derivatives live in the public site's document root, so a
     * root-relative "/media/..." only resolves on the public hostname. The
     * dashboard runs on its own hostname and needs absolute URLs.
     */
    private static function publicUrl(string $path): string
    {
        if ($path === '' || defined('PUBLIC_ROOT') || preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $base = rtrim((string)Config::get('site_url', ''), '/');
        return $base === '' ? $path : $base . $path;
    }

    /** @return array<int, array> variants keyed by width, ascending */
    public static function variants(int $mediaId): array
    {
        return self::$variantCache[$mediaId] ??= DB::all(
            'SELECT * FROM media_variants WHERE media_id = ? ORDER BY width ASC',
            [$mediaId]
        );
    }

    /** Best single URL for an image (used for og:image, lightbox, etc.). */
    public static function url(?int $id, int $preferredWidth = 1440): string
    {
        $media = self::find($id);
        if (!$media) {
            return '';
        }
        if ($media['mime'] === 'image/svg+xml') {
            return self::publicUrl('/media/' . $media['filename']);
        }
        $best = null;
        foreach (self::variants((int)$media['id']) as $v) {
            if ($v['format'] === 'jpg' && $v['label'] === 'fallback') {
                $best ??= $v;
                continue;
            }
            $best = $v;
            if ((int)$v['width'] >= $preferredWidth) {
                break;
            }
        }
        return $best ? self::publicUrl((string)$best['path']) : '';
    }

    /**
     * A complete, layout-shift-free <img> tag.
     *
     * @param array{class?:string, sizes?:string, alt?:string, priority?:bool,
     *              style?:string, width?:int, height?:int, onclick?:string} $opts
     */
    public static function img(?int $id, array $opts = []): string
    {
        $media = self::find($id);
        if (!$media) {
            return '';
        }
        $alt = array_key_exists('alt', $opts) ? (string)$opts['alt'] : (string)$media['alt'];

        if ($media['mime'] === 'image/svg+xml') {
            return sprintf(
                '<img src="%s" alt="%s"%s%s>',
                e(self::publicUrl('/media/' . $media['filename'])),
                e($alt),
                isset($opts['class']) ? ' class="' . e((string)$opts['class']) . '"' : '',
                isset($opts['style']) ? ' style="' . e((string)$opts['style']) . '"' : ''
            );
        }

        $variants = self::variants((int)$media['id']);
        $srcset   = [];
        $fallback = '';
        $largest  = null;
        foreach ($variants as $v) {
            if ($v['label'] === 'fallback') {
                $fallback = (string)$v['path'];
                continue;
            }
            $srcset[] = self::publicUrl((string)$v['path']) . ' ' . $v['width'] . 'w';
            $largest  = $v;
        }
        $src = $fallback !== '' ? self::publicUrl($fallback) : (string)($largest['path'] ?? '');
        if ($src === '') {
            return '';
        }

        $priority = (bool)($opts['priority'] ?? false);
        // Images the CSS sizes with `width: auto` have no box until they load,
        // and a zero-sized lazy image never enters the viewport — so those
        // callers ask for eager loading explicitly.
        $loading = (string)($opts['loading'] ?? ($priority ? 'eager' : 'lazy'));
        $attrs = [
            'src'    => $src,
            'alt'    => $alt,
            'width'  => (string)($opts['width'] ?? $media['width'] ?? ''),
            'height' => (string)($opts['height'] ?? $media['height'] ?? ''),
        ];
        if ($srcset) {
            $attrs['srcset'] = implode(', ', $srcset);
            $attrs['sizes']  = (string)($opts['sizes'] ?? '100vw');
        }
        if (isset($opts['class'])) {
            $attrs['class'] = (string)$opts['class'];
        }
        if (isset($opts['style'])) {
            $attrs['style'] = (string)$opts['style'];
        }
        $attrs['decoding'] = 'async';
        if ($priority) {
            $attrs['fetchpriority'] = 'high';
        }
        if ($loading === 'lazy') {
            $attrs['loading'] = 'lazy';
        }
        if (isset($opts['data'])) {
            foreach ((array)$opts['data'] as $k => $v) {
                $attrs['data-' . $k] = (string)$v;
            }
        }

        $html = '<img';
        foreach ($attrs as $k => $v) {
            if ($v === '') {
                continue;
            }
            $html .= ' ' . $k . '="' . e($v) . '"';
        }
        return $html . '>';
    }

    /** Preload link for the hero image. */
    public static function preload(?int $id, string $sizes = '100vw'): string
    {
        $media = self::find($id);
        if (!$media || $media['mime'] === 'image/svg+xml') {
            return '';
        }
        $srcset = [];
        $href = '';
        foreach (self::variants((int)$media['id']) as $v) {
            if ($v['label'] === 'fallback') {
                $href = (string)$v['path'];
                continue;
            }
            $srcset[] = self::publicUrl((string)$v['path']) . ' ' . $v['width'] . 'w';
        }
        if ($href === '' && $srcset === []) {
            return '';
        }
        return sprintf(
            '<link rel="preload" as="image" href="%s"%s fetchpriority="high">',
            e(self::publicUrl($href)),
            $srcset ? ' imagesrcset="' . e(implode(', ', $srcset)) . '" imagesizes="' . e($sizes) . '"' : ''
        );
    }

    public static function alt(?int $id): string
    {
        return (string)(self::find($id)['alt'] ?? '');
    }
}
