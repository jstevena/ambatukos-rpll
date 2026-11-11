<?php
declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): int
    {
        return (int)(self::user()['id_pengguna'] ?? 0);
    }

    public static function role(): ?string
    {
        return self::user()['peran'] ?? null;
    }

    public static function login(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id_pengguna' => (int)$u['id_pengguna'],
            'nama' => $u['nama'],
            'username' => $u['username'],
            'peran' => $u['peran'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** Pembatasan akses per peran (R15 / NFR-02). ['*'] = semua pengguna yang sudah login. */
    public static function require(array $roles): void
    {
        if (!self::check()) {
            flash('warn', 'Silakan masuk terlebih dahulu.');
            header('Location: ' . url('login'));
            exit;
        }
        if ($roles !== ['*'] && !in_array(self::role(), $roles, true)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Akses ditolak']);
            exit;
        }
    }

    public static function home(): string
    {
        return match (self::role()) {
            'penyewa' => '/beranda',
            'admin', 'pemilik' => '/dashboard',
            default => '/kamar',
        };
    }

    public static function verifyCsrf(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $sent)) {
            http_response_code(419);
            View::render('errors/419', ['title' => 'Sesi kedaluwarsa']);
            exit;
        }
    }
}
