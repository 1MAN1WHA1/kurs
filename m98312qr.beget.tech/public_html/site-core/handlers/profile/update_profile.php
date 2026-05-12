<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        die('Ошибка безопасности: неверный CSRF-токен');
    }

    $user_id = (int)$_SESSION['user_id'];
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($new !== $confirm) {
        redirect('change_password.php?error=1');
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current, (string)$user['password_hash'])) {
        redirect('change_password.php?error=2');
    }

    $new_hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $stmt->execute([
        ':hash' => $new_hash,
        ':id' => $user_id,
    ]);

    redirect('change_password.php?updated=1');
}
