<?php
final class HomeworkModel
{
    private static function hasColumn(PDO $pdo, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute(['homework_submissions', $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function latestForUserLesson(PDO $pdo, int $userId, int $lessonId): ?array
    {
        $fields = [
            'id',
            'file_path',
            'original_name',
            'mime_type',
            'created_at'
        ];

        $fields[] = self::hasColumn($pdo, 'status') ? 'status' : "'new' AS status";
        $fields[] = self::hasColumn($pdo, 'teacher_comment') ? 'teacher_comment' : 'NULL AS teacher_comment';
        $fields[] = self::hasColumn($pdo, 'checked_at') ? 'checked_at' : 'NULL AS checked_at';

        $stmt = $pdo->prepare(
            'SELECT ' . implode(', ', $fields) . '
             FROM homework_submissions
             WHERE user_id = ? AND lesson_id = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId, $lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function completedLessonIdsForCourse(PDO $pdo, int $userId, int $courseId): array
    {
        if (self::hasColumn($pdo, 'status')) {
            $stmt = $pdo->prepare(
                "SELECT DISTINCT l.id
                 FROM lessons l
                 JOIN homework_submissions hs ON hs.lesson_id = l.id
                 WHERE l.course_id = ?
                   AND hs.user_id = ?
                   AND hs.status = 'accepted'"
            );
            $stmt->execute([$courseId, $userId]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT DISTINCT l.id
                 FROM lessons l
                 JOIN homework_submissions hs ON hs.lesson_id = l.id
                 WHERE l.course_id = ?
                   AND hs.user_id = ?"
            );
            $stmt->execute([$courseId, $userId]);
        }

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function countPending(PDO $pdo): int
    {
        if (self::hasColumn($pdo, 'status')) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM homework_submissions WHERE status IN ('new', 'in_review')");
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM homework_submissions");
        }
        return (int)$stmt->fetchColumn();
    }

    public static function listForAdmin(PDO $pdo, string $status = 'all'): array
    {
        $fields = [
            'hs.id',
            'hs.original_name',
            'hs.created_at',
            'u.email',
            'l.title AS lesson_title',
            'p.title AS course_title',
            'hs.user_id',
            'hs.lesson_id'
        ];

        $fields[] = self::hasColumn($pdo, 'status') ? 'hs.status' : "'new' AS status";
        $fields[] = self::hasColumn($pdo, 'teacher_comment') ? 'hs.teacher_comment' : 'NULL AS teacher_comment';
        $fields[] = self::hasColumn($pdo, 'checked_at') ? 'hs.checked_at' : 'NULL AS checked_at';

        $sql = 'SELECT ' . implode(', ', $fields) . '
                FROM homework_submissions hs
                JOIN users u ON u.id = hs.user_id
                JOIN lessons l ON l.id = hs.lesson_id
                JOIN products p ON p.id = l.course_id';

        $params = [];

        if ($status !== 'all' && self::hasColumn($pdo, 'status')) {
            $sql .= ' WHERE hs.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY hs.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findForAdmin(PDO $pdo, int $submissionId): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT hs.id, hs.user_id, hs.lesson_id, l.course_id
             FROM homework_submissions hs
             JOIN lessons l ON l.id = hs.lesson_id
             WHERE hs.id = ?
             LIMIT 1"
        );
        $stmt->execute([$submissionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}