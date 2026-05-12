<?php
final class LessonModel
{
    private static function hasColumn(PDO $pdo, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute(['lessons', $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function findById(PDO $pdo, int $lessonId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM lessons WHERE id = ? LIMIT 1');
        $stmt->execute([$lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function listByCourseId(PDO $pdo, int $courseId): array
    {
        $orderBy = 'id ASC';

        if (self::hasColumn($pdo, 'lesson_order')) {
            $orderBy = 'lesson_order ASC, id ASC';
        } elseif (self::hasColumn($pdo, 'position')) {
            $orderBy = 'position ASC, id ASC';
        } elseif (self::hasColumn($pdo, 'created_at')) {
            $orderBy = 'created_at ASC, id ASC';
        }

        $sql = 'SELECT * FROM lessons WHERE course_id = ? ORDER BY ' . $orderBy;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countByCourseId(PDO $pdo, int $courseId): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
        $stmt->execute([$courseId]);
        return (int)$stmt->fetchColumn();
    }
}