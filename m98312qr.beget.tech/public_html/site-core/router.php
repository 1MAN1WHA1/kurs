<?php
require_once __DIR__ . '/bootstrap.php';

function app_route(): string
{
    $route = trim((string)($_GET['route'] ?? ''));
    if ($route !== '') {
        return trim($route, '/');
    }

    $uriPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($baseDir !== '' && $baseDir !== '.' && starts_with($uriPath, $baseDir)) {
        $uriPath = substr($uriPath, strlen($baseDir));
    }

    return trim($uriPath, '/');
}

function app_dispatch(string $file): void
{
    global $pdo;

    $fullPath = SITE_CORE_ROOT . '/' . ltrim($file, '/');
    if (!is_file($fullPath)) {
        http_response_code(500);
        echo 'Ошибка маршрутизации: файл обработчика не найден.';
        exit;
    }

    require $fullPath;
    exit;
}

$route = app_route();

$routes = [
    '' => 'controllers/catalog/index.php',
    'index' => 'controllers/catalog/index.php',
    'index.php' => 'controllers/catalog/index.php',

    'login' => 'controllers/auth/login.php',
    'register' => 'controllers/auth/register.php',
    'logout' => 'handlers/auth/logout.php',

    'profile' => 'controllers/profile/profile.php',
    'change-password' => 'controllers/profile/change_password.php',
    'change_password' => 'controllers/profile/change_password.php',
    'update-profile' => 'handlers/profile/update_profile.php',
    'update_profile' => 'handlers/profile/update_profile.php',
    'upload-avatar' => 'handlers/profile/upload_avatar.php',
    'upload_avatar' => 'handlers/profile/upload_avatar.php',
    'order-details' => 'controllers/profile/order_details.php',
    'order_details' => 'controllers/profile/order_details.php',

    'course' => 'controllers/catalog/course.php',
    'buy-course' => 'controllers/orders/buy_course.php',
    'buy_course' => 'controllers/orders/buy_course.php',
    'make-order' => 'handlers/orders/make_order.php',
    'make_order' => 'handlers/orders/make_order.php',
    'pay-course' => 'handlers/orders/pay_course.php',
    'pay_course' => 'handlers/orders/pay_course.php',
    'delete-order' => 'handlers/orders/delete_order.php',
    'delete_order' => 'handlers/orders/delete_order.php',

    'view-lesson' => 'controllers/learning/view_lesson.php',
    'view_lesson' => 'controllers/learning/view_lesson.php',
    'upload-homework' => 'handlers/learning/upload_homework.php',
    'upload_homework' => 'handlers/learning/upload_homework.php',
    'delete-homework' => 'handlers/learning/delete_homework.php',
    'delete_homework' => 'handlers/learning/delete_homework.php',
    'submit-test' => 'handlers/learning/submit_test.php',
    'submit_test' => 'handlers/learning/submit_test.php',

    'admin' => 'controllers/admin/panel.php',
    'admin-panel' => 'controllers/admin/panel.php',
    'admin_panel' => 'controllers/admin/panel.php',
    'admin/orders' => 'controllers/admin/orders.php',
    'admin_orders' => 'controllers/admin/orders.php',
    'admin/add-item' => 'controllers/admin/add_item.php',
    'add-item' => 'controllers/admin/add_item.php',
    'add_item' => 'controllers/admin/add_item.php',
    'admin/edit-item' => 'controllers/admin/edit_item.php',
    'edit-item' => 'controllers/admin/edit_item.php',
    'edit_item' => 'controllers/admin/edit_item.php',
    'admin/seeder' => 'controllers/admin/seeder.php',
    'admin-seeder' => 'controllers/admin/seeder.php',
    'admin_seeder' => 'controllers/admin/seeder.php',
    'admin/delete-item' => 'handlers/admin/delete_item.php',
    'delete-item' => 'handlers/admin/delete_item.php',
    'delete_item' => 'handlers/admin/delete_item.php',
    'admin/homeworks' => 'controllers/admin/homeworks.php',
    'admin/review-homework' => 'handlers/admin/review_homework.php',
    'admin_review_homework' => 'handlers/admin/review_homework.php',
];

if (isset($routes[$route])) {
    app_dispatch($routes[$route]);
}

http_response_code(404);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Страница не найдена</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <section class="hero-card text-center">
        <div class="form-hint text-uppercase mb-2">Ошибка маршрутизации</div>
        <h1 class="hero-title">Страница не найдена</h1>
        <p class="hero-text">Запрошенный адрес отсутствует. Вернитесь в каталог или воспользуйтесь навигацией сайта.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
            <a href="<?= e(url()) ?>" class="btn btn-primary rounded-4 px-4 py-3">На главную</a>
            <a href="<?= e(url('profile')) ?>" class="btn btn-outline-dark rounded-4 px-4 py-3">Личный кабинет</a>
        </div>
    </section>
</div>
</body>
</html>
