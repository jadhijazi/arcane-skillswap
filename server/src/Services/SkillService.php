<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\SkillRepository;
use App\Models\Skill;

class SkillService
{
    private SkillRepository $repo;

    public function __construct(SkillRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createSkill(array $data): Skill
    {
        $skill = new Skill([
            'name' => $data['name'] ?? '',
            'category' => $data['category'] ?? null,
        ]);

        if (empty($skill->name)) {
            throw new \Exception('Skill name is required');
        }

        $id = $this->repo->create($skill);
        $skill->id = $id;
        return $skill;
    }

    public function getSkill(int $id): Skill
    {
        $skill = $this->repo->findById($id);
        if (!$skill) {
            throw new \Exception('Skill not found');
        }
        return $skill;
    }

    public function listSkills(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $skills = $this->repo->findAll($perPage, $offset);
        $total = $this->repo->count();
        return [
            'skills' => $skills,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int)ceil($total / $perPage),
        ];
    }

    public function searchSkills(string $query, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $skills = $this->repo->search($query, $perPage, $offset);
        return [
            'skills' => $skills,
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function filterByCategory(string $category, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $skills = $this->repo->filterByCategory($category, $perPage, $offset);
        return [
            'skills' => $skills,
            'category' => $category,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function updateSkill(int $id, array $data): Skill
    {
        $skill = $this->getSkill($id);
        $skill->name = $data['name'] ?? $skill->name;
        $skill->category = $data['category'] ?? $skill->category;
        $this->repo->update($id, $skill);
        return $skill;
    }

    public function deleteSkill(int $id): void
    {
        $this->getSkill($id);
        $this->repo->delete($id);
    }

    public function getTrending(int $limit = 10): array
    {
        return ['skills' => $this->repo->getTrending($limit)];
    }
}
