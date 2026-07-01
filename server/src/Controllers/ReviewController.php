<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\ReviewService;
use App\Helpers\ResponseHelper;

class ReviewController
{
    private ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
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
            $review = $this->reviewService->createReview($userId, $data);
            return ResponseHelper::json($response, true, 'Review created', ['review' => $review])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $review = $this->reviewService->getReview((int)$args['id']);
            return ResponseHelper::json($response, true, 'Review found', ['review' => $review]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function getTutorReviews(Request $request, Response $response, array $args): Response
    {
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $result = $this->reviewService->getTutorReviews((int)$args['tutor_id'], $page, $perPage);
        return ResponseHelper::json($response, true, 'Tutor reviews retrieved', $result);
    }
}
