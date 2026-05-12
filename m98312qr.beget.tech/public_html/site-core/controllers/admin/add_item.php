<?php
require_once __DIR__ . '/../../bootstrap.php';

require_admin();

$errorMsg = '';
$successMsg = '';

$title = trim((string)($_POST['title'] ?? ''));
$shortDescription = trim((string)($_POST['short_description'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$price = trim((string)($_POST['price'] ?? ''));
$imageUrl = trim((string)($_POST['image_url'] ?? ''));
$isCourse = !empty($_POST['is_course']) ? 1 : 0;

$difficultyLevel = trim((string)($_POST['difficulty_level'] ?? ''));
$durationText = trim((string)($_POST['duration_text'] ?? ''));
$previewVideoUrl = trim((string)($_POST['preview_video_url'] ?? ''));
$learningFormat = trim((string)($_POST['learning_format'] ?? ''));
$targetAudience = trim((string)($_POST['target_audience'] ?? ''));
$skillsResult = trim((string)($_POST['skills_result'] ?? ''));
$programText = trim((string)($_POST['program_text'] ?? ''));
$requirementsText = trim((string)($_POST['requirements_text'] ?? ''));
$bonusText = trim((string)($_POST['bonus_text'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'Ошибка безопасности: неверный CSRF-токен.';
    } elseif ($title === '' || $description === '' || $price === '') {
        $errorMsg = 'Заполните обязательные поля.';
    } elseif (!is_numeric($price)) {
        $errorMsg = 'Цена должна быть числом.';
    } else {
        try {
            $columns = ['title', 'description', 'price', 'image_url', 'is_course'];
            $placeholders = ['?', '?', '?', '?', '?'];
            $params = [$title, $description, (float)$price, $imageUrl, $isCourse];

            if (db_has_column($pdo, 'products', 'short_description')) {
                $columns[] = 'short_description';
                $placeholders[] = '?';
                $params[] = $shortDescription;
            }
            if (db_has_column($pdo, 'products', 'difficulty_level')) {
                $columns[] = 'difficulty_level';
                $placeholders[] = '?';
                $params[] = $difficultyLevel;
            }
            if (db_has_column($pdo, 'products', 'duration_text')) {
                $columns[] = 'duration_text';
                $placeholders[] = '?';
                $params[] = $durationText;
            }
            if (db_has_column($pdo, 'products', 'preview_video_url')) {
                $columns[] = 'preview_video_url';
                $placeholders[] = '?';
                $params[] = $previewVideoUrl;
            }
            if (db_has_column($pdo, 'products', 'learning_format')) {
                $columns[] = 'learning_format';
                $placeholders[] = '?';
                $params[] = $learningFormat;
            }
            if (db_has_column($pdo, 'products', 'target_audience')) {
                $columns[] = 'target_audience';
                $placeholders[] = '?';
                $params[] = $targetAudience;
            }
            if (db_has_column($pdo, 'products', 'skills_result')) {
                $columns[] = 'skills_result';
                $placeholders[] = '?';
                $params[] = $skillsResult;
            }
            if (db_has_column($pdo, 'products', 'program_text')) {
                $columns[] = 'program_text';
                $placeholders[] = '?';
                $params[] = $programText;
            }
            if (db_has_column($pdo, 'products', 'requirements_text')) {
                $columns[] = 'requirements_text';
                $placeholders[] = '?';
                $params[] = $requirementsText;
            }
            if (db_has_column($pdo, 'products', 'bonus_text')) {
                $columns[] = 'bonus_text';
                $placeholders[] = '?';
                $params[] = $bonusText;
            }

            $sql = 'INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $successMsg = 'Запись успешно добавлена.';
            $title = $shortDescription = $description = $price = $imageUrl = '';
            $difficultyLevel = $durationText = $previewVideoUrl = $learningFormat = '';
            $targetAudience = $skillsResult = $programText = $requirementsText = $bonusText = '';
            $isCourse = 0;
        } catch (Throwable $e) {
            $errorMsg = 'Не удалось сохранить запись.';
        }
    }
}

$pageTitle = 'Добавление записи';

require SITE_CORE_ROOT . '/templates/layout/header.php';
?>

<div class="container py-4 admin-add-page">
    <section class="hero-card mb-4">
        <div class="page-header mb-0">
            <div>
                <div class="eyebrow">Администрирование</div>
                <h1 class="page-title">Добавление новой записи</h1>
                <p class="page-subtitle">
                    Создание нового курса или цифрового товара с расширенным описанием и параметрами.
                </p>
            </div>
            <div class="nav-actions">
                <a href="/admin" class="btn-soft">Назад в админку</a>
                <a href="/admin/orders" class="btn-ghost">Все заказы</a>
            </div>
        </div>
    </section>

    <?php if ($errorMsg !== ''): ?>
        <div class="alert alert-danger rounded-4"><?= e($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($successMsg !== ''): ?>
        <div class="alert alert-success rounded-4"><?= e($successMsg) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/add-item" class="admin-add-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="add-layout">
            <div class="add-main">
                <div class="panel-card form-section mb-4">
                    <h2 class="section-title">Основная информация</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Название</label>
                            <input type="text" name="title" class="form-control" required value="<?= e($title) ?>">
                        </div>

                        <?php if (db_has_column($pdo, 'products', 'short_description')): ?>
                            <div class="col-12">
                                <label class="form-label">Краткое описание</label>
                                <input type="text" name="short_description" class="form-control" value="<?= e($shortDescription) ?>">
                            </div>
                        <?php endif; ?>

                        <div class="col-md-4">
                            <label class="form-label">Цена</label>
                            <input type="text" name="price" class="form-control" required value="<?= e($price) ?>">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Ссылка на изображение</label>
                            <input type="text" name="image_url" class="form-control" value="<?= e($imageUrl) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Полное описание</label>
                            <textarea name="description" class="form-control" rows="6" required><?= e($description) ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_course" name="is_course" value="1" <?= $isCourse ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="is_course">
                                    Это курс, а не обычный товар
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel-card form-section mb-4">
                    <h2 class="section-title">Параметры курса</h2>
                    <div class="row g-3">
                        <?php if (db_has_column($pdo, 'products', 'difficulty_level')): ?>
                            <div class="col-md-6">
                                <label class="form-label">Уровень</label>
                                <input type="text" name="difficulty_level" class="form-control" value="<?= e($difficultyLevel) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'duration_text')): ?>
                            <div class="col-md-6">
                                <label class="form-label">Длительность</label>
                                <input type="text" name="duration_text" class="form-control" value="<?= e($durationText) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'learning_format')): ?>
                            <div class="col-md-6">
                                <label class="form-label">Формат обучения</label>
                                <input type="text" name="learning_format" class="form-control" value="<?= e($learningFormat) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'preview_video_url')): ?>
                            <div class="col-md-6">
                                <label class="form-label">Ссылка на превью-видео</label>
                                <input type="text" name="preview_video_url" class="form-control" value="<?= e($previewVideoUrl) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'target_audience')): ?>
                            <div class="col-12">
                                <label class="form-label">Для кого подходит курс</label>
                                <textarea name="target_audience" class="form-control" rows="4"><?= e($targetAudience) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'skills_result')): ?>
                            <div class="col-12">
                                <label class="form-label">Что получит пользователь</label>
                                <textarea name="skills_result" class="form-control" rows="4"><?= e($skillsResult) ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel-card form-section">
                    <h2 class="section-title">Содержимое и дополнительные блоки</h2>
                    <div class="row g-3">
                        <?php if (db_has_column($pdo, 'products', 'program_text')): ?>
                            <div class="col-12">
                                <label class="form-label">Программа курса</label>
                                <textarea name="program_text" class="form-control" rows="6"><?= e($programText) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'requirements_text')): ?>
                            <div class="col-12">
                                <label class="form-label">Требования</label>
                                <textarea name="requirements_text" class="form-control" rows="4"><?= e($requirementsText) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if (db_has_column($pdo, 'products', 'bonus_text')): ?>
                            <div class="col-12">
                                <label class="form-label">Бонусы / дополнительные материалы</label>
                                <textarea name="bonus_text" class="form-control" rows="4"><?= e($bonusText) ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="add-side">
                <div class="panel-card sticky-side-card">
                    <h3 class="h5 fw-bold mb-3">Подсказка</h3>
                    <p class="text-muted mb-3">
                        Заполняй карточку как полноценную страницу продукта. Для курсов лучше указывать
                        программу, длительность, формат и итоговый результат.
                    </p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary rounded-4 py-3">
                            Сохранить запись
                        </button>
                        <a href="/admin" class="btn btn-outline-secondary rounded-4">
                            Отменить
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

<?php require SITE_CORE_ROOT . '/templates/layout/footer.php'; ?>