<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_login();
$message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности: неверный CSRF-токен.';
    } else {
        $user_id = (int)$_SESSION['user_id'];
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if ($new !== $confirm) $error = 'Новый пароль и подтверждение не совпадают.';
        elseif (mb_strlen($new) < 6) $error = 'Новый пароль должен содержать минимум 6 символов.';
        else {
            $hash = UserModel::findPasswordHash($pdo, $user_id);
            if (!$hash || !password_verify($current, $hash)) $error = 'Текущий пароль неверен.';
            else {
                UserModel::updatePasswordHash($pdo, $user_id, password_hash($new, PASSWORD_DEFAULT));
                $message = 'Пароль успешно изменён.';
            }
        }
    }
}
?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Сменить пароль</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/app.css" rel="stylesheet"></head>
<body class="app-body"><div class="app-shell"><nav class="app-nav"><div class="brand-wrap"><div class="brand-logo">🔒</div><div><p class="brand-title mb-0">Смена пароля</p><p class="brand-subtitle">обновление данных аккаунта пользователя</p></div></div><div class="nav-actions"><a href="/profile" class="btn-soft">Назад в профиль</a></div></nav>
<section class="panel-card page-card" style="max-width:760px;margin:0 auto;">
<div class="page-header"><div><h1 class="page-title">Обновление пароля</h1><p class="page-subtitle">Перед изменением необходимо подтвердить текущий пароль и ввести новый.</p></div></div>
<?php if ($message !== ''): ?><div class="alert alert-success rounded-4"><?= e($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger rounded-4"><?= e($error) ?></div><?php endif; ?>
<form action="" method="POST" class="row g-3">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="col-12"><label class="form-label">Текущий пароль</label><input type="password" name="current_password" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Новый пароль</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
<div class="col-md-6"><label class="form-label">Подтвердите новый пароль</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
<div class="col-12 d-grid"><button type="submit" class="btn btn-primary rounded-4 py-3">Сохранить новый пароль</button></div>
</form></section></div></body></html>
