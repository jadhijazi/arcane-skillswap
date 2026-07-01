<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\AvailabilitySlot;
use PDO;

class AvailabilitySlotRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(AvailabilitySlot $slot): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO availability_slots (user_id, start_time, end_time, created_at) VALUES (:user_id, :start_time, :end_time, NOW())');
        $stmt->execute([
            ':user_id' => $slot->user_id,
            ':start_time' => $slot->start_time,
            ':end_time' => $slot->end_time,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?AvailabilitySlot
    {
        $stmt = $this->pdo->prepare('SELECT * FROM availability_slots WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new AvailabilitySlot($row) : null;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM availability_slots WHERE user_id = :user_id ORDER BY start_time ASC');
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new AvailabilitySlot($row), $rows);
    }

    public function hasOverlap(int $userId, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as cnt FROM availability_slots WHERE user_id = :user_id AND NOT (end_time <= :start_time OR start_time >= :end_time)';
        $params = [':user_id' => $userId, ':start_time' => $startTime, ':end_time' => $endTime];

        if ($excludeId) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'] > 0;
    }

    public function update(int $id, AvailabilitySlot $slot): void
    {
        $stmt = $this->pdo->prepare('UPDATE availability_slots SET start_time = :start_time, end_time = :end_time WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':start_time' => $slot->start_time,
            ':end_time' => $slot->end_time,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM availability_slots WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
