<?php
final class UserModel
{
    public static function findForLogin(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function createClient(PDO $pdo, string $email, string $hash): void
    {
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, 'client')");
        $stmt->execute([':email' => $email, ':hash' => $hash]);
    }

    public static function findProfile(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare('SELECT id, username, email, avatar_url FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findPasswordHash(PDO $pdo, int $userId): ?string
    {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        return is_string($hash) ? $hash : null;
    }

    public static function updatePasswordHash(PDO $pdo, int $userId, string $hash): void
    {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $userId]);
    }

    public static function updateAvatar(PDO $pdo, int $userId, string $path): void
    {
        $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
        $stmt->execute([$path, $userId]);
    }
}
