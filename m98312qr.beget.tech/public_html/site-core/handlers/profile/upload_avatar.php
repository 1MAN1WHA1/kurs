<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('profile'));
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    die('CSRF токен неверный');
}
if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    die('Ошибка загрузки файла');
}
$file = $_FILES['avatar'];
if ($file['size'] > 5 * 1024 * 1024) {
    die('Файл слишком большой');
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: '';
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
if (!isset($allowed[$mime])) {
    die('Допустимы только JPG и PNG');
}
$ext = $allowed[$mime];
$dirFs = PUBLIC_ROOT . '/avatars';
if (!is_dir($dirFs)) {
    mkdir($dirFs, 0755, true);
}
$name = 'avatar_' . (int)$_SESSION['user_id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destFs = $dirFs . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $destFs)) {
    die('Не удалось сохранить файл');
}
UserModel::updateAvatar($pdo, (int)$_SESSION['user_id'], 'avatars/' . $name);
redirect(url('profile?avatar=ok'));
