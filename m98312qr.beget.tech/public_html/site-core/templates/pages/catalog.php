<section class="hero-card">
    <div class="hero-grid">
        <div>
            <h1 class="hero-title">Каталог курсов и цифровых материалов</h1>
            <p class="hero-text">Выбирайте программы обучения, изучайте содержимое курсов и получайте доступ к урокам сразу после подтверждения оплаты.</p>
            <div class="hero-pills">
                <span class="hero-pill">Онлайн-доступ</span>
                <span class="hero-pill">Практические задания</span>
                <span class="hero-pill">Тестирование по урокам</span>
            </div>
        </div>
        <div class="hero-side">
            <div class="metric-card"><small>Формат</small><strong>Курсы и цифровые продукты</strong></div>
            <div class="metric-card"><small>Личный кабинет</small><strong>Покупки и доступы в одном месте</strong></div>
            <div class="metric-card"><small>Практика</small><strong>Загрузка и проверка работ</strong></div>
            <div class="metric-card"><small>Навигация</small><strong>Поиск по названию и типу</strong></div>
        </div>
    </div>
</section>

<section class="panel-card filter-card">
    <form action="<?= e(url()) ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-lg-7">
            <label class="form-label">Поиск по названию</label>
            <input type="text" name="q" class="form-control" placeholder="Например: маркетинг, дизайн, аналитика" value="<?= e($q) ?>">
        </div>
        <div class="col-lg-3">
            <label class="form-label">Тип материала</label>
            <select name="type" class="form-select">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Все</option>
                <option value="course" <?= $type === 'course' ? 'selected' : '' ?>>Только курсы</option>
                <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Только товары</option>
            </select>
        </div>
        <div class="col-lg-2 d-grid"><button type="submit" class="btn btn-primary btn-lg rounded-4">Найти</button></div>
    </form>
    <?php if ($q !== '' || $type !== 'all'): ?>
        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="form-hint">Найдено записей: <?= (int)$foundCount ?></div>
            <a href="<?= e(url()) ?>" class="top-link">Сбросить фильтры</a>
        </div>
    <?php endif; ?>
</section>

<section class="catalog-grid">
    <?php if (empty($products)): ?>
        <div class="panel-card section-card">
            <h3 class="page-title h4">Ничего не найдено</h3>
            <p class="page-subtitle">Попробуйте изменить поисковый запрос или выбрать другой тип материалов.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($products as $product): ?>
        <article class="panel-card catalog-card">
            <?php $img = !empty($product['image_url']) ? $product['image_url'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80'; ?>
            <div class="card-media"><img src="<?= e($img) ?>" alt="<?= e((string)$product['title']) ?>"></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <h2 class="card-title"><?= e((string)$product['title']) ?></h2>
                    <?php if ((int)($product['is_course'] ?? 0) === 1): ?><span class="badge-soft">Курс</span><?php else: ?><span class="badge-status neutral">Товар</span><?php endif; ?>
                </div>
                <p class="card-text"><?= e(mb_strimwidth((string)($product['description'] ?? ''), 0, 160, '…')) ?></p>
                <div class="price-row"><div class="price-tag"><?= e((string)$product['price']) ?> ₽</div></div>
            </div>
            <div class="card-footer-actions">
                <?php if ((int)($product['is_course'] ?? 0) === 1): ?>
                    <a href="<?= e(url('course?id=' . (int)$product['id'])) ?>" class="btn btn-outline-primary w-100 rounded-4">Подробнее о курсе</a>
                <?php else: ?>
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <form method="POST" action="<?= e(url('make-order')) ?>" class="w-100">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <button class="btn btn-primary w-100 rounded-4">Купить товар</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= e(url('login')) ?>" class="btn btn-outline-primary w-100 rounded-4">Войти для покупки</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
