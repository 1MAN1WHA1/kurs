<?php
final class TestModel
{
    private static function ready(PDO $pdo): bool
    {
        return db_has_table($pdo, 'lesson_tests') && db_has_table($pdo, 'test_questions') && db_has_table($pdo, 'test_answers') && db_has_table($pdo, 'test_attempts');
    }

    private static function passingExpr(PDO $pdo): string
    {
        if (db_has_column($pdo, 'lesson_tests', 'pass_score')) {
            return 'COALESCE(pass_score, 70) AS passing_score';
        }
        if (db_has_column($pdo, 'lesson_tests', 'passing_score')) {
            return 'COALESCE(passing_score, 70) AS passing_score';
        }
        return '70 AS passing_score';
    }

    private static function orderColumn(PDO $pdo, string $table): string
    {
        if (db_has_column($pdo, $table, 'position')) {
            return 'position';
        }
        if (db_has_column($pdo, $table, 'question_order')) {
            return 'question_order';
        }
        if (db_has_column($pdo, $table, 'answer_order')) {
            return 'answer_order';
        }
        return 'id';
    }

    private static function passedColumn(PDO $pdo): string
    {
        if (db_has_column($pdo, 'test_attempts', 'is_passed')) {
            return 'is_passed';
        }
        if (db_has_column($pdo, 'test_attempts', 'passed')) {
            return 'passed';
        }
        return '';
    }

    private static function scoreColumn(PDO $pdo): string
    {
        if (db_has_column($pdo, 'test_attempts', 'score_percent')) {
            return 'score_percent';
        }
        if (db_has_column($pdo, 'test_attempts', 'score')) {
            return 'score';
        }
        return '';
    }

