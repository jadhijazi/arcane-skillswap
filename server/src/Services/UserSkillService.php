<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserSkillRepository;
use App\Repositories\UserRepository;
use App\Models\UserSkill;

class UserSkillService
{
    private UserSkillRepository $repo;
    private UserRepository $userRepo;

    public function __construct(UserSkillRepository $repo, UserRepository $userRepo)
    {
        $this->repo = $repo;
        $this->userRepo = $userRepo;
    }

    public function createSkillOffering(int $userId, array $data): UserSkill
    {
        $userSkill = new UserSkill([
            'user_id' => $userId,
            'skill_id' => $data['skill_id'] ?? 0,
            'hourly_rate' => (float)($data['hourly_rate'] ?? 0),
            'experience_level' => $data['experience_level'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if ($userSkill->skill_id <= 0) {
            throw new \Exception('Valid skill_id required');
        }
        if ($userSkill->hourly_rate < 0) {
            throw new \Exception('Hourly rate must be positive');
        }

        $id = $this->repo->create($userSkill);
        $userSkill->id = $id;
        $this->userRepo->assignRole($userId, 'Tutor');
        return $userSkill;
    }

    public function getSkillOffering(int $id): UserSkill
    {
        $userSkill = $this->repo->findById($id);
        if (!$userSkill) {
            throw new \Exception('Skill offering not found');
        }
        return $userSkill;
    }

    public function getUserSkills(int $userId): array
    {
        return $this->repo->findByUserId($userId);
    }

    public function updateSkillOffering(int $id, array $data): UserSkill
    {
        $userSkill = $this->getSkillOffering($id);
        $userSkill->hourly_rate = (float)($data['hourly_rate'] ?? $userSkill->hourly_rate);
        $userSkill->experience_level = $data['experience_level'] ?? $userSkill->experience_level;
        $userSkill->description = $data['description'] ?? $userSkill->description;
        
        if ($userSkill->hourly_rate < 0) {
            throw new \Exception('Hourly rate must be positive');
        }

        $this->repo->update($id, $userSkill);
        return $userSkill;
    }

    public function deleteSkillOffering(int $id): void
    {
        $this->getSkillOffering($id);
        $this->repo->delete($id);
    }
}
