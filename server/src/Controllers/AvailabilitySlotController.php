<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\AvailabilitySlotService;
use App\Helpers\ResponseHelper;

class AvailabilitySlotController
{
    private AvailabilitySlotService $slotService;

    public function __construct(AvailabilitySlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    public function create(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $data = (array)$request->getParsedBody();
        try {
            $slot = $this->slotService->createSlot($userId, $data);
            return ResponseHelper::json($response, true, 'Availability slot created', ['slot' => $slot])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $slot = $this->slotService->getSlot((int)$args['id']);
            return ResponseHelper::json($response, true, 'Availability slot found', ['slot' => $slot]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function getByUser(Request $request, Response $response, array $args): Response
    {
        $slots = $this->slotService->getUserSlots((int)$args['user_id']);
        return ResponseHelper::json($response, true, 'Availability slots retrieved', ['slots' => $slots]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        try {
            $slot = $this->slotService->getSlot((int)$args['id']);
            if ($slot->user_id !== $userId) {
                return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
            }

            $data = (array)$request->getParsedBody();
            $slot = $this->slotService->updateSlot((int)$args['id'], $data);
            return ResponseHelper::json($response, true, 'Availability slot updated', ['slot' => $slot]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        try {
            $slot = $this->slotService->getSlot((int)$args['id']);
            if ($slot->user_id !== $userId) {
                return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
            }

            $this->slotService->deleteSlot((int)$args['id']);
            return ResponseHelper::json($response, true, 'Availability slot deleted', (object)[])->withStatus(204);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }
}
