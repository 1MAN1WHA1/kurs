<?php

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return '/' . $path;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function ensure_csrf(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_token(): string
{
    ensure_csrf();
    return (string)$_SESSION['csrf_token'];
}

function csrf_check(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals((string)$_SESSION['csrf_token'], $token);
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect(url('login'));
    }
}

function require_admin(): void
{
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('403 Forbidden');
    }
}

function starts_with(string $haystack, string $needle): bool
{
    return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
}

function route_query(array $params): string
{
    return http_build_query($params);
}

function db_has_table(PDO $pdo, string $table): bool
{
    static $cache = [];
    $key = 'table:' . $table;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
}

function db_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
}

function recalc_course_progress(PDO $pdo, int $userId, int $courseId): int
{
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
    $totalStmt->execute([$courseId]);
    $totalLessons = (int)$totalStmt->fetchColumn();

    $doneLessons = 0;
    if ($totalLessons > 0) {
        $doneByHomework = 0;
        if (db_has_table($pdo, 'homework_submissions')) {
            if (db_has_column($pdo, 'homework_submissions', 'status')) {
                $stmt = $pdo->prepare(
                    'SELECT COUNT(DISTINCT l.id)
                     FROM lessons l
                     JOIN homework_submissions hs ON hs.lesson_id = l.id AND hs.user_id = ? AND hs.status = ?
                     WHERE l.course_id = ?'
                );
                $stmt->execute([$userId, 'accepted', $courseId]);
            } else {
                $stmt = $pdo->prepare(
                    'SELECT COUNT(DISTINCT l.id)
                     FROM lessons l
                     JOIN homework_submissions hs ON hs.lesson_id = l.id AND hs.user_id = ?
                     WHERE l.course_id = ?'
                );
                $stmt->execute([$userId, $courseId]);
            }
            $doneByHomework = (int)$stmt->fetchColumn();
        }

        $doneByTests = 0;
        if (db_has_table($pdo, 'lesson_tests') && db_has_table($pdo, 'test_attempts')) {
            $passedCol = db_has_column($pdo, 'test_attempts', 'is_passed') ? 'is_passed' : (db_has_column($pdo, 'test_attempts', 'passed') ? 'passed' : '');
            if ($passedCol !== '') {
                $stmt = $pdo->prepare(
                    'SELECT COUNT(DISTINCT lt.lesson_id)
                     FROM lesson_tests lt
                     JOIN test_attempts ta ON ta.test_id = lt.id
                     WHERE lt.lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)
                       AND ta.user_id = ?
                       AND ta.' . $passedCol . ' = 1'
                );
                $stmt->execute([$courseId, $userId]);
                $doneByTests = (int)$stmt->fetchColumn();
            }
        }

        $doneLessons = max($doneByHomework, $doneByTests);
        if ($doneLessons > $totalLessons) {
            $doneLessons = $totalLessons;
        }
    }

    $progress = $totalLessons > 0 ? (int)floor(($doneLessons / $totalLessons) * 100) : 0;
    if ($progress > 100) {
        $progress = 100;
    }

    $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM course_progress WHERE user_id = ? AND course_id = ?');
    $existsStmt->execute([$userId, $courseId]);
    $exists = (int)$existsStmt->fetchColumn() > 0;

    $hasCompleted = db_has_column($pdo, 'course_progress', 'completed_lessons');
    $hasTotal = db_has_column($pdo, 'course_progress', 'total_lessons');
    $hasUpdated = db_has_column($pdo, 'course_progress', 'updated_at');

    if ($exists) {
        $parts = ['progress_percent = ?'];
        $params = [$progress];
        if ($hasCompleted) {
            $parts[] = 'completed_lessons = ?';
            $params[] = $doneLessons;
        }
        if ($hasTotal) {
            $parts[] = 'total_lessons = ?';
            $params[] = $totalLessons;
        }
        if ($hasUpdated) {
            $parts[] = 'updated_at = CURRENT_TIMESTAMP';
        }
        $params[] = $userId;
        $params[] = $courseId;
        $stmt = $pdo->prepare('UPDATE course_progress SET ' . implode(', ', $parts) . ' WHERE user_id = ? AND course_id = ?');
        $stmt->execute($params);
    } else {
        $columns = ['user_id', 'course_id', 'progress_percent'];
        $placeholders = ['?', '?', '?'];
        $params = [$userId, $courseId, $progress];
        if ($hasCompleted) {
            $columns[] = 'completed_lessons';
            $placeholders[] = '?';
            $params[] = $doneLessons;
        }
        if ($hasTotal) {
            $columns[] = 'total_lessons';
            $placeholders[] = '?';
            $params[] = $totalLessons;
        }
        $stmt = $pdo->prepare('INSERT INTO course_progress (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($params);
    }

    return $progress;
}