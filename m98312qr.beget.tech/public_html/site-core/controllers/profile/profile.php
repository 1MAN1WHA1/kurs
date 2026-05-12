<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/OrderModel.php';
require_once __DIR__ . '/../../models/ProductModel.php';

require_login();
$user_id = (int)$_SESSION['user_id'];
$user = UserModel::findProfile($pdo, $user_id);
$orders = OrderModel::listForUser($pdo, $user_id);
$recommendations = ProductModel::recommendationsForUser($pdo, $user_id, 3);

function renderStatus(string $status): string {
    return match ($status) {
        'paid' => 'Оплачен',
        'new'  => 'Новый',
        default => $status,
    };
}
function statusBadgeClass(string $status): string {
    return match ($status) {
        'paid' => 'success',
        'new'  => 'warning',
        default => 'neutral',
    };
}
$avatarOk = (!empty($_GET['avatar']) && $_GET['avatar'] === 'ok');
$avatarSrc = !empty($user['avatar_url']) ? (string)$user['avatar_url'] : 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Личный кабинет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <nav class="app-nav">
        <div class="brand-wrap">
            <div class="brand-logo">L</div>
            <div>
                <p class="brand-title mb-0">Личный кабинет</p>
                <p class="brand-subtitle">заказы, рекомендации и управление аккаунтом</p>
            </div>
        </div>
        <div class="nav-actions">
            <a href="<?= e(url()) ?>" class="btn-soft">Каталог</a>
            <a href="<?= e(url('change-password')) ?>" class="btn-gradient">Сменить пароль</a>
            <a href="<?= e(url('logout')) ?>" class="btn-ghost">Выйти</a>
        </div>
    </nav>

    <section class="hero-card">
        <div class="hero-grid">
            <div class="d-flex gap-4 align-items-center flex-wrap">
                <img src="<?= e($avatarSrc) ?>" alt="avatar" style="width:108px;height:108px;object-fit:cover;border-radius:28px;border:4px solid rgba(255,255,255,.85);box-shadow:var(--shadow);">
                <div>
                    <h1 class="page-title">Профиль пользователя</h1>
                    <p class="page-subtitle">В кабинете отображаются покупки, доступные курсы и персональные рекомендации.</p>
                    <?php if (!empty($user['username'])): ?><div><b>Логин:</b> <?= e((string)$user['username']) ?></div><?php endif; ?>
                    <?php if (!empty($user['email'])): ?><div><b>Email:</b> <?= e((string)$user['email']) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="sidebar-stack">
                <div class="metric-card"><small>Всего заказов</small><strong><?= count($orders) ?></strong></div>
                <div class="metric-card"><small>Рекомендации</small><strong><?= count($recommendations) ?></strong></div>
            </div>
        </div>
    </section>

    <div class="info-grid">
        <section class="panel-card page-card">
            <div class="page-header"><div><h2 class="page-title h3">Аватар и данные профиля</h2><p class="page-subtitle">При необходимости загрузите новый аватар для личного кабинета.</p></div></div>
            <?php if ($avatarOk): ?><div class="alert alert-success rounded-4">Аватар обновлён.</div><?php endif; ?>
            <form action="<?= e(url('upload-avatar')) ?>" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="col-12">
                    <label class="form-label">Загрузить новый аватар</label>
                    <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png" required>
                    <div class="form-hint mt-2">Разрешены JPG и PNG, размер до 5 МБ.</div>
                </div>
                <div class="col-12 d-grid"><button class="btn btn-primary rounded-4 py-3">Сохранить аватар</button></div>
            </form>
        </section>

        <section class="panel-card page-card">
            <div class="page-header"><div><h2 class="page-title h3">Быстрые действия</h2><p class="page-subtitle">Откройте каталог, поменяйте пароль или перейдите к администрированию.</p></div></div>
            <div class="d-grid gap-3">
                <a href="<?= e(url()) ?>" class="btn btn-outline-primary rounded-4 py-3">Открыть каталог</a>
                <a href="<?= e(url('change-password')) ?>" class="btn btn-outline-dark rounded-4 py-3">Изменить пароль</a>
                <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?><a href="<?= e(url('admin')) ?>" class="btn btn-outline-success rounded-4 py-3">Перейти в админку</a><?php endif; ?>
            </div>
        </section>
    </div>

    <section class="panel-card page-card mt-4">
        <div class="page-header"><div><h2 class="page-title h3">Мои заказы</h2><p class="page-subtitle">Оплаченные курсы открывают доступ к урокам, домашним заданиям и тестам.</p></div></div>
        <?php if (empty($orders)): ?>
            <div class="alert alert-light border rounded-4">Вы пока не сделали ни одного заказа.</div>
        <?php else: ?>
            <div class="list-clean">
                <?php foreach ($orders as $order): ?>
                    <?php $isCourse = (int)($order['is_course'] ?? 0) === 1; $detailsUrl = $isCourse ? (($order['status'] === 'paid') ? url('course?id=' . (int)$order['product_id']) : url('buy-course?id=' . (int)$order['product_id'])) : url('order-details?id=' . (int)$order['id']); ?>
                    <article class="order-item">
                        <div>
                            <div class="d-flex gap-2 flex-wrap align-items-center mb-1">
                                <h3 class="card-title mb-0"><?= e((string)$order['title']) ?></h3>
                                <?php if ($isCourse): ?><span class="badge-soft">Курс</span><?php endif; ?>
                                <span class="badge-status <?= e(statusBadgeClass((string)$order['status'])) ?>"><?= e(renderStatus((string)$order['status'])) ?></span>
                            </div>
                            <div class="form-hint">Дата заказа: <?= e((string)$order['created_at']) ?></div>
                            <div class="price-tag mt-2"><?= e((string)$order['price']) ?> ₽</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                            <a href="<?= e($detailsUrl) ?>" class="btn btn-outline-primary rounded-4">Подробнее</a>
                            <form action="<?= e(url('delete-order')) ?>" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить этот заказ?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger rounded-4">Удалить</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($recommendations): ?>
        <section class="panel-card page-card mt-4">
            <div class="page-header"><div><h2 class="page-title h3">Персональные рекомендации</h2><p class="page-subtitle">Система подбирает следующие курсы по вашим покупкам и тематике уже открытых программ.</p></div></div>
            <div class="catalog-grid compact-grid">
                <?php foreach ($recommendations as $item): ?>
                    <article class="panel-card catalog-card compact-card">
                        <div class="card-body">
                            <h3 class="card-title"><?= e((string)$item['title']) ?></h3>
                            <p class="card-text"><?= e(mb_strimwidth((string)($item['description'] ?? ''), 0, 120, '…')) ?></p>
                            <div class="price-tag"><?= e((string)$item['price']) ?> ₽</div>
                        </div>
                        <div class="card-footer-actions">
                            <a href="<?= e(url('course?id=' . (int)$item['id'])) ?>" class="btn btn-outline-primary w-100 rounded-4">Открыть курс</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
