<?php
declare(strict_types=1);

namespace Core;

final class Seo
{
    public const PAGES = ['home' => 'Home', 'privacy' => 'Privacy policy', 'terms' => 'Terms & disclaimer'];

    public static function meta(string $slug): array
    {
        $row = DB::first('SELECT * FROM seo_meta WHERE page_slug = ?', [$slug]) ?? [];
        return $row + [
            'page_slug'         => $slug,
            'title'             => '',
            'description'       => '',
            'canonical'         => '',
            'og_title'          => '',
            'og_description'    => '',
            'og_image_media_id' => null,
            'twitter_card'      => 'summary_large_image',
            'noindex'           => 0,
        ];
    }

    public static function save(string $slug, array $data, int $userId): void
    {
        $payload = [
            'title'             => Sanitizer::text((string)($data['title'] ?? '')),
            'description'       => Sanitizer::text((string)($data['description'] ?? '')),
            'canonical'         => Sanitizer::text((string)($data['canonical'] ?? '')),
            'og_title'          => Sanitizer::text((string)($data['og_title'] ?? '')),
            'og_description'    => Sanitizer::text((string)($data['og_description'] ?? '')),
            'og_image_media_id' => ($data['og_image_media_id'] ?? '') !== '' ? (int)$data['og_image_media_id'] : null,
            'twitter_card'      => in_array((string)($data['twitter_card'] ?? ''), ['summary', 'summary_large_image'], true) ? (string)$data['twitter_card'] : 'summary_large_image',
            'noindex'           => !empty($data['noindex']) ? 1 : 0,
            'updated_at'        => now(),
        ];
        $exists = DB::value('SELECT COUNT(*) FROM seo_meta WHERE page_slug = ?', [$slug]);
        if ($exists) {
            DB::update('seo_meta', $payload, 'page_slug = :slug', ['slug' => $slug]);
        } else {
            DB::insert('seo_meta', $payload + ['page_slug' => $slug]);
        }
        Cache::flush();
        Activity::log($userId, 'seo.save', 'page', null, ['slug' => $slug]);
    }

    public static function siteUrl(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return rtrim((string)Config::get('site_url', ''), '/') . '/' . ltrim($path, '/');
    }

