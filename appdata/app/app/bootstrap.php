<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');

spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Repositories', 'Services', 'Controllers', ''] as $dir) {
        $file = BASE_PATH . '/app/' . ($dir !== '' ? $dir . '/' : '') . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require BASE_PATH . '/app/helpers.php';

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}
