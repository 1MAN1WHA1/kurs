<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/HomeworkModel.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Неверный метод');
}

if (!csrf_check($_POST['csrf_token'] ?? null)) {
    die('CSRF токен неверный');
}

$submissionId = (int)($_POST['submission_id'] ?? 0);
$status = (string)($_POST['status'] ?? 'new');
$teacherComment = trim((string)($_POST['teacher_comment'] ?? ''));

$allowed = ['new', 'in_review', 'accepted', 'rejected'];

if ($submissionId <= 0 || !in_array($status, $allowed, true)) {
    die('Некорректные данные');
}

$submission = HomeworkModel::findForAdmin($pdo, $submissionId);
if (!$submission) {
    die('Работа не найдена');
}

$hasStatus = db_has_column($pdo, 'homework_submissions', 'status');
$hasComment = db_has_column($pdo, 'homework_submissions', 'teacher_comment');
$hasChecked = db_has_column($pdo, 'homework_submissions', 'checked_at');

$parts = [];
$params = [];

if ($hasStatus) {
    $parts[] = 'status = ?';
    $params[] = $status;
}
if ($hasComment) {
    $parts[] = 'teacher_comment = ?';
    $params[] = $teacherComment;
}
if ($hasChecked) {
    $parts[] = 'checked_at = CURRENT_TIMESTAMP';
}

if ($parts) {
    $params[] = $submissionId;
    $stmt = $pdo->prepare('UPDATE homework_submissions SET ' . implode(', ', $parts) . ' WHERE id = ?');
    $stmt->execute($params);
}

recalc_course_progress($pdo, (int)$submission['user_id'], (int)$submission['course_id']);

redirect(url('admin/homeworks'));