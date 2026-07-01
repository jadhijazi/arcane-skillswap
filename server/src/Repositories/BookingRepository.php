<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Booking;
use PDO;

class BookingRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Booking $booking): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO bookings (learner_id, tutor_id, user_skill_id, start_time, end_time, status, amount, created_at) VALUES (:learner_id, :tutor_id, :user_skill_id, :start_time, :end_time, :status, :amount, NOW())');
        $stmt->execute([
            ':learner_id' => $booking->learner_id,
            ':tutor_id' => $booking->tutor_id,
            ':user_skill_id' => $booking->user_skill_id,
            ':start_time' => $booking->start_time,
            ':end_time' => $booking->end_time,
            ':status' => $booking->status,
            ':amount' => $booking->amount,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Booking
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Booking($row) : null;
    }

    public function findByLearner(int $learnerId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bookings WHERE learner_id = :learner_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':learner_id', $learnerId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Booking($row), $rows);
    }

    public function findByTutor(int $tutorId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bookings WHERE tutor_id = :tutor_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':tutor_id', $tutorId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Booking($row), $rows);
    }

    public function update(int $id, Booking $booking): void
    {
        $stmt = $this->pdo->prepare('UPDATE bookings SET status = :status, amount = :amount WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':status' => $booking->status,
            ':amount' => $booking->amount,
        ]);
    }

    public function findByStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bookings WHERE status = :status ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Booking($row), $rows);
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bookings ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Booking($row), $rows);
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM bookings');
        return (int)$stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM bookings WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetchColumn();
    }
}
