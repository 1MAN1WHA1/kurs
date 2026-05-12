<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/LessonModel.php';
require_once __DIR__ . '/../../models/ProductModel.php';
require_once __DIR__ . '/../../models/OrderModel.php';
require_once __DIR__ . '/../../models/HomeworkModel.php';
require_once __DIR__ . '/../../models/TestModel.php';

require_login();
$user_id  = (int)$_SESSION['user_id'];
$lesson_id = (int)($_GET['id'] ?? 0);
if ($lesson_id <= 0) {
    die('Урок не найден');
}

$lesson = LessonModel::findById($pdo, $lesson_id);
if (!$lesson) {
    die('Урок не найден');
}

$course_id = (int)$lesson['course_id'];
$course = ProductModel::findCourseById($pdo, $course_id);
$hasAccess = OrderModel::userHasPaidCourse($pdo, $user_id, $course_id);
$lastHw = $hasAccess ? HomeworkModel::latestForUserLesson($pdo, $user_id, $lesson_id) : null;
$test = $hasAccess ? TestModel::findByLessonId($pdo, $lesson_id) : null;
$questions = $test ? TestModel::questionsWithAnswers($pdo, (int)$test['id']) : [];
$lastAttempt = $test ? TestModel::lastAttempt($pdo, (int)$test['id'], $user_id) : null;
$testPassed = $test ? TestModel::passedForLesson($pdo, $lesson_id, $user_id) : false;

