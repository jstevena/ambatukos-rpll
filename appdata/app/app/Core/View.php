<?php
declare(strict_types=1);

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/views/' . $template . '.php';
        $content = ob_get_clean();
        $title = $title ?? 'AmbatuKos';
        require BASE_PATH . '/app/views/' . $layout . '.php';
        unset($_SESSION['_old']);
    }
}
