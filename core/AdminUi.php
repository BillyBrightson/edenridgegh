<?php
declare(strict_types=1);

namespace Core;

/** Small presentational helpers for the dashboard chrome. */
final class AdminUi
{
    private const NAV_ICONS = [
        'dashboard' => '<path d="M4 13h6V4H4zM14 20h6v-9h-6zM4 20h6v-4H4zM14 8h6V4h-6z"/>',
        'pages'     => '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 12h6M9 16h6"/>',
        'inbox'     => '<path d="M3 13h5l1 3h6l1-3h5M3 13l2-8h14l2 8v6H3z"/>',
        'media'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.6"/><path d="M4 17l5-5 4 4 3-2 4 4"/>',
        'gallery'   => '<rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="5"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="10" width="8" height="11"/>',
        'video'     => '<rect x="3" y="6" width="12" height="12" rx="2"/><path d="M15 10l6-3v10l-6-3z"/>',
        'brush'     => '<path d="M4 20c3 0 3-4 6-4 2 0 3 1 3 3M13 13l6-9 2 2-9 6z"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'seo'       => '<circle cx="11" cy="11" r="7"/><path d="M16 16l5 5"/>',
        'users'     => '<circle cx="9" cy="8" r="3.4"/><path d="M2 21c0-4 3.5-6 7-6s7 2 7 6M17 8.5a3 3 0 010 5M18 21c0-2-.6-3.4-1.5-4.4"/>',
        'activity'  => '<path d="M3 12h4l3 8 4-16 3 8h4"/>',
        'tools'     => '<path d="M14.5 3.5a5 5 0 00-6 6L3 15v6h6l5.5-5.5a5 5 0 006-6l-3.2 3.2-2.8-.7-.7-2.8z"/>',
    ];

    public static function icon(string $key): string
    {
        $path = self::NAV_ICONS[$key] ?? '';
        if ($path === '') {
            return '';
        }
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
    }

    /** Tiny action glyphs used inside repeater rows. */
    public static function glyph(string $key): string
    {
        $paths = [
            'drag'      => '<path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/>',
            'trash'     => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
            'duplicate' => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5h10"/>',
            'up'        => '<path d="M12 19V5M6 11l6-6 6 6"/>',
            'down'      => '<path d="M12 5v14M6 13l6 6 6-6"/>',
            'plus'      => '<path d="M12 5v14M5 12h14"/>',
            'eye'       => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.6"/>',
            'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
            'download'  => '<path d="M12 4v11M7 12l5 5 5-5M4 20h16"/>',
        ];
        $path = $paths[$key] ?? '';
        if ($path === '') {
            return '';
        }
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? 'E', 0, 1);
        $last  = count($parts) > 1 ? mb_substr((string)end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    }

    /** Sidebar definition: [path, label, icon, ability]. */
    public static function nav(): array
    {
        return [
            ['/', 'Dashboard', 'dashboard', 'view_dashboard'],
            ['__group', 'Content', '', ''],
            ['/pages/home', 'Home page', 'pages', 'edit_content'],
            ['/pages/privacy', 'Privacy page', 'pages', 'edit_content'],
            ['/pages/terms', 'Terms page', 'pages', 'edit_content'],
            ['/media', 'Media library', 'media', 'edit_content'],
            ['/gallery', 'Gallery', 'gallery', 'edit_content'],
            ['/video', 'Video tour', 'video', 'edit_content'],
            ['__group', 'Leads', '', ''],
            ['/enquiries', 'Enquiries', 'inbox', 'view_enquiries'],
            ['__group', 'Configuration', '', ''],
            ['/appearance', 'Appearance', 'brush', 'edit_content'],
            ['/settings', 'Site settings', 'settings', 'edit_content'],
            ['/seo', 'SEO', 'seo', 'edit_content'],
            ['/users', 'Users', 'users', 'manage_users'],
            ['/activity', 'Activity log', 'activity', 'view_activity'],
            ['/tools', 'Tools', 'tools', 'manage_tools'],
        ];
    }
}
