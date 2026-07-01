<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use App\Helpers\JwtHelper;
use PDO;

class AuthService
{
    private UserRepository $users;
    private JwtHelper $jwt;
    private int $accessTtl;
    private int $refreshTtl;
    private PDO $pdo;

    public function __construct(UserRepository $users, JwtHelper $jwt, PDO $pdo, int $accessTtl, int $refreshTtl)
    {
        $this->users = $users;
        $this->jwt = $jwt;
        $this->pdo = $pdo;
        $this->accessTtl = $accessTtl;
        $this->refreshTtl = $refreshTtl;
    }

    public function register(array $data): array
    {
        if ($this->users->findByEmail($data['email'])) {
            throw new \Exception('Email already registered');
        }

        $user = new User([
            'email' => filter_var($data['email'], FILTER_VALIDATE_EMAIL),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'bio' => $data['bio'] ?? null,
            'profile_photo' => $data['profile_photo'] ?? null,
            'faculty' => $data['faculty'] ?? null,
            'year' => $data['year'] ?? null,
        ]);

        $id = $this->users->create($user);
        $user->id = $id;
        $this->users->assignRole($id, 'Learner');
        $this->users->createWallet($id);

        return $this->issueTokens($user);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !$user->is_active) {
            throw new \Exception('Invalid credentials');
        }

        if (!password_verify($password, $user->password_hash)) {
            throw new \Exception('Invalid credentials');
        }

        return $this->issueTokens($user);
    }

    public function refresh(string $refreshToken): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM refresh_tokens WHERE token = :token AND revoked = 0 AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([':token' => $refreshToken]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \Exception('Invalid refresh token');
        }

        $user = $this->users->findById((int)$row['user_id']);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $roles = $this->users->getRoles($user->id);
        $accessToken = $this->jwt->issueAccessToken(['sub' => $user->id, 'roles' => $roles], $this->accessTtl);

        return ['access_token' => $accessToken];
    }

    public function revokeRefreshToken(string $token): void
    {
        $stmt = $this->pdo->prepare('UPDATE refresh_tokens SET revoked = 1 WHERE token = :token');
        $stmt->execute([':token' => $token]);
    }

    /** Mock forgot-password: generates a reset token returned in the response. */
    public function forgotPassword(string $email): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return ['message' => 'If the email exists, a reset link has been sent'];
        }

        $token = bin2hex(random_bytes(32));
        $this->users->setPasswordResetToken($user->id, $token);

        return [
            'message' => 'If the email exists, a reset link has been sent',
            'reset_token' => $token,
            'expires_in' => '1 hour',
        ];
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->users->findByResetToken($token);
        if (!$user) {
            throw new \Exception('Invalid or expired reset token');
        }

        if (strlen($newPassword) < 6) {
            throw new \Exception('Password must be at least 6 characters');
        }

        $user->password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->users->update($user->id, $user);
        $this->users->clearPasswordResetToken($user->id);
    }

    private function issueTokens(User $user): array
    {
        $roles = $this->users->getRoles($user->id);
        if (empty($roles)) {
            $roles = ['Learner'];
        }

        $accessToken = $this->jwt->issueAccessToken(['sub' => $user->id, 'roles' => $roles], $this->accessTtl);
        $refreshToken = bin2hex(random_bytes(32));

        $stmt = $this->pdo->prepare(
            'INSERT INTO refresh_tokens (user_id, token, expires_at, created_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL :ttl SECOND), NOW())'
        );
        $stmt->execute([':user_id' => $user->id, ':token' => $refreshToken, ':ttl' => $this->refreshTtl]);

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'roles' => $roles,
        ];
    }
}
