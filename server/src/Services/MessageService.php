<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\MessageRepository;
use App\Models\Message;

class MessageService
{
    private MessageRepository $repo;
    private NotificationService $notificationService;

    public function __construct(MessageRepository $repo, NotificationService $notificationService)
    {
        $this->repo = $repo;
        $this->notificationService = $notificationService;
    }

    public function sendMessage(int $senderId, int $recipientId, string $content): Message
    {
        if (empty($content)) {
            throw new \Exception('Message content is required');
        }
        if ($senderId === $recipientId) {
            throw new \Exception('Cannot send message to yourself');
        }

        $message = new Message([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'content' => $content,
            'is_read' => false,
        ]);

        $id = $this->repo->create($message);
        $message->id = $id;

        $this->notificationService->createNotification(
            $recipientId,
            'message.received',
            ['message_id' => $id, 'sender_id' => $senderId]
        );

        return $message;
    }

    public function getMessage(int $id): Message
    {
        $message = $this->repo->findById($id);
        if (!$message) {
            throw new \Exception('Message not found');
        }
        return $message;
    }

    public function getConversation(int $userId, int $otherUserId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $messages = $this->repo->getConversation($userId, $otherUserId, $perPage, $offset);
        return [
            'messages' => $messages,
            'other_user_id' => $otherUserId,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->repo->getUnreadCount($userId);
    }

    public function markAsRead(int $messageId): void
    {
        $this->getMessage($messageId); // Verify exists
        $this->repo->markAsRead($messageId);
    }

    public function markConversationAsRead(int $userId, int $senderId): void
    {
        $this->repo->markConversationAsRead($userId, $senderId);
    }
}
