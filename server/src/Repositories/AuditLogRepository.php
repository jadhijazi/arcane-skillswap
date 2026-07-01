<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AuditLogRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(?int $userId, string $action, ?string $ipAddress = null, ?array $meta = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, ip_address, meta, created_at) VALUES (:user_id, :action, :ip_address, :meta, NOW())'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':ip_address' => $ipAddress,
            ':meta' => $meta ? json_encode($meta) : null,
        ]);
    }

    public function findRecent(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
