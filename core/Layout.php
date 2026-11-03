<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Decides which side columns exist and renders the panels inside them. */
final class Layout
{
    public const MODES = ['none', 'left', 'right', 'both'];
    /** Header/menu arrangements (setting layout.header): key => label. Pure structure – every theme styles them through its normal tokens. */
    public const HEADERS = [
        'classic' => 'Classic – logo row, full-width menu below',
        'inline' => 'Compact – logo, menu and account in one slim bar',
        'hanging' => 'Hanging logo – oversized logo badge hangs off an offset menu bar',
        'float' => 'Floating islands – detached glass islands for logo, menu and account',
        'topbar' => 'Top bar – slim bar with menu on the left and login/register on the right, logo below',
        'rail' => 'Side rail – fixed vertical menu with logo on top and account at the bottom',
    ];
    /** Shape of the login / account area (setting layout.acct_shape). */
    public const ACCT_SHAPES = ['buttons' => 'Buttons – the theme\'s normal buttons', 'pill' => 'Pills – fully rounded', 'square' => 'Square – sharp, flat edges', 'links' => 'Text links – minimal, separated by thin dividers'];
    public const BRAND_ALIGN = ['left' => 'Left', 'center' => 'Centred'];
    /** Visual treatment of the header zone, on top of whatever the theme does (setting layout.header_look). */
    public const LOOKS = [
        'theme' => 'Theme default',
        'glass' => 'Frosted glass',
        'solid' => 'Solid bar with accent line',
        'gradient' => 'Colour wash',
        'neon' => 'Neon edge',
        'carbon' => 'Carbon steel',
        'transparent' => 'Transparent (no background)',
    ];
    public const NAME_POS = ['right' => 'Beside the logo', 'below' => 'Under the logo'];
    public const CONTAINER_WIDTHS = [0 => 'Theme default', 1100 => 'Narrow – 1100 px', 1240 => 'Medium – 1240 px', 1360 => 'Wide – 1360 px', 1520 => 'Extra wide – 1520 px', 1760 => 'Full – 1760 px'];
    public const RHYTHMS = ['even' => 'Even – everything aligned', 'stagger' => 'Staggered – offset sidebars, alternating insets, wide first tile'];

    public static function header(): string
    {
        $h = (string) setting('layout.header', 'classic');
        return isset(self::HEADERS[$h]) ? $h : 'classic';
    }

    public static function headerLook(): string
    {
        $v = (string) setting('layout.header_look', 'theme');
        return isset(self::LOOKS[$v]) ? $v : 'theme';
    }

    /** Logo height in px; 0 = automatic (the theme / header style decides). */
    public static function logoSize(): int
    {
        $v = (int) setting('layout.logo_size', 0);
        return $v <= 0 ? 0 : max(28, min(200, $v));
    }

    /** Site name font size in px; 0 = automatic. */
    public static function nameSize(): int
    {
        $v = (int) setting('layout.name_size', 0);
        return $v <= 0 ? 0 : max(14, min(80, $v));
    }

    public static function namePos(): string
    {
        $v = (string) setting('layout.name_pos', 'right');
        return isset(self::NAME_POS[$v]) ? $v : 'right';
    }

    /** Custom colour of the site name, or '' for the theme's own. */
    public static function nameColor(): string
    {
        $v = (string) setting('layout.name_color', '');
        return is_hex($v) ? strtolower($v) : '';
    }

    /** Width of the side panel columns in px; 0 = theme default. */
    public static function sidebarW(): int
    {
        $v = (int) setting('layout.sidebar_w', 0);
        return $v <= 0 ? 0 : max(220, min(460, $v));
    }

    /** Maximum page width in px; 0 = theme default. */
    public static function containerW(): int
    {
        $v = (int) setting('layout.container_w', 0);
        return isset(self::CONTAINER_WIDTHS[$v]) ? $v : 0;
    }

    public static function acctShape(): string
    {
        $v = (string) setting('layout.acct_shape', 'buttons');
        return isset(self::ACCT_SHAPES[$v]) ? $v : 'buttons';
    }

    public static function brandAlign(): string
    {
        $v = (string) setting('layout.brand_align', 'left');
        return isset(self::BRAND_ALIGN[$v]) ? $v : 'left';
    }

    public static function rhythm(): string
    {
        $r = (string) setting('layout.rhythm', 'even');
        return isset(self::RHYTHMS[$r]) ? $r : 'even';
    }

    /** @return array{mode:string,left:array,right:array,above:array} */
    public static function resolve(?string $override): array
    {
        $mode = in_array($override, self::MODES, true) ? $override : (string) setting('layout.mode', 'right');
        if (!in_array($mode, self::MODES, true)) {
            $mode = 'right';
        }
        $left = in_array($mode, ['left', 'both'], true) ? Panels::render('left') : [];
        $right = in_array($mode, ['right', 'both'], true) ? Panels::render('right') : [];
        // A column without any visible panel disappears; the grid adapts automatically.
        if (setting('layout.mirror')) { // swap the sides: panels configured "left" appear on the right and vice versa
            [$left, $right] = [$right, $left];
        }
        $eff = ($left && $right) ? 'both' : ($left ? 'left' : ($right ? 'right' : 'none'));
        return ['mode' => $eff, 'left' => $left, 'right' => $right, 'above' => Panels::render('above_footer')];
    }
}
