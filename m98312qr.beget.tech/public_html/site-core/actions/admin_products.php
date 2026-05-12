<?php
// сюда попадаем из admin_panel.php
// check_admin.php уже подключил dp.php, значит есть $pdo и сессия

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
if (!in_array($type, ['all', 'course', 'product'], true)) $type = 'all';

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// WHERE + params
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "title LIKE ?";
    $params[] = "%{$q}%";
}
if ($type === 'course') $where[] = "is_course = 1";
if ($type === 'product') $where[] = "is_course = 0";

$whereSql = $where ? (" WHERE " . implode(" AND ", $where)) : "";

// COUNT
$countSql = "SELECT COUNT(*) FROM products" . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) $page = $totalPages;

// SELECT
$listSql = "SELECT * FROM products" . $whereSql . " ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$items = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// Сообщения
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Функция сборки query для пагинации
$buildQuery = function(array $overrides = []) {
    $base = [
        'q' => $_GET['q'] ?? '',
        'type' => $_GET['type'] ?? 'all',
        'page' => $_GET['page'] ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return http_build_query($merged);
};
