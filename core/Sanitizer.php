<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Allow-list HTML sanitizer for admin-authored content (pages, custom panels). */
final class Sanitizer
{
    private const ALLOWED = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li',
        'blockquote', 'code', 'pre', 'a', 'img', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'div',
        'small', 'sub', 'sup', 'figure', 'figcaption',
    ];
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'meta',
        'svg', 'math', 'textarea', 'select', 'noscript', 'template', 'base', 'applet', 'frame', 'frameset'];
    private const ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        '*' => ['class'],
    ];

    public static function html(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="mcms-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('mcms-root');
        if (!$root) {
            return e($html);
        }
        self::clean($root);
        $out = '';
        foreach ($root->childNodes as $n) {
            $out .= $doc->saveHTML($n);
        }
        return $out;
    }

    private static function clean(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            self::clean($child);
            if (!in_array($tag, self::ALLOWED, true)) { // unwrap unknown tags, keep their (cleaned) content
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $allowed = array_merge(self::ATTRS['*'], self::ATTRS[$tag] ?? []);
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed, true)) {
                    $child->removeAttribute($attr->name);
                } elseif (($name === 'href' || $name === 'src') && !self::safeUrl($attr->value)) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'a' && strtolower($child->getAttribute('target')) === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    private static function safeUrl(string $v): bool
    {
        $v = preg_replace('/[\x00-\x20]+/', '', $v) ?? '';
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $v, $m)) {
            return in_array(strtolower($m[1]), ['http', 'https', 'mailto'], true);
        }
        return true; // relative URL
    }
}
