<?php
$pageTitle = $pageTitle ?? 'LearnMarket';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <nav class="app-nav">
        <div class="brand-wrap">
            <div class="brand-logo">L</div>
            <div>
                <p class="brand-title mb-0">LearnMarket</p>
                <p class="brand-subtitle">образовательная платформа и цифровые продукты</p>
            </div>
        </div>

        <div class="nav-actions">
            <a href="/" class="btn-soft">Каталог</a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="/profile" class="btn-soft">Личный кабинет</a>
                <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/admin" class="btn-soft">Админка</a>
                <?php endif; ?>
                <a href="/logout" class="btn-ghost">Выйти</a>
            <?php else: ?>
                <a href="/login" class="btn-soft">Войти</a>
                <a href="/register" class="btn-gradient">Регистрация</a>
            <?php endif; ?>
        </div>
    </nav>