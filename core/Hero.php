<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Hero banner: slider / single banner with effects, built from admin-defined slides and/or featured news.
 * Config lives in the `hero.config` setting; slides in `hero_slides`. Rendering is pure markup + CSS;
 * assets/hero.js adds autoplay, arrows, dots, swipe and parallax.
 */
final class Hero
{
    public const DEFAULTS = [
        'enabled' => true, 'placement' => 'home', 'source' => 'slides', 'height' => 'normal', 'width' => 'contained',
        'transition' => 'fade', 'interval' => 7, 'autoplay' => true, 'pause' => true, 'arrows' => true, 'dots' => true,
        'effect' => 'embers', 'parallax' => true,
    ];
    public const OPTIONS = [
        'placement' => ['home' => 'Home page only', 'all' => 'All public pages (compact)', 'off' => 'Off'],
        'source' => ['slides' => 'My slides', 'news' => 'Featured news posts', 'both' => 'Slides, then featured news'],
        'height' => ['compact' => 'Compact', 'normal' => 'Normal', 'tall' => 'Tall', 'full' => 'Full screen'],
        'width' => ['contained' => 'Contained (rounded card)', 'full' => 'Full width (edge to edge)'],
        'transition' => ['fade' => 'Fade', 'slide' => 'Slide', 'zoom' => 'Zoom'],
        'effect' => ['embers' => 'Rising embers', 'aurora' => 'Aurora glow', 'grid' => 'Moving grid', 'none' => 'None'],
    ];
    private static bool $scriptDone = false;

    public static function config(): array
    {
        $c = array_merge(self::DEFAULTS, (array) setting('hero.config', []));
        foreach (self::OPTIONS as $k => $opts) {
            if (!array_key_exists((string) $c[$k], $opts)) {
                $c[$k] = self::DEFAULTS[$k];
            }
        }
        $c['interval'] = max(3, min(30, (int) $c['interval']));
        foreach (['enabled', 'autoplay', 'pause', 'arrows', 'dots', 'parallax'] as $b) {
            $c[$b] = (bool) $c[$b];
        }
        return $c;
    }

    private static function moduleOn(string $key): bool
    {
        return (bool) db()->val('SELECT 1 FROM {modules} WHERE module_key = ? AND enabled = 1', [$key]);
    }

    /** @return array<int,array> normalised slides */
    public static function slides(array $cfg): array
    {
        $out = [];
        if (in_array($cfg['source'], ['slides', 'both'], true)) {
            $now = now();
            foreach (db()->all(
                'SELECT * FROM {hero_slides} WHERE enabled = 1 AND (starts_at IS NULL OR starts_at <= ?) AND (ends_at IS NULL OR ends_at >= ?) ORDER BY sort_order, id',
                [$now, $now]
            ) as $s) {
                $btns = [];
                foreach ([1, 2] as $n) {
                    $url = $s["btn{$n}_label"] !== '' ? Nav::resolve(['target_type' => $s["btn{$n}_type"], 'target_ref' => $s["btn{$n}_ref"]]) : null;
                    if ($url) {
                        $btns[] = [$s["btn{$n}_label"], $url];
                    }
                }
                $out[] = [
                    'eyebrow' => $s['eyebrow'], 'headline' => $s['headline'], 'subline' => $s['subline'], 'image' => upload_url('hero', $s['image']),
                    'overlay' => (int) $s['overlay'], 'align' => $s['align'], 'buttons' => $btns,
                    'from' => is_hex((string) $s['color_from']) ? $s['color_from'] : '', 'to' => is_hex((string) $s['color_to']) ? $s['color_to'] : '',
                ];
            }
        }
        if (in_array($cfg['source'], ['news', 'both'], true) && self::moduleOn('news')) {
            foreach (\MagicCMS\Modules\News\News::featured(5) as $p) {
                $out[] = [
                    'eyebrow' => ($p['cat_name'] ?: t('News')), 'headline' => $p['title'], 'subline' => $p['excerpt'],
                    'image' => \MagicCMS\Modules\News\News::coverUrl($p), 'overlay' => 50, 'align' => 'left',
                    'buttons' => [[t('Read more'), url('news/' . $p['slug'])], [t('All news'), url('news')]],
                    'from' => '', 'to' => '',
                ];
            }
        }
        return $out;
    }

