<?php
declare(strict_types=1);

namespace Core;

final class Enquiry
{
    public const STATUSES = [
        'new'       => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'won'       => 'Won',
        'lost'      => 'Lost',
        'spam'      => 'Spam',
    ];

    private const RATE_LIMIT       = 5;    // submissions
    private const RATE_WINDOW      = 3600; // per hour, per IP
    private const MIN_FORM_SECONDS = 3;

    /**
     * Validate a submission. Returns [cleanData, errors].
     * @return array{0: array, 1: array<string,string>}
     */
    public static function validate(array $input, array $interestOptions): array
    {
        $errors = [];
        $name    = Sanitizer::text((string)($input['name'] ?? ''));
        $email   = trim((string)($input['email'] ?? ''));
        $phone   = Sanitizer::text((string)($input['phone'] ?? ''));
        $interest = Sanitizer::text((string)($input['interest'] ?? ''));
        $message = trim(strip_tags((string)($input['message'] ?? '')));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Please enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (!self::domainResolves($email)) {
            // Soft check: only reject when DNS is available and clearly says no.
            $errors['email'] = 'We could not find that email domain. Please check the spelling.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\s.\-]{6,32}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number, or leave it blank.';
        }
        if ($interest !== '' && $interestOptions !== [] && !in_array($interest, $interestOptions, true)) {
            $interest = $interestOptions[0];
        }
        if (mb_strlen($message) > 2000) {
            $errors['message'] = 'Please keep your message under 2000 characters.';
        }

        return [[
            'name'     => $name,
            'email'    => mb_strtolower($email),
            'phone'    => $phone,
            'interest' => $interest,
            'message'  => $message,
        ], $errors];
    }

