<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$router = require BASE_PATH . '/app/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
