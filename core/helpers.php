<?php
declare(strict_types=1);

/** Escape for HTML output. */
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Display helper for headline fields.
 * Editors write plain text; *stars* become the accent italic span and
 * newlines become <br>. Everything else is escaped.
 */
function headline(?string $v): string
{
    $out = e($v);
    $out = preg_replace('/\*([^*]+)\*/u', '<span class="italic">$1</span>', $out) ?? $out;
    return nl2br($out, false);
}

/** Escape + linkify newlines for body copy. */
function para(?string $v): string
{
    return nl2br(e($v), false);
}

/** Split a textarea into paragraphs on blank lines. */
function paragraphs(?string $v): array
{
    $parts = preg_split('/\n\s*\n/u', trim((string)$v)) ?: [];
    return array_values(array_filter(array_map('trim', $parts), fn($p) => $p !== ''));
}

/** Array get with dot notation. */
function arr_get(array $a, string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $a)) {
        return $a[$key];
    }
    $cur = $a;
    foreach (explode('.', $key) as $seg) {
        if (!is_array($cur) || !array_key_exists($seg, $cur)) {
            return $default;
        }
        $cur = $cur[$seg];
    }
    return $cur;
}

/** Rows of a repeater field, always an array of arrays. */
function rows(array $content, string $key): array
{
    $v = $content[$key] ?? [];
    if (!is_array($v)) {
        return [];
    }
    return array_values(array_filter($v, 'is_array'));
}

/** Is a repeater row visible? Rows default to visible. */
function row_visible(array $row): bool
{
    if (!array_key_exists('is_visible', $row)) {
        return true;
    }
    return (bool)$row['is_visible'];
}

function str_slug(string $v): string
{
    $v = strtolower(trim($v));
    $v = preg_replace('/[^a-z0-9]+/', '-', $v) ?? $v;
    return trim($v, '-') ?: 'item';
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function human_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $b = (float)$bytes;
    while ($b >= 1024 && $i < count($units) - 1) {
        $b /= 1024;
        $i++;
    }
    return ($i === 0 ? (string)(int)$b : number_format($b, 1)) . ' ' . $units[$i];
}

function human_date(?string $utc, string $format = 'j M Y, H:i'): string
{
    if (!$utc) {
        return '—';
    }
    $ts = strtotime($utc . ' UTC');
    return $ts ? date($format, $ts) : '—';
}

/** Hash an IP address — we never store raw addresses. */
function ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr(hash_hmac('sha256', $ip, \Core\Config::get('app_key', 'eden')), 0, 32);
}

function client_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    return $xhr || str_contains($accept, 'application/json');
}

/** Versioned asset URL — busts caches on deploy without manual renaming. */
function asset(string $path): string
{
    $abs = PUBLIC_PATH . $path;
    $v   = is_file($abs) ? (string)filemtime($abs) : '1';
    return $path . '?v=' . $v;
}

function admin_asset(string $path): string
{
    $abs = ADMIN_PUBLIC_PATH . $path;
    $v   = is_file($abs) ? (string)filemtime($abs) : '1';
    return $path . '?v=' . $v;
}

/** Digits-only phone, for tel: and wa.me links. */
function phone_digits(?string $v): string
{
    return preg_replace('/\D+/', '', (string)$v) ?? '';
}
