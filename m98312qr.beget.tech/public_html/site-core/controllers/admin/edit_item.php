<?php
require_once __DIR__ . '/../../bootstrap.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(url('admin?err=not_found'));
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) redirect(url('admin?err=not_found'));
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $errors[] = 'CSRF токен неверный.';
    } else {
        $title = trim((string)($_POST['title'] ?? ''));
        $shortDescription = trim((string)($_POST['short_description'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = trim((string)($_POST['price'] ?? ''));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));
        $isCourse = isset($_POST['is_course']) ? 1 : 0;
        $level = trim((string)($_POST['level'] ?? ''));
        $durationText = trim((string)($_POST['duration_text'] ?? ''));
        $targetAudience = trim((string)($_POST['target_audience'] ?? ''));
        $learningOutcomes = trim((string)($_POST['learning_outcomes'] ?? ''));
        $programText = trim((string)($_POST['program_text'] ?? ''));
        $requirementsText = trim((string)($_POST['requirements_text'] ?? ''));
        if ($title === '') $errors[] = 'Название не может быть пустым.';
        if ($price === '' || !is_numeric($price) || (float)$price < 0) $errors[] = 'Цена должна быть неотрицательным числом.';
        if (!$errors) {
            $upd = $pdo->prepare('UPDATE products SET title = ?, short_description = ?, description = ?, price = ?, image_url = ?, is_course = ?, level = ?, duration_text = ?, target_audience = ?, learning_outcomes = ?, program_text = ?, requirements_text = ? WHERE id = ?');
            $upd->execute([$title, $shortDescription !== '' ? $shortDescription : null, $description, $price, $imageUrl !== '' ? $imageUrl : null, $isCourse, $level !== '' ? $level : null, $durationText !== '' ? $durationText : null, $targetAudience !== '' ? $targetAudience : null, $learningOutcomes !== '' ? $learningOutcomes : null, $programText !== '' ? $programText : null, $requirementsText !== '' ? $requirementsText : null, $id]);
            redirect(url('admin?msg=updated'));
        }
        $product['title'] = $title;
        $product['short_description'] = $shortDescription;
        $product['description'] = $description;
        $product['price'] = $price;
        $product['image_url'] = $imageUrl;
        $product['is_course'] = $isCourse;
        $product['level'] = $level;
        $product['duration_text'] = $durationText;
        $product['target_audience'] = $targetAudience;
        $product['learning_outcomes'] = $learningOutcomes;
        $product['program_text'] = $programText;
        $product['requirements_text'] = $requirementsText;
    }
}
$img = !empty($product['image_url']) ? (string)$product['image_url'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80';
?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Редактирование записи</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/app.css" rel="stylesheet"></head>
<body class="app-body"><div class="app-shell"><nav class="app-nav"><div class="brand-wrap"><div class="brand-logo">✎</div><div><p class="brand-title mb-0">Редактирование записи</p><p class="brand-subtitle">обновление параметров товара или курса</p></div></div><div class="nav-actions"><a href="<?= e(url('admin')) ?>" class="btn-soft">Назад в админку</a></div></nav>
<section class="panel-card page-card">
<div class="page-header"><div><h1 class="page-title">Редактирование #<?= (int)$product['id'] ?></h1><p class="page-subtitle">Изменения сразу отображаются в каталоге и на странице курса.</p></div></div>
<?php if ($errors): ?><div class="alert alert-danger rounded-4"><?php foreach ($errors as $eMsg): ?><div><?= e($eMsg) ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="row g-4 align-items-start">
<div class="col-lg-4"><img src="<?= e($img) ?>" class="img-fluid rounded-5 shadow-sm" alt="preview"></div>
<div class="col-lg-8"><form method="POST" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="col-12"><label class="form-label">Название</label><input type="text" name="title" class="form-control" value="<?= e((string)$product['title']) ?>" required></div>
<div class="col-12"><label class="form-label">Краткое описание</label><input type="text" name="short_description" class="form-control" value="<?= e((string)($product['short_description'] ?? '')) ?>"></div>
<div class="col-12"><label class="form-label">Описание</label><textarea name="description" class="form-control" rows="5"><?= e((string)($product['description'] ?? '')) ?></textarea></div>
<div class="col-md-6"><label class="form-label">Цена</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= e((string)$product['price']) ?>" required></div>
<div class="col-md-6"><label class="form-label">Ссылка на изображение</label><input type="text" name="image_url" class="form-control" value="<?= e((string)($product['image_url'] ?? '')) ?>"></div>
<div class="col-12"><label class="border rounded-4 p-3 d-flex align-items-center gap-2"><input class="form-check-input" type="checkbox" name="is_course" <?= ((int)($product['is_course'] ?? 0) === 1) ? 'checked' : '' ?>> Это курс</label></div>
<div class="col-md-4"><label class="form-label">Уровень</label><input type="text" name="level" class="form-control" value="<?= e((string)($product['level'] ?? '')) ?>"></div>
<div class="col-md-4"><label class="form-label">Длительность</label><input type="text" name="duration_text" class="form-control" value="<?= e((string)($product['duration_text'] ?? '')) ?>"></div>
<div class="col-md-4"><label class="form-label">Для кого</label><input type="text" name="target_audience" class="form-control" value="<?= e((string)($product['target_audience'] ?? '')) ?>"></div>
<div class="col-12"><label class="form-label">Результаты обучения</label><textarea name="learning_outcomes" class="form-control" rows="4"><?= e((string)($product['learning_outcomes'] ?? '')) ?></textarea></div>
<div class="col-12"><label class="form-label">Программа курса</label><textarea name="program_text" class="form-control" rows="4"><?= e((string)($product['program_text'] ?? '')) ?></textarea></div>
<div class="col-12"><label class="form-label">Требования</label><textarea name="requirements_text" class="form-control" rows="3"><?= e((string)($product['requirements_text'] ?? '')) ?></textarea></div>
<div class="col-12 d-grid"><button class="btn btn-primary rounded-4 py-3">Сохранить изменения</button></div>
</form></div></div></section></div></body></html>
