<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Safe BBCode → HTML. Everything is HTML-escaped FIRST; only whitelisted tags are then
 * re-introduced from the escaped text, so user input can never inject markup or attributes.
 * Supported: [b] [i] [u] [s] [quote] [quote=Name] [code] [url] [url=…] [img] (https only) [list]/[list=1] [*] [spoiler]
 */
final class Bbcode
{
    public static function render(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\x01", '', $text);

        // 1. protect code blocks
        $codes = [];
        $text = preg_replace_callback('/\[code\](.*?)\[\/code\]/is', static function ($m) use (&$codes) {
            $codes[] = '<pre class="bb-code"><code>' . htmlspecialchars(trim($m[1], "\n"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
            return "\x01" . (count($codes) - 1) . "\x01";
        }, (string) $text);

        // 2. escape everything else
        $s = htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 3. inline tags (repeat so nesting works)
        $inline = ['b' => 'strong', 'i' => 'em', 'u' => 'u', 's' => 's'];
        for ($i = 0; $i < 4; $i++) {
            foreach ($inline as $bb => $html) {
                $s = preg_replace('/\[' . $bb . '\]((?:(?!\[' . $bb . '\]).)*?)\[\/' . $bb . '\]/is', "<$html>$1</$html>", $s);
            }
        }
        $s = preg_replace('/\[spoiler\](.*?)\[\/spoiler\]/is', '<details class="bb-spoiler"><summary>Spoiler</summary>$1</details>', $s);

        // 4. links & images (strict URL grammar; quotes are already entity-escaped)
        $url = 'https?:\/\/[^\s\[\]<>]+';
        $s = preg_replace('/\[url=(' . $url . ')\](.*?)\[\/url\]/is', '<a href="$1" rel="nofollow ugc noopener" target="_blank">$2</a>', $s);
        $s = preg_replace('/\[url\](' . $url . ')\[\/url\]/i', '<a href="$1" rel="nofollow ugc noopener" target="_blank">$1</a>', $s);
        $s = preg_replace('/\[img\](https:\/\/[^\s\[\]<>]+)\[\/img\]/i', '<img class="bb-img" src="$1" alt="" loading="lazy">', $s);
        $s = self::autolink((string) $s);

        // 5. lists
        $s = preg_replace_callback('/\[list(=1)?\]\s*(.*?)\[\/list\]/is', static function ($m) {
            $items = array_filter(array_map('trim', explode('[*]', $m[2])), 'strlen');
            $tag = $m[1] ? 'ol' : 'ul';
            return "<$tag>" . implode('', array_map(static fn($i) => '<li>' . $i . '</li>', $items)) . "</$tag>";
        }, $s);

        // 6. quotes (innermost first, max depth 5)
        for ($i = 0; $i < 5; $i++) {
            $n = 0;
            $s = preg_replace_callback('/\[quote(?:=(?:&quot;)?([^\]\n]{1,40}?)(?:&quot;)?)?\]((?:(?!\[quote[\]=]).)*?)\[\/quote\]/is', static function ($m) {
                $who = $m[1] !== '' && $m[1] !== null ? '<cite>' . $m[1] . ' ' . htmlspecialchars(t('wrote'), ENT_QUOTES, 'UTF-8') . ':</cite>' : '';
                return '<blockquote class="bb-quote">' . $who . $m[2] . '</blockquote>';
            }, $s, -1, $n);
            if (!$n) {
                break;
            }
        }

        // 7. emoticons (only when standing alone) + line breaks
        $s = Emoji::render((string) $s);
        $s = nl2br($s, false);
        // no stray line breaks around block-level output (lists, quotes, code, spoilers)
        $s = preg_replace('~(?:<br>\s*)+(?=<(?:ul|ol|blockquote|details)\b|\x01)~', '', (string) $s);
        $s = preg_replace('~(</(?:ul|ol|blockquote|details)>|\x01\d+\x01)(?:\s*<br>)+~', '$1', (string) $s);

        // 8. restore code blocks
        return (string) preg_replace_callback('/\x01(\d+)\x01/', static fn($m) => $codes[(int) $m[1]] ?? '', $s);
    }

    /** Turn bare URLs into links – in plain text only, never inside a tag or an existing link (that would corrupt the markup). */
    private static function autolink(string $html): string
    {
        $out = '';
        $inLink = 0;
        foreach (preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if ($part[0] === '<') {
                if (preg_match('/^<a[\s>]/i', $part)) {
                    $inLink++;
                } elseif (stripos($part, '</a') === 0) {
                    $inLink = max(0, $inLink - 1);
                }
                $out .= $part;
            } elseif ($inLink === 0) {
                $out .= preg_replace('~(https?://[^\s<\[]+)~i', '<a href="$1" rel="nofollow ugc noopener" target="_blank">$1</a>', $part);
            } else {
                $out .= $part;
            }
        }
        return $out;
    }

    /** Plain-text excerpt for lists/search results. */
    public static function excerpt(string $text, int $len = 160): string
    {
        $t = preg_replace('/\[(?:quote|code|spoiler)[^\]]*\].*?\[\/(?:quote|code|spoiler)\]/is', ' ', $text);
        $t = preg_replace('/\[\/?[a-z*]+(?:=[^\]]*)?\]/i', '', (string) $t);
        $t = trim(preg_replace('/\s+/u', ' ', (string) $t));
        return mb_strlen($t) > $len ? mb_substr($t, 0, $len - 1) . '…' : $t;
    }
}
