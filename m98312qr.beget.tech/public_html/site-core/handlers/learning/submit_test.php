<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/TestModel.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Неверный метод');
}
if (!csrf_check($_POST['csrf_token'] ?? null)) {
    die('CSRF токен неверный');
}

$userId = (int)$_SESSION['user_id'];
$testId = (int)($_POST['test_id'] ?? 0);
$lessonId = (int)($_POST['lesson_id'] ?? 0);
$answers = $_POST['answers'] ?? [];
if ($testId <= 0 || $lessonId <= 0 || !is_array($answers)) {
    die('Некорректные данные теста');
}

$lessonStmt = $pdo->prepare('SELECT course_id FROM lessons WHERE id = ? LIMIT 1');
$lessonStmt->execute([$lessonId]);
$courseId = (int)$lessonStmt->fetchColumn();
if ($courseId <= 0) {
    die('Урок не найден');
}

$accessStmt = $pdo->prepare("SELECT 1 FROM orders WHERE user_id = ? AND product_id = ? AND status = 'paid' LIMIT 1");
$accessStmt->execute([$userId, $courseId]);
if (!$accessStmt->fetchColumn()) {
    die('Нет доступа к тесту');
}

$submitted = [];
foreach ($answers as $questionId => $answerId) {
    $submitted[(int)$questionId] = (int)$answerId;
}

$result = TestModel::evaluate($pdo, $testId, $submitted);
TestModel::saveAttempt(
    $pdo,
    $testId,
    $lessonId,
    $userId,
    (int)$result['score'],
    (bool)$result['passed'],
    (int)$result['correct_answers'],
    (int)$result['total_questions']
);
if ((bool)$result['passed']) {
    recalc_course_progress($pdo, $userId, $courseId);
}

redirect(url('view-lesson?id=' . $lessonId . '&test=' . ((bool)$result['passed'] ? 'passed' : 'failed') . '&score=' . (int)$result['score']));
