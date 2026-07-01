<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\NotificationService;
use App\Helpers\ResponseHelper;

class NotificationController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function list(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $result = $this->notificationService->getUserNotifications($userId, $page, $perPage);
        return ResponseHelper::json($response, true, 'Notifications retrieved', $result);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $notification = $this->notificationService->getNotification((int)$args['id']);
            return ResponseHelper::json($response, true, 'Notification found', ['notification' => $notification]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function getUnreadCount(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $count = $this->notificationService->getUnreadCount($userId);
        return ResponseHelper::json($response, true, 'Unread count retrieved', ['unread_count' => $count]);
    }

    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        try {
            $this->notificationService->markAsRead((int)$args['id']);
            return ResponseHelper::json($response, true, 'Notification marked as read', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function markAllAsRead(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $this->notificationService->markAllAsRead($userId);
        return ResponseHelper::json($response, true, 'All notifications marked as read', (object)[]);
    }
}
