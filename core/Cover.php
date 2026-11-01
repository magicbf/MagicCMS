<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Procedural abstract covers. Deterministic: the same seed + colour + style always produce the same SVG,
 * so posts without a picture get stable artwork with zero storage and no external services.
 * Styles: blades, orbits, mesh, waves.
 */
final class Cover
{
    public const STYLES = ['blades', 'orbits', 'mesh', 'waves'];

    private int $state;

    private function __construct(int $seed)
    {
        $this->state = ($seed * 2654435761 + 12345) & 0xFFFFFFFF ?: 1;
    }

    /** xorshift32 → float 0..1 */
    private function r(): float
    {
        $x = $this->state;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;
        $this->state = $x & 0xFFFFFFFF;
        return $this->state / 4294967296;
    }

    private function between(float $a, float $b): float
    {
        return $a + ($b - $a) * $this->r();
    }

    /** Mix two #rrggbb colours (0 = a, 1 = b). */
    public static function mix(string $a, string $b, float $t): string
    {
        $pa = sscanf(ltrim($a, '#'), '%2x%2x%2x');
        $pb = sscanf(ltrim($b, '#'), '%2x%2x%2x');
        return sprintf('#%02x%02x%02x', ...array_map(fn($i) => (int) round($pa[$i] + ($pb[$i] - $pa[$i]) * $t), [0, 1, 2]));
    }