    public static function findByLessonId(PDO $pdo, int $lessonId): ?array
    {
        if (!db_has_table($pdo, 'lesson_tests')) {
            return null;
        }
        $whereActive = db_has_column($pdo, 'lesson_tests', 'is_active') ? ' AND is_active = 1' : '';
        $stmt = $pdo->prepare('SELECT *, ' . self::passingExpr($pdo) . ' FROM lesson_tests WHERE lesson_id = ?' . $whereActive . ' ORDER BY id DESC LIMIT 1');
        $stmt->execute([$lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mapByLessonIds(PDO $pdo, array $lessonIds): array
    {
        if (!db_has_table($pdo, 'lesson_tests')) {
            return [];
        }
        $lessonIds = array_values(array_filter(array_map('intval', $lessonIds)));
        if (!$lessonIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
        $whereActive = db_has_column($pdo, 'lesson_tests', 'is_active') ? ' AND is_active = 1' : '';
        $stmt = $pdo->prepare('SELECT id, lesson_id, title, ' . self::passingExpr($pdo) . ' FROM lesson_tests WHERE lesson_id IN (' . $placeholders . ')' . $whereActive);
        $stmt->execute($lessonIds);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['lesson_id']] = $row;
        }
        return $map;
    }

    public static function questionsWithAnswers(PDO $pdo, int $testId): array
    {
        if (!self::ready($pdo)) {
            return [];
        }
        $orderCol = self::orderColumn($pdo, 'test_questions');
        $qStmt = $pdo->prepare('SELECT id, question_text FROM test_questions WHERE test_id = ? ORDER BY ' . $orderCol . ' ASC, id ASC');
        $qStmt->execute([$testId]);
        $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$questions) {
            return [];
        }
        $qIds = array_map(static fn(array $row): int => (int)$row['id'], $questions);
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $answerOrder = self::orderColumn($pdo, 'test_answers');
        $aStmt = $pdo->prepare('SELECT id, question_id, answer_text FROM test_answers WHERE question_id IN (' . $placeholders . ') ORDER BY ' . $answerOrder . ' ASC, id ASC');
        $aStmt->execute($qIds);
        $answers = [];
        foreach ($aStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $answers[(int)$row['question_id']][] = $row;
        }
        foreach ($questions as &$question) {
            $question['answers'] = $answers[(int)$question['id']] ?? [];
        }
        unset($question);
        return $questions;
    }

    public static function evaluate(PDO $pdo, int $testId, array $submitted): array
    {
        if (!self::ready($pdo)) {
            return ['score' => 0, 'passed' => false, 'correct_answers' => 0, 'total_questions' => 0];
        }
        $qStmt = $pdo->prepare('SELECT id FROM test_questions WHERE test_id = ?');
        $qStmt->execute([$testId]);
        $questionIds = array_map('intval', $qStmt->fetchAll(PDO::FETCH_COLUMN));
        if (!$questionIds) {
            return ['score' => 0, 'passed' => false, 'correct_answers' => 0, 'total_questions' => 0];
        }
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $aStmt = $pdo->prepare('SELECT id, question_id FROM test_answers WHERE question_id IN (' . $placeholders . ') AND is_correct = 1');
        $aStmt->execute($questionIds);
        $correctMap = [];
        foreach ($aStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $correctMap[(int)$row['question_id']] = (int)$row['id'];
        }
        $right = 0;
        foreach ($questionIds as $qid) {
            $answerId = isset($submitted[$qid]) ? (int)$submitted[$qid] : 0;
            if ($answerId > 0 && isset($correctMap[$qid]) && $correctMap[$qid] === $answerId) {
                $right++;
            }
        }
        $totalQuestions = count($questionIds);
        $score = (int)round(($right / $totalQuestions) * 100);
        $test = self::findById($pdo, $testId);
        $passingScore = (int)($test['passing_score'] ?? 70);
        return ['score' => $score, 'passed' => $score >= $passingScore, 'correct_answers' => $right, 'total_questions' => $totalQuestions];
    }

    public static function saveAttempt(PDO $pdo, int $testId, int $lessonId, int $userId, int $score, bool $passed, int $correctAnswers, int $totalQuestions): void
    {
        if (!db_has_table($pdo, 'test_attempts')) {
            return;
        }
        $columns = ['user_id', 'test_id'];
        $placeholders = ['?', '?'];
        $params = [$userId, $testId];
        if (db_has_column($pdo, 'test_attempts', 'lesson_id')) {
            $columns[] = 'lesson_id';
            $placeholders[] = '?';
            $params[] = $lessonId;
        }
        $scoreCol = self::scoreColumn($pdo);
        if ($scoreCol !== '') {
            $columns[] = $scoreCol;
            $placeholders[] = '?';
            $params[] = $score;
        }
        if (db_has_column($pdo, 'test_attempts', 'correct_answers')) {
            $columns[] = 'correct_answers';
            $placeholders[] = '?';
            $params[] = $correctAnswers;
        }
        if (db_has_column($pdo, 'test_attempts', 'total_questions')) {
            $columns[] = 'total_questions';
            $placeholders[] = '?';
            $params[] = $totalQuestions;
        }
        $passedCol = self::passedColumn($pdo);
        if ($passedCol !== '') {
            $columns[] = $passedCol;
            $placeholders[] = '?';
            $params[] = $passed ? 1 : 0;
        }
        $stmt = $pdo->prepare('INSERT INTO test_attempts (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($params);
    }

    public static function lastAttempt(PDO $pdo, int $testId, int $userId): ?array
    {
        if (!db_has_table($pdo, 'test_attempts')) {
            return null;
        }
        $scoreCol = self::scoreColumn($pdo);
        $passedCol = self::passedColumn($pdo);
        $select = [];
        $select[] = $scoreCol !== '' ? $scoreCol . ' AS score_percent' : '0 AS score_percent';
        $select[] = $passedCol !== '' ? $passedCol . ' AS is_passed' : '0 AS is_passed';
        if (db_has_column($pdo, 'test_attempts', 'created_at')) {
            $select[] = 'created_at';
        }
        $stmt = $pdo->prepare('SELECT ' . implode(', ', $select) . ' FROM test_attempts WHERE test_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$testId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row) {
            $row['passed'] = (int)$row['is_passed'];
        }
        return $row;
    }

    public static function passedForLesson(PDO $pdo, int $lessonId, int $userId): bool
    {
        if (!db_has_table($pdo, 'lesson_tests') || !db_has_table($pdo, 'test_attempts')) {
            return false;
        }
        $passedCol = self::passedColumn($pdo);
        if ($passedCol === '') {
            return false;
        }
        $stmt = $pdo->prepare('SELECT 1 FROM lesson_tests lt JOIN test_attempts ta ON ta.test_id = lt.id WHERE lt.lesson_id = ? AND ta.user_id = ? AND ta.' . $passedCol . ' = 1 LIMIT 1');
        $stmt->execute([$lessonId, $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function attemptsCount(PDO $pdo): int
    {
        if (!db_has_table($pdo, 'test_attempts')) {
            return 0;
        }
        $stmt = $pdo->query('SELECT COUNT(*) FROM test_attempts');
        return (int)$stmt->fetchColumn();
    }

    private static function findById(PDO $pdo, int $testId): ?array
    {
        if (!db_has_table($pdo, 'lesson_tests')) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT *, ' . self::passingExpr($pdo) . ' FROM lesson_tests WHERE id = ? LIMIT 1');
        $stmt->execute([$testId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}