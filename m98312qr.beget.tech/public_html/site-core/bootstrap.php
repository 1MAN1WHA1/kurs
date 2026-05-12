<?php

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/config/Database.php';

$pdo = Database::get();
ensure_csrf();

if (!defined('SITE_CORE_ROOT')) define('SITE_CORE_ROOT', __DIR__);
if (!defined('PUBLIC_ROOT')) define('PUBLIC_ROOT', dirname(__DIR__));
