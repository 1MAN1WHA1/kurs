<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();
$user_id = (int)$_SESSION['user_id'];
$course_id = (int)($_GET['id'] ?? 0);
$error = ($_GET['err'] ?? '') === 'payment' ? 'Не удалось провести оплату. Попробуйте ещё раз.' : '';
if ($course_id <= 0) { http_response_code(400); die('Некорректный курс'); }
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) { http_response_code(404); die('Курс не найден'); }
$paid = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? AND status = 'paid' LIMIT 1");
$paid->execute([$user_id, $course_id]);
if ($paid->fetchColumn()) { redirect(url('course?id=' . $course_id)); }
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата курса</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <nav class="app-nav">
        <div class="brand-wrap"><div class="brand-logo">₽</div><div><p class="brand-title mb-0">Оформление покупки</p><p class="brand-subtitle">подтверждение доступа к курсу</p></div></div>
        <div class="nav-actions"><a href="<?= e(url('course?id=' . $course_id)) ?>" class="btn-soft">Назад к курсу</a><a href="<?= e(url('profile')) ?>" class="btn-ghost">Профиль</a></div>
    </nav>
    <section class="panel-card page-card" style="max-width:860px;margin:0 auto;">
        <div class="page-header"><div><h1 class="page-title">Оплата курса</h1><p class="page-subtitle">После подтверждения оплаты пользователь получает доступ к программе, урокам и встроенным тестам.</p></div></div>
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="metric-card h-100">
                    <small>Выбранный курс</small>
                    <strong><?= e((string)$course['title']) ?></strong>
                    <div class="price-tag mt-3"><?= e((string)$course['price']) ?> ₽</div>
                    <p class="form-hint mt-3 mb-0">После оплаты в личном кабинете появится доступ к материалам и домашним заданиям.</p>
                </div>
            </div>
            <div class="col-lg-5">
                <?php if ($error !== ''): ?><div class="alert alert-danger rounded-4"><?= e($error) ?></div><?php endif; ?>
                <form method="post" action="<?= e(url('pay-course')) ?>" class="panel-card section-card">
                    <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label class="form-label">Способ оплаты</label>
                    <div class="d-grid gap-2 mb-3">
                        <label class="border rounded-4 p-3"><input class="form-check-input me-2" type="radio" name="payment_method" value="card" checked> Банковская карта</label>
                        <label class="border rounded-4 p-3"><input class="form-check-input me-2" type="radio" name="payment_method" value="sbp"> СБП</label>
                        <label class="border rounded-4 p-3"><input class="form-check-input me-2" type="radio" name="payment_method" value="wallet"> Электронный кошелёк</label>
                    </div>
                    <button class="btn btn-success w-100 rounded-4 py-3">Подтвердить оплату</button>
                </form>
            </div>
        </div>
    </section>
</div>
</body>
</html>
