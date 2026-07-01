<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Skill;
use PDO;

class SkillRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Skill $skill): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO skills (name, category, created_at) VALUES (:name, :category, NOW())');
        $stmt->execute([
            ':name' => $skill->name,
            ':category' => $skill->category,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Skill
    {
        $stmt = $this->pdo->prepare('SELECT * FROM skills WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Skill($row) : null;
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM skills LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Skill($row), $rows);
    }

    public function search(string $query, int $limit = 50, int $offset = 0): array
    {
        $q = '%' . $query . '%';
        $stmt = $this->pdo->prepare('SELECT * FROM skills WHERE name LIKE :q OR category LIKE :q LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':q', $q);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Skill($row), $rows);
    }

    public function filterByCategory(string $category, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM skills WHERE category = :category LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Skill($row), $rows);
    }

    public function update(int $id, Skill $skill): void
    {
        $stmt = $this->pdo->prepare('UPDATE skills SET name = :name, category = :category WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':name' => $skill->name,
            ':category' => $skill->category,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM skills WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM skills');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }

    public function getTrending(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, COUNT(us.id) AS tutor_count, COUNT(b.id) AS booking_count
             FROM skills s
             LEFT JOIN user_skills us ON s.id = us.skill_id
             LEFT JOIN bookings b ON us.id = b.user_skill_id AND b.status = :completed
             GROUP BY s.id
             ORDER BY booking_count DESC, tutor_count DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':completed', 'completed');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
