<?php
declare(strict_types=1);

namespace Core;

/**
 * The line-icon registry. Every icon field in the dashboard picks a key from
 * here, so the client can change an icon without touching markup.
 * All icons share the 24x24 stroked grid used by the reference build.
 */
final class Icons
{
    /** @var array<string, array{label:string, path:string}> */
    private const SET = [
        'roof'      => ['label' => 'Roofline',        'path' => '<path d="M3 11l9-7 9 7M5 10v10h14V10"/>'],
        'building'  => ['label' => 'Building',        'path' => '<path d="M4 21V9l8-5 8 5v12M9 21v-6h6v6"/>'],
        'shield'    => ['label' => 'Shield',          'path' => '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/>'],
        'trend'     => ['label' => 'Growth',          'path' => '<path d="M3 17l6-6 4 4 8-8M21 5v6M21 5h-6"/>'],
        'family'    => ['label' => 'Family',          'path' => '<path d="M12 4a4 4 0 100 8 4 4 0 000-8zM4 21c0-4 4-6 8-6s8 2 8 6"/>'],
        'lines'     => ['label' => 'Connectivity',    'path' => '<path d="M3 12h18M3 6h18M3 18h18"/>'],
        'grid'      => ['label' => 'Grid',            'path' => '<path d="M4 4h16v16H4zM4 9h16M9 4v16"/>'],
        'plus'      => ['label' => 'Crosshair',       'path' => '<path d="M12 2v20M2 12h20"/>'],
        'phone'     => ['label' => 'Phone',           'path' => '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.12.9.34 1.79.65 2.64a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.44-1.44a2 2 0 012.11-.45c.85.31 1.74.53 2.64.65A2 2 0 0122 16.92z"/>'],
        'whatsapp'  => ['label' => 'WhatsApp',        'path' => '<path d="M17.6 6.4A8 8 0 105.7 17.8L4 20l2.3-1.6a8 8 0 0011.3-12z"/>'],
        'email'     => ['label' => 'Email',           'path' => '<path d="M4 4h16v16H4zM22 6l-10 7L2 6"/>'],
        'address'   => ['label' => 'Location pin',    'path' => '<path d="M12 2C8 2 5 5 5 9c0 5.5 7 13 7 13s7-7.5 7-13c0-4-3-7-7-7z"/><circle cx="12" cy="9" r="2.4"/>'],
        'pool'      => ['label' => 'Swimming pool',   'path' => '<path d="M2 18c2 0 2-1.5 4-1.5S8 18 10 18s2-1.5 4-1.5S16 18 18 18s2-1.5 4-1.5M7 15V5a2 2 0 014 0v10M13 15V5a2 2 0 014 0v10M7 8h10"/>'],
        'lounge'    => ['label' => 'Lounge seating',  'path' => '<path d="M4 12V8a2 2 0 014 0v4M16 12V8a2 2 0 014 0v4M2 12h20v6H2zM5 18v2M19 18v2"/>'],
        'fitness'   => ['label' => 'Fitness',         'path' => '<path d="M4 8v8M8 6v12M16 6v12M20 8v8M8 12h8"/>'],
        'gate'      => ['label' => 'Gated access',    'path' => '<path d="M3 21V9l9-6 9 6v12M9 21V13h6v8M3 12h18"/>'],
        'clubhouse' => ['label' => 'Clubhouse',       'path' => '<path d="M2 20h20M4 20V10l8-6 8 6v10M10 20v-5h4v5"/>'],
        'play'      => ['label' => "Children's play", 'path' => '<path d="M12 3v6M8 21l4-12 4 12M5 12h14"/><circle cx="12" cy="4" r="1.6"/>'],
        'garden'    => ['label' => 'Landscaping',     'path' => '<path d="M12 21v-6M12 15c-3 0-5-2-5-5s2-6 5-6 5 3 5 6-2 5-5 5zM7 21h10"/>'],
        'power'     => ['label' => 'Utilities',       'path' => '<path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z"/>'],
        'water'     => ['label' => 'Water',           'path' => '<path d="M12 3s6 6.5 6 10a6 6 0 01-12 0c0-3.5 6-10 6-10z"/>'],
        'wifi'      => ['label' => 'Connectivity',    'path' => '<path d="M2 8.5a16 16 0 0120 0M5 12a11 11 0 0114 0M8.5 15.5a6 6 0 017 0"/><circle cx="12" cy="19" r="1.2"/>'],
        'car'       => ['label' => 'Parking',         'path' => '<path d="M5 17h14M3 17v-4l2-6h14l2 6v4M6 17v2M18 17v2M6 13h2M16 13h2"/>'],
        'key'       => ['label' => 'Ownership',       'path' => '<circle cx="8" cy="12" r="4"/><path d="M12 12h10M18 12v4M21 12v3"/>'],
        'star'      => ['label' => 'Quality',         'path' => '<path d="M12 3l2.7 5.6 6.3.9-4.5 4.3 1 6.2-5.5-3-5.5 3 1-6.2L3 9.5l6.3-.9z"/>'],
        'clock'     => ['label' => 'Time',            'path' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>'],
        'check'     => ['label' => 'Checkmark',       'path' => '<path d="M4 12.5l5 5 11-11"/>'],
        'document'  => ['label' => 'Document',        'path' => '<path d="M6 2h8l4 4v16H6zM14 2v4h4M9 12h6M9 16h6"/>'],
        'none'      => ['label' => 'No icon',         'path' => ''],
    ];

    public static function has(string $key): bool
    {
        return isset(self::SET[$key]);
    }

    /** @return array<string, string> key => label */
    public static function options(): array
    {
        return array_map(fn(array $i) => $i['label'], self::SET);
    }

    public static function svg(string $key, string $class = 'why-icon', float $strokeWidth = 1.3): string
    {
        $icon = self::SET[$key] ?? null;
        if ($icon === null || $icon['path'] === '') {
            return '';
        }
        return sprintf(
            '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" aria-hidden="true">%s</svg>',
            e($class),
            e((string)$strokeWidth),
            $icon['path']
        );
    }

    /** The Eden Ridge monogram used in the nav and footer. */
    public static function mark(): string
    {
        return '<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            . '<rect x="6" y="8" width="12" height="16" stroke="currentColor" stroke-width="1.3"/>'
            . '<rect x="20" y="14" width="12" height="18" stroke="currentColor" stroke-width="1.3"/>'
            . '<line x1="6" y1="14" x2="18" y2="14" stroke="currentColor" stroke-width="1.3"/>'
            . '<line x1="20" y1="20" x2="32" y2="20" stroke="currentColor" stroke-width="1.3"/></svg>';
    }

    /** Brand glyphs for the footer social row. */
    public static function social(string $platform): string
    {
        $paths = [
            'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/>',
            'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 00-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.8c0-.5.4-.8 1-.8z"/>',
            'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
            'tiktok'    => '<path d="M15 3v10.5a3.5 3.5 0 11-3.5-3.5M15 3c0 2.5 2 4.5 4.5 4.5"/>',
            'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 014 0v4"/>',
            'youtube'   => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10.5 9.5l5 2.5-5 2.5z"/>',
        ];
        $p = $paths[$platform] ?? null;
        if ($p === null) {
            return '';
        }
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">' . $p . '</svg>';
    }

    /** @return array<string,string> */
    public static function socialOptions(): array
    {
        return [
            'instagram' => 'Instagram',
            'facebook'  => 'Facebook',
            'x'         => 'X (Twitter)',
            'tiktok'    => 'TikTok',
            'linkedin'  => 'LinkedIn',
            'youtube'   => 'YouTube',
        ];
    }
}
