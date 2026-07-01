<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TutorRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Search tutors offering a specific skill with filtering and sorting
     */
    public function searchTutors(array $filters = [], string $sort = 'rating', int $limit = 50, int $offset = 0): array
    {
        $where = ['us.skill_id = :skill_id'];
        $params = [':skill_id' => $filters['skill_id'] ?? 0];

        if (isset($filters['faculty']) && $filters['faculty']) {
            $where[] = 'u.faculty = :faculty';
            $params[':faculty'] = $filters['faculty'];
        }

        if (isset($filters['min_rating']) && $filters['min_rating'] !== null) {
            $where[] = '(SELECT COALESCE(AVG(r.rating), 0) FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.tutor_id = u.id) >= :min_rating';
            $params[':min_rating'] = (float)$filters['min_rating'];
        }

        if (isset($filters['max_rate']) && $filters['max_rate'] !== null) {
            $where[] = 'us.hourly_rate <= :max_rate';
            $params[':max_rate'] = (float)$filters['max_rate'];
        }

        if (isset($filters['min_rate']) && $filters['min_rate'] !== null) {
            $where[] = 'us.hourly_rate >= :min_rate';
            $params[':min_rate'] = (float)$filters['min_rate'];
        }

        if (isset($filters['experience_level']) && $filters['experience_level']) {
            $where[] = 'us.experience_level = :experience_level';
            $params[':experience_level'] = $filters['experience_level'];
        }

        $avgRatingSub = '(SELECT COALESCE(AVG(r.rating), 0) FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.tutor_id = u.id)';
        $sessionsSub = "(SELECT COUNT(*) FROM bookings b WHERE b.tutor_id = u.id AND b.status = 'completed')";

        $orderBy = match ($sort) {
            'rating' => "{$avgRatingSub} DESC, {$sessionsSub} DESC",
            'price' => 'us.hourly_rate ASC',
            'popular' => "{$sessionsSub} DESC",
            default => "{$avgRatingSub} DESC",
        };

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT u.id, u.first_name, u.last_name, u.bio, u.profile_photo, u.faculty,
                {$avgRatingSub} AS avg_rating, {$sessionsSub} AS total_sessions,
                us.id AS user_skill_id, us.hourly_rate, us.experience_level, us.description
                FROM user_skills us
                JOIN users u ON us.user_id = u.id
                WHERE u.is_active = 1 AND {$whereClause}
                ORDER BY {$orderBy}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key === ':skill_id' || $key === ':min_rating' || $key === ':max_rate' || $key === ':min_rate') {
                $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTutors(array $filters = []): int
    {
        $where = ['us.skill_id = :skill_id'];
        $params = [':skill_id' => $filters['skill_id'] ?? 0];

        if (isset($filters['faculty']) && $filters['faculty']) {
            $where[] = 'u.faculty = :faculty';
            $params[':faculty'] = $filters['faculty'];
        }

        if (isset($filters['min_rating']) && $filters['min_rating'] !== null) {
            $where[] = '(SELECT COALESCE(AVG(r.rating), 0) FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.tutor_id = u.id) >= :min_rating';
            $params[':min_rating'] = (float)$filters['min_rating'];
        }

        if (isset($filters['max_rate']) && $filters['max_rate'] !== null) {
            $where[] = 'us.hourly_rate <= :max_rate';
            $params[':max_rate'] = (float)$filters['max_rate'];
        }

        if (isset($filters['min_rate']) && $filters['min_rate'] !== null) {
            $where[] = 'us.hourly_rate >= :min_rate';
            $params[':min_rate'] = (float)$filters['min_rate'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(DISTINCT u.id) as cnt FROM user_skills us JOIN users u ON us.user_id = u.id WHERE {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['cnt'];
    }
}
