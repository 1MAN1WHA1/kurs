<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Неверный метод');
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('CSRF ошибка');
}

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
$stmt->execute([$product_id]);
if (!$stmt->fetchColumn()) {
    die('Товар не найден');
}
$stmt = $pdo->prepare('INSERT INTO orders (user_id, product_id) VALUES (?, ?)');
$stmt->execute([$user_id, $product_id]);
redirect(url('profile'));
