<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\AuthService;
use App\Helpers\ResponseHelper;
use App\Validators\AuthValidator;
use App\Helpers\SanitizeHelper;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request, Response $response): Response
    {
        $data = SanitizeHelper::sanitizeArray((array)$request->getParsedBody(), ['first_name', 'last_name', 'bio']);
        $validator = (new AuthValidator())->validateRegister($data);

        if ($validator->fails()) {
            return ResponseHelper::json($response, false, 'Validation failed', null, $validator->errors())->withStatus(422);
        }

        try {
            $result = $this->authService->register($data);
            return ResponseHelper::json($response, true, 'Registration successful', [
                'user' => $result['user'],
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'roles' => $result['roles'],
            ])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        $validator = (new AuthValidator())->validateLogin($data);

        if ($validator->fails()) {
            return ResponseHelper::json($response, false, 'Validation failed', null, $validator->errors())->withStatus(422);
        }

        try {
            $result = $this->authService->login($data['email'], $data['password']);
            return ResponseHelper::json($response, true, 'Login successful', [
                'user' => $result['user'],
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'roles' => $result['roles'],
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(401);
        }
    }

    public function refresh(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        try {
            $result = $this->authService->refresh($data['refresh_token'] ?? '');
            return ResponseHelper::json($response, true, 'Token refreshed', ['access_token' => $result['access_token']]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(401);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        $token = $data['refresh_token'] ?? '';
        if ($token) {
            $this->authService->revokeRefreshToken($token);
        }

        return ResponseHelper::json($response, true, 'Logged out', (object)[]);
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        $validator = (new AuthValidator())->validateForgotPassword($data);

        if ($validator->fails()) {
            return ResponseHelper::json($response, false, 'Validation failed', null, $validator->errors())->withStatus(422);
        }

        $result = $this->authService->forgotPassword($data['email']);
        return ResponseHelper::json($response, true, $result['message'], $result);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        $token = $data['reset_token'] ?? '';
        $password = $data['new_password'] ?? '';

        if (empty($token) || empty($password)) {
            return ResponseHelper::json($response, false, 'reset_token and new_password are required', null, [])->withStatus(422);
        }

        try {
            $this->authService->resetPassword($token, $password);
            return ResponseHelper::json($response, true, 'Password reset successful', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }
}
