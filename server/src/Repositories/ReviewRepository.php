<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Review;
use PDO;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Review $review): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO reviews (booking_id, reviewer_id, rating, comment, created_at) VALUES (:booking_id, :reviewer_id, :rating, :comment, NOW())');
        $stmt->execute([
            ':booking_id' => $review->booking_id,
            ':reviewer_id' => $review->reviewer_id,
            ':rating' => $review->rating,
            ':comment' => $review->comment,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Review
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reviews WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Review($row) : null;
    }

    public function findByBooking(int $bookingId): ?Review
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reviews WHERE booking_id = :booking_id LIMIT 1');
        $stmt->execute([':booking_id' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Review($row) : null;
    }

    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reviews WHERE reviewer_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Review($row), $rows);
    }

    public function findForTutor(int $tutorId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT r.* FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.tutor_id = :tutor_id ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':tutor_id', $tutorId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Review($row), $rows);
    }

    public function getAverageRating(int $tutorId): float
    {
        $stmt = $this->pdo->prepare('SELECT AVG(rating) as avg_rating FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.tutor_id = :tutor_id');
        $stmt->execute([':tutor_id' => $tutorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['avg_rating'] ? (float)$row['avg_rating'] : 0.0;
    }

    public function getCount(int $tutorId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as cnt FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.tutor_id = :tutor_id');
        $stmt->execute([':tutor_id' => $tutorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM reviews');
        return (int)$stmt->fetchColumn();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM reviews WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
