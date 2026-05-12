<?php
final class ProductModel
{
    public static function searchCatalog(PDO $pdo, string $q, string $type): array
    {
        $sql = 'SELECT * FROM products';
        $params = [];
        $where = [];
        if ($q !== '') {
            $where[] = 'title LIKE ?';
            $params[] = '%' . $q . '%';
        }
        if ($type === 'course') {
            $where[] = 'is_course = 1';
        } elseif ($type === 'product') {
            $where[] = 'is_course = 0';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findCourseById(PDO $pdo, int $courseId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_course = 1 LIMIT 1');
        $stmt->execute([$courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findById(PDO $pdo, int $productId): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function recommendationsForUser(PDO $pdo, int $userId, int $limit = 3): array
    {
        $paidStmt = $pdo->prepare(
            "SELECT p.id, p.title, p.description
             FROM orders o
             JOIN products p ON p.id = o.product_id
             WHERE o.user_id = ? AND o.status = 'paid' AND p.is_course = 1"
        );
        $paidStmt->execute([$userId]);
        $paidCourses = $paidStmt->fetchAll(PDO::FETCH_ASSOC);

        $excludeIds = array_map(static fn(array $row): int => (int)$row['id'], $paidCourses);
        $courses = self::allCourses($pdo);

        if (!$paidCourses) {
            return array_slice($courses, 0, $limit);
        }

        $keywords = [];
        foreach ($paidCourses as $course) {
            $keywords = array_merge($keywords, self::keywords((string)$course['title'] . ' ' . (string)($course['description'] ?? '')));
        }
        $keywords = array_unique($keywords);

        $scored = [];
        foreach ($courses as $course) {
            $id = (int)$course['id'];
            if (in_array($id, $excludeIds, true)) {
                continue;
            }
            $hay = mb_strtolower((string)$course['title'] . ' ' . (string)($course['description'] ?? ''));
            $score = 0;
            foreach ($keywords as $word) {
                if ($word !== '' && mb_strpos($hay, $word) !== false) {
                    $score++;
                }
            }
            if ($score === 0) {
                $score = max(0, 5 - abs($id - ($excludeIds[0] ?? $id)));
            }
            $course['_score'] = $score;
            $scored[] = $course;
        }

        usort($scored, static function(array $a, array $b): int {
            return ($b['_score'] <=> $a['_score']) ?: ((int)$b['id'] <=> (int)$a['id']);
        });

        return array_slice($scored, 0, $limit);
    }

    public static function recommendationsForCourse(PDO $pdo, int $courseId, int $userId = 0, int $limit = 3): array
    {
        $current = self::findCourseById($pdo, $courseId);
        if (!$current) {
            return [];
        }
        $exclude = [$courseId];
        if ($userId > 0) {
            $stmt = $pdo->prepare("SELECT product_id FROM orders WHERE user_id = ? AND status = 'paid'");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
                $exclude[] = (int)$pid;
            }
        }
        $keywords = self::keywords((string)$current['title'] . ' ' . (string)($current['description'] ?? ''));
        $scored = [];
        foreach (self::allCourses($pdo) as $course) {
            $id = (int)$course['id'];
            if (in_array($id, $exclude, true)) {
                continue;
            }
            $hay = mb_strtolower((string)$course['title'] . ' ' . (string)($course['description'] ?? ''));
            $score = 0;
            foreach ($keywords as $word) {
                if ($word !== '' && mb_strpos($hay, $word) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $course['_score'] = $score;
                $scored[] = $course;
            }
        }
        usort($scored, static fn(array $a, array $b): int => ($b['_score'] <=> $a['_score']) ?: ((int)$b['id'] <=> (int)$a['id']));
        if (!$scored) {
            $all = array_filter(self::allCourses($pdo), static fn(array $row): bool => (int)$row['id'] !== $courseId);
            return array_slice(array_values($all), 0, $limit);
        }
        return array_slice($scored, 0, $limit);
    }

    private static function allCourses(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM products WHERE is_course = 1 ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function keywords(string $text): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?: '';
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = ['курс','урок','для','как','это','или','все','что','при','под','над','без','and','the','with','она','его','её'];
        return array_values(array_filter(array_unique($words), static function(string $word) use ($stop): bool {
            return mb_strlen($word) >= 4 && !in_array($word, $stop, true);
        }));
    }
}
