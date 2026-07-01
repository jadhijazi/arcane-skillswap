<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\AdminService;
use App\Helpers\ResponseHelper;
use App\Helpers\PaginationHelper;

class AdminController
{
    private AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $stats = $this->adminService->getDashboardStats();
        return ResponseHelper::json($response, true, 'Dashboard statistics', $stats);
    }

    public function listUsers(Request $request, Response $response): Response
    {
        $pagination = PaginationHelper::resolve($request->getQueryParams());
        $result = $this->adminService->listUsers($pagination['page'], $pagination['per_page']);
        return ResponseHelper::json($response, true, 'Users retrieved', $result);
    }

    public function listTutors(Request $request, Response $response): Response
    {
        $pagination = PaginationHelper::resolve($request->getQueryParams());
        $result = $this->adminService->listTutors($pagination['page'], $pagination['per_page']);
        return ResponseHelper::json($response, true, 'Tutors retrieved', $result);
    }

    public function deactivateUser(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        try {
            $this->adminService->deactivateUser(
                (int)$args['id'],
                (int)$jwt->sub,
                $request->getServerParams()['REMOTE_ADDR'] ?? null
            );
            return ResponseHelper::json($response, true, 'User deactivated', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function activateUser(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        try {
            $this->adminService->activateUser(
                (int)$args['id'],
                (int)$jwt->sub,
                $request->getServerParams()['REMOTE_ADDR'] ?? null
            );
            return ResponseHelper::json($response, true, 'User activated', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function listBookings(Request $request, Response $response): Response
    {
        $pagination = PaginationHelper::resolve($request->getQueryParams());
        $status = $request->getQueryParams()['status'] ?? null;
        $result = $this->adminService->listBookings($pagination['page'], $pagination['per_page'], $status);
        return ResponseHelper::json($response, true, 'Bookings retrieved', $result);
    }

    public function deleteReview(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        try {
            $this->adminService->moderateReview(
                (int)$args['id'],
                (int)$jwt->sub,
                $request->getServerParams()['REMOTE_ADDR'] ?? null
            );
            return ResponseHelper::json($response, true, 'Review removed', (object)[]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function walletReport(Request $request, Response $response): Response
    {
        $report = $this->adminService->getWalletReport();
        return ResponseHelper::json($response, true, 'Wallet report', $report);
    }

    public function auditLogs(Request $request, Response $response): Response
    {
        $pagination = PaginationHelper::resolve($request->getQueryParams(), 50);
        $result = $this->adminService->getAuditLogs($pagination['page'], $pagination['per_page']);
        return ResponseHelper::json($response, true, 'Audit logs retrieved', $result);
    }
}
