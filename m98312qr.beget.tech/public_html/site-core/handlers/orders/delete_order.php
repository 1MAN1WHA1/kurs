<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Неверный метод');
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('CSRF токен неверный');
}
$userId = (int)$_SESSION['user_id'];
$orderId = (int)($_POST['order_id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $userId]);
redirect(url('profile'));
