<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Data-driven menus (menus + menu_items tables), edited in Admin → Navigation. */
final class Nav
{
    /** Built-in link targets; modules extend via the `nav.targets` filter. */
    public static function targets(): array
    {
        return Hooks::filter('nav.targets', [
            'home' => ['Home', '/'],
            'members' => ['Members', '/members'],
        ]);
    }

    public static function resolve(array $i): ?string
    {
        switch ($i['target_type']) {
            case 'module':
                $t = self::targets()[$i['target_ref']] ?? null;
                return $t ? url($t[1]) : null;
            case 'page':
                $slug = db()->val('SELECT slug FROM {pages} WHERE id = ? AND published = 1', [(int) $i['target_ref']]);
                return $slug ? url('p/' . $slug) : null;
            case 'url':
                return self::safeUrl((string) $i['target_ref']);
            case 'action':
                return $i['target_ref'] === 'auth' ? url(user() ? 'profile' : 'login') : null;
        }
        return null;
    }

    /** Only http(s), mailto, anchors and site-relative paths are allowed as link targets. */
    public static function safeUrl(string $u): ?string
    {
        $u = trim($u);
        if ($u === '') {
            return null;
        }
        if (preg_match('/^(https?:\/\/|mailto:)/i', $u) || $u[0] === '#') {
            return $u;
        }
        if ($u[0] === '/' && !str_starts_with($u, '//')) {
            return url($u);
        }
        return null;
    }

    /** @return array<int,array> nested items ready for the nav template */
    public static function tree(string $menuKey = 'main'): array
    {
        $rows = db()->all(
            'SELECT i.* FROM {menu_items} i JOIN {menus} m ON m.id = i.menu_id WHERE m.menu_key = ? ORDER BY i.sort_order, i.id',
            [$menuKey]
        );
        $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        $base = rtrim(Bootstrap::$base, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = '/' . trim(substr($path, strlen($base)), '/');
        }
        $nodes = [];
        foreach ($rows as $r) {
            $vis = json_decode((string) $r['visibility'], true) ?: [];
            $who = $vis['who'] ?? 'all';
            $u = user();
            if (($who === 'guests' && $u) || ($who === 'members' && !$u) || ($who === 'staff' && Auth::level() < 50)) {
                continue;
            }
            $href = self::resolve($r);
            if ($href === null) {
                continue; // broken target (deleted/unpublished page) – hide rather than 404
            }
            $label = $r['label'];
            if ($r['target_type'] === 'action' && $r['target_ref'] === 'auth' && $u) {
                $label = $u['username'];
            }
            $local = parse_url($href, PHP_URL_PATH) ?: '';
            if ($base !== '' && str_starts_with($local, $base)) {
                $local = substr($local, strlen($base));
            }
            $local = '/' . trim($local, '/');
            $nodes[(int) $r['id']] = [
                'id' => (int) $r['id'], 'parent' => (int) $r['parent_id'], 'label' => $label, 'icon' => $r['icon'],
                'url' => $href, 'new_tab' => (bool) $r['new_tab'], 'badge' => $r['badge'],
                'color' => is_hex((string) $r['color']) ? $r['color'] : '', 'hideMobile' => empty($vis['mobile'] ?? true),
                'active' => !str_starts_with($href, '#') && !preg_match('#^[a-z]+:#i', $local) && ($local === $path || ($local !== '/' && str_starts_with($path, $local . '/'))),
                'children' => [],
            ];
        }
        $tree = [];
        foreach ($nodes as $id => &$n) {
            if ($n['parent'] && isset($nodes[$n['parent']]) && $nodes[$n['parent']]['parent'] === 0) {
                $nodes[$n['parent']]['children'][] = &$n;
            } elseif (!$n['parent']) {
                $tree[] = &$n;
            }
        }
        unset($n);
        foreach ($tree as &$n) {
            if (array_filter($n['children'], fn($c) => $c['active'])) {
                $n['active'] = true;
            }
        }
        return $tree;
    }
}
