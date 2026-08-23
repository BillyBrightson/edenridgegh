<?php
declare(strict_types=1);

namespace Core;

/**
 * Loads the JSON seed content in /content into the database.
 * Image references are written as "@filename.jpg" and resolved to media ids
 * here, so the seed files stay readable and portable.
 */
final class Seeder
{
    /** @return array<string,int> counts by kind */
    public static function run(bool $withDerivatives = true): array
    {
        $counts = ['media' => 0, 'sections' => 0, 'gallery' => 0, 'settings' => 0, 'pages' => 0];

        $mediaIds = self::seedMedia($withDerivatives, $counts);
        self::seedSettings($counts);
        self::seedHome($mediaIds, $counts);
        self::seedSimplePages($counts);
        self::seedGallery($mediaIds, $counts);
        self::seedSeo();

        Cache::flush();
        return $counts;
    }

    private static function json(string $file): array
    {
        $path = APP_ROOT . '/content/' . $file;
        if (!is_file($path)) {
            throw new \RuntimeException('Missing seed file: ' . $file);
        }
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON in seed file: ' . $file);
        }
        return $data;
    }

    /** @return array<string,int> filename => media id */
    private static function seedMedia(bool $withDerivatives, array &$counts): array
    {
        $alts = self::json('media.json');
        $ids  = [];
        foreach ($alts as $filename => $alt) {
            $path = APP_ROOT . '/storage/uploads/' . $filename;
            if (!is_file($path)) {
                Logger::error('Seed image missing', ['file' => $filename]);
                continue;
            }
            $existing = DB::first('SELECT * FROM media WHERE filename = ?', [$filename]);
            if ($existing) {
                $ids[$filename] = (int)$existing['id'];
                continue;
            }
            $size = @getimagesize($path) ?: [null, null, null];
            $id = DB::insert('media', [
                'filename'      => $filename,
                'original_name' => $filename,
                'mime'          => (string)(mime_content_type($path) ?: 'image/jpeg'),
                'ext'           => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                'bytes'         => (int)filesize($path),
                'width'         => $size[0],
                'height'        => $size[1],
                'alt'           => (string)$alt,
                'caption'       => '',
                'lqip'          => null,
                'focal_x'       => 0.5,
                'focal_y'       => 0.5,
                'hash'          => (string)hash_file('sha1', $path),
                'uploaded_by'   => null,
                'created_at'    => now(),
            ]);
            $ids[$filename] = $id;
            $counts['media']++;
            if ($withDerivatives) {
                $row = DB::first('SELECT * FROM media WHERE id = ?', [$id]);
                if ($row) {
                    ImageProcessor::generate($row);
                }
            }
        }
        Media::flushCache();
        return $ids;
    }

    private static function seedSettings(array &$counts): void
    {
        foreach (self::json('settings.json') as $key => $def) {
            if (DB::value('SELECT COUNT(*) FROM settings WHERE key = ?', [$key])) {
                continue;
            }
            DB::insert('settings', [
                'key'        => $key,
                'value'      => (string)$def['value'],
                'type'       => (string)($def['type'] ?? 'string'),
                'updated_at' => now(),
            ]);
            $counts['settings']++;
        }
        // A random preview token, so the holding page can always be bypassed
        // by someone who has the link.
        if ((string)Settings::get('maintenance_bypass_token', '') === '') {
            Settings::set('maintenance_bypass_token', bin2hex(random_bytes(16)));
        }
        Settings::flush();
    }

    private static function pageId(string $slug, string $title): int
    {
        $page = DB::first('SELECT id FROM pages WHERE slug = ?', [$slug]);
        if ($page) {
            return (int)$page['id'];
        }
        return DB::insert('pages', [
            'slug'         => $slug,
            'title'        => $title,
            'is_published' => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private static function seedHome(array $mediaIds, array &$counts): void
    {
        $pageId = self::pageId('home', 'Home');
        $seed   = self::json('home.json');
        $order  = 0;

        foreach (Schema::sections() as $key => $definition) {
            $order++;
            if (DB::first('SELECT id FROM sections WHERE page_id = ? AND key = ?', [$pageId, $key])) {
                continue;
            }
            $content = self::resolveMedia($seed[$key] ?? [], $mediaIds);
            $content = array_replace(Schema::blank($definition['fields']), $content);
            DB::insert('sections', [
                'page_id'       => $pageId,
                'key'           => $key,
                'type'          => $key,
                'title'         => $definition['title'],
                'content'       => Content::encode($content),
                'draft_content' => null,
                'sort_order'    => $order,
                'is_published'  => 1,
                'updated_by'    => null,
                'updated_at'    => now(),
            ]);
            $counts['sections']++;
        }
    }

    private static function seedSimplePages(array &$counts): void
    {
        foreach (self::json('pages.json') as $slug => $def) {
            $pageId = self::pageId($slug, (string)$def['title']);
            if (DB::first('SELECT id FROM sections WHERE page_id = ? AND key = ?', [$pageId, 'body'])) {
                continue;
            }
            DB::insert('sections', [
                'page_id'       => $pageId,
                'key'           => 'body',
                'type'          => 'richtext',
                'title'         => (string)$def['title'],
                'content'       => Content::encode(['body' => Sanitizer::richtext((string)$def['body'])]),
                'draft_content' => null,
                'sort_order'    => 1,
                'is_published'  => 1,
                'updated_by'    => null,
                'updated_at'    => now(),
            ]);
            $counts['pages']++;
        }
    }

    private static function seedGallery(array $mediaIds, array &$counts): void
    {
        $seed = self::json('gallery.json');
        $categoryIds = [];
        foreach ($seed['categories'] as $category) {
            $existing = DB::first('SELECT id FROM gallery_categories WHERE slug = ?', [$category['slug']]);
            $categoryIds[$category['slug']] = $existing
                ? (int)$existing['id']
                : DB::insert('gallery_categories', [
                    'slug'       => $category['slug'],
                    'label'      => $category['label'],
                    'sort_order' => (int)$category['sort_order'],
                ]);
        }
        $order = 0;
        foreach ($seed['items'] as $item) {
            $order++;
            $mediaId = $mediaIds[$item['file']] ?? null;
            if (!$mediaId) {
                continue;
            }
            $exists = DB::first('SELECT id FROM gallery_items WHERE media_id = ? AND caption = ?', [$mediaId, $item['caption']]);
            if ($exists) {
                continue;
            }
            DB::insert('gallery_items', [
                'media_id'     => $mediaId,
                'category_id'  => $categoryIds[$item['category']] ?? null,
                'title'        => '',
                'caption'      => (string)$item['caption'],
                'sort_order'   => $order,
                'is_published' => 1,
            ]);
            $counts['gallery']++;
        }
    }

    private static function seedSeo(): void
    {
        $defaults = [
            'home' => [
                'title'       => 'Eden Ridge — Bold Contemporary Residences | Community 25, Tema',
                'description' => "Eden Ridge is a gated collection of contemporary four-bedroom residences in Community 25, Tema, set within Ghana's Eastern Corridor. Register your interest today.",
                'og_title'    => 'Eden Ridge — Bold Contemporary Residences',
                'og_description' => 'A gated community of contemporary residences in Community 25, Tema, Greater Accra.',
            ],
            'privacy' => [
                'title'       => 'Privacy Policy — Eden Ridge',
                'description' => 'How Eden Ridge collects and uses the information you provide through this website.',
            ],
            'terms' => [
                'title'       => 'Terms & Disclaimer — Eden Ridge',
                'description' => 'Terms of use and disclaimer for the Eden Ridge website.',
            ],
        ];
        foreach ($defaults as $slug => $meta) {
            if (DB::value('SELECT COUNT(*) FROM seo_meta WHERE page_slug = ?', [$slug])) {
                continue;
            }
            $ogImage = DB::value('SELECT id FROM media WHERE filename = ?', ['7b37227a-hero-exterior.jpg']);
            DB::insert('seo_meta', [
                'page_slug'         => $slug,
                'title'             => $meta['title'],
                'description'       => $meta['description'],
                'canonical'         => '',
                'og_title'          => $meta['og_title'] ?? '',
                'og_description'    => $meta['og_description'] ?? '',
                'og_image_media_id' => $slug === 'home' ? $ogImage : null,
                'twitter_card'      => 'summary_large_image',
                'noindex'           => 0,
                'updated_at'        => now(),
            ]);
        }
    }

    /** Recursively turn "@filename.jpg" markers into media ids. */
    private static function resolveMedia(array $node, array $mediaIds): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = self::resolveMedia($value, $mediaIds);
            } elseif (is_string($value) && str_starts_with($value, '@')) {
                $node[$key] = $mediaIds[substr($value, 1)] ?? null;
            }
        }
        return $node;
    }

    /** Create the first admin account. */
    public static function createAdmin(string $name, string $email, string $password, bool $mustReset = false): int
    {
        $existing = DB::first('SELECT id FROM users WHERE lower(email) = ?', [strtolower($email)]);
        if ($existing) {
            return (int)$existing['id'];
        }
        return DB::insert('users', [
            'name'          => $name,
            'email'         => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'admin',
            'must_reset'    => $mustReset ? 1 : 0,
            'totp_secret'   => null,
            'last_login_at' => null,
            'is_active'     => 1,
            'created_at'    => now(),
        ]);
    }
}
