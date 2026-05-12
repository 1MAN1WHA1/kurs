<?php
require_once __DIR__ . '/../../bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin'));
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    redirect(url('admin?err=csrf'));
}
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect(url('admin?err=not_found'));
}
$stmt = $pdo->prepare('SELECT id, is_course FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    redirect(url('admin?err=not_found'));
}
$chkOrders = $pdo->prepare('SELECT 1 FROM orders WHERE product_id = ? LIMIT 1');
$chkOrders->execute([$id]);
if ($chkOrders->fetchColumn()) {
    redirect(url('admin?err=has_orders'));
}
if ((int)$product['is_course'] === 1) {
    $chkLessons = $pdo->prepare('SELECT 1 FROM lessons WHERE course_id = ? LIMIT 1');
    $chkLessons->execute([$id]);
    if ($chkLessons->fetchColumn()) {
        redirect(url('admin?err=has_lessons'));
    }
}
try {
    $del = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $del->execute([$id]);
    redirect(url('admin?msg=deleted'));
} catch (Throwable $e) {
    redirect(url('admin?err=delete_failed'));
}
