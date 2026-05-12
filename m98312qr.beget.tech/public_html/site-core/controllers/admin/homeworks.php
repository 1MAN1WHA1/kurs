<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/HomeworkModel.php';

require_admin();

$status = (string)($_GET['status'] ?? 'all');
$allowed = ['all', 'new', 'in_review', 'accepted', 'rejected'];

if (!in_array($status, $allowed, true)) {
    $status = 'all';
}

$items = HomeworkModel::listForAdmin($pdo, $status);

function hwStatusLabel(string $status): string
{
    if ($status === 'new') return 'Новая';
    if ($status === 'in_review') return 'На проверке';
    if ($status === 'accepted') return 'Принята';
    if ($status === 'rejected') return 'Отклонена';
    return $status;
}

function hwStatusClass(string $status): string
{
    if ($status === 'new') return 'bg-secondary';
    if ($status === 'in_review') return 'bg-warning text-dark';
    if ($status === 'accepted') return 'bg-success';
    if ($status === 'rejected') return 'bg-danger';
    return 'bg-secondary';
}

$pageTitle = 'Проверка домашних заданий';

require SITE_CORE_ROOT . '/templates/layout/header.php';
?>

<div class="container py-4 admin-homeworks-page">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="mb-1 fw-bold">Проверка домашних заданий</h1>
            <p class="text-muted mb-0">
                Управление статусами, комментариями и прогрессом студентов по курсам.
            </p>
        </div>
        <div>
            <a href="/admin" class="btn btn-outline-secondary rounded-3">
                ← Назад в админку
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" action="/admin/homeworks" class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold">Фильтр по статусу</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все работы</option>
                        <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Новые</option>
                        <option value="in_review" <?= $status === 'in_review' ? 'selected' : '' ?>>На проверке</option>
                        <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Принятые</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Отклонённые</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <button class="btn btn-primary rounded-3 w-100">
                        Показать
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body py-5 text-center">
                <h4 class="mb-2">Работы не найдены</h4>
                <p class="text-muted mb-0">По выбранному фильтру домашних заданий пока нет.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-4">
            <?php foreach ($items as $item): ?>
                <div class="card shadow-sm border-0 rounded-4 hw-card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
                            
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                    <h3 class="h4 mb-0 fw-bold">
                                        <?= e((string)$item['course_title']) ?>
                                    </h3>
                                    <span class="badge <?= e(hwStatusClass((string)$item['status'])) ?> px-3 py-2 rounded-pill">
                                        <?= e(hwStatusLabel((string)$item['status'])) ?>
                                    </span>
                                </div>

                                <div class="row g-3 small-info">
                                    <div class="col-md-6">
                                        <div class="hw-info-box">
                                            <div class="hw-info-label">Урок</div>
                                            <div class="hw-info-value"><?= e((string)$item['lesson_title']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="hw-info-box">
                                            <div class="hw-info-label">Пользователь</div>
                                            <div class="hw-info-value"><?= e((string)$item['email']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="hw-info-box">
                                            <div class="hw-info-label">Файл</div>
                                            <div class="hw-info-value text-break"><?= e((string)$item['original_name']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="hw-info-box">
                                            <div class="hw-info-label">Дата отправки</div>
                                            <div class="hw-info-value"><?= e((string)$item['created_at']) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($item['teacher_comment'])): ?>
                                    <div class="mt-4">
                                        <div class="hw-info-label mb-2">Текущий комментарий преподавателя</div>
                                        <div class="alert alert-light border rounded-3 mb-0">
                                            <?= nl2br(e((string)$item['teacher_comment'])) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="hw-form-wrap">
                                <form action="/admin/review-homework" method="POST" class="hw-review-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="submission_id" value="<?= (int)$item['id'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Новый статус</label>
                                        <select name="status" class="form-select rounded-3">
                                            <option value="new" <?= $item['status'] === 'new' ? 'selected' : '' ?>>Новая</option>
                                            <option value="in_review" <?= $item['status'] === 'in_review' ? 'selected' : '' ?>>На проверке</option>
                                            <option value="accepted" <?= $item['status'] === 'accepted' ? 'selected' : '' ?>>Принята</option>
                                            <option value="rejected" <?= $item['status'] === 'rejected' ? 'selected' : '' ?>>Отклонена</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Комментарий</label>
                                        <textarea
                                            name="teacher_comment"
                                            class="form-control rounded-3"
                                            rows="6"
                                            placeholder="Например: работа принята, но нужно исправить оформление / добавить пояснение / доработать файл..."
                                        ><?= e((string)($item['teacher_comment'] ?? '')) ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary rounded-3 w-100">
                                        Сохранить результат проверки
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require SITE_CORE_ROOT . '/templates/layout/footer.php'; ?>