$hwStatus = (string)($_GET['hw'] ?? '');
$testStatus = (string)($_GET['test'] ?? '');
$scoreValue = isset($_GET['score']) ? (int)$_GET['score'] : null;
$downloadPath = '';
if ($lastHw) {
    $path = (string)($lastHw['file_path'] ?? '');
    if (starts_with($path, 'homeworks/')) {
        $downloadPath = $path;
    }
}
$statusMap = [
    'new' => ['Ожидает проверки', 'warning'],
    'in_review' => ['На проверке', 'neutral'],
    'accepted' => ['Принято', 'success'],
    'rejected' => ['Нужно доработать', 'danger'],
];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e((string)($lesson['title'] ?? 'Урок')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <nav class="app-nav">
        <div class="brand-wrap">
            <div class="brand-logo">▶</div>
            <div>
                <p class="brand-title mb-0">Материал урока</p>
                <p class="brand-subtitle">теория, практика, тестирование и статус выполнения</p>
            </div>
        </div>
        <div class="nav-actions">
            <a href="<?= e(url('course?id=' . $course_id)) ?>" class="btn-soft">К курсу</a>
            <a href="<?= e(url('profile')) ?>" class="btn-ghost">Профиль</a>
        </div>
    </nav>

    <section class="panel-card page-card">
        <div class="page-header">
            <div>
                <div class="form-hint text-uppercase mb-2"><?= $course ? 'Курс: ' . e((string)$course['title']) : 'Урок' ?></div>
                <h1 class="page-title"><?= e((string)($lesson['title'] ?? '')) ?></h1>
                <p class="page-subtitle"><?= e((string)($lesson['description'] ?? 'Урок содержит видео, теоретический материал, задание и тест.')) ?></p>
            </div>
        </div>

        <?php if (!$hasAccess): ?>
            <div class="alert alert-danger rounded-4 mb-4">Доступ закрыт. Чтобы посмотреть этот урок, сначала купите курс.</div>
            <a href="<?= e(url('buy-course?id=' . $course_id)) ?>" class="btn btn-success rounded-4 px-4 py-3">Купить курс</a>
        <?php else: ?>
            <div class="video-frame ratio ratio-16x9 mb-4"><video src="<?= e((string)($lesson['video_url'] ?? '')) ?>" controls></video></div>

            <?php if ($hwStatus === 'ok'): ?><div class="alert alert-success rounded-4">Домашнее задание загружено и отправлено на проверку.</div><?php endif; ?>
            <?php if ($hwStatus === 'deleted'): ?><div class="alert alert-warning rounded-4">Домашнее задание удалено. Прогресс пересчитан.</div><?php endif; ?>
            <?php if ($hwStatus === 'none'): ?><div class="alert alert-info rounded-4">Домашнее задание по этому уроку не найдено.</div><?php endif; ?>
            <?php if ($testStatus === 'passed'): ?><div class="alert alert-success rounded-4">Тест пройден. Результат: <?= (int)$scoreValue ?>%.</div><?php endif; ?>
            <?php if ($testStatus === 'failed'): ?><div class="alert alert-warning rounded-4">Тест не пройден. Результат: <?= (int)$scoreValue ?>%. Попробуйте ещё раз.</div><?php endif; ?>

            <div class="info-grid lesson-details-grid">
                <section class="panel-card page-card">
                    <h2 class="page-title h3">Краткая теория</h2>
                    <div class="article-block"><?= nl2br(e((string)($lesson['theory_text'] ?? 'Текстовый блок урока пока не заполнен. Здесь можно разместить основные тезисы, ключевые идеи и дополнительные пояснения.'))) ?></div>
                </section>
                <section class="panel-card page-card">
                    <h2 class="page-title h3">Практическое задание</h2>
                    <div class="article-block"><?= nl2br(e((string)($lesson['practice_text'] ?? 'После просмотра урока подготовьте файл с результатом работы и загрузите его в систему.'))) ?></div>
                </section>
            </div>

            <div class="info-grid mt-4 lesson-details-grid">
                <section class="panel-card page-card">
                    <h2 class="page-title h3">Загрузка домашнего задания</h2>
                    <p class="page-subtitle">Поддерживаются файлы .zip и .docx. После загрузки работа получает статус «Ожидает проверки».</p>
                    <form action="<?= e(url('upload-homework')) ?>" method="POST" enctype="multipart/form-data" class="row g-3 mt-1">
                        <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="col-12">
                            <label class="form-label">Выберите файл</label>
                            <input type="file" name="homework" class="form-control" accept=".zip,.docx" required>
                        </div>
                        <div class="col-12 d-grid"><button type="submit" class="btn btn-success rounded-4 py-3">Отправить на проверку</button></div>
                    </form>
                </section>

                <section class="panel-card page-card">
                    <h2 class="page-title h3">Статус последней работы</h2>
                    <?php if ($lastHw): ?>
                        <?php [$statusTitle, $statusClass] = $statusMap[(string)$lastHw['status']] ?? ['Статус не определён', 'neutral']; ?>
                        <div class="metric-card">
                            <small>Файл</small>
                            <strong><?= e((string)($lastHw['original_name'] ?? '')) ?></strong>
                            <div class="mt-2"><span class="badge-status <?= e($statusClass) ?>"><?= e($statusTitle) ?></span></div>
                            <div class="form-hint mt-2">Загружено: <?= e((string)($lastHw['created_at'] ?? '')) ?></div>
                            <?php if (!empty($lastHw['checked_at'])): ?><div class="form-hint">Проверено: <?= e((string)$lastHw['checked_at']) ?></div><?php endif; ?>
                        </div>
                        <?php if (!empty($lastHw['teacher_comment'])): ?>
                            <div class="alert alert-light border rounded-4 mt-3 mb-0"><b>Комментарий проверяющего:</b><br><?= nl2br(e((string)$lastHw['teacher_comment'])) ?></div>
                        <?php endif; ?>
                        <div class="d-grid gap-2 mt-3">
                            <?php if ($downloadPath !== ''): ?><a class="btn btn-outline-secondary rounded-4" href="<?= e($downloadPath) ?>" target="_blank" rel="noopener">Скачать последнюю работу</a><?php endif; ?>
                            <form action="<?= e(url('delete-homework')) ?>" method="POST" onsubmit="return confirm('Удалить домашнее задание по этому уроку?');">
                                <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="btn btn-outline-danger rounded-4 w-100">Удалить работу</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border rounded-4 mb-0">Работа по этому уроку ещё не загружалась.</div>
                    <?php endif; ?>
                </section>
            </div>

            <?php if ($test): ?>
                <section class="panel-card page-card mt-4">
                    <div class="page-header">
                        <div>
                            <h2 class="page-title h3"><?= e((string)($test['title'] ?? 'Тест по уроку')) ?></h2>
                            <p class="page-subtitle"><?= e((string)($test['description'] ?? 'После изучения материала ответьте на вопросы. Для зачёта требуется не менее проходного балла.')) ?></p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <span class="hero-pill">Проходной балл: <?= (int)($test['passing_score'] ?? 70) ?>%</span>
                        <?php if ($testPassed): ?><span class="hero-pill">Статус: тест пройден</span><?php endif; ?>
                        <?php if ($lastAttempt): ?><span class="hero-pill">Последний результат: <?= (int)$lastAttempt['score_percent'] ?>%</span><?php endif; ?>
                    </div>
                    <?php if ($questions): ?>
                        <form action="<?= e(url('submit-test')) ?>" method="POST" class="quiz-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="test_id" value="<?= (int)$test['id'] ?>">
                            <input type="hidden" name="lesson_id" value="<?= (int)$lesson_id ?>">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="quiz-question">
                                    <div class="fw-semibold mb-2">Вопрос <?= $index + 1 ?>. <?= e((string)$question['question_text']) ?></div>
                                    <div class="d-grid gap-2">
                                        <?php foreach ($question['answers'] as $answer): ?>
                                            <label class="answer-option">
                                                <input type="radio" name="answers[<?= (int)$question['id'] ?>]" value="<?= (int)$answer['id'] ?>" required>
                                                <span><?= e((string)$answer['answer_text']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary rounded-4 px-4 py-3">Отправить ответы</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-light border rounded-4 mb-0">Тест подключён, но вопросы ещё не добавлены.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
