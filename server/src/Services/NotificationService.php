<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificationRepository;
use App\Models\Notification;

class NotificationService
{
    private NotificationRepository $repo;

    public function __construct(NotificationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createNotification(int $userId, string $type, array $data = []): Notification
    {
        $notification = new Notification([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'is_read' => false,
        ]);

        $id = $this->repo->create($notification);
        $notification->id = $id;
        return $notification;
    }

    public function getNotification(int $id): Notification
    {
        $notification = $this->repo->findById($id);
        if (!$notification) {
            throw new \Exception('Notification not found');
        }
        return $notification;
    }

    public function getUserNotifications(int $userId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $notifications = $this->repo->findByUser($userId, $perPage, $offset);
        return [
            'notifications' => $notifications,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->repo->getUnreadCount($userId);
    }

    public function markAsRead(int $id): void
    {
        $this->getNotification($id);
        $this->repo->markAsRead($id);
    }

    public function markAllAsRead(int $userId): void
    {
        $this->repo->markAllAsRead($userId);
    }
}
