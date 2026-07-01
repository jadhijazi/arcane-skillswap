<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\SkillService;
use App\Helpers\ResponseHelper;

class SkillController
{
    private SkillService $skillService;

    public function __construct(SkillService $skillService)
    {
        $this->skillService = $skillService;
    }

    public function create(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        try {
            $skill = $this->skillService->createSkill($data);
            return ResponseHelper::json($response, true, 'Skill created', ['skill' => $skill])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $skill = $this->skillService->getSkill((int)$args['id']);
            return ResponseHelper::json($response, true, 'Skill found', ['skill' => $skill]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function list(Request $request, Response $response): Response
    {
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $result = $this->skillService->listSkills($page, $perPage);
        return ResponseHelper::json($response, true, 'Skills retrieved', $result);
    }

    public function search(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);

        if (empty($query)) {
            return ResponseHelper::json($response, false, 'Search query required', null, [])->withStatus(400);
        }

        $result = $this->skillService->searchSkills($query, $page, $perPage);
        return ResponseHelper::json($response, true, 'Skills found', $result);
    }

    public function filterByCategory(Request $request, Response $response): Response
    {
        $category = $request->getQueryParams()['category'] ?? '';
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);

        if (empty($category)) {
            return ResponseHelper::json($response, false, 'Category filter required', null, [])->withStatus(400);
        }

        $result = $this->skillService->filterByCategory($category, $page, $perPage);
        return ResponseHelper::json($response, true, 'Skills filtered', $result);
    }

    public function trending(Request $request, Response $response): Response
    {
        $limit = min(50, max(1, (int)($request->getQueryParams()['limit'] ?? 10)));
        $result = $this->skillService->getTrending($limit);
        return ResponseHelper::json($response, true, 'Trending skills', $result);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $data = (array)$request->getParsedBody();
        try {
            $skill = $this->skillService->updateSkill((int)$args['id'], $data);
            return ResponseHelper::json($response, true, 'Skill updated', ['skill' => $skill]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $this->skillService->deleteSkill((int)$args['id']);
            return ResponseHelper::json($response, true, 'Skill deleted', (object)[])->withStatus(204);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }
}
