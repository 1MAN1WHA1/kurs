<?php
require_once __DIR__ . '/../../bootstrap.php';

$pageTitle = 'Главная';

// Логика каталога (SQL/фильтры)
require SITE_CORE_ROOT . '/actions/catalog.php';

// Шаблоны
require SITE_CORE_ROOT . '/templates/layout/header.php';
require SITE_CORE_ROOT . '/templates/pages/catalog.php';
require SITE_CORE_ROOT . '/templates/layout/footer.php';
