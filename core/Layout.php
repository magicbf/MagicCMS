<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Decides which side columns exist and renders the panels inside them. */
final class Layout
{
    public const MODES = ['none', 'left', 'right', 'both'];

    /** @return array{mode:string,left:array,right:array} */
    public static function resolve(?string $override): array
    {
        $mode = in_array($override, self::MODES, true) ? $override : (string) setting('layout.mode', 'both');
        if (!in_array($mode, self::MODES, true)) {
            $mode = 'both';
        }
        $left = in_array($mode, ['left', 'both'], true) ? Panels::render('left') : [];
        $right = in_array($mode, ['right', 'both'], true) ? Panels::render('right') : [];
        // A column without any visible panel disappears; the grid adapts automatically.
        $eff = ($left && $right) ? 'both' : ($left ? 'left' : ($right ? 'right' : 'none'));
        return ['mode' => $eff, 'left' => $left, 'right' => $right];
    }
}
