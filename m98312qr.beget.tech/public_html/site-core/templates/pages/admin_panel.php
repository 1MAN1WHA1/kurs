<section class="hero-card">
    <div class="page-header mb-0">
        <div>
            <h1 class="page-title">Панель администратора</h1>
            <p class="page-subtitle">Управление каталогом, аналитикой, проверкой домашних заданий и учебным контентом.</p>
        </div>
        <div class="nav-actions">
            <a href="<?= e(url()) ?>" class="btn-soft">На сайт</a>
            <a href="<?= e(url('admin/add-item')) ?>" class="btn-gradient">Добавить запись</a>
            <a href="<?= e(url('admin/homeworks')) ?>" class="btn-soft">Проверка ДЗ<?php if ($pendingHomeworkCount > 0): ?> (<?= (int)$pendingHomeworkCount ?>)<?php endif; ?></a>
            <a href="<?= e(url('admin/orders')) ?>" class="btn-ghost">Заказы</a>
        </div>
    </div>
</section>

<div class="dashboard-grid mb-4">
    <div class="metric-card"><small>Пользователи</small><strong><?= (int)$analytics['users_total'] ?></strong></div>
    <div class="metric-card"><small>Курсы в каталоге</small><strong><?= (int)$analytics['courses_total'] ?></strong></div>
    <div class="metric-card"><small>Оплаченные заказы</small><strong><?= (int)$analytics['orders_paid'] ?></strong></div>
    <div class="metric-card"><small>Выручка</small><strong><?= number_format((float)$analytics['revenue_total'], 0, '', ' ') ?> ₽</strong></div>
    <div class="metric-card"><small>ДЗ на проверке</small><strong><?= (int)$analytics['pending_homeworks'] ?></strong></div>
    <div class="metric-card"><small>Принятые ДЗ</small><strong><?= (int)$analytics['accepted_homeworks'] ?></strong></div>
    <div class="metric-card"><small>Попытки тестов</small><strong><?= (int)$analytics['tests_attempts'] ?></strong></div>
    <div class="metric-card"><small>Средний прогресс</small><strong><?= (int)round((float)$analytics['avg_progress']) ?>%</strong></div>
</div>

<?php if ($msg === 'updated'): ?><div class="alert alert-success rounded-4">Запись обновлена.</div><?php endif; ?>
<?php if ($msg === 'deleted'): ?><div class="alert alert-success rounded-4">Запись удалена.</div><?php endif; ?>
<?php if ($err === 'csrf'): ?><div class="alert alert-danger rounded-4">CSRF токен неверный. Удаление заблокировано.</div><?php endif; ?>
<?php if ($err === 'not_found'): ?><div class="alert alert-danger rounded-4">Запись не найдена.</div><?php endif; ?>
<?php if ($err === 'has_orders'): ?><div class="alert alert-warning rounded-4">Нельзя удалить запись: с ней уже связаны заказы.</div><?php endif; ?>
<?php if ($err === 'has_lessons'): ?><div class="alert alert-warning rounded-4">Нельзя удалить курс: у него существуют уроки.</div><?php endif; ?>
<?php if ($err === 'delete_failed'): ?><div class="alert alert-danger rounded-4">Удаление не выполнено из-за ограничений базы данных.</div><?php endif; ?>

<section class="panel-card page-card mb-4">
    <div class="page-header">
        <div>
            <h2 class="page-title h3">Популярные курсы</h2>
            <p class="page-subtitle">Здесь отображаются наиболее покупаемые программы и средний прогресс по ним.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Курс</th><th>Покупок</th><th>Средний прогресс</th></tr></thead>
            <tbody>
            <?php if (empty($topCourses)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Пока нет оплаченных курсов.</td></tr>
            <?php else: foreach ($topCourses as $row): ?>
                <tr>
                    <td><?= e((string)$row['title']) ?></td>
                    <td><?= (int)$row['sales_count'] ?></td>
                    <td><?= (int)round((float)$row['avg_progress']) ?>%</td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel-card filter-card">
    <form method="GET" action="<?= e(url('admin')) ?>" class="row g-3 align-items-end">
        <div class="col-lg-7">
            <label class="form-label">Поиск по названию</label>
            <input type="text" name="q" class="form-control" placeholder="Введите название записи" value="<?= e($q) ?>">
        </div>
        <div class="col-lg-3">
            <label class="form-label">Тип записи</label>
            <select name="type" class="form-select">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Все</option>
                <option value="course" <?= $type === 'course' ? 'selected' : '' ?>>Только курсы</option>
                <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Только товары</option>
            </select>
        </div>
        <div class="col-lg-2 d-grid"><button class="btn btn-primary btn-lg rounded-4">Применить</button></div>
    </form>
    <div class="mt-3 form-hint">Всего записей: <?= (int)$totalRows ?> · Страница <?= (int)$page ?> из <?= (int)$totalPages ?></div>
</section>

<section class="panel-card section-card">
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead>
            <tr>
                <th style="width:70px;">ID</th>
                <th>Название</th>
                <th style="width:110px;">Тип</th>
                <th style="width:120px;">Цена</th>
                <th style="width:180px;">Создан</th>
                <th style="width:220px;" class="text-end">Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">Ничего не найдено.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e((string)$it['title']) ?></div>
                        <div class="form-hint"><?= e(mb_strimwidth((string)($it['description'] ?? ''), 0, 90, '…')) ?></div>
                    </td>
                    <td><?php if ((int)($it['is_course'] ?? 0) === 1): ?><span class="badge-soft">Курс</span><?php else: ?><span class="badge-status neutral">Товар</span><?php endif; ?></td>
                    <td><?= e((string)$it['price']) ?> ₽</td>
                    <td class="form-hint"><?= e((string)($it['created_at'] ?? '')) ?></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <a href="<?= e(url('admin/edit-item?id=' . (int)$it['id'])) ?>" class="btn btn-outline-primary rounded-4">Редактировать</a>
                            <form action="<?= e(url('admin/delete-item')) ?>" method="POST" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите удалить запись?');">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="btn btn-outline-danger rounded-4">Удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav class="mt-4">
        <ul class="pagination justify-content-center mb-0">
            <?php $prev = max(1, $page - 1); $next = min($totalPages, $page + 1); ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link rounded-start-4" href="<?= e(url('admin?' . $buildQuery(['page' => $prev]))) ?>">«</a></li>
            <?php $start = max(1, $page - 3); $end = min($totalPages, $page + 3); for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(url('admin?' . $buildQuery(['page' => $i]))) ?>"><?= (int)$i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link rounded-end-4" href="<?= e(url('admin?' . $buildQuery(['page' => $next]))) ?>">»</a></li>
        </ul>
    </nav>
</section>
