<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserSkill;
use PDO;

class UserSkillRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(UserSkill $userSkill): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO user_skills (user_id, skill_id, hourly_rate, experience_level, description, created_at) VALUES (:user_id, :skill_id, :hourly_rate, :experience_level, :description, NOW())');
        $stmt->execute([
            ':user_id' => $userSkill->user_id,
            ':skill_id' => $userSkill->skill_id,
            ':hourly_rate' => $userSkill->hourly_rate,
            ':experience_level' => $userSkill->experience_level,
            ':description' => $userSkill->description,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?UserSkill
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_skills WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new UserSkill($row) : null;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT us.*, s.name as skill_name, s.category as skill_category FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new UserSkill($row), $rows);
    }

    public function findBySkillId(int $skillId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT us.*, u.first_name, u.last_name, u.profile_photo, s.name as skill_name FROM user_skills us JOIN users u ON us.user_id = u.id JOIN skills s ON us.skill_id = s.id WHERE us.skill_id = :skill_id LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':skill_id', $skillId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new UserSkill($row), $rows);
    }

    public function update(int $id, UserSkill $userSkill): void
    {
        $stmt = $this->pdo->prepare('UPDATE user_skills SET hourly_rate = :hourly_rate, experience_level = :experience_level, description = :description WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':hourly_rate' => $userSkill->hourly_rate,
            ':experience_level' => $userSkill->experience_level,
            ':description' => $userSkill->description,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_skills WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
