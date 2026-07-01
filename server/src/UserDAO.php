<?php
/**
 * ============================================================================
 * User Data Access Object (DAO/Repository)
 * ============================================================================
 * 
 * Handles all database operations for User entities using the repository pattern.
 * All queries use prepared statements to prevent SQL injection.
 * 
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

class UserDAO {
    private Database $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    /**
     * Create a new user
     * 
     * @param string $name User's full name
     * @param string $email User's email (unique)
     * @param string $passwordHash Bcrypt-hashed password
     * @param string $facility User's faculty (Computing, Engineering, etc.)
     * @param string $role User role (Learner, Tutor, Admin)
     * @return int User ID
     */
    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $facility,
        string $role = 'Learner'
    ): int {
        $data = [
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'facility' => $facility,
            'role' => $role,
            'wallet_balance' => 0.00
        ];
        
        return $this->db->insert('user', $data);
    }
    
    /**
     * Find user by ID
     */
    public function findById(int $userId): ?array {
        return $this->db->fetchOne(
            'SELECT * FROM user WHERE id = ?',
            [$userId]
        );
    }
    
    /**
     * Find user by email (for login)
     */
    public function findByEmail(string $email): ?array {
        return $this->db->fetchOne(
            'SELECT * FROM user WHERE email = ?',
            [$email]
        );
    }
    
    /**
     * Get all tutors in a specific skill, ordered by rating
     */
    public function findTutorsBySkill(int $skillId, ?string $facility = null, ?float $minRate = null, ?float $maxRate = null): array {
        $sql = '
            SELECT DISTINCT u.*, 
                   us.hourly_rate, 
                   us.level,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.id) as review_count
            FROM user u
            INNER JOIN user_skill us ON u.id = us.user_id
            LEFT JOIN booking b ON u.id = b.tutor_id
            LEFT JOIN review r ON b.id = r.booking_id
            WHERE us.skill_id = ? AND u.role IN ("Tutor", "Learner")
        ';
        
        $params = [$skillId];
        
        if ($facility) {
            $sql .= ' AND u.facility = ?';
            $params[] = $facility;
        }
        
        if ($minRate !== null) {
            $sql .= ' AND us.hourly_rate >= ?';
            $params[] = $minRate;
        }
        
        if ($maxRate !== null) {
            $sql .= ' AND us.hourly_rate <= ?';
            $params[] = $maxRate;
        }
        
        $sql .= ' GROUP BY u.id, us.id ORDER BY avg_rating DESC, review_count DESC';
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Search users by name (for discovery)
     */
    public function searchByName(string $query): array {
        return $this->db->fetchAll(
            'SELECT * FROM user WHERE name LIKE ? AND role IN ("Tutor", "Learner") LIMIT 20',
            ["%{$query}%"]
        );
    }
    
    /**
     * Get all tutors in a facility
     */
    public function getTutorsByFacility(string $facility): array {
        return $this->db->fetchAll(
            'SELECT * FROM user WHERE facility = ? AND role IN ("Tutor", "Learner") ORDER BY name ASC',
            [$facility]
        );
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): int {
        $allowedFields = ['name', 'bio', 'photo_url', 'facility'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return 0;
        }
        
        return $this->db->update('user', $updateData, ['id' => $userId]);
    }
    
    /**
     * Update wallet balance (after booking completion or withdrawal)
     */
    public function updateWalletBalance(int $userId, float $amount): int {
        return $this->db->update(
            'user',
            ['wallet_balance' => $amount],
            ['id' => $userId]
        );
    }
    
    /**
     * Get wallet balance for a user
     */
    public function getWalletBalance(int $userId): float {
        $result = $this->db->fetchColumn(
            'SELECT wallet_balance FROM user WHERE id = ?',
            [$userId]
        );
        return (float) ($result ?? 0);
    }
    
    /**
     * Add role to user (makes learner also a tutor)
     */
    public function addRole(int $userId, string $role): int {
        // Get current role
        $currentRole = $this->db->fetchColumn(
            'SELECT role FROM user WHERE id = ?',
            [$userId]
        );
        
        if ($currentRole === 'Admin') {
            return 0; // Admins can't have additional roles
        }
        
        $newRole = ($currentRole === 'Learner' && $role === 'Tutor') ? 'Tutor' : $currentRole;
        
        return $this->db->update('user', ['role' => $newRole], ['id' => $userId]);
    }
    
    /**
     * Get user's skills with rates
     */
    public function getUserSkills(int $userId): array {
        return $this->db->fetchAll(
            '
            SELECT us.*, s.name as skill_name, s.category
            FROM user_skill us
            INNER JOIN skill s ON us.skill_id = s.id
            WHERE us.user_id = ?
            ORDER BY s.category, s.name
            ',
            [$userId]
        );
    }
    
    /**
     * Get tutor statistics (earnings, sessions completed, rating)
     */
    public function getTutorStats(int $tutorId): array {
        $stats = $this->db->fetchOne(
            '
            SELECT 
                COUNT(DISTINCT b.id) as total_sessions,
                SUM(CASE WHEN b.status = "Completed" THEN 1 ELSE 0 END) as completed_sessions,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(DISTINCT r.id) as total_reviews,
                COALESCE(SUM(CASE WHEN b.status = "Completed" THEN b.total * 0.9 ELSE 0 END), 0) as total_earnings
            FROM booking b
            LEFT JOIN review r ON b.id = r.booking_id
            WHERE b.tutor_id = ?
            ',
            [$tutorId]
        );
        
        return $stats ?? [
            'total_sessions' => 0,
            'completed_sessions' => 0,
            'avg_rating' => 0,
            'total_reviews' => 0,
            'total_earnings' => 0
        ];
    }
    
    /**
     * Verify if user can be deleted (admin only)
     */
    public function canDelete(int $userId): bool {
        // Users with active bookings cannot be deleted
        $activeBookings = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM booking WHERE (learner_id = ? OR tutor_id = ?) AND status IN ("Pending", "Confirmed")',
            [$userId, $userId]
        );
        
        return (int) $activeBookings === 0;
    }
    
    /**
     * Delete user (soft or hard)
     */
    public function delete(int $userId): int {
        return $this->db->delete('user', ['id' => $userId]);
    }
}