<?php
/**
 * ============================================================================
 * Booking Data Access Object (DAO/Repository)
 * ============================================================================
 * 
 * Handles all database operations for Booking entities.
 * Manages the complete booking lifecycle: Pending → Confirmed → Completed → Cancelled
 * 
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

class BookingDAO {
    private Database $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    /**
     * Create a new booking (request)
     */
    public function create(
        int $learnerId,
        int $tutorId,
        int $skillId,
        string $scheduledAt,
        int $durationMinutes,
        float $total
    ): int {
        $data = [
            'learner_id' => $learnerId,
            'tutor_id' => $tutorId,
            'skill_id' => $skillId,
            'scheduled_at' => $scheduledAt,
            'duration' => $durationMinutes,
            'total' => $total,
            'status' => 'Pending'
        ];
        
        return $this->db->insert('booking', $data);
    }
    
    /**
     * Find booking by ID with full details
     */
    public function findById(int $bookingId): ?array {
        return $this->db->fetchOne(
            '
            SELECT b.*, 
                   learner.name as learner_name, learner.email as learner_email,
                   tutor.name as tutor_name, tutor.email as tutor_email,
                   skill.name as skill_name, skill.category as skill_category
            FROM booking b
            INNER JOIN user learner ON b.learner_id = learner.id
            INNER JOIN user tutor ON b.tutor_id = tutor.id
            INNER JOIN skill ON b.skill_id = skill.id
            WHERE b.id = ?
            ',
            [$bookingId]
        );
    }
    
    /**
     * Get all bookings for a learner
     */
    public function findByLearner(int $learnerId, ?string $status = null): array {
        $sql = '
            SELECT b.*, 
                   tutor.name as tutor_name, tutor.photo_url as tutor_photo,
                   skill.name as skill_name,
                   COALESCE(r.rating, NULL) as review_rating
            FROM booking b
            INNER JOIN user tutor ON b.tutor_id = tutor.id
            INNER JOIN skill ON b.skill_id = skill.id
            LEFT JOIN review r ON b.id = r.booking_id
            WHERE b.learner_id = ?
        ';
        
        $params = [$learnerId];
        
        if ($status) {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY b.scheduled_at DESC';
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get all bookings for a tutor (incoming requests and scheduled sessions)
     */
    public function findByTutor(int $tutorId, ?string $status = null): array {
        $sql = '
            SELECT b.*, 
                   learner.name as learner_name, learner.photo_url as learner_photo,
                   skill.name as skill_name
            FROM booking b
            INNER JOIN user learner ON b.learner_id = learner.id
            INNER JOIN skill ON b.skill_id = skill.id
            WHERE b.tutor_id = ?
        ';
        
        $params = [$tutorId];
        
        if ($status) {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY 
                    CASE 
                        WHEN b.status = "Pending" THEN 1
                        WHEN b.status = "Confirmed" THEN 2
                        ELSE 3
                    END,
                    b.scheduled_at ASC';
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get available time slots for a tutor on a specific date
     */
    public function getTutorAvailableSlots(int $tutorId, string $date): array {
        // This is a simplified version - full implementation would depend on
        // a separate availability/schedule table
        // For now, we return all 1-hour slots, excluding booked times
        
        $bookedSlots = $this->db->fetchAll(
            '
            SELECT 
                HOUR(b.scheduled_at) as hour,
                CEIL(b.duration / 60) as hours_booked
            FROM booking b
            WHERE b.tutor_id = ? 
                AND DATE(b.scheduled_at) = ?
                AND b.status IN ("Pending", "Confirmed")
            ',
            [$tutorId, $date]
        );
        
        // Return array of available hours (9 AM to 5 PM)
        $availableHours = [];
        $bookedHours = [];
        
        foreach ($bookedSlots as $slot) {
            for ($h = $slot['hour']; $h < $slot['hour'] + $slot['hours_booked']; $h++) {
                $bookedHours[$h] = true;
            }
        }
        
        for ($h = 9; $h < 17; $h++) {
            if (!isset($bookedHours[$h])) {
                $availableHours[] = [
                    'hour' => $h,
                    'time' => sprintf('%02d:00', $h),
                    'available' => true
                ];
            }
        }
        
        return $availableHours;
    }
    
    /**
     * Accept/confirm a booking request
     */
    public function confirmBooking(int $bookingId): int {
        return $this->db->update(
            'booking',
            ['status' => 'Confirmed'],
            ['id' => $bookingId]
        );
    }
    
    /**
     * Decline a booking request
     */
    public function declineBooking(int $bookingId): int {
        return $this->db->update(
            'booking',
            ['status' => 'Cancelled'],
            ['id' => $bookingId]
        );
    }
    
    /**
     * Mark booking as completed (after session is done)
     */
    public function completeBooking(int $bookingId): int {
        return $this->db->update(
            'booking',
            ['status' => 'Completed'],
            ['id' => $bookingId]
        );
    }
    
    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId): int {
        return $this->db->update(
            'booking',
            ['status' => 'Cancelled'],
            ['id' => $bookingId]
        );
    }
    
    /**
     * Get earnings breakdown for a tutor
     */
    public function getTutorEarnings(int $tutorId, ?string $startDate = null, ?string $endDate = null): array {
        $sql = '
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed_bookings,
                COALESCE(SUM(CASE WHEN status = "Completed" THEN total ELSE 0 END), 0) as gross_earnings,
                COALESCE(SUM(CASE WHEN status = "Completed" THEN total * 0.1 ELSE 0 END), 0) as platform_commission,
                COALESCE(SUM(CASE WHEN status = "Completed" THEN total * 0.9 ELSE 0 END), 0) as net_earnings
            FROM booking
            WHERE tutor_id = ?
        ';
        
        $params = [$tutorId];
        
        if ($startDate) {
            $sql .= ' AND DATE(scheduled_at) >= ?';
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= ' AND DATE(scheduled_at) <= ?';
            $params[] = $endDate;
        }
        
        return $this->db->fetchOne($sql, $params) ?? [
            'total_bookings' => 0,
            'completed_bookings' => 0,
            'gross_earnings' => 0,
            'platform_commission' => 0,
            'net_earnings' => 0
        ];
    }
    
    /**
     * Get all bookings within a date range (for calendar export)
     */
    public function getBookingsInRange(int $userId, string $startDate, string $endDate): array {
        return $this->db->fetchAll(
            '
            SELECT b.id, b.scheduled_at, b.duration, b.status,
                   CASE 
                       WHEN b.learner_id = ? THEN tutor.name
                       ELSE learner.name
                   END as other_party_name,
                   skill.name as skill_name
            FROM booking b
            INNER JOIN user tutor ON b.tutor_id = tutor.id
            INNER JOIN user learner ON b.learner_id = learner.id
            INNER JOIN skill ON b.skill_id = skill.id
            WHERE (b.learner_id = ? OR b.tutor_id = ?)
                AND b.scheduled_at BETWEEN ? AND ?
                AND b.status = "Confirmed"
            ORDER BY b.scheduled_at ASC
            ',
            [$userId, $userId, $userId, $startDate, $endDate]
        );
    }
    
    /**
     * Check if user already has a confirmed/pending booking at a specific time
     */
    public function hasConflict(int $tutorId, string $scheduledAt, int $durationMinutes): bool {
        // Checks whether the new slot [scheduledAt, scheduledAt + durationMinutes)
        // overlaps with any existing Pending/Confirmed booking for the tutor.
        $sql = '
            SELECT COUNT(*) AS conflict_count
            FROM booking b
            WHERE b.tutor_id = ?
              AND b.status IN ("Pending", "Confirmed")
              AND b.scheduled_at                                        < DATE_ADD(?, INTERVAL ? MINUTE)
              AND DATE_ADD(b.scheduled_at, INTERVAL b.duration MINUTE) > ?
        ';

        $count = $this->db->fetchColumn(
            $sql,
            [$tutorId, $scheduledAt, $durationMinutes, $scheduledAt]
        );

        return (int) $count > 0;
    }
}