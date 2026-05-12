<?php
// сюда попадаем из index.php, dp.php уже подключен и $pdo есть

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
if (!in_array($type, ['all', 'course', 'product'], true)) {
    $type = 'all';
}

$sql = "SELECT * FROM products";
$params = [];
$where = [];

// Поиск по названию
if ($q !== '') {
    $where[] = "title LIKE ?";
    $params[] = "%" . $q . "%";
}

// Фильтр по типу
if ($type === 'course') {
    $where[] = "is_course = 1";
} elseif ($type === 'product') {
    $where[] = "is_course = 0";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$foundCount = count($products);
