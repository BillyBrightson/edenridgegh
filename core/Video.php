<?php
declare(strict_types=1);

namespace Core;

/**
 * The walkthrough lives on YouTube, Vimeo or Bunny — never on this server.
 * We store the provider and URL, and normalise it to a privacy-friendly
 * embed URL that is only loaded once a visitor asks for it.
 */
final class Video
{
    public const PROVIDERS = [
        'youtube'    => 'YouTube (unlisted)',
        'vimeo'      => 'Vimeo',
        'bunny'      => 'Bunny Stream',
        'direct_url' => 'Direct embed URL',
    ];

    public static function configured(): bool
    {
        return trim((string)Settings::get('video_url', '')) !== '';
    }

    /** A ready-to-use iframe src, or '' when nothing is configured. */
    public static function embedUrl(): string
    {
        $raw = trim((string)Settings::get('video_url', ''));
        if ($raw === '') {
            return '';
        }
        $provider = (string)Settings::get('video_provider', 'youtube');

        return match ($provider) {
            'youtube' => self::youtube($raw),
            'vimeo'   => self::vimeo($raw),
            default   => $raw,
        };
    }

    /** The URL emailed to a lead — the watch page, not the embed. */
    public static function shareUrl(): string
    {
        return trim((string)Settings::get('video_url', ''));
    }

    private static function youtube(string $raw): string
    {
        if (preg_match('#(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{6,})#', $raw, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
        }
        return $raw;
    }

    private static function vimeo(string $raw): string
    {
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $raw, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
        }
        return $raw;
    }

    /** Log a gated request against an enquiry and email the link. */
    public static function recordRequest(int $enquiryId): void
    {
        DB::insert('video_requests', [
            'enquiry_id' => $enquiryId,
            'revealed'   => 1,
            'created_at' => now(),
        ]);
        $enquiry = Enquiry::find($enquiryId);
        $url     = self::shareUrl();
        if (!$enquiry || $url === '') {
            return;
        }
        $body = Mailer::merge((string)Settings::get('video_email_body', ''), [
            'name'      => (string)$enquiry['name'],
            'video_url' => $url,
        ]);
        Mailer::queue(
            (string)$enquiry['email'],
            'Your Eden Ridge video walkthrough',
            Mailer::render('video_link', ['enquiry' => $enquiry, 'body' => $body, 'video_url' => $url]),
            (string)Settings::get('contact_email', '') ?: null
        );
    }

    public static function requestCount(): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM video_requests');
    }

    public static function requests(int $limit = 50): array
    {
        return DB::all(
            'SELECT vr.*, e.name, e.email, e.phone, e.status
             FROM video_requests vr
             LEFT JOIN enquiries e ON e.id = vr.enquiry_id
             ORDER BY vr.created_at DESC LIMIT ?',
            [$limit]
        );
    }
}
