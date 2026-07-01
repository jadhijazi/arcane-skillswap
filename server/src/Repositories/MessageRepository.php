<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Message;
use PDO;

class MessageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Message $message): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (sender_id, recipient_id, content, is_read, created_at) VALUES (:sender_id, :recipient_id, :content, :is_read, NOW())');
        $stmt->execute([
            ':sender_id' => $message->sender_id,
            ':recipient_id' => $message->recipient_id,
            ':content' => $message->content,
            ':is_read' => $message->is_read ? 1 : 0,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Message
    {
        $stmt = $this->pdo->prepare('SELECT * FROM messages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Message($row) : null;
    }

    public function getConversation(int $userId1, int $userId2, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM messages WHERE (sender_id = :user1 AND recipient_id = :user2) OR (sender_id = :user2 AND recipient_id = :user1) ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':user1', $userId1);
        $stmt->bindValue(':user2', $userId2);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Message($row), array_reverse($rows));
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as cnt FROM messages WHERE recipient_id = :user_id AND is_read = 0');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }

    public function markAsRead(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function markConversationAsRead(int $userId, int $senderId): void
    {
        $stmt = $this->pdo->prepare('UPDATE messages SET is_read = 1 WHERE recipient_id = :user_id AND sender_id = :sender_id AND is_read = 0');
        $stmt->execute([':user_id' => $userId, ':sender_id' => $senderId]);
    }
}
