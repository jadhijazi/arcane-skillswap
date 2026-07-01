<?php
/**
 * ============================================================================
 * UserSkill Data Access Object (DAO/Repository)
 * ============================================================================
 *
 * Manages the user_skill junction table: the skills a tutor offers,
 * their hourly rate, and proficiency level.
 *
 * A user must have at least one user_skill entry to be discoverable
 * as a tutor in search results.
 *
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

class UserSkillDAO {
    private Database $db;

    public function __construct(Database $database) {
        $this->db = $database;
    }

    /**
     * Add a skill to a tutor's profile.
     * Throws if the user already offers this skill (UNIQUE constraint).
     *
     * @return int  New user_skill ID
     */
    public function addSkill(int $userId, int $skillId, float $hourlyRate, string $level): int {
        // Check for duplicate before insert for a friendlier error message
        $exists = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM user_skill WHERE user_id = ? AND skill_id = ?',
            [$userId, $skillId]
        );

        if ((int) $exists > 0) {
            throw new RuntimeException('You already offer this skill. Use updateSkill() to change the rate or level.');
        }

        return $this->db->insert('user_skill', [
            'user_id'     => $userId,
            'skill_id'    => $skillId,
            'hourly_rate' => $hourlyRate,
            'level'       => $level,
        ]);
    }

    /**
     * Update hourly rate and/or proficiency level for an existing user_skill.
     *
     * @param array $data  Associative array; allowed keys: 'hourly_rate', 'level'
     * @return int  Rows affected (1 = success, 0 = record not found)
     */
    public function updateSkill(int $userId, int $skillId, array $data): int {
        $allowed = ['hourly_rate', 'level'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return 0;
        }

        // Build UPDATE … WHERE user_id = ? AND skill_id = ?
        $setClause = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updateData)));
        $sql = "UPDATE user_skill SET {$setClause} WHERE user_id = ? AND skill_id = ?";
        $params = array_merge(array_values($updateData), [$userId, $skillId]);

        $stmt = $this->db->execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Remove a skill from a tutor's profile.
     * Blocked if the tutor has active (Pending/Confirmed) bookings for this skill.
     *
     * @return int  Rows deleted (1 = success, 0 = not found)
     * @throws RuntimeException if active bookings exist for this skill
     */
    public function removeSkill(int $userId, int $skillId): int {
        $activeBookings = (int) $this->db->fetchColumn(
            '
            SELECT COUNT(*) FROM booking
            WHERE tutor_id = ? AND skill_id = ? AND status IN ("Pending", "Confirmed")
            ',
            [$userId, $skillId]
        );

        if ($activeBookings > 0) {
            throw new RuntimeException(
                'Cannot remove this skill while you have active or pending bookings for it.'
            );
        }

        return $this->db->delete('user_skill', ['user_id' => $userId, 'skill_id' => $skillId]);
    }

    /**
     * Get all skills offered by a specific user (tutor profile view).
     */
    public function findByUser(int $userId): array {
        return $this->db->fetchAll(
            '
            SELECT us.id, us.skill_id, us.hourly_rate, us.level, us.created_at,
                   s.name     AS skill_name,
                   s.category AS skill_category
            FROM user_skill us
            INNER JOIN skill s ON us.skill_id = s.id
            WHERE us.user_id = ?
            ORDER BY s.category, s.name
            ',
            [$userId]
        );
    }

    /**
     * Find a single user_skill record.
     * Returns null if the user does not offer that skill.
     */
    public function findOne(int $userId, int $skillId): ?array {
        return $this->db->fetchOne(
            '
            SELECT us.*, s.name AS skill_name, s.category
            FROM user_skill us
            INNER JOIN skill s ON us.skill_id = s.id
            WHERE us.user_id = ? AND us.skill_id = ?
            ',
            [$userId, $skillId]
        );
    }

    /**
     * Get all tutors who offer a specific skill, with aggregated rating.
     * Supports optional filtering by faculty, min/max rate, and minimum rating.
     * This is the core query that powers the Search & Discovery page.
     */
    public function findTutorsBySkill(
        int    $skillId,
        ?string $faculty   = null,
        ?float  $minRate   = null,
        ?float  $maxRate   = null,
        ?float  $minRating = null
    ): array {
        $sql = '
            SELECT
                u.id, u.name, u.photo_url, u.bio, u.facility, u.role,
                us.hourly_rate, us.level,
                COALESCE(AVG(r.rating), 0) AS avg_rating,
                COUNT(DISTINCT r.id)        AS review_count
            FROM user_skill us
            INNER JOIN user u ON us.user_id = u.id
            LEFT JOIN booking b ON b.tutor_id = u.id AND b.skill_id = us.skill_id
            LEFT JOIN review  r ON r.booking_id = b.id
            WHERE us.skill_id = ?
              AND u.role IN ("Tutor", "Learner")
        ';

        $params = [$skillId];

        if ($faculty !== null) {
            $sql    .= ' AND u.facility = ?';
            $params[] = $faculty;
        }
        if ($minRate !== null) {
            $sql    .= ' AND us.hourly_rate >= ?';
            $params[] = $minRate;
        }
        if ($maxRate !== null) {
            $sql    .= ' AND us.hourly_rate <= ?';
            $params[] = $maxRate;
        }

        $sql .= ' GROUP BY u.id, us.id';

        if ($minRating !== null) {
            $sql    .= ' HAVING avg_rating >= ?';
            $params[] = $minRating;
        }

        $sql .= ' ORDER BY avg_rating DESC, review_count DESC';

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get all skills in the catalog (skill table), grouped by category.
     * Used to populate the category sidebar and skill picker dropdowns.
     */
    public function getAllSkillsByCategory(): array {
        $rows = $this->db->fetchAll(
            'SELECT id, name, category FROM skill ORDER BY category, name'
        );

        // Group into ['Programming' => [...], 'Mathematics' => [...], ...]
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['category']][] = $row;
        }

        return $grouped;
    }
}
