<?php
declare(strict_types=1);

namespace Core;

/**
 * A minimal SMTP client. The host may have no Composer and no CLI, and
 * bare mail() is unreliable for deliverability, so we speak SMTP directly:
 * EHLO, optional STARTTLS, AUTH LOGIN/PLAIN, MAIL FROM, RCPT TO, DATA.
 */
final class Mailer
{
    private const TIMEOUT = 20;

    /** Queue a message, then attempt delivery immediately. */
    public static function queue(string $to, string $subject, string $bodyHtml, ?string $replyTo = null): int
    {
        $id = DB::insert('mail_outbox', [
            'to_email'   => $to,
            'subject'    => $subject,
            'body_html'  => $bodyHtml,
            'reply_to'   => $replyTo,
            'attempts'   => 0,
            'last_error' => null,
            'sent_at'    => null,
            'created_at' => now(),
        ]);
        self::attempt($id);
        return $id;
    }

    /** Try to deliver one queued message. */
    public static function attempt(int $id): bool
    {
        $row = DB::first('SELECT * FROM mail_outbox WHERE id = ? AND sent_at IS NULL', [$id]);
        if (!$row) {
            return false;
        }
        try {
            self::send((string)$row['to_email'], (string)$row['subject'], (string)$row['body_html'], $row['reply_to'] ? (string)$row['reply_to'] : null);
            DB::update('mail_outbox', ['sent_at' => now(), 'last_error' => null], 'id = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $ex) {
            DB::update('mail_outbox', [
                'attempts'   => (int)$row['attempts'] + 1,
                'last_error' => mb_substr($ex->getMessage(), 0, 400),
            ], 'id = :id', ['id' => $id]);
            Logger::error('Mail delivery failed', ['to' => $row['to_email'], 'error' => $ex->getMessage()]);
            return false;
        }
    }

    /**
     * Poor-man's queue worker: retry a couple of pending messages per request.
     * Called on public requests so an SMTP outage self-heals with no cron.
     */
    public static function drain(int $limit = 2): void
    {
        $pending = DB::all(
            'SELECT id FROM mail_outbox WHERE sent_at IS NULL AND attempts < 5 ORDER BY id ASC LIMIT ?',
            [$limit]
        );
        foreach ($pending as $row) {
            self::attempt((int)$row['id']);
        }
    }

    public static function pendingCount(): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM mail_outbox WHERE sent_at IS NULL AND attempts > 0');
    }

    public static function failed(int $limit = 20): array
    {
        return DB::all(
            'SELECT * FROM mail_outbox WHERE sent_at IS NULL AND attempts > 0 ORDER BY id DESC LIMIT ?',
            [$limit]
        );
    }

    /** Effective SMTP settings: dashboard values win over config.php. */
    public static function settings(): array
    {
        $cfg = (array)Config::get('mail', []);
        return [
            'transport'  => (string)Settings::get('smtp_transport', $cfg['transport'] ?? 'smtp'),
            'host'       => (string)Settings::get('smtp_host', $cfg['host'] ?? ''),
            'port'       => (int)Settings::get('smtp_port', $cfg['port'] ?? 587),
            'encryption' => (string)Settings::get('smtp_encryption', $cfg['encryption'] ?? 'tls'),
            'username'   => (string)Settings::get('smtp_username', $cfg['username'] ?? ''),
            'password'   => (string)Settings::get('smtp_password', $cfg['password'] ?? ''),
            'from_email' => (string)Settings::get('smtp_from_email', $cfg['from_email'] ?? 'no-reply@edenridgegh.com'),
            'from_name'  => (string)Settings::get('smtp_from_name', $cfg['from_name'] ?? 'Eden Ridge'),
        ];
    }

    /** @throws \RuntimeException on any delivery failure */
    public static function send(string $to, string $subject, string $bodyHtml, ?string $replyTo = null): void
    {
        $s = self::settings();

        if ($s['transport'] === 'log') {
            Logger::info('Mail (log transport)', ['to' => $to, 'subject' => $subject]);
            return;
        }
        if ($s['host'] === '') {
            throw new \RuntimeException('No SMTP host is configured.');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid recipient address: ' . $to);
        }

        $transport = $s['encryption'] === 'ssl' ? 'ssl://' : '';
        $context   = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $socket    = @stream_socket_client(
            $transport . $s['host'] . ':' . $s['port'],
            $errno,
            $errstr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$socket) {
            throw new \RuntimeException("Could not connect to {$s['host']}:{$s['port']} — {$errstr}");
        }
        stream_set_timeout($socket, self::TIMEOUT);

        try {
            self::expect($socket, 220);
            $hostname = (string)(parse_url((string)Config::get('site_url', 'edenridgegh.com'), PHP_URL_HOST) ?: 'edenridgegh.com');
            self::command($socket, 'EHLO ' . $hostname, 250);

            if ($s['encryption'] === 'tls') {
                self::command($socket, 'STARTTLS', 220);
                $ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$ok) {
                    throw new \RuntimeException('STARTTLS negotiation failed.');
                }
                self::command($socket, 'EHLO ' . $hostname, 250);
            }

            if ($s['username'] !== '') {
                self::command($socket, 'AUTH LOGIN', 334);
                self::command($socket, base64_encode($s['username']), 334);
                self::command($socket, base64_encode($s['password']), 235);
            }

            self::command($socket, 'MAIL FROM:<' . $s['from_email'] . '>', 250);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', 354);

            $message = self::buildMessage($to, $subject, $bodyHtml, $replyTo, $s);
            fwrite($socket, $message . "\r\n.\r\n");
            self::expect($socket, 250);
            self::command($socket, 'QUIT', [221, 250]);
        } finally {
            @fclose($socket);
        }
    }

    private static function buildMessage(string $to, string $subject, string $bodyHtml, ?string $replyTo, array $s): string
    {
        $boundary = 'eden' . bin2hex(random_bytes(12));
        $text = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>#i', "\n", $bodyHtml) ?? $bodyHtml), ENT_QUOTES, 'UTF-8'));

        $headers = [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . self::encodeHeader($s['from_name']) . ' <' . $s['from_email'] . '>',
            'To: <' . $to . '>',
            'Subject: ' . self::encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (parse_url((string)Config::get('site_url', ''), PHP_URL_HOST) ?: 'edenridgegh.com') . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($text) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($bodyHtml) . "\r\n"
            . "--{$boundary}--";

        // Dot-stuffing, per RFC 5321.
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        return preg_replace('/^\./m', '..', $message) ?? $message;
    }

    private static function encodeHeader(string $value): string
    {
        return preg_match('/[\x80-\xFF]/', $value)
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }

    /** @param int|int[] $expected */
    private static function command($socket, string $command, int|array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expected);
    }

    /** @param int|int[] $expected */
    private static function expect($socket, int|array $expected): string
    {
        $expected = (array)$expected;
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        $code = (int)substr(trim($response), 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
        return $response;
    }

    /** Render an email template into the branded shell. */
    public static function render(string $template, array $data = []): string
    {
        $inner = View::render('emails/' . $template, $data);
        return View::render('emails/layout', ['content' => $inner] + $data);
    }

    /** Replace {{merge}} tags in an editable template. */
    public static function merge(string $template, array $values): string
    {
        foreach ($values as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
        return preg_replace('/\{\{[a-z_]+\}\}/', '', $template) ?? $template;
    }
}
