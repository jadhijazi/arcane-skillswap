<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\WalletRepository;
use App\Models\Wallet;

class WalletService
{
    private WalletRepository $repo;
    private float $platformCommission;

    public function __construct(WalletRepository $repo, float $platformCommission = 0.10)
    {
        $this->repo = $repo;
        $this->platformCommission = $platformCommission;
    }

    public function getWallet(int $userId): Wallet
    {
        return $this->repo->getOrCreate($userId);
    }

    public function creditTutorEarnings(int $tutorId, float $bookingAmount): void
    {
        $commission = $bookingAmount * $this->platformCommission;
        $tutorEarnings = $bookingAmount - $commission;

        $wallet = $this->repo->getOrCreate($tutorId);
        $this->repo->credit($wallet->id, $tutorEarnings, 'Booking payment (10% platform commission deducted)');
    }

    public function debitLearner(int $learnerId, float $amount, string $description = ''): void
    {
        if (!$this->repo->hasSufficientBalance($learnerId, $amount)) {
            throw new \Exception('Insufficient wallet balance');
        }

        $wallet = $this->repo->getOrCreate($learnerId);
        $this->repo->debit($wallet->id, $amount, $description);
    }

    public function refundLearner(int $learnerId, float $amount, string $description = ''): void
    {
        $wallet = $this->repo->getOrCreate($learnerId);
        $this->repo->credit($wallet->id, $amount, $description);
    }

    public function hasSufficientBalance(int $userId, float $amount): bool
    {
        return $this->repo->hasSufficientBalance($userId, $amount);
    }

    public function getWalletBalance(int $userId): float
    {
        $wallet = $this->repo->getOrCreate($userId);
        return $this->repo->getBalance($wallet->id);
    }

    public function getTransactionHistory(int $userId, int $page = 1, int $perPage = 50): array
    {
        $wallet = $this->repo->getOrCreate($userId);
        $offset = ($page - 1) * $perPage;
        $transactions = $this->repo->getTransactions($wallet->id, $perPage, $offset);
        return [
            'transactions' => $transactions,
            'balance' => $this->repo->getBalance($wallet->id),
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function getWalletReport(int $userId): array
    {
        $wallet = $this->repo->getOrCreate($userId);
        $transactions = $this->repo->getTransactions($wallet->id, 100, 0);

        $credits = 0.0;
        $debits = 0.0;
        foreach ($transactions as $tx) {
            if ($tx->type === 'credit') {
                $credits += $tx->amount;
            } else {
                $debits += $tx->amount;
            }
        }

        return [
            'balance' => $this->repo->getBalance($wallet->id),
            'total_credits' => $credits,
            'total_debits' => $debits,
            'transaction_count' => count($transactions),
        ];
    }
}
