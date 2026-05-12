<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/OrderModel.php';
require_login();
$order_id = (int)($_GET['id'] ?? 0);
$user_id  = (int)$_SESSION['user_id'];
if ($order_id <= 0) {
    http_response_code(404);
    die('Заказ не найден');
}
$order = OrderModel::findDetailedForUser($pdo, $order_id, $user_id);
if (!$order) {
    http_response_code(404);
    die('Заказ не найден или у вас нет прав на его просмотр');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Заказ #<?= (int)$order['order_id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <nav class="app-nav"><div class="brand-wrap"><div class="brand-logo">🧾</div><div><p class="brand-title mb-0">Детали заказа</p><p class="brand-subtitle">информация о приобретённом товаре</p></div></div><div class="nav-actions"><a href="<?= e(url('profile')) ?>" class="btn-soft">Назад в профиль</a></div></nav>
    <section class="panel-card page-card">
        <div class="row g-3">
            <div class="col-md-4"><?php $img = !empty($order['image_url']) ? (string)$order['image_url'] : 'https://via.placeholder.com/300'; ?><img src="<?= e($img) ?>" class="img-fluid rounded-4" alt="Фото"></div>
            <div class="col-md-8">
                <h1 class="page-title h3 mb-3"><?= e((string)$order['title']) ?></h1>
                <p class="page-subtitle mb-3"><?= e((string)($order['description'] ?? '')) ?></p>
                <div class="hero-pills mb-3"><span class="hero-pill">Заказ #<?= (int)$order['order_id'] ?></span><span class="hero-pill">Дата: <?= e((string)$order['created_at']) ?></span><span class="hero-pill">Статус: <?= e((string)$order['status']) ?></span></div>
                <div class="price-tag">Цена: <?= number_format((float)$order['price'], 0, '', ' ') ?> ₽</div>
            </div>
        </div>
    </section>
</div>
</body>
</html>
