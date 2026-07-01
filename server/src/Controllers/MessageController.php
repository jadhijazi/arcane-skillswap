<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\MessageService;
use App\Helpers\ResponseHelper;

class MessageController
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function send(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $data = (array)$request->getParsedBody();
        try {
            $recipientId = (int)($data['recipient_id'] ?? 0);
            $content = $data['content'] ?? '';

            if ($recipientId <= 0) {
                return ResponseHelper::json($response, false, 'recipient_id is required', null, [])->withStatus(400);
            }

            $message = $this->messageService->sendMessage($userId, $recipientId, $content);
            return ResponseHelper::json($response, true, 'Message sent', ['message' => $message])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $message = $this->messageService->getMessage((int)$args['id']);
            return ResponseHelper::json($response, true, 'Message found', ['message' => $message]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function getConversation(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $otherUserId = (int)$args['other_user_id'];

        $result = $this->messageService->getConversation($userId, $otherUserId, $page, $perPage);
        return ResponseHelper::json($response, true, 'Conversation retrieved', $result);
    }

    public function getUnreadCount(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $count = $this->messageService->getUnreadCount($userId);
        return ResponseHelper::json($response, true, 'Unread count retrieved', ['unread_count' => $count]);
    }

    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        try {
            $this->messageService->markAsRead((int)$args['id']);
            return ResponseHelper::json($response, true, 'Message marked as read', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function markConversationAsRead(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $senderId = (int)$args['sender_id'];
        $this->messageService->markConversationAsRead($userId, $senderId);
        return ResponseHelper::json($response, true, 'Conversation marked as read', (object)[]);
    }
}
