<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url());
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('CSRF токен неверный');
}

$user_id = (int)$_SESSION['user_id'];
$course_id = (int)($_POST['course_id'] ?? 0);
$payment_method = (string)($_POST['payment_method'] ?? 'card');
$allowedMethods = ['card', 'sbp', 'wallet'];
if ($course_id <= 0) {
    http_response_code(400);
    die('Некорректный курс');
}
if (!in_array($payment_method, $allowedMethods, true)) {
    $payment_method = 'card';
}
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_course = 1");
$stmt->execute([$course_id]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    die('Курс не найден');
}

try {
    $pdo->beginTransaction();
    $paid = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? AND status = 'paid' LIMIT 1");
    $paid->execute([$user_id, $course_id]);
    if ($paid->fetchColumn()) {
        $pdo->commit();
        redirect(url('course?id=' . $course_id));
    }
    $checkNew = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? AND status = 'new' ORDER BY id DESC LIMIT 1");
    $checkNew->execute([$user_id, $course_id]);
    $newOrderId = $checkNew->fetchColumn();
    if ($newOrderId) {
        $upd = $pdo->prepare('UPDATE orders SET status = ?, payment_method = ? WHERE id = ?');
        $upd->execute(['paid', $payment_method, $newOrderId]);
    } else {
        $ins = $pdo->prepare("INSERT INTO orders (user_id, product_id, status, payment_method) VALUES (?, ?, 'paid', ?)");
        $ins->execute([$user_id, $course_id, $payment_method]);
    }
    $pdo->commit();
    redirect(url('course?id=' . $course_id));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect(url('buy-course?id=' . $course_id . '&err=payment'));
}
