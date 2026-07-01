<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReviewRepository;
use App\Repositories\BookingRepository;
use App\Models\Review;

class ReviewService
{
    private ReviewRepository $repo;
    private BookingRepository $bookingRepo;
    private NotificationService $notificationService;

    public function __construct(
        ReviewRepository $repo,
        BookingRepository $bookingRepo,
        NotificationService $notificationService
    ) {
        $this->repo = $repo;
        $this->bookingRepo = $bookingRepo;
        $this->notificationService = $notificationService;
    }

    public function createReview(int $reviewerId, array $data): Review
    {
        $bookingId = (int)($data['booking_id'] ?? 0);
        if ($bookingId <= 0) {
            throw new \Exception('booking_id is required');
        }

        // Verify booking exists and is completed
        $booking = $this->bookingRepo->findById($bookingId);
        if (!$booking) {
            throw new \Exception('Booking not found');
        }
        if ($booking->status !== 'completed') {
            throw new \Exception('Only completed bookings can be reviewed');
        }

        // Ensure reviewer is either learner or tutor
        if ($booking->learner_id !== $reviewerId && $booking->tutor_id !== $reviewerId) {
            throw new \Exception('Unauthorized to review this booking');
        }

        // Check if review already exists
        $existing = $this->repo->findByBooking($bookingId);
        if ($existing) {
            throw new \Exception('Review already exists for this booking');
        }

        $rating = (int)($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('Rating must be between 1 and 5');
        }

        $review = new Review([
            'booking_id' => $bookingId,
            'reviewer_id' => $reviewerId,
            'rating' => $rating,
            'comment' => $data['comment'] ?? null,
        ]);

        $id = $this->repo->create($review);
        $review->id = $id;

        $this->notificationService->createNotification(
            $booking->tutor_id,
            'review.created',
            ['review_id' => $id, 'booking_id' => $bookingId, 'rating' => $rating]
        );

        return $review;
    }

    public function getReview(int $id): Review
    {
        $review = $this->repo->findById($id);
        if (!$review) {
            throw new \Exception('Review not found');
        }
        return $review;
    }

    public function getTutorReviews(int $tutorId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $reviews = $this->repo->findForTutor($tutorId, $perPage, $offset);
        $avgRating = $this->repo->getAverageRating($tutorId);
        $count = $this->repo->getCount($tutorId);
        return [
            'reviews' => $reviews,
            'average_rating' => $avgRating,
            'total_reviews' => $count,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}
