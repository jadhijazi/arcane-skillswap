<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\WalletRepository;
use App\Models\User;

class UserService
{
    private UserRepository $repo;
    private ReviewRepository $reviewRepo;
    private WalletRepository $walletRepo;

    public function __construct(
        UserRepository $repo,
        ReviewRepository $reviewRepo,
        WalletRepository $walletRepo
    ) {
        $this->repo = $repo;
        $this->reviewRepo = $reviewRepo;
        $this->walletRepo = $walletRepo;
    }

    public function getUser(int $id): User
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            throw new \Exception('User not found');
        }
        return $user;
    }

    public function updateUser(int $id, array $data): User
    {
        $user = $this->getUser($id);

        if (isset($data['first_name'])) {
            $user->first_name = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $user->last_name = $data['last_name'];
        }
        if (isset($data['bio'])) {
            $user->bio = $data['bio'];
        }
        if (isset($data['profile_photo'])) {
            $user->profile_photo = $data['profile_photo'];
        }
        if (isset($data['faculty'])) {
            $user->faculty = $data['faculty'];
        }
        if (isset($data['year'])) {
            $user->year = $data['year'];
        }

        $this->repo->update($id, $user);
        return $user;
    }

    public function getUserProfile(int $id): array
    {
        $user = $this->getUser($id);
        $avgRating = $this->reviewRepo->getAverageRating($id);
        $reviewCount = $this->reviewRepo->getCount($id);

        return [
            'user' => $user,
            'roles' => $this->repo->getRoles($id),
            'average_rating' => $avgRating,
            'total_reviews' => $reviewCount,
            'total_sessions' => $this->repo->countCompletedSessionsForTutor($id),
            'earnings' => $this->walletRepo->getBalance($this->walletRepo->getOrCreate($id)->id),
        ];
    }

    public function changePassword(int $id, string $oldPassword, string $newPassword): void
    {
        $user = $this->getUser($id);

        if (!password_verify($oldPassword, $user->password_hash)) {
            throw new \Exception('Current password is incorrect');
        }

        if (strlen($newPassword) < 6) {
            throw new \Exception('New password must be at least 6 characters');
        }

        $user->password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->repo->update($id, $user);
    }
}
