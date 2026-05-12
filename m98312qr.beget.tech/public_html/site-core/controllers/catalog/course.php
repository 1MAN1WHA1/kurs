<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/ProductModel.php';
require_once __DIR__ . '/../../models/OrderModel.php';
require_once __DIR__ . '/../../models/LessonModel.php';
require_once __DIR__ . '/../../models/HomeworkModel.php';
require_once __DIR__ . '/../../models/TestModel.php';

$course_id = (int)($_GET['id'] ?? 0);
if ($course_id <= 0) {
    http_response_code(404);
    die('Курс не найден');
}

$course = ProductModel::findCourseById($pdo, $course_id);
if (!$course) {
    http_response_code(404);
    die('Курс не найден');
}

$user_id = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$hasAccess = $user_id > 0 ? OrderModel::userHasPaidCourse($pdo, $user_id, $course_id) : false;
$lessons = LessonModel::listByCourseId($pdo, $course_id);
$lessonIds = array_map(static fn(array $lesson): int => (int)$lesson['id'], $lessons);
$testsByLesson = TestModel::mapByLessonIds($pdo, $lessonIds);
$completed = $user_id > 0 ? array_fill_keys(HomeworkModel::completedLessonIdsForCourse($pdo, $user_id, $course_id), true) : [];
$totalLessons = count($lessons);
$doneLessons = count($completed);
$progressPercent = 0;
if ($hasAccess && $totalLessons > 0) {
    $progressPercent = recalc_course_progress($pdo, $user_id, $course_id);
    $completed = array_fill_keys(HomeworkModel::completedLessonIdsForCourse($pdo, $user_id, $course_id), true);
    $doneLessons = count($completed);
}
$recommendations = ProductModel::recommendationsForCourse($pdo, $course_id, $user_id, 3);
$img = !empty($course['image_url']) ? (string)$course['image_url'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80';
$level = (string)($course['level'] ?? 'Базовый');
$duration = (string)($course['duration_text'] ?? (($totalLessons > 0 ? $totalLessons . ' уроков' : 'Свободный темп')));
$audience = trim((string)($course['target_audience'] ?? 'Подходит начинающим специалистам и тем, кто хочет структурировать знания.'));
$outcomes = trim((string)($course['learning_outcomes'] ?? 'После прохождения курса пользователь получает практический материал, выполняет задания и закрепляет знания тестированием.'));
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e((string)$course['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
  <nav class="app-nav">
    <div class="brand-wrap">
      <div class="brand-logo">L</div>
      <div>
        <p class="brand-title mb-0">Карточка курса</p>
        <p class="brand-subtitle">полная программа, уроки, тестирование и контроль выполнения</p>
      </div>
    </div>
    <div class="nav-actions">
      <a href="<?= e(url()) ?>" class="btn-soft">Каталог</a>
      <?php if (!empty($_SESSION['user_id'])): ?><a href="<?= e(url('profile')) ?>" class="btn-soft">Личный кабинет</a><?php endif; ?>
      <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?><a href="<?= e(url('admin')) ?>" class="btn-ghost">Админка</a><?php endif; ?>
    </div>
  </nav>

  <section class="hero-card">
    <div class="hero-grid">
      <div>
        <div class="form-hint text-uppercase mb-2">Онлайн-обучение</div>
        <h1 class="hero-title"><?= e((string)$course['title']) ?></h1>
        <?php if (!empty($course['short_description'])): ?><p class="hero-text mb-3"><?= e((string)$course['short_description']) ?></p><?php endif; ?>
        <p class="hero-text"><?= nl2br(e((string)($course['description'] ?? 'Описание курса пока не заполнено.'))) ?></p>
        <div class="hero-pills">
          <span class="hero-pill">Цена: <?= e((string)$course['price']) ?> ₽</span>
          <span class="hero-pill">Уровень: <?= e($level) ?></span>
          <span class="hero-pill">Продолжительность: <?= e($duration) ?></span>
        </div>
      </div>
      <div class="panel-card media-tall">
        <img src="<?= e($img) ?>" alt="<?= e((string)$course['title']) ?>" class="cover-media">
      </div>
    </div>
  </section>

  <div class="info-grid course-highlights">
    <section class="panel-card page-card">
      <h2 class="page-title h4">Для кого этот курс</h2>
      <p class="page-subtitle mt-2"><?= nl2br(e($audience)) ?></p>
    </section>
    <section class="panel-card page-card">
      <h2 class="page-title h4">Что получит пользователь</h2>
      <p class="page-subtitle mt-2"><?= nl2br(e($outcomes)) ?></p>
    </section>
  </div>

  <?php if (!$user_id): ?>
    <section class="panel-card page-card mt-4">
      <div class="alert alert-warning rounded-4 mb-4">Чтобы открыть программу обучения, выполнить задания и пройти тесты, войдите в аккаунт.</div>
      <a href="<?= e(url('login')) ?>" class="btn btn-primary rounded-4 px-4 py-3">Войти в систему</a>
    </section>
  <?php elseif (!$hasAccess): ?>
    <section class="panel-card page-card mt-4">
      <div class="alert alert-danger rounded-4 mb-4">Доступ к материалам пока закрыт. После оплаты откроются уроки, домашние задания и тестирование.</div>
      <a href="<?= e(url('buy-course?id=' . $course_id)) ?>" class="btn btn-success rounded-4 px-4 py-3">Перейти к оплате</a>
    </section>
  <?php else: ?>
    <div class="info-grid mt-4">
      <section class="panel-card page-card">
        <div class="d-flex justify-content-between mb-2"><div class="fw-semibold">Прогресс обучения</div><div class="form-hint"><?= (int)$doneLessons ?>/<?= (int)$totalLessons ?> · <?= (int)$progressPercent ?>%</div></div>
        <div class="progress-modern"><div class="progress-bar" role="progressbar" style="width: <?= (int)$progressPercent ?>%"></div></div>
        <div class="form-hint mt-3">Прогресс обновляется после успешной проверки домашнего задания или прохождения теста.</div>
      </section>
      <section class="panel-card page-card">
        <h2 class="page-title h4">Статус доступа</h2>
        <span class="badge-status success mt-3">Материалы доступны</span>
        <p class="page-subtitle mt-3">Вы можете открывать уроки, загружать практические работы и проходить встроенные тесты по темам.</p>
      </section>
    </div>
  <?php endif; ?>

  <section class="panel-card page-card mt-4">
    <div class="page-header">
      <div>
        <h2 class="page-title h3">Программа курса</h2>
        <p class="page-subtitle">У каждого урока есть краткое описание, блок материалов и домашнее задание.</p>
      </div>
    </div>
    <?php if (!$lessons): ?>
      <div class="alert alert-light border rounded-4">Уроков пока нет.</div>
    <?php else: ?>
      <div class="list-clean lesson-cards">
        <?php foreach ($lessons as $index => $lesson): ?>
          <?php $lid = (int)$lesson['id']; $lessonTest = $testsByLesson[$lid] ?? null; ?>
          <article class="lesson-card-rich">
            <div class="lesson-card-head">
              <div>
                <div class="form-hint text-uppercase mb-1">Урок <?= $index + 1 ?></div>
                <h3 class="card-title mb-1"><?= e((string)$lesson['title']) ?></h3>
                <p class="card-text mb-0"><?= e((string)($lesson['description'] ?? 'Описание урока пока не заполнено.')) ?></p>
              </div>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <?php if (!empty($completed[$lid])): ?><span class="badge-status success">Завершён</span><?php else: ?><span class="badge-status warning">В процессе</span><?php endif; ?>
                <?php if ($lessonTest): ?><span class="badge-status neutral">Тест включён</span><?php endif; ?>
              </div>
            </div>
            <div class="lesson-card-grid">
              <div>
                <div class="mini-title">Материалы</div>
                <p class="form-hint mb-0"><?= e((string)($lesson['theory_text'] ?? 'Основной теоретический блок открывается на странице урока.')) ?></p>
              </div>
              <div>
                <div class="mini-title">Практика</div>
                <p class="form-hint mb-0"><?= e((string)($lesson['practice_text'] ?? 'После просмотра урока необходимо выполнить практическое задание и загрузить файл.')) ?></p>
              </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <?php if ($hasAccess): ?>
                <a href="<?= e(url('view-lesson?id=' . $lid)) ?>" class="btn btn-outline-primary rounded-4">Открыть урок</a>
              <?php else: ?>
                <a href="<?= e(url('buy-course?id=' . $course_id)) ?>" class="btn btn-outline-primary rounded-4">Оформить доступ</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($recommendations): ?>
    <section class="panel-card page-card mt-4">
      <div class="page-header">
        <div>
          <h2 class="page-title h3">Похожие курсы</h2>
          <p class="page-subtitle">Подборка формируется автоматически по тематике выбранного курса и вашим покупкам.</p>
        </div>
      </div>
      <div class="catalog-grid compact-grid">
        <?php foreach ($recommendations as $item): ?>
          <article class="panel-card catalog-card compact-card">
            <div class="card-body">
              <h3 class="card-title"><?= e((string)$item['title']) ?></h3>
              <p class="card-text"><?= e(mb_strimwidth((string)($item['description'] ?? ''), 0, 120, '…')) ?></p>
              <div class="price-tag"><?= e((string)$item['price']) ?> ₽</div>
            </div>
            <div class="card-footer-actions">
              <a href="<?= e(url('course?id=' . (int)$item['id'])) ?>" class="btn btn-outline-primary w-100 rounded-4">Открыть</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
</body>
</html>
