<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(User $user): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, bio, profile_photo, faculty, year, is_active, created_at, updated_at) VALUES (:email, :password_hash, :first_name, :last_name, :bio, :profile_photo, :faculty, :year, :is_active, NOW(), NOW())');
        $stmt->execute([
            ':email' => $user->email,
            ':password_hash' => $user->password_hash,
            ':first_name' => $user->first_name,
            ':last_name' => $user->last_name,
            ':bio' => $user->bio,
            ':profile_photo' => $user->profile_photo,
            ':faculty' => $user->faculty,
            ':year' => $user->year,
            ':is_active' => $user->is_active ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }

        return new User($row);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }

        return new User($row);
    }

    public function update(int $id, User $user): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET first_name = :first_name, last_name = :last_name, bio = :bio, profile_photo = :profile_photo, faculty = :faculty, year = :year, password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':first_name' => $user->first_name,
            ':last_name' => $user->last_name,
            ':bio' => $user->bio,
            ':profile_photo' => $user->profile_photo,
            ':faculty' => $user->faculty,
            ':year' => $user->year,
            ':password_hash' => $user->password_hash,
        ]);
    }

    /** @return string[] */
    public function getRoles(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function assignRole(int $userId, string $roleName): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $roleName]);
        $roleId = $stmt->fetchColumn();
        if (!$roleId) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
        );
        $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
    }

    public function createWallet(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO wallets (user_id, balance, currency, updated_at) VALUES (:user_id, 0.00, :currency, NOW())'
        );
        $stmt->execute([':user_id' => $userId, ':currency' => 'USD']);
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, GROUP_CONCAT(r.name) AS roles FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             GROUP BY u.id ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByRole(string $roleName, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, GROUP_CONCAT(r2.name) AS roles FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id AND r.name = :role
             LEFT JOIN user_roles ur2 ON u.id = ur2.user_id
             LEFT JOIN roles r2 ON ur2.role_id = r2.id
             GROUP BY u.id ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':role', $roleName);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn();
    }

    public function countByRole(string $roleName): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT u.id) FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id WHERE r.name = :role'
        );
        $stmt->execute([':role' => $roleName]);
        return (int)$stmt->fetchColumn();
    }

    public function countBookings(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM bookings');
        return (int)$stmt->fetchColumn();
    }

    public function countBookingsByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM bookings WHERE status = :status');
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function setActive(int $userId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $stmt->execute([':id' => $userId, ':active' => $active ? 1 : 0]);
    }

    public function setPasswordResetToken(int $userId, string $token): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_reset_token = :token, password_reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id'
        );
        $stmt->execute([':id' => $userId, ':token' => $token]);
    }

    public function findByResetToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW() LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new User($row) : null;
    }

    public function clearPasswordResetToken(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $userId]);
    }

    public function countCompletedSessionsForTutor(int $tutorId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM bookings WHERE tutor_id = :tutor_id AND status = 'completed'"
        );
        $stmt->execute([':tutor_id' => $tutorId]);
        return (int)$stmt->fetchColumn();
    }
}
