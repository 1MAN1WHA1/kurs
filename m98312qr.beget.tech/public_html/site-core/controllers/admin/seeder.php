<?php
require_once __DIR__ . '/../../bootstrap.php';
require_admin();
$message = '';
function exportProductsToCsv(PDO $pdo): string {
    $exportDir = PUBLIC_ROOT . '/exports/'; if (!is_dir($exportDir) && !mkdir($exportDir, 0755, true)) return 'Не удалось создать папку exports.';
    $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv'; $fullPath = $exportDir . $filename;
    $stmt = $pdo->query('SELECT * FROM products ORDER BY id ASC'); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fp = fopen($fullPath, 'w'); if (!$fp) return 'Не удалось создать CSV файл.';
    if (empty($rows)) { fputcsv($fp, ['empty']); fclose($fp); return "Таблица products пустая. Бэкап создан: exports/{$filename}"; }
    fputcsv($fp, array_keys($rows[0])); foreach ($rows as $row) fputcsv($fp, $row); fclose($fp); return "Бэкап сохранён: exports/{$filename}";
}
function seedProducts(PDO $pdo, int $count): string {
    if ($count < 1) return 'Количество должно быть больше нуля.';
    $tplStmt = $pdo->query('SELECT * FROM products ORDER BY RAND() LIMIT 1'); $tpl = $tplStmt->fetch(PDO::FETCH_ASSOC); if (!$tpl) return 'В products нет ни одной записи.';
    $ins = $pdo->prepare('INSERT INTO products (title, description, price, image_url, is_course) VALUES (?, ?, ?, ?, ?)'); $inserted = 0;
    for ($i = 0; $i < $count; $i++) { $suffix = ' #' . date('His') . '_' . bin2hex(random_bytes(2)); $title = (string)$tpl['title'] . $suffix; $price = (float)$tpl['price']; $delta = random_int(-15, 15) / 100; $newPrice = round($price * (1 + $delta), 2); try { $ins->execute([$title, (string)($tpl['description'] ?? ''), $newPrice, (string)($tpl['image_url'] ?? ''), (int)($tpl['is_course'] ?? 0)]); $inserted++; } catch (Throwable $e) { continue; } }
    return "Сгенерировано записей: {$inserted} из {$count}.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') { if (!csrf_check($_POST['csrf_token'] ?? null)) $message = 'Ошибка безопасности: неверный CSRF-токен.'; else { $count = (int)($_POST['count'] ?? 0); $message = exportProductsToCsv($pdo) . '<br>' . seedProducts($pdo, $count); } }
?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Seeder + CSV</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/app.css" rel="stylesheet"></head>
<body class="app-body"><div class="app-shell"><nav class="app-nav"><div class="brand-wrap"><div class="brand-logo">⚙</div><div><p class="brand-title mb-0">Тестовые данные и резервная копия</p><p class="brand-subtitle">наполнение каталога и экспорт products в CSV</p></div></div><div class="nav-actions"><a href="/admin" class="btn-soft">Назад в админку</a></div></nav>
<section class="panel-card page-card" style="max-width:760px;margin:0 auto;">
<div class="page-header"><div><h1 class="page-title">Seeder + CSV backup</h1><p class="page-subtitle">Сначала создаётся CSV-копия таблицы products, затем система генерирует новые записи по шаблону.</p></div></div>
<?php if ($message !== ''): ?><div class="alert alert-info rounded-4"><?= $message ?></div><?php endif; ?>
<form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="col-12"><label class="form-label">Количество новых записей</label><input type="number" name="count" class="form-control" value="50" min="1" max="1000" required></div>
<div class="col-12"><div class="alert alert-warning rounded-4 mb-0">Инструмент нужен для демонстрации каталога на защите: он помогает быстро наполнить систему и одновременно сохранить резервную копию текущих записей.</div></div>
<div class="col-12 d-grid"><button class="btn btn-success rounded-4 py-3">Выполнить экспорт и генерацию</button></div>
</form></section></div></body></html>
