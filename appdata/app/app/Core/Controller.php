<?php
declare(strict_types=1);

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function input(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Simpan input agar form terisi kembali setelah galat validasi. */
    protected function keepOld(): void
    {
        $old = $_POST;
        unset($old['password'], $old['password2'], $old['_csrf']);
        $_SESSION['_old'] = $old;
    }

    protected function page(): int
    {
        return max(1, (int)($_GET['page'] ?? 1));
    }
}
