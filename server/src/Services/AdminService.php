<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\BookingRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\SkillRepository;
use App\Repositories\WalletRepository;
use App\Repositories\AuditLogRepository;
use App\Helpers\PaginationHelper;

class AdminService
{
    private UserRepository $users;
    private BookingRepository $bookings;
    private ReviewRepository $reviews;
    private SkillRepository $skills;
    private WalletRepository $wallets;
    private AuditLogRepository $auditLogs;

    public function __construct(
        UserRepository $users,
        BookingRepository $bookings,
        ReviewRepository $reviews,
        SkillRepository $skills,
        WalletRepository $wallets,
        AuditLogRepository $auditLogs
    ) {
        $this->users = $users;
        $this->bookings = $bookings;
        $this->reviews = $reviews;
        $this->skills = $skills;
        $this->wallets = $wallets;
        $this->auditLogs = $auditLogs;
    }

    public function getDashboardStats(): array
    {
        return [
            'total_users' => $this->users->countAll(),
            'total_tutors' => $this->users->countByRole('Tutor'),
            'total_bookings' => $this->users->countBookings(),
            'completed_bookings' => $this->users->countBookingsByStatus('completed'),
            'total_skills' => $this->skills->count(),
            'total_reviews' => $this->reviews->countAll(),
            'platform_balance' => $this->wallets->getPlatformCommissionTotal(),
        ];
    }

    public function listUsers(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $users = $this->users->findAll($perPage, $offset);
        $total = $this->users->countAll();

        return array_merge(
            ['users' => $users],
            PaginationHelper::meta($total, $page, $perPage)
        );
    }

    public function listTutors(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $tutors = $this->users->findByRole('Tutor', $perPage, $offset);
        $total = $this->users->countByRole('Tutor');

        return array_merge(
            ['tutors' => $tutors],
            PaginationHelper::meta($total, $page, $perPage)
        );
    }

    public function deactivateUser(int $userId, int $adminId, ?string $ip = null): void
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $this->users->setActive($userId, false);
        $this->auditLogs->log($adminId, 'user.deactivated', $ip, ['target_user_id' => $userId]);
    }

    public function activateUser(int $userId, int $adminId, ?string $ip = null): void
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $this->users->setActive($userId, true);
        $this->auditLogs->log($adminId, 'user.activated', $ip, ['target_user_id' => $userId]);
    }

    public function listBookings(int $page = 1, int $perPage = 20, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $bookings = $status
            ? $this->bookings->findByStatus($status, $perPage, $offset)
            : $this->bookings->findAll($perPage, $offset);
        $total = $status
            ? $this->bookings->countByStatus($status)
            : $this->bookings->countAll();

        return array_merge(
            ['bookings' => $bookings],
            PaginationHelper::meta($total, $page, $perPage)
        );
    }

    public function moderateReview(int $reviewId, int $adminId, ?string $ip = null): void
    {
        $review = $this->reviews->findById($reviewId);
        if (!$review) {
            throw new \Exception('Review not found');
        }

        $this->reviews->delete($reviewId);
        $this->auditLogs->log($adminId, 'review.deleted', $ip, ['review_id' => $reviewId]);
    }

    public function getWalletReport(): array
    {
        return $this->wallets->getPlatformReport();
    }

    public function getAuditLogs(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $logs = $this->auditLogs->findRecent($perPage, $offset);

        return [
            'logs' => $logs,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}
