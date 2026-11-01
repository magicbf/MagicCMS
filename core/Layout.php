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
        'rail' => 'Side rail – fixed vertical menu with logo on top and account at the bottom',
    ];
    public const RHYTHMS = ['even' => 'Even – everything aligned', 'stagger' => 'Staggered – offset sidebars, alternating insets, wide first tile'];

    public static function header(): string
    {
        $h = (string) setting('layout.header', 'classic');
        return isset(self::HEADERS[$h]) ? $h : 'classic';
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
