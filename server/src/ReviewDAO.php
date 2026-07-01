<?php
/**
 * ============================================================================
 * Review Data Access Object (DAO/Repository)
 * ============================================================================
 *
 * Handles all database operations for Review entities.
 * Reviews can only be created for Completed bookings (enforced here).
 *
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

class ReviewDAO {
    private Database $db;

    public function __construct(Database $database) {
        $this->db = $database;
    }

    /**
     * Create a review for a completed booking.
     * Enforces: booking must be Completed and not already reviewed.
     *
     * @return int  New review ID
     * @throws RuntimeException if booking is not eligible
     */
    public function create(int $bookingId, int $rating, string $comment = ''): int {
        // Guard: booking must exist and be Completed
        $booking = $this->db->fetchOne(
            'SELECT id, status FROM booking WHERE id = ?',
            [$bookingId]
        );

        if (!$booking) {
            throw new RuntimeException('Booking not found.');
        }
        if ($booking['status'] !== 'Completed') {
            throw new RuntimeException('Reviews can only be submitted for completed sessions.');
        }

        // Guard: no duplicate reviews (booking_id is UNIQUE in schema)
        $existing = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM review WHERE booking_id = ?',
            [$bookingId]
        );
        if ((int) $existing > 0) {
            throw new RuntimeException('A review has already been submitted for this booking.');
        }

        return $this->db->insert('review', [
            'booking_id' => $bookingId,
            'rating'     => $rating,
            'comment'    => $comment,
        ]);
    }

    /**
     * Find a single review by its ID.
     */
    public function findById(int $reviewId): ?array {
        return $this->db->fetchOne(
            '
            SELECT r.*, b.learner_id, b.tutor_id, b.skill_id,
                   s.name AS skill_name,
                   learner.name AS learner_name
            FROM review r
            INNER JOIN booking b ON r.booking_id = b.id
            INNER JOIN skill   s ON b.skill_id   = s.id
            INNER JOIN user learner ON b.learner_id = learner.id
            WHERE r.id = ?
            ',
            [$reviewId]
        );
    }

    /**
     * Find the review for a specific booking (if it exists).
     */
    public function findByBooking(int $bookingId): ?array {
        return $this->db->fetchOne(
            'SELECT * FROM review WHERE booking_id = ?',
            [$bookingId]
        );
    }

    /**
     * Get all reviews written about a tutor, newest first.
     * Joins learner name and skill name for display.
     */
    public function findByTutor(int $tutorId): array {
        return $this->db->fetchAll(
            '
            SELECT r.id, r.rating, r.comment, r.created_at,
                   learner.name  AS learner_name,
                   learner.photo_url AS learner_photo,
                   s.name        AS skill_name,
                   b.scheduled_at
            FROM review r
            INNER JOIN booking b ON r.booking_id = b.id
            INNER JOIN user learner ON b.learner_id = learner.id
            INNER JOIN skill   s   ON b.skill_id   = s.id
            WHERE b.tutor_id = ?
            ORDER BY r.created_at DESC
            ',
            [$tutorId]
        );
    }

    /**
     * Get the average rating and total review count for a tutor.
     * Used on profile cards and search results.
     */
    public function getTutorRatingSummary(int $tutorId): array {
        return $this->db->fetchOne(
            '
            SELECT
                COALESCE(AVG(r.rating), 0)  AS avg_rating,
                COUNT(r.id)                  AS total_reviews
            FROM review r
            INNER JOIN booking b ON r.booking_id = b.id
            WHERE b.tutor_id = ?
            ',
            [$tutorId]
        ) ?? ['avg_rating' => 0, 'total_reviews' => 0];
    }

    /**
     * Get rating breakdown (count per star) for a tutor — useful for a
     * star-distribution bar chart on the profile page.
     *
     * Returns: [['stars' => 5, 'count' => 12], ['stars' => 4, 'count' => 8], …]
     */
    public function getTutorRatingBreakdown(int $tutorId): array {
        return $this->db->fetchAll(
            '
            SELECT r.rating AS stars, COUNT(*) AS count
            FROM review r
            INNER JOIN booking b ON r.booking_id = b.id
            WHERE b.tutor_id = ?
            GROUP BY r.rating
            ORDER BY r.rating DESC
            ',
            [$tutorId]
        );
    }

    /**
     * Get all reviews written BY a specific learner.
     */
    public function findByLearner(int $learnerId): array {
        return $this->db->fetchAll(
            '
            SELECT r.id, r.rating, r.comment, r.created_at,
                   tutor.name     AS tutor_name,
                   tutor.photo_url AS tutor_photo,
                   s.name         AS skill_name,
                   b.scheduled_at
            FROM review r
            INNER JOIN booking b ON r.booking_id = b.id
            INNER JOIN user tutor ON b.tutor_id  = tutor.id
            INNER JOIN skill   s  ON b.skill_id  = s.id
            WHERE b.learner_id = ?
            ORDER BY r.created_at DESC
            ',
            [$learnerId]
        );
    }

    /**
     * Update an existing review (within an edit window — enforced by caller).
     *
     * @return int  Number of rows affected (1 = success, 0 = not found)
     */
    public function update(int $reviewId, int $rating, string $comment): int {
        return $this->db->update(
            'review',
            ['rating' => $rating, 'comment' => $comment],
            ['id' => $reviewId]
        );
    }

    /**
     * Delete a review (Admin use only).
     */
    public function delete(int $reviewId): int {
        return $this->db->delete('review', ['id' => $reviewId]);
    }
}