    /** MX/A lookup, skipped entirely when DNS functions are unavailable. */
    private static function domainResolves(string $email): bool
    {
        if (!function_exists('checkdnsrr')) {
            return true;
        }
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }
        try {
            return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Layered spam checks. Returns an error string, or null when the
     * submission looks human.
     */
    public static function spamProblem(array $input): ?string
    {
        if (trim((string)($input['website'] ?? '')) !== '') {
            Logger::info('Honeypot triggered', ['ip' => ip_hash()]);
            return 'Your message could not be sent.';
        }
        $started = (int)($input['form_started'] ?? 0);
        if ($started > 0 && (time() - $started) < self::MIN_FORM_SECONDS) {
            return 'That was submitted a little too quickly — please try once more.';
        }
        if (self::rateLimited()) {
            return 'You have sent several enquiries recently. Please email us directly.';
        }
        if (Settings::get('turnstile_secret', '') !== '' && !self::turnstilePasses($input)) {
            return 'The anti-spam check failed. Please reload the page and try again.';
        }
        return null;
    }

    public static function rateLimited(): bool
    {
        $since = gmdate('Y-m-d H:i:s', time() - self::RATE_WINDOW);
        $count = (int)DB::value(
            'SELECT COUNT(*) FROM enquiries WHERE ip_hash = ? AND created_at > ?',
            [ip_hash(), $since]
        );
        return $count >= self::RATE_LIMIT;
    }

    private static function turnstilePasses(array $input): bool
    {
        $token = (string)($input['cf-turnstile-response'] ?? '');
        if ($token === '') {
            return false;
        }
        $body = http_build_query([
            'secret'   => (string)Settings::get('turnstile_secret', ''),
            'response' => $token,
            'remoteip' => client_ip(),
        ]);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 5,
        ]]);
        $raw = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
        if ($raw === false) {
            Logger::error('Turnstile verification unreachable — allowing the submission');
            return true; // Never block a real buyer because Cloudflare is down.
        }
        return (bool)(json_decode($raw, true)['success'] ?? false);
    }

    /** Persist an enquiry. Kept short: SQLite is a single-writer database. */
    public static function create(array $data, bool $videoRequest = false): int
    {
        return DB::insert('enquiries', [
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?: null,
            'interest'      => $data['interest'] ?: null,
            'message'       => $data['message'] ?: null,
            'status'        => 'new',
            'video_request' => $videoRequest ? 1 : 0,
            'source_page'   => mb_substr((string)($data['source_page'] ?? '/'), 0, 190),
            'referrer'      => mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 250) ?: null,
            'utm_source'    => self::utm('utm_source'),
            'utm_medium'    => self::utm('utm_medium'),
            'utm_campaign'  => self::utm('utm_campaign'),
            'ip_hash'       => ip_hash(),
            'user_agent'    => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
            'is_spam'       => 0,
            'deleted_at'    => null,
            'created_at'    => now(),
        ]);
    }

    private static function utm(string $key): ?string
    {
        $v = Sanitizer::text((string)($_POST[$key] ?? $_GET[$key] ?? ''));
        return $v !== '' ? mb_substr($v, 0, 120) : null;
    }

    /** Queue the sales notification and the branded auto-reply. */
    public static function notify(int $id): void
    {
        $enquiry = self::find($id);
        if (!$enquiry) {
            return;
        }
        $investor   = self::isInvestor($enquiry);
        $recipients = self::recipients($investor);
        $adminLink  = rtrim((string)Config::get('admin_url', ''), '/') . '/enquiries/' . $id;

        foreach ($recipients as $to) {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            Mailer::queue(
                $to,
                'New enquiry — ' . $enquiry['name'] . ($enquiry['interest'] ? ' (' . $enquiry['interest'] . ')' : ''),
                Mailer::render('enquiry_notification', ['enquiry' => $enquiry, 'admin_link' => $adminLink]),
                (string)$enquiry['email']
            );
        }

        if (Settings::bool('autoreply_enabled', true)) {
            $subject = (string)Settings::get('autoreply_subject', 'Thank you for your interest in Eden Ridge');
            $bodyTpl = (string)Settings::get('autoreply_body', '');
            $body    = Mailer::merge($bodyTpl, [
                'name'     => $enquiry['name'],
                'email'    => $enquiry['email'],
                'phone'    => $enquiry['phone'] ?? '',
                'interest' => $enquiry['interest'] ?? '',
            ]);
            Mailer::queue(
                (string)$enquiry['email'],
                $subject,
                Mailer::render('enquiry_autoreply', ['enquiry' => $enquiry, 'body' => $body]),
                self::replyTo($investor)
            );
        }
    }

    /**
     * Investor enquiries have their own inbox. Matching on the substring rather
     * than the exact dropdown label keeps this working when the client rewords
     * the option under Home page -> Enquiry form.
     */
    private static function isInvestor(array $enquiry): bool
    {
        return stripos((string)($enquiry['interest'] ?? ''), 'invest') !== false;
    }

    /** @return list<string> the inbox list for this enquiry, sales as the fallback */
    private static function recipients(bool $investor): array
    {
        $list = $investor ? (string)Settings::get('investor_emails', '') : '';
        if (trim($list) === '') {
            $list = (string)Settings::get('notification_emails', '');
        }
        return array_values(array_filter(array_map('trim', explode(',', $list))));
    }

    /** Where a reply to the auto-reply should land. */
    private static function replyTo(bool $investor): ?string
    {
        $candidates = $investor ? self::recipients(true) : [];
        $candidates[] = (string)Settings::get('contact_email', '');
        foreach ($candidates as $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return $address;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Inbox
    // ------------------------------------------------------------------

    public static function find(int $id): ?array
    {
        return DB::first('SELECT * FROM enquiries WHERE id = ?', [$id]);
    }

    /** @return array{rows: array, total: int} */
    public static function search(array $filters, int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = self::buildWhere($filters);
        $order = in_array((string)($filters['sort'] ?? ''), ['name', 'status', 'interest', 'email'], true)
            ? '"' . $filters['sort'] . '" ASC'
            : 'created_at DESC';
        $rows = DB::all(
            "SELECT * FROM enquiries WHERE {$where} ORDER BY {$order}, id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );
        $total = (int)DB::value("SELECT COUNT(*) FROM enquiries WHERE {$where}", $params);
        return ['rows' => $rows, 'total' => $total];
    }

    /** Every matching row, for CSV export. */
    public static function exportRows(array $filters): array
    {
        [$where, $params] = self::buildWhere($filters);
        return DB::all("SELECT * FROM enquiries WHERE {$where} ORDER BY created_at DESC", $params);
    }

    /** @return array{0:string, 1:array} */
    private static function buildWhere(array $filters): array
    {
        $where  = [(string)($filters['trash'] ?? '') === '1' ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL'];
        $params = [];

        $status = (string)($filters['status'] ?? '');
        if ($status !== '' && isset(self::STATUSES[$status])) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $interest = (string)($filters['interest'] ?? '');
        if ($interest !== '') {
            $where[] = 'interest = ?';
            $params[] = $interest;
        }
        if ((string)($filters['video'] ?? '') === '1') {
            $where[] = 'video_request = 1';
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $from = trim((string)($filters['from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        $to = trim((string)($filters['to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        return [implode(' AND ', $where), $params];
    }

    public static function unreadCount(): int
    {
        return (int)DB::value("SELECT COUNT(*) FROM enquiries WHERE status = 'new' AND deleted_at IS NULL");
    }

    public static function countSince(string $utc): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM enquiries WHERE created_at >= ? AND deleted_at IS NULL', [$utc]);
    }

    public static function setStatus(int $id, string $status, int $userId): void
    {
        if (!isset(self::STATUSES[$status])) {
            return;
        }
        DB::update('enquiries', [
            'status'  => $status,
            'is_spam' => $status === 'spam' ? 1 : 0,
        ], 'id = :id', ['id' => $id]);
        Activity::log($userId, 'enquiry.status', 'enquiry', $id, ['status' => $status]);
    }

    public static function addNote(int $id, string $body, int $userId): void
    {
        $body = Sanitizer::text($body);
        if ($body === '') {
            return;
        }
        DB::insert('enquiry_notes', [
            'enquiry_id' => $id,
            'user_id'    => $userId,
            'body'       => mb_substr($body, 0, 2000),
            'created_at' => now(),
        ]);
        Activity::log($userId, 'enquiry.note', 'enquiry', $id);
    }

    public static function notes(int $id): array
    {
        return DB::all(
            'SELECT n.*, u.name AS user_name FROM enquiry_notes n
             LEFT JOIN users u ON u.id = n.user_id
             WHERE n.enquiry_id = ? ORDER BY n.created_at ASC',
            [$id]
        );
    }

    public static function trash(int $id, int $userId): void
    {
        DB::update('enquiries', ['deleted_at' => now()], 'id = :id', ['id' => $id]);
        Activity::log($userId, 'enquiry.delete', 'enquiry', $id);
    }

    public static function restore(int $id, int $userId): void
    {
        DB::update('enquiries', ['deleted_at' => null], 'id = :id', ['id' => $id]);
        Activity::log($userId, 'enquiry.restore', 'enquiry', $id);
    }

    /** Purge anything sitting in the trash for more than 30 days. */
    public static function purgeOldTrash(): void
    {
        DB::delete('enquiries', 'deleted_at IS NOT NULL AND deleted_at < ?', [gmdate('Y-m-d H:i:s', time() - 30 * 86400)]);
    }

    public static function toCsv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Date (UTC)', 'Name', 'Email', 'Phone', 'Interest', 'Message', 'Status', 'Video request', 'Source', 'Referrer', 'UTM source', 'UTM medium', 'UTM campaign']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['created_at'], $r['name'], $r['email'], $r['phone'], $r['interest'],
                $r['message'], $r['status'], $r['video_request'] ? 'yes' : 'no',
                $r['source_page'], $r['referrer'], $r['utm_source'], $r['utm_medium'], $r['utm_campaign'],
            ]);
        }
        rewind($out);
        return (string)stream_get_contents($out);
    }

    /** Pre-filled WhatsApp reply link for the inbox. */
    public static function whatsappLink(array $enquiry): string
    {
        $number = phone_digits((string)($enquiry['phone'] ?? ''));
        if ($number === '') {
            return '';
        }
        $text = 'Hello ' . $enquiry['name'] . ', thank you for your enquiry about Eden Ridge.';
        return 'https://wa.me/' . $number . '?text=' . rawurlencode($text);
    }
}