    /** Head tags for a page: title, description, canonical, OG, Twitter. */
    public static function headTags(string $slug, string $path = '/'): string
    {
        $m = self::meta($slug);
        $siteName = (string)Settings::get('site_name', 'Eden Ridge');
        $title = $m['title'] !== '' ? $m['title'] : $siteName;
        $desc  = $m['description'];
        $canonical = $m['canonical'] !== '' ? $m['canonical'] : self::siteUrl($path);
        $ogTitle = $m['og_title'] !== '' ? $m['og_title'] : $title;
        $ogDesc  = $m['og_description'] !== '' ? $m['og_description'] : $desc;
        $ogImage = Media::url($m['og_image_media_id'] ? (int)$m['og_image_media_id'] : null, 1920);

        $out  = '<title>' . e($title) . '</title>' . "\n";
        if ($desc !== '') {
            $out .= '<meta name="description" content="' . e($desc) . '">' . "\n";
        }
        $out .= '<link rel="canonical" href="' . e($canonical) . '">' . "\n";
        if ((int)$m['noindex'] === 1) {
            $out .= '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
        $out .= '<meta property="og:type" content="website">' . "\n";
        $out .= '<meta property="og:site_name" content="' . e($siteName) . '">' . "\n";
        $out .= '<meta property="og:title" content="' . e($ogTitle) . '">' . "\n";
        if ($ogDesc !== '') {
            $out .= '<meta property="og:description" content="' . e($ogDesc) . '">' . "\n";
        }
        $out .= '<meta property="og:url" content="' . e($canonical) . '">' . "\n";
        if ($ogImage !== '') {
            $out .= '<meta property="og:image" content="' . e(self::siteUrl($ogImage)) . '">' . "\n";
        }
        $out .= '<meta name="twitter:card" content="' . e((string)$m['twitter_card']) . '">' . "\n";
        return $out;
    }

    /** Organization / RealEstateAgent / Residence / FAQ structured data. */
    public static function jsonLd(): string
    {
        $siteName = (string)Settings::get('site_name', 'Eden Ridge');
        $phone    = (string)Settings::get('contact_phone', '');
        $email    = (string)Settings::get('contact_email', '');
        $sameAs   = [];
        foreach (Icons::socialOptions() as $platform => $_) {
            $url = (string)Settings::get('social_' . $platform . '_url', '');
            if ($url !== '') {
                $sameAs[] = $url;
            }
        }

        $address = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => (string)Settings::get('contact_address', 'Community 25, Tema'),
            'addressLocality' => 'Tema',
            'addressRegion'   => 'Greater Accra',
            'addressCountry'  => 'GH',
        ];

        $blocks = [];
        $blocks[] = array_filter([
            '@context'    => 'https://schema.org',
            '@type'       => 'RealEstateAgent',
            'name'        => $siteName,
            'description' => (string)Settings::get('tagline', ''),
            'url'         => self::siteUrl('/'),
            'telephone'   => $phone ?: null,
            'email'       => $email ?: null,
            'address'     => $address,
            'sameAs'      => $sameAs ?: null,
        ]);

        $residence = Content::get('residence');
        $specs = [];
        foreach (rows($residence, 'specs') as $spec) {
            $specs[] = ['@type' => 'PropertyValue', 'name' => (string)($spec['label'] ?? ''), 'value' => (string)($spec['value'] ?? '')];
        }
        $blocks[] = array_filter([
            '@context'            => 'https://schema.org',
            '@type'               => 'Residence',
            'name'                => (string)($residence['card_title'] ?? 'The Eden Ridge Residence'),
            'description'         => (string)($residence['price_note'] ?? ''),
            'numberOfRooms'       => 4,
            'address'             => $address,
            'additionalProperty'  => $specs ?: null,
        ]);

        $faq = Content::get('faq');
        $questions = [];
        foreach (rows($faq, 'items') as $item) {
            $q = trim((string)($item['question'] ?? ''));
            $a = trim(strip_tags((string)($item['answer'] ?? '')));
            if ($q === '' || $a === '') {
                continue;
            }
            $questions[] = [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
            ];
        }
        if ($questions) {
            $blocks[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $questions];
        }

        $out = '';
        foreach ($blocks as $block) {
            $out .= '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                . '</script>' . "\n";
        }
        return $out;
    }

    /** BreadcrumbList for the standalone pages. */
    public static function breadcrumb(string $label, string $path): string
    {
        $data = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => self::siteUrl('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $label, 'item' => self::siteUrl($path)],
            ],
        ];
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    public static function sitemap(): string
    {
        $urls = [];
        $lastmod = gmdate('Y-m-d', Cache::lastPublishTime());
        foreach (['/' => '1.0', '/privacy' => '0.3', '/terms' => '0.3'] as $path => $priority) {
            $slug = $path === '/' ? 'home' : trim($path, '/');
            $page = Content::page($slug);
            if ($page && (int)$page['is_published'] !== 1) {
                continue;
            }
            if ((int)self::meta($slug)['noindex'] === 1) {
                continue;
            }
            $urls[] = sprintf(
                "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <priority>%s</priority>\n  </url>",
                e(self::siteUrl($path)),
                $lastmod,
                $priority
            );
        }
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode("\n", $urls) . "\n</urlset>\n";
    }

    public static function robots(): string
    {
        $custom = (string)Settings::get('robots_txt', '');
        if (trim($custom) !== '') {
            return $custom;
        }
        return "User-agent: *\nAllow: /\nDisallow: /thank-you\n\nSitemap: " . self::siteUrl('/sitemap.xml') . "\n";
    }
}
