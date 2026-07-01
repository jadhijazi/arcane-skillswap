<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TutorRepository;

class TutorDiscoveryService
{
    private TutorRepository $repo;

    public function __construct(TutorRepository $repo)
    {
        $this->repo = $repo;
    }

    public function searchTutors(array $query): array
    {
        $skill_id = (int)($query['skill_id'] ?? 0);
        if ($skill_id <= 0) {
            throw new \Exception('skill_id is required');
        }

        $filters = [
            'skill_id' => $skill_id,
            'faculty' => $query['faculty'] ?? null,
            'min_rating' => isset($query['min_rating']) ? (float)$query['min_rating'] : null,
            'max_rate' => isset($query['max_rate']) ? (float)$query['max_rate'] : null,
            'min_rate' => isset($query['min_rate']) ? (float)$query['min_rate'] : null,
            'experience_level' => $query['experience_level'] ?? null,
        ];

        $page = (int)($query['page'] ?? 1);
        $perPage = (int)($query['per_page'] ?? 50);
        $sort = $query['sort'] ?? 'rating'; // rating, price, popular

        $offset = ($page - 1) * $perPage;
        $tutors = $this->repo->searchTutors($filters, $sort, $perPage, $offset);
        $total = $this->repo->countTutors($filters);

        return [
            'tutors' => $tutors,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int)ceil($total / $perPage),
            'sort' => $sort,
        ];
    }
}
