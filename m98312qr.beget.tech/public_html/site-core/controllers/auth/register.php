<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/UserModel.php';

$errorMsg = '';
$successMsg = '';
$email = trim((string)($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $pass = (string)($_POST['password'] ?? '');
        $passConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($email === '' || $pass === '') {
            $errorMsg = 'Заполните все поля.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Некорректный формат Email.';
        } elseif ($pass !== $passConfirm) {
            $errorMsg = 'Пароли не совпадают.';
        } elseif (mb_strlen($pass) < 6) {
            $errorMsg = 'Пароль должен содержать минимум 6 символов.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            try {
                UserModel::createClient($pdo, $email, $hash);
                $successMsg = 'Регистрация выполнена успешно. Теперь можно войти в систему.';
                $email = '';
            } catch (PDOException $e) {
                $errorMsg = ((string)$e->getCode() === '23000') ? 'Такой email уже зарегистрирован.' : 'Не удалось выполнить регистрацию. Попробуйте позже.';
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
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-side">
            <div class="brand-logo mb-4">L</div>
            <h1>Создание нового аккаунта</h1>
            <p>После регистрации пользователь сможет покупать материалы, изучать уроки и хранить прогресс внутри системы.</p>
            <ul>
                <li>единый доступ к заказам;</li>
                <li>отдельный профиль с аватаром;</li>
                <li>домашние задания по урокам.</li>
            </ul>
            <a href="/" class="btn btn-light rounded-4 mt-3">Перейти в каталог</a>
        </div>
        <div class="auth-form">
            <div class="mb-4">
                <div class="form-hint text-uppercase">Регистрация</div>
                <h2 class="page-title h3">Создать аккаунт</h2>
                <p class="page-subtitle">Заполните данные и получите доступ к платформе обучения.</p>
            </div>
            <?php if ($errorMsg !== ''): ?><div class="alert alert-danger rounded-4"><?= e($errorMsg) ?></div><?php endif; ?>
            <?php if ($successMsg !== ''): ?>
                <div class="alert alert-success rounded-4"><?= e($successMsg) ?> <a href="/login" class="top-link">Войти</a></div>
            <?php else: ?>
                <form method="POST" action="/register">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required value="<?= e($email) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Подтверждение пароля</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-4 py-3">Зарегистрироваться</button>
                </form>
                <div class="mt-4 text-center"><a href="/login" class="top-link">Уже есть аккаунт? Войти</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
