<?php
declare(strict_types=1);

namespace Core;

/**
 * Allow-list HTML sanitiser for the restricted richtext fields
 * (bold, italic, links, unordered lists, paragraphs, line breaks).
 * Applied on save AND on render.
 */
final class Sanitizer
{
    private const ALLOWED = [
        'p'      => [],
        'br'     => [],
        'strong' => [],
        'b'      => [],
        'em'     => [],
        'i'      => [],
        'ul'     => [],
        'ol'     => [],
        'li'     => [],
        'a'      => ['href', 'title', 'target', 'rel'],
        'span'   => ['class'],
    ];

    public static function richtext(?string $html): string
    {
        $html = trim((string)$html);
        if ($html === '') {
            return '';
        }
        // Strip anything that can execute before parsing.
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*/?>#is', '', $html) ?? $html;

        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="eden-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('eden-root');
        if (!$root) {
            return e(strip_tags($html));
        }
        self::clean($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function clean(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $child->parentNode?->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (!array_key_exists($tag, self::ALLOWED)) {
                // Unwrap unknown elements, keeping their text content.
                while ($child->firstChild) {
                    $child->parentNode?->insertBefore($child->firstChild, $child);
                }
                $child->parentNode?->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (!in_array($name, self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                    continue;
                }
                if ($name === 'href' && !self::safeUrl($attr->nodeValue ?? '')) {
                    $child->removeAttribute('href');
                }
                if ($name === 'class' && !preg_match('/^[a-z0-9 _-]*$/i', $attr->nodeValue ?? '')) {
                    $child->removeAttribute('class');
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            self::clean($child);
        }
    }

    private static function safeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (preg_match('#^(https?:|mailto:|tel:|/|\#)#i', $url)) {
            return true;
        }
        return false;
    }

    /** Sanitise an uploaded SVG: strip scripts, event handlers and external refs. */
    public static function svg(string $svg): string
    {
        $svg = preg_replace('#<(script|foreignObject)\b.*?</\1>#is', '', $svg) ?? $svg;
        $svg = preg_replace('#<!DOCTYPE[^>]*>#i', '', $svg) ?? $svg;
        $svg = preg_replace('#<\?xml-stylesheet[^>]*\?>#i', '', $svg) ?? $svg;
        $svg = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $svg) ?? $svg;
        $svg = preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i", '', $svg) ?? $svg;
        // Only fragment references survive.
        $svg = preg_replace('#\s(xlink:href|href)\s*=\s*"(?!\#)[^"]*"#i', '', $svg) ?? $svg;
        $svg = preg_replace('#javascript:#i', '', $svg) ?? $svg;
        return $svg;
    }

    /** A plain-text-only field: strip all markup. */
    public static function text(?string $v): string
    {
        return trim(strip_tags((string)$v));
    }
}