    public static function html(bool $isHome, bool $skip = false): string
    {
        $cfg = self::config();
        if ($skip || !$cfg['enabled'] || $cfg['placement'] === 'off' || ($cfg['placement'] === 'home' && !$isHome) || !self::moduleOn('hero')) {
            return '';
        }
        $slides = self::slides($cfg);
        if (!$slides) {
            return '';
        }
        $height = $isHome ? $cfg['height'] : 'compact';
        $multi = count($slides) > 1;
        $cls = 'hero hero-h-' . $height . ' hero-tr-' . $cfg['transition'] . ' hero-fx-' . $cfg['effect'];
        $o = '<div class="hero-wrap' . ($cfg['width'] === 'full' ? ' hero-wrap-full' : '') . '">' . ($cfg['width'] === 'full' ? '' : '<div class="container">');
        $o .= '<section class="' . $cls . '" data-hero data-interval="' . $cfg['interval'] . '" data-autoplay="' . ($cfg['autoplay'] && $multi ? 1 : 0) . '" data-pause="' . (int) $cfg['pause'] . '" data-parallax="' . (int) $cfg['parallax'] . '" aria-roledescription="carousel" aria-label="' . e(t('Highlights')) . '">';
        $o .= '<div class="hero-fx" aria-hidden="true">' . self::effect($cfg['effect']) . '</div><div class="hero-slides">';
        foreach ($slides as $i => $s) {
            $style = ($s['from'] ? '--hf:' . $s['from'] . ';' : '') . ($s['to'] ? '--hs:' . $s['to'] . ';' : '') . '--ov:' . max(0, min(90, $s['overlay'])) / 100;
            $o .= '<article class="hero-slide align-' . e($s['align']) . ($s['image'] ? ' has-image' : '') . ($i === 0 ? ' is-active' : '') . '" style="' . e($style) . '" role="group" aria-roledescription="slide" aria-label="' . ($i + 1) . ' / ' . count($slides) . '"' . ($i === 0 ? '' : ' aria-hidden="true"') . '>';
            $o .= $s['image'] ? '<div class="hero-media" style="background-image:url(\'' . e($s['image']) . '\')"></div><div class="hero-overlay"></div>' : '<div class="hero-bg"></div>';
            $o .= '<div class="hero-content">';
            $o .= $s['eyebrow'] !== '' ? '<span class="eyebrow">' . e($s['eyebrow']) . '</span>' : '';
            $o .= $s['headline'] !== '' ? '<h2 class="hero-title">' . e($s['headline']) . '</h2>' : '';
            $o .= $s['subline'] !== '' ? '<p class="lead">' . e($s['subline']) . '</p>' : '';
            if ($s['buttons']) {
                $o .= '<div class="hero-cta">';
                foreach ($s['buttons'] as $b => [$label, $href]) {
                    $o .= '<a class="btn ' . ($b === 0 ? 'btn-dark' : 'btn-glass') . '" href="' . e($href) . '"' . ($i === 0 ? '' : ' tabindex="-1"') . '>' . e($label) . ($b === 0 ? ' <span aria-hidden="true">→</span>' : '') . '</a>';
                }
                $o .= '</div>';
            }
            $o .= '</div></article>';
        }
        $o .= '</div>';
        if ($multi && $cfg['arrows']) {
            $o .= '<button class="hero-arrow prev" type="button" aria-label="' . e(t('Previous slide')) . '">‹</button><button class="hero-arrow next" type="button" aria-label="' . e(t('Next slide')) . '">›</button>';
        }
        if ($multi && $cfg['dots']) {
            $o .= '<div class="hero-dots">';
            foreach ($slides as $i => $_) {
                $o .= '<button type="button" class="hero-dot' . ($i === 0 ? ' is-active' : '') . '" aria-label="' . e(t('Go to slide {n}', ['n' => $i + 1])) . '"></button>';
            }
            $o .= '</div>';
        }
        $o .= '</section>' . ($cfg['width'] === 'full' ? '' : '</div>') . '</div>';
        if (!self::$scriptDone && $multi || !self::$scriptDone && $cfg['parallax']) {
            self::$scriptDone = true;
            $o .= '<script src="' . e(asset('hero.js')) . '" defer></script>';
        }
        return $o;
    }

    private static function effect(string $effect): string
    {
        switch ($effect) {
            case 'embers':
                $o = '';
                for ($i = 0; $i < 18; $i++) {
                    $x = ($i * 37 + 11) % 100;
                    $s = 3 + ($i * 5) % 4;
                    $d = 7 + ($i * 3) % 9;
                    $dl = -(($i * 13) % 17);
                    $dx = (($i * 29) % 80) - 40;
                    $o .= '<i style="--x:' . $x . '%;--s:' . $s . 'px;--d:' . $d . 's;--dl:' . $dl . 's;--dx:' . $dx . 'px"></i>';
                }
                return $o;
            case 'aurora':
                return '<span class="fxa-1"></span><span class="fxa-2"></span>';
            case 'grid':
                return '<span class="fxg"></span>';
        }
        return '';
    }
}
