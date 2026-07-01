<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\UserSkillService;
use App\Helpers\ResponseHelper;

class UserSkillController
{
    private UserSkillService $userSkillService;

    public function __construct(UserSkillService $userSkillService)
    {
        $this->userSkillService = $userSkillService;
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
            $userSkill = $this->userSkillService->createSkillOffering($userId, $data);
            return ResponseHelper::json($response, true, 'Skill offering created', ['user_skill' => $userSkill])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $userSkill = $this->userSkillService->getSkillOffering((int)$args['id']);
            return ResponseHelper::json($response, true, 'Skill offering found', ['user_skill' => $userSkill]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function getByUser(Request $request, Response $response, array $args): Response
    {
        $userSkills = $this->userSkillService->getUserSkills((int)$args['user_id']);
        return ResponseHelper::json($response, true, 'User skills retrieved', ['user_skills' => $userSkills]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        try {
            $userSkill = $this->userSkillService->getSkillOffering((int)$args['id']);
            if ($userSkill->user_id !== $userId) {
                return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
            }

            $data = (array)$request->getParsedBody();
            $userSkill = $this->userSkillService->updateSkillOffering((int)$args['id'], $data);
            return ResponseHelper::json($response, true, 'Skill offering updated', ['user_skill' => $userSkill]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        try {
            $userSkill = $this->userSkillService->getSkillOffering((int)$args['id']);
            if ($userSkill->user_id !== $userId) {
                return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
            }

            $this->userSkillService->deleteSkillOffering((int)$args['id']);
            return ResponseHelper::json($response, true, 'Skill offering deleted', (object)[])->withStatus(204);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }
}
