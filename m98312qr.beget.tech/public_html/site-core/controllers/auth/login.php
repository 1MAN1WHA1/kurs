<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/UserModel.php';

$errorMsg = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $emailVal = trim((string)($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');

        if ($emailVal === '' || $pass === '') {
            $errorMsg = 'Заполните все поля.';
        } elseif (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Некорректный формат Email.';
        } else {
            $user = UserModel::findForLogin($pdo, $emailVal);
            if (!$user || !password_verify($pass, (string)$user['password_hash'])) {
                $errorMsg = 'Неверный email или пароль.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['email']   = (string)$user['email'];
                $_SESSION['role']    = (string)$user['role'];
                ensure_csrf();
                redirect('/');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-side">
            <div class="brand-logo mb-4">L</div>
            <h1>Вход в аккаунт платформы</h1>
            <p>После входа пользователь получает доступ к покупкам, заказам, урокам и загрузке домашних заданий.</p>
            <ul>
                <li>доступ к купленным курсам;</li>
                <li>личный кабинет и история заказов;</li>
                <li>прогресс обучения в базе данных.</li>
            </ul>
            <a href="/" class="btn btn-light rounded-4 mt-3">Вернуться в каталог</a>
        </div>
        <div class="auth-form">
            <div class="mb-4">
                <div class="form-hint text-uppercase">Авторизация</div>
                <h2 class="page-title h3">Добро пожаловать</h2>
                <p class="page-subtitle">Введите email и пароль, чтобы продолжить работу с платформой.</p>
            </div>
            <?php if ($errorMsg !== ''): ?>
                <div class="alert alert-danger rounded-4"><?= e($errorMsg) ?></div>
            <?php endif; ?>
            <form method="POST" action="/login" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($emailVal) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-4 py-3">Войти</button>
            </form>
            <div class="mt-4 text-center">
                <a href="/register" class="top-link">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
