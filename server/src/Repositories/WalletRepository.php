<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use PDO;

class WalletRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getOrCreate(int $userId): Wallet
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new Wallet($row);
        }

        $stmt = $this->pdo->prepare('INSERT INTO wallets (user_id, balance, currency, updated_at) VALUES (:user_id, 0.0, :currency, NOW())');
        $stmt->execute([':user_id' => $userId, ':currency' => 'USD']);
        $id = (int)$this->pdo->lastInsertId();

        return new Wallet([
            'id' => $id,
            'user_id' => $userId,
            'balance' => 0.0,
            'currency' => 'USD',
        ]);
    }

    public function credit(int $walletId, float $amount, string $description = ''): WalletTransaction
    {
        $stmt = $this->pdo->prepare('UPDATE wallets SET balance = balance + :amount WHERE id = :wallet_id');
        $stmt->execute([':wallet_id' => $walletId, ':amount' => $amount]);

        $stmt = $this->pdo->prepare('INSERT INTO wallet_transactions (wallet_id, amount, type, description, created_at) VALUES (:wallet_id, :amount, :type, :description, NOW())');
        $stmt->execute([':wallet_id' => $walletId, ':amount' => $amount, ':type' => 'credit', ':description' => $description]);

        $id = (int)$this->pdo->lastInsertId();
        return new WalletTransaction([
            'id' => $id,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
        ]);
    }

    public function debit(int $walletId, float $amount, string $description = ''): WalletTransaction
    {
        $stmt = $this->pdo->prepare('UPDATE wallets SET balance = balance - :amount WHERE id = :wallet_id');
        $stmt->execute([':wallet_id' => $walletId, ':amount' => $amount]);

        $stmt = $this->pdo->prepare('INSERT INTO wallet_transactions (wallet_id, amount, type, description, created_at) VALUES (:wallet_id, :amount, :type, :description, NOW())');
        $stmt->execute([':wallet_id' => $walletId, ':amount' => $amount, ':type' => 'debit', ':description' => $description]);

        $id = (int)$this->pdo->lastInsertId();
        return new WalletTransaction([
            'id' => $id,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'type' => 'debit',
            'description' => $description,
        ]);
    }

    public function getBalance(int $walletId): float
    {
        $stmt = $this->pdo->prepare('SELECT balance FROM wallets WHERE id = :wallet_id');
        $stmt->execute([':wallet_id' => $walletId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['balance'] : 0.0;
    }

    public function getTransactions(int $walletId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallet_transactions WHERE wallet_id = :wallet_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':wallet_id', $walletId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new WalletTransaction($row), $rows);
    }

    public function getPlatformReport(): array
    {
        $summaryStmt = $this->pdo->query(
            'SELECT SUM(w.balance) AS total_wallet_balance, COUNT(DISTINCT w.user_id) AS wallet_holders FROM wallets w'
        );
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $creditStmt = $this->pdo->query(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_transactions WHERE type = 'credit'"
        );
        $debitStmt = $this->pdo->query(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_transactions WHERE type = 'debit'"
        );
        $summary['total_credits'] = (float)($creditStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $summary['total_debits'] = (float)($debitStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stmt = $this->pdo->query(
            'SELECT wt.*, w.user_id FROM wallet_transactions wt
             JOIN wallets w ON wt.wallet_id = w.id
             ORDER BY wt.created_at DESC LIMIT 20'
        );
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'summary' => $summary,
            'recent_transactions' => $recent,
        ];
    }

    public function getPlatformCommissionTotal(): float
    {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE description LIKE '%commission%'"
        );
        return (float)$stmt->fetchColumn();
    }

    public function hasSufficientBalance(int $userId, float $amount): bool
    {
        $wallet = $this->getOrCreate($userId);
        return $this->getBalance($wallet->id) >= $amount;
    }
}
