<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notification;
use PDO;

class NotificationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Notification $notification): int
    {
        $dataJson = $notification->data ? json_encode($notification->data) : null;
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, type, data, is_read, created_at) VALUES (:user_id, :type, :data, :is_read, NOW())');
        $stmt->execute([
            ':user_id' => $notification->user_id,
            ':type' => $notification->type,
            ':data' => $dataJson,
            ':is_read' => $notification->is_read ? 1 : 0,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Notification
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        if ($row['data']) {
            $row['data'] = json_decode($row['data'], true);
        }
        return new Notification($row);
    }

    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => {
            if ($row['data']) {
                $row['data'] = json_decode($row['data'], true);
            }
            return new Notification($row);
        }, $rows);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }

    public function markAsRead(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }
}
