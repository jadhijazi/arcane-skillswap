<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\WalletService;
use App\Helpers\ResponseHelper;

class WalletController
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function getBalance(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $wallet = $this->walletService->getWallet($userId);
        $balance = $this->walletService->getWalletBalance($userId);
        return ResponseHelper::json($response, true, 'Wallet retrieved', ['wallet' => $wallet, 'balance' => $balance]);
    }

    public function getTransactions(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $result = $this->walletService->getTransactionHistory($userId, $page, $perPage);
        return ResponseHelper::json($response, true, 'Transactions retrieved', $result);
    }

    public function getReport(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $report = $this->walletService->getWalletReport($userId);
        return ResponseHelper::json($response, true, 'Wallet report', $report);
    }
}
