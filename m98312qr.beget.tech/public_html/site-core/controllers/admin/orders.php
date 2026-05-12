<?php
require_once __DIR__ . '/../../bootstrap.php';

require_admin();

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? 'all');

$allowedStatuses = ['all', 'new', 'paid'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

function orderStatusLabel(string $status): string
{
    if ($status === 'paid') return 'Оплачен';
    if ($status === 'new') return 'Новый';
    return $status;
}

function orderStatusClass(string $status): string
{
    if ($status === 'paid') return 'success';
    if ($status === 'new') return 'warning';
    return 'neutral';
}

$selectPayment = db_has_column($pdo, 'orders', 'payment_method')
    ? 'orders.payment_method'
    : "'' AS payment_method";

$sql = "
    SELECT
        orders.id AS order_id,
        orders.created_at,
        orders.status,
        {$selectPayment},
        users.email,
        products.title,
        products.price,
        products.is_course
    FROM orders
    JOIN users ON users.id = orders.user_id
    JOIN products ON products.id = orders.product_id
";

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = 'orders.status = ?';
    $params[] = $status;
}

if ($q !== '') {
    $where[] = '(users.email LIKE ? OR products.title LIKE ? OR orders.id = ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = (int)$q;
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY orders.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalPaid = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn();
$totalNew = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();

$revenueStmt = $pdo->query("
    SELECT COALESCE(SUM(products.price), 0)
    FROM orders
    JOIN products ON products.id = orders.product_id
    WHERE orders.status = 'paid'
");
$totalRevenue = (float)$revenueStmt->fetchColumn();

$pageTitle = 'Все заказы';

require SITE_CORE_ROOT . '/templates/layout/header.php';
?>

<div class="container py-4 admin-orders-page">
    <section class="hero-card mb-4">
        <div class="page-header mb-0">
            <div>
                <div class="eyebrow">Администрирование</div>
                <h1 class="page-title">Все заказы</h1>
                <p class="page-subtitle">
                    Контроль оформленных заказов, статусов оплаты и общей активности по платформе.
                </p>
            </div>
            <div class="nav-actions">
                <a href="/admin" class="btn-soft">Назад в админку</a>
            </div>
        </div>
    </section>

    <section class="stats-grid mb-4">
        <div class="stat-box">
            <small>Всего заказов</small>
            <strong><?= (int)$totalOrders ?></strong>
            <span>Все записи в системе</span>
        </div>
        <div class="stat-box">
            <small>Оплаченные</small>
            <strong><?= (int)$totalPaid ?></strong>
            <span>Заказы со статусом paid</span>
        </div>
        <div class="stat-box">
            <small>Новые</small>
            <strong><?= (int)$totalNew ?></strong>
            <span>Ожидают оплаты</span>
        </div>
        <div class="stat-box">
            <small>Выручка</small>
            <strong><?= number_format($totalRevenue, 0, '', ' ') ?> ₽</strong>
            <span>По оплаченных заказам</span>
        </div>
    </section>

    <section class="panel-card order-filter-card mb-4">
        <form method="GET" action="/admin/orders" class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label">Поиск</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="По email, названию курса или ID заказа"
                    value="<?= e($q) ?>"
                >
            </div>
            <div class="col-lg-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Новые</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Оплаченные</option>
                </select>
            </div>
            <div class="col-lg-2 d-grid">
                <button class="btn btn-primary rounded-4">Показать</button>
            </div>
            <div class="col-lg-2 d-grid">
                <a href="/admin/orders" class="btn btn-outline-secondary rounded-4">Сбросить</a>
            </div>
        </form>
    </section>

    <section class="panel-card table-card">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <h3 class="h5 mb-2">Заказы не найдены</h3>
                <p class="mb-0">Попробуй изменить параметры поиска или выбрать другой статус.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 90px;">ID</th>
                            <th>Клиент</th>
                            <th>Позиция</th>
                            <th style="width: 130px;">Тип</th>
                            <th style="width: 130px;">Цена</th>
                            <th style="width: 140px;">Статус</th>
                            <th style="width: 180px;">Оплата</th>
                            <th style="width: 180px;">Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="fw-bold">#<?= (int)$it['order_id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string)$it['email']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e((string)$it['title']) ?></div>
                            </td>
                            <td>
                                <?php if ((int)($it['is_course'] ?? 0) === 1): ?>
                                    <span class="badge-soft">Курс</span>
                                <?php else: ?>
                                    <span class="badge-status neutral">Товар</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= number_format((float)$it['price'], 0, '', ' ') ?> ₽</td>
                            <td>
                                <span class="order-status-chip <?= e(orderStatusClass((string)$it['status'])) ?>">
                                    <?= e(orderStatusLabel((string)$it['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($it['payment_method'])): ?>
                                    <?= e((string)$it['payment_method']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e((string)$it['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require SITE_CORE_ROOT . '/templates/layout/footer.php'; ?>