<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
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

$stmt = $pdo->prepare('SELECT course_id FROM lessons WHERE id = ? LIMIT 1');
$stmt->execute([$lesson_id]);
$course_id = (int)$stmt->fetchColumn();
if ($course_id <= 0) {
    die('Урок не найден');
}

$sel = $pdo->prepare('SELECT id, file_path FROM homework_submissions WHERE user_id = ? AND lesson_id = ?');
$sel->execute([$user_id, $lesson_id]);
$subs = $sel->fetchAll(PDO::FETCH_ASSOC);
if (!$subs) {
    redirect(url('view-lesson?id=' . $lesson_id . '&hw=none'));
}

$del = $pdo->prepare('DELETE FROM homework_submissions WHERE user_id = ? AND lesson_id = ?');
$del->execute([$user_id, $lesson_id]);

$baseDir = realpath(PUBLIC_ROOT . '/homeworks');
if ($baseDir) {
    foreach ($subs as $s) {
        $relPath = (string)($s['file_path'] ?? '');
        if (!starts_with($relPath, 'homeworks/')) {
            continue;
        }
        $abs = realpath(PUBLIC_ROOT . '/' . $relPath);
        if ($abs && starts_with($abs, $baseDir) && is_file($abs)) {
            @unlink($abs);
        }
    }
}

recalc_course_progress($pdo, $user_id, $course_id);
redirect(url('view-lesson?id=' . $lesson_id . '&hw=deleted'));
