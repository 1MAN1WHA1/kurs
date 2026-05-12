<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

$user_id  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Неверный метод');
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    die('CSRF токен неверный');
}

$lesson_id = (int)($_POST['lesson_id'] ?? 0);
if ($lesson_id <= 0) {
    die('Некорректный урок');
}
if (empty($_FILES['homework'])) {
    die('Файл не передан');
}

$stmt = $pdo->prepare('SELECT id, course_id FROM lessons WHERE id = ?');
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lesson) {
    die('Урок не найден');
}
$course_id = (int)$lesson['course_id'];

$access = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? AND status = 'paid' LIMIT 1");
$access->execute([$user_id, $course_id]);
if (!$access->fetchColumn()) {
    die('Доступ закрыт: сначала купите курс');
}

$allowedExt = ['zip', 'docx'];
$allowedMime = [
    'application/zip',
    'application/x-zip-compressed',
    'application/octet-stream',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$file = $_FILES['homework'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    die('Ошибка загрузки: ' . (int)$file['error']);
}
if ($file['size'] > 20 * 1024 * 1024) {
    die('Файл слишком большой (макс 20MB)');
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    die('Можно загружать только .zip или .docx');
}
if (!in_array($mime, $allowedMime, true)) {
    die('Недопустимый тип файла');
}

$uploadDirFs = PUBLIC_ROOT . '/homeworks/';
$uploadDirPublic = 'homeworks/';
if (!is_dir($uploadDirFs)) {
    die('Папка homeworks не найдена.');
}
$userDirFs = $uploadDirFs . 'user_' . $user_id . '/';
$userDirPublic = $uploadDirPublic . 'user_' . $user_id . '/';
if (!is_dir($userDirFs)) {
    mkdir($userDirFs, 0755, true);
}

$newName = 'hw_' . $lesson_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destinationFs = $userDirFs . $newName;
$destinationPublic = $userDirPublic . $newName;
if (!move_uploaded_file($file['tmp_name'], $destinationFs)) {
    die('Не удалось сохранить файл');
}

if (db_has_column($pdo, 'homework_submissions', 'status')) {
    $ins = $pdo->prepare('INSERT INTO homework_submissions (user_id, lesson_id, file_path, original_name, mime_type, status) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute([$user_id, $lesson_id, $destinationPublic, $file['name'], $mime, 'new']);
} else {
    $ins = $pdo->prepare('INSERT INTO homework_submissions (user_id, lesson_id, file_path, original_name, mime_type) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$user_id, $lesson_id, $destinationPublic, $file['name'], $mime]);
}
recalc_course_progress($pdo, $user_id, $course_id);
redirect(url('view-lesson?id=' . $lesson_id . '&hw=ok'));