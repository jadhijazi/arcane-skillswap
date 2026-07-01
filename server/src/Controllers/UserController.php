<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\UserService;
use App\Helpers\ResponseHelper;

class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getCurrentUser(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        try {
            $profile = $this->userService->getUserProfile($userId);
            return ResponseHelper::json($response, true, 'User profile retrieved', $profile);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function getProfile(Request $request, Response $response, array $args): Response
    {
        try {
            $profile = $this->userService->getUserProfile((int)$args['id']);
            return ResponseHelper::json($response, true, 'User profile retrieved', $profile);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function update(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $data = (array)$request->getParsedBody();
        try {
            $user = $this->userService->updateUser($userId, $data);
            return ResponseHelper::json($response, true, 'Profile updated', ['user' => $user]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function changePassword(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $data = (array)$request->getParsedBody();
        try {
            $oldPassword = $data['old_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';

            if (empty($oldPassword) || empty($newPassword)) {
                return ResponseHelper::json($response, false, 'old_password and new_password are required', null, [])->withStatus(400);
            }

            $this->userService->changePassword($userId, $oldPassword, $newPassword);
            return ResponseHelper::json($response, true, 'Password changed', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }
}
