<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Tiny regex router.
 *   $r->get('/forum/topic/{id:\d+}', [Controller::class, 'show'], ['layout' => 'none', 'perm' => 'x']);
 * Options: layout (none|left|right|both), auth (bool), perm (string), csrf (bool, default true for POST)
 */
final class Router
{
    private array $routes = [];

    public function get(string $p, $h, array $o = []): void
    {
        $this->add('GET', $p, $h, $o);
    }

    public function post(string $p, $h, array $o = []): void
    {
        $this->add('POST', $p, $h, $o);
    }

    public function any(string $p, $h, array $o = []): void
    {
        $this->add('GET', $p, $h, $o);
        $this->add('POST', $p, $h, $o);
    }

    private function add(string $method, string $pattern, $handler, array $opts): void
    {
        $re = '';
        foreach (preg_split('/(\{\w+(?::[^}]+)?\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if (preg_match('/^\{(\w+)(?::([^}]+))?\}$/', $part, $m)) {
                $re .= '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')';
            } else {
                $re .= preg_quote($part, '#');
            }
        }
        $this->routes[] = ['m' => $method, 're' => '#^' . $re . '$#', 'h' => $handler, 'o' => $opts];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim(rawurldecode($path), '/');
        $pathMatched = false;
        foreach ($this->routes as $r) {
            if (!preg_match($r['re'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($r['m'] !== $method) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->run($r, $params);
            return;
        }
        if ($pathMatched) {
            http_response_code(405);
            echo View::page(t('Method not allowed'), render('message', ['title' => '405', 'text' => t('Method not allowed.')]));
            return;
        }
        $this->notFound();
    }

    public function notFound(): void
    {
        http_response_code(404);
        echo View::page(t('Page not found'), render('404'), ['layout' => 'none']);
    }

    private function run(array $r, array $params): void
    {
        $o = $r['o'];
        if (!empty($o['auth']) && !user()) {
            flash('info', t('Please log in to continue.'));
            redirect('/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        }
        if (!empty($o['perm']) && !can($o['perm'])) {
            if (!user() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                redirect('/login?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
            }
            http_response_code(403);
            echo View::page(t('Forbidden'), render('message', ['title' => '403', 'text' => t('You do not have permission to view this page.')]), ['layout' => 'none']);
            return;
        }
        if ($r['m'] === 'POST' && ($o['csrf'] ?? true) && !Csrf::verify()) {
            http_response_code(419);
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
                json_out(['ok' => false, 'error' => 'csrf'], 419);
            }
            echo View::page(t('Session expired'), render('message', ['title' => t('Session expired'), 'text' => t('Your form session expired. Go back, reload the page and try again.')]), ['layout' => 'none']);
            return;
        }
        View::$routeLayout = $o['layout'] ?? null;
        $h = $r['h'];
        if (is_array($h) && is_string($h[0])) {
            $h = [new $h[0](), $h[1]];
        }
        $out = $h($params);
        if (is_string($out)) {
            echo $out;
        }
    }
}
