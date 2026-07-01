<?php
/**
 * ============================================================================
 * Message Data Access Object (DAO/Repository)
 * ============================================================================
 *
 * Handles all database operations for the in-app messaging system.
 * Messages are one-to-one (sender → receiver) and stored flat;
 * conversations are derived by pairing (min_id, max_id) of the two users.
 *
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

class MessageDAO {
    private Database $db;

    public function __construct(Database $database) {
        $this->db = $database;
    }

    /**
     * Send a message from one user to another.
     *
     * @return int  New message ID
     */
    public function send(int $senderId, int $receiverId, string $body): int {
        if ($senderId === $receiverId) {
            throw new RuntimeException('A user cannot send a message to themselves.');
        }

        return $this->db->insert('message', [
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'body'        => $body,
        ]);
    }

    /**
     * Get the full conversation thread between two users, oldest first.
     * Either user can be sender or receiver in any given message.
     */
    public function getConversation(int $userAId, int $userBId): array {
        return $this->db->fetchAll(
            '
            SELECT m.id, m.sender_id, m.receiver_id, m.body, m.sent_at,
                   sender.name   AS sender_name,
                   sender.photo_url AS sender_photo
            FROM message m
            INNER JOIN user sender ON m.sender_id = sender.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.sent_at ASC
            ',
            [$userAId, $userBId, $userBId, $userAId]
        );
    }

    /**
     * Get the inbox for a user: one row per conversation partner,
     * showing the most recent message and partner details.
     * Ordered by most recent message first.
     */
    public function getInbox(int $userId): array {
        return $this->db->fetchAll(
            '
            SELECT
                partner.id          AS partner_id,
                partner.name        AS partner_name,
                partner.photo_url   AS partner_photo,
                latest.body         AS last_message,
                latest.sent_at      AS last_sent_at,
                latest.sender_id    AS last_sender_id,
                unread.unread_count
            FROM (
                -- Find the most recent message timestamp per conversation partner
                SELECT
                    CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
                    MAX(sent_at) AS latest_sent_at
                FROM message
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY partner_id
            ) AS conv
            INNER JOIN message latest ON (
                (latest.sender_id = ? AND latest.receiver_id = conv.partner_id
                 OR latest.sender_id = conv.partner_id AND latest.receiver_id = ?)
                AND latest.sent_at = conv.latest_sent_at
            )
            INNER JOIN user partner ON partner.id = conv.partner_id
            LEFT JOIN (
                -- Count unread messages from each partner
                SELECT sender_id, COUNT(*) AS unread_count
                FROM message
                WHERE receiver_id = ? AND is_read = 0
                GROUP BY sender_id
            ) AS unread ON unread.sender_id = conv.partner_id
            ORDER BY conv.latest_sent_at DESC
            ',
            [$userId, $userId, $userId, $userId, $userId, $userId]
        );
    }

    /**
     * Get a single message by ID.
     */
    public function findById(int $messageId): ?array {
        return $this->db->fetchOne(
            'SELECT * FROM message WHERE id = ?',
            [$messageId]
        );
    }

    /**
     * Count unread messages for a user (notification badge).
     */
    public function countUnread(int $userId): int {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM message WHERE receiver_id = ? AND is_read = 0',
            [$userId]
        );
    }

    /**
     * Mark all messages in a conversation as read (called when user opens thread).
     *
     * @return int  Number of rows marked read
     */
    public function markConversationRead(int $readerId, int $senderId): int {
        // Mark all messages sent by $senderId to $readerId as read
        $sql = '
            UPDATE message
            SET is_read = 1
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
        ';
        $stmt = $this->db->execute($sql, [$readerId, $senderId]);
        return $stmt->rowCount();
    }

    /**
     * Delete a single message (sender can delete their own message).
     */
    public function delete(int $messageId): int {
        return $this->db->delete('message', ['id' => $messageId]);
    }
}
