<?php
final class AnalyticsModel
{
    private static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function hasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function summary(PDO $pdo): array
    {
        $summary = [];
        $summary['users_total'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $summary['courses_total'] = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_course = 1')->fetchColumn();
        $summary['orders_paid'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn();

        $summary['revenue_total'] = (float)$pdo->query(
            "SELECT COALESCE(SUM(p.price), 0)
             FROM orders o
             JOIN products p ON p.id = o.product_id
             WHERE o.status = 'paid'"
        )->fetchColumn();

        if (self::hasColumn($pdo, 'homework_submissions', 'status')) {
            $summary['pending_homeworks'] = (int)$pdo->query("SELECT COUNT(*) FROM homework_submissions WHERE status IN ('new', 'in_review')")->fetchColumn();
            $summary['accepted_homeworks'] = (int)$pdo->query("SELECT COUNT(*) FROM homework_submissions WHERE status = 'accepted'")->fetchColumn();
        } else {
            $summary['pending_homeworks'] = (int)$pdo->query("SELECT COUNT(*) FROM homework_submissions")->fetchColumn();
            $summary['accepted_homeworks'] = 0;
        }

        $summary['tests_attempts'] = self::hasTable($pdo, 'test_attempts')
            ? (int)$pdo->query('SELECT COUNT(*) FROM test_attempts')->fetchColumn()
            : 0;

        $summary['avg_progress'] = (float)$pdo->query('SELECT COALESCE(AVG(progress_percent), 0) FROM course_progress')->fetchColumn();

        return $summary;
    }

    public static function topCourses(PDO $pdo, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));

        $sql = "SELECT p.id, p.title, COUNT(*) AS sales_count, COALESCE(AVG(cp.progress_percent), 0) AS avg_progress
                FROM orders o
                JOIN products p ON p.id = o.product_id
                LEFT JOIN course_progress cp ON cp.course_id = p.id
                WHERE o.status = 'paid' AND p.is_course = 1
                GROUP BY p.id, p.title
                ORDER BY sales_count DESC, p.id DESC
                LIMIT $limit";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}