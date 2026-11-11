<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    /** $roles: null = publik, ['*'] = semua yang login, atau daftar peran. */
    public function add(string $method, string $pattern, array $handler, ?array $roles = null): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [$method, $regex, $handler, $roles];
    }

    public function get(string $p, array $h, ?array $r = null): void { $this->add('GET', $p, $h, $r); }
    public function post(string $p, array $h, ?array $r = null): void { $this->add('POST', $p, $h, $r); }

    /** Kembali ke halaman sebelumnya bila masih satu host, jika tidak ke beranda peran. */
    private function backUrl(): string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $ref !== '' ? parse_url($ref, PHP_URL_HOST) : null;
        $mine = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
        if ($host !== null && $host !== false && $host === $mine) {
            return $ref;
        }
        return url(Auth::check() ? Auth::home() : 'kamar');
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        foreach ($this->routes as [$m, $regex, $handler, $roles]) {
            if ($m !== $method || !preg_match($regex, $path, $mt)) {
                continue;
            }
            $params = array_values(array_filter($mt, 'is_string', ARRAY_FILTER_USE_KEY));
            if ($roles !== null) {
                Auth::require($roles);
            }
            if ($method === 'POST') {
                Auth::verifyCsrf();
            }
            [$class, $fn] = $handler;
            try {
                (new $class())->$fn(...$params);
            } catch (AppException $e) {
                flash('error', $e->getMessage());
                if ($method === 'POST') {
                    $old = $_POST;
                    unset($old['password'], $old['password2'], $old['_csrf']);
                    $_SESSION['_old'] = $old;
                }
                header('Location: ' . $this->backUrl());
            } catch (Throwable $e) {
                error_log('[AmbatuKos] ' . $e);
                http_response_code(500);
                View::render('errors/500', ['title' => 'Terjadi kesalahan']);
            }
            return;
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'Halaman tidak ditemukan']);
    }
}
