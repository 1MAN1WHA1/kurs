<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../models/AnalyticsModel.php';
require_once __DIR__ . '/../../models/HomeworkModel.php';
require_admin();

$pageTitle = 'Админка';
require SITE_CORE_ROOT . '/actions/admin_products.php';
$analytics = AnalyticsModel::summary($pdo);
$topCourses = AnalyticsModel::topCourses($pdo, 5);
$pendingHomeworkCount = HomeworkModel::countPending($pdo);

require SITE_CORE_ROOT . '/templates/layout/header.php';
require SITE_CORE_ROOT . '/templates/pages/admin_panel.php';
require SITE_CORE_ROOT . '/templates/layout/footer.php';
