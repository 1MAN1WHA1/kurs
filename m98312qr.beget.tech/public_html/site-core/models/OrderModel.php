<?php
final class OrderModel
{
    public static function listForUser(PDO $pdo, int $userId): array
    {
        $sql = "
            SELECT
                orders.id,
                orders.product_id,
                orders.created_at,
                orders.status,
                products.title,
                products.price,
                products.is_course
            FROM orders
            JOIN products ON products.id = orders.product_id
            WHERE orders.user_id = ?
            ORDER BY orders.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findDetailedForUser(PDO $pdo, int $orderId, int $userId): ?array
    {
        $sql = "
            SELECT
                orders.id AS order_id,
                orders.created_at,
                orders.status,
                products.title,
                products.description,
                products.price,
                products.image_url,
                products.is_course,
                products.id AS product_id
            FROM orders
            JOIN products ON orders.product_id = products.id
            WHERE orders.id = ? AND orders.user_id = ?
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function listAll(PDO $pdo): array
    {
        $sql = "
            SELECT
                orders.id AS order_id,
                orders.created_at,
                orders.status,
                users.email,
                products.title,
                products.price,
                products.id AS product_id
            FROM orders
            JOIN users ON orders.user_id = users.id
            JOIN products ON orders.product_id = products.id
            ORDER BY orders.id DESC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function userHasPaidCourse(PDO $pdo, int $userId, int $courseId): bool
    {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM orders
            WHERE user_id = ? AND product_id = ? AND status = 'paid'
            LIMIT 1
        ");
        $stmt->execute([$userId, $courseId]);
        return (bool)$stmt->fetchColumn();
    }
}