    public static function svg(int $seed, string $hex, string $style = 'blades', int $w = 1280, int $h = 720): string
    {
        $hex = is_hex($hex) ? strtolower($hex) : '#ff5238';
        $style = in_array($style, self::STYLES, true) ? $style : self::STYLES[$seed % 4];
        $c = new self($seed + crc32($style));
        $bg = self::mix($hex, '#090605', .86);
        $light = self::mix($hex, '#ffffff', .38);
        $deep = self::mix($hex, '#000000', .55);

        $defs = '<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . self::mix($hex, '#090605', .72) . '"/><stop offset="1" stop-color="' . $bg . '"/></linearGradient>'
            . '<linearGradient id="gA" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $light . '"/><stop offset=".55" stop-color="' . $hex . '"/><stop offset="1" stop-color="' . $deep . '"/></linearGradient>'
            . '<linearGradient id="gB" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="' . $deep . '"/><stop offset="1" stop-color="' . $hex . '"/></linearGradient>'
            . '<radialGradient id="glow"><stop offset="0" stop-color="' . $hex . '" stop-opacity=".75"/><stop offset="1" stop-color="' . $hex . '" stop-opacity="0"/></radialGradient>'
            . '<radialGradient id="vig" cx=".5" cy=".45" r=".75"><stop offset=".55" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".55"/></radialGradient>';
        $body = '<rect width="' . $w . '" height="' . $h . '" fill="url(#bg)"/>';

        switch ($style) {
            case 'blades':
                $body .= '<g transform="rotate(' . round($c->between(-26, -12), 1) . ' ' . $w / 2 . ' ' . $h / 2 . ')">';
                $n = 6 + (int) $c->between(0, 3);
                $x = -$w * .25;
                for ($i = 0; $i < $n; $i++) {
                    $bw = $c->between($w * .07, $w * .2);
                    $top = $c->between(-$h * .2, $h * .25);
                    $bot = $c->between($h * .75, $h * 1.2);
                    $sk = $c->between(40, 150);
                    $fill = $i % 3 === 0 ? 'url(#gA)' : ($i % 3 === 1 ? 'url(#gB)' : $deep);
                    $op = round($c->between(.18, .92), 2);
                    $body .= sprintf('<polygon points="%.1f,%.1f %.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="%s" opacity="%s"/>', $x, $bot, $x + $sk, $top, $x + $sk + $bw, $top, $x + $bw, $bot, $fill, $op);
                    $x += $bw * $c->between(.55, 1.05);
                }
                $body .= '</g>';
                for ($i = 0; $i < 4; $i++) {
                    $y = $c->between($h * .1, $h * .9);
                    $body .= sprintf('<line x1="0" y1="%.1f" x2="%d" y2="%.1f" stroke="%s" stroke-opacity=".18" stroke-width="1.5"/>', $y, $w, $y - $c->between(60, 220), $light);
                }
                break;

            case 'orbits':
                $cx = $c->between($w * .3, $w * .7);
                $cy = $c->between($h * .3, $h * .7);
                $body .= sprintf('<circle cx="%.0f" cy="%.0f" r="%.0f" fill="url(#glow)" opacity=".55"/>', $cx, $cy, $h * .8);
                $n = 5 + (int) $c->between(0, 3);
                for ($i = 0; $i < $n; $i++) {
                    $rx = $w * ($i + 2) * .085;
                    $ry = $rx * $c->between(.22, .5);
                    $rot = $c->between(-70, 70);
                    $op = round(.5 - $i * .05, 2);
                    $body .= sprintf('<g transform="rotate(%.1f %.0f %.0f)"><ellipse cx="%.0f" cy="%.0f" rx="%.0f" ry="%.0f" fill="none" stroke="%s" stroke-opacity="%s" stroke-width="%.1f"/>', $rot, $cx, $cy, $cx, $cy, $rx, $ry, $i % 2 ? $light : $hex, $op, $c->between(1.2, 3.4));
                    $a = $c->between(0, 6.28);
                    $body .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" fill="%s"/></g>', $cx + $rx * cos($a), $cy + $ry * sin($a), $c->between(5, 13), $light);
                }
                $body .= sprintf('<circle cx="%.0f" cy="%.0f" r="%.0f" fill="url(#gA)"/>', $cx, $cy, $c->between(26, 52));
                break;

            case 'mesh':
                for ($i = 0; $i < 4; $i++) {
                    $body .= sprintf('<circle cx="%.0f" cy="%.0f" r="%.0f" fill="url(#glow)" opacity="%s"/>', $c->between(0, $w), $c->between(0, $h), $c->between($h * .45, $h * 1.0), round($c->between(.35, .8), 2));
                }
                $gs = 48;
                for ($x = 0; $x <= $w; $x += $gs) {
                    $body .= sprintf('<line x1="%d" y1="0" x2="%d" y2="%d" stroke="%s" stroke-opacity=".07"/>', $x, $x, $h, $light);
                }
                for ($y = 0; $y <= $h; $y += $gs) {
                    $body .= sprintf('<line x1="0" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-opacity=".07"/>', $y, $w, $y, $light);
                }
                for ($i = 0; $i < 22; $i++) {
                    $body .= sprintf('<circle cx="%d" cy="%d" r="%.1f" fill="%s" opacity="%s"/>', (int) ($c->between(0, $w / $gs)) * $gs, (int) ($c->between(0, $h / $gs)) * $gs, $c->between(2, 5), $light, round($c->between(.3, .9), 2));
                }
                break;

            default: // waves
                $layers = 5;
                for ($i = 0; $i < $layers; $i++) {
                    $base = $h * (.35 + $i * .13);
                    $amp = $c->between(40, 120);
                    $p = sprintf('M0,%.0f ', $base);
                    $segs = 4;
                    for ($s = 0; $s < $segs; $s++) {
                        $x0 = $w * $s / $segs;
                        $x1 = $w * ($s + 1) / $segs;
                        $p .= sprintf('C%.0f,%.0f %.0f,%.0f %.0f,%.0f ', $x0 + ($x1 - $x0) * .4, $base + ($s % 2 ? -1 : 1) * $amp * $c->between(.5, 1.3), $x0 + ($x1 - $x0) * .6, $base + ($s % 2 ? 1 : -1) * $amp * $c->between(.5, 1.3), $x1, $base + $c->between(-$amp, $amp) * .4);
                    }
                    $p .= sprintf('L%d,%d L0,%d Z', $w, $h, $h);
                    $body .= sprintf('<path d="%s" fill="%s" opacity="%s"/>', $p, $i % 2 ? 'url(#gA)' : 'url(#gB)', round(.25 + $i * .13, 2));
                }
                $body .= sprintf('<circle cx="%.0f" cy="%.0f" r="%.0f" fill="url(#glow)" opacity=".5"/>', $c->between($w * .6, $w * .9), $c->between($h * .1, $h * .3), $h * .45);
                break;
        }
        $body .= '<rect width="' . $w . '" height="' . $h . '" fill="url(#vig)"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="xMidYMid slice" role="img" aria-label="">'
            . '<defs>' . $defs . '</defs>' . $body . '</svg>';
    }
}